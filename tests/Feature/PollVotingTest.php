<?php

namespace Tests\Feature;

use App\Models\Poll;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PollVotingTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_poll_is_shown_on_homepage(): void
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

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Haftanın Anketi')
            ->assertSee('Hangi ilan türünü arıyorsunuz?');
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
}
