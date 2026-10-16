<?php

namespace Tests\Feature;

use App\Models\Poll;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PollVotingTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_poll_is_available_for_site_popup(): void
    {
        $poll = Poll::create([
            'title' => 'Haftanın Anketi',
            'question' => 'Hangi ilan türünü arıyorsunuz?',
            'status' => 'active',
            'results_visibility' => 'after_vote',
        ]);
        $poll->options()->createMany([
            ['text' => 'Satılık', 'sort_order' => 1],
            ['text' => 'Kiralık', 'sort_order' => 2],
        ]);

        $this->getJson(route('polls.active'))
            ->assertOk()
            ->assertJsonPath('poll.id', $poll->id)
            ->assertJsonPath('poll.question', 'Hangi ilan türünü arıyorsunuz?');
    }

    public function test_same_visitor_can_vote_only_once(): void
    {
        $poll = Poll::create([
            'title' => 'Test Anketi',
            'question' => 'Tercihiniz nedir?',
            'status' => 'active',
            'results_visibility' => 'after_vote',
        ]);
        $options = $poll->options()->createMany([
            ['text' => 'Birinci', 'sort_order' => 1],
            ['text' => 'İkinci', 'sort_order' => 2],
        ]);

        $this->withHeader('User-Agent', 'Poll Test Browser')
            ->post(route('polls.vote', $poll), ['option_id' => $options[0]->getKey()])
            ->assertRedirect()
            ->assertSessionHas('poll_status');

        $this->withHeader('User-Agent', 'Poll Test Browser')
            ->post(route('polls.vote', $poll), ['option_id' => $options[1]->getKey()])
            ->assertRedirect()
            ->assertSessionHas('poll_info');

        $this->assertDatabaseCount('poll_votes', 1);
        $this->assertDatabaseHas('poll_options', [
            'id' => $options[0]->getKey(),
            'votes_count' => 1,
        ]);
        $this->assertDatabaseHas('poll_options', [
            'id' => $options[1]->getKey(),
            'votes_count' => 0,
        ]);
    }
    private function makePoll(array $attributes = []): Poll
    {
        $poll = Poll::create(array_merge([
            'title' => 'Test', 'question' => 'Tercihiniz?', 'status' => 'active',
            'results_visibility' => 'always',
        ], $attributes));
        $poll->options()->createMany([['text' => 'A'], ['text' => 'B']]);
        return $poll;
    }

    public function test_publication_dates_and_multiple_active_polls_are_preserved(): void
    {
        $this->freezeTime();
        $current = $this->makePoll(['starts_at' => now(), 'ends_at' => now()->addHour()]);
        $future = $this->makePoll(['starts_at' => now()->addHour()]);
        $expired = $this->makePoll(['ends_at' => now()]);
        $draft = $this->makePoll(['status' => 'draft']);
        $this->assertSame('active', $current->fresh()->status);
        $this->getJson(route('polls.active'))->assertJsonPath('poll.id', $current->id);
        foreach ([$future, $expired, $draft] as $poll) {
            $this->postJson(route('polls.vote', $poll), ['option_id' => $poll->options->first()->id])->assertNotFound();
        }
    }

    public function test_browser_identity_survives_ip_changes_and_different_browsers_can_vote(): void
    {
        $poll = $this->makePoll();
        $data = ['option_id' => $poll->options->first()->id, 'voter_token' => '11111111-1111-4111-8111-111111111111'];
        $this->postJson(route('polls.vote', $poll), $data)->assertOk()->assertJsonPath('recorded', true);
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])->postJson(route('polls.vote', $poll), $data)->assertOk();
        $this->getJson(route('polls.active', ['voter_token' => $data['voter_token']]))->assertJsonPath('poll', null);
        $data['voter_token'] = '22222222-2222-4222-8222-222222222222';
        $this->postJson(route('polls.vote', $poll), $data)->assertOk();
        $this->assertSame(2, $poll->votes()->count());
        $this->assertSame(2, $poll->options()->first()->votes_count);
    }

    public function test_account_vote_is_recognized_on_another_browser(): void
    {
        $poll = $this->makePoll();
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);
        foreach (['11111111-1111-4111-8111-111111111111', '22222222-2222-4222-8222-222222222222'] as $token) {
            $this->postJson(route('polls.vote', $poll), [
                'option_id' => $poll->options->first()->id, 'voter_token' => $token,
            ])->assertOk();
        }
        $this->getJson(route('polls.active'))->assertJsonPath('poll', null);
        $this->assertSame(1, $poll->votes()->count());
    }

    public function test_vote_requires_an_option_belonging_to_the_poll(): void
    {
        $poll = $this->makePoll();
        $other = $this->makePoll();
        $this->postJson(route('polls.vote', $poll), [])->assertUnprocessable();
        $this->postJson(route('polls.vote', $poll), ['option_id' => $other->options->first()->id])->assertUnprocessable();
        $this->assertSame(0, $poll->votes()->count());
    }
}
