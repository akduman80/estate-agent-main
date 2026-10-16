<?php

namespace Tests\Feature;

use App\Models\Poll;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Storage;

class PollVotingPeriodTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function poll(): Poll
    {
        $poll = Poll::create(['title' => 'Site anketi', 'question' => 'Web sitemizi nasıl buldunuz?', 'status' => 'active']);
        $poll->options()->createMany([['text' => 'Beğendim'], ['text' => 'Geliştirilebilir']]);
        return $poll;
    }

    public function test_reset_is_scoped_atomic_and_runs_only_once(): void
    {
        $poll = $this->poll();
        $other = $this->poll();
        $token = '11111111-1111-4111-8111-111111111111';
        foreach ([$poll, $other] as $item) {
            $this->postJson(route('polls.vote', $item), ['option_id' => $item->options->first()->id, 'voter_token' => $token])->assertOk();
        }
        $this->artisan('polls:reset-voting-once', ['poll' => $poll->id])->assertSuccessful();
        $period = $poll->fresh()->voting_period;
        $this->assertNotNull($period);
        $backupPath = "poll-vote-backups/poll-{$poll->id}-{$period}.json";
        Storage::disk('local')->assertExists($backupPath);
        $backup = json_decode(Storage::disk('local')->get($backupPath), true, flags: JSON_THROW_ON_ERROR);
        $this->assertCount(1, $backup['votes']);
        $this->assertCount(2, $backup['options']);
        $this->assertSame($poll->id, $backup['poll']['id']);
        $this->assertNotNull($poll->fresh()->voting_reset_at);
        $this->assertSame(0, $poll->votes()->count());
        $this->assertEquals(0, $poll->options()->sum('votes_count'));
        $this->assertSame(1, $other->votes()->count());
        $this->assertEquals(1, $other->options()->sum('votes_count'));
        $this->assertNull($other->fresh()->voting_period);
        $this->assertSame('Web sitemizi nasıl buldunuz?', $poll->fresh()->question);
        $this->assertSame(2, $poll->options()->count());
        $this->getJson(route('polls.active', ['voter_token' => $token]))->assertJsonPath('poll.period', $period);
        $data = ['option_id' => $poll->options->first()->id, 'voter_token' => $token];
        $this->postJson(route('polls.vote', $poll), $data)->assertConflict();
        $data['period'] = $period;
        $this->postJson(route('polls.vote', $poll), $data)->assertOk();
        $this->withServerVariables(['REMOTE_ADDR' => '10.1.1.1'])->postJson(route('polls.vote', $poll), $data)->assertOk();
        $this->assertSame(1, $poll->votes()->count());
        $this->artisan('polls:reset-voting-once', ['poll' => $poll->id])->assertSuccessful();
        $this->assertSame($period, $poll->fresh()->voting_period);
        $this->assertCount(1, Storage::disk('local')->allFiles('poll-vote-backups'));
        $this->assertSame(1, $poll->votes()->count());
        $this->assertEquals(1, $poll->options()->sum('votes_count'));
        $this->getJson(route('polls.active', ['voter_token' => $token]))->assertJsonPath('poll', null);
    }

    public function test_failed_backup_prevents_reset(): void
    {
        $poll = $this->poll();
        $this->postJson(route('polls.vote', $poll), ['option_id' => $poll->options->first()->id])->assertOk();
        $disk = \Mockery::mock(\Illuminate\Filesystem\FilesystemAdapter::class);
        $disk->shouldReceive('put')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('local')->andReturn($disk);
        $this->artisan('polls:reset-voting-once', ['poll' => $poll->id])->assertFailed();
        $this->assertSame(1, $poll->votes()->count());
        $this->assertEquals(1, $poll->options()->sum('votes_count'));
        $this->assertNull($poll->fresh()->voting_period);
        $this->assertNull($poll->fresh()->voting_reset_at);
    }

    public function test_members_can_vote_again_after_reset_and_only_once_across_browsers(): void
    {
        $poll = $this->poll();
        $user = User::factory()->create();
        $this->actingAs($user);
        $option = $poll->options->first()->id;
        $this->postJson(route('polls.vote', $poll), ['option_id' => $option])->assertOk();
        $this->artisan('polls:reset-voting-once', ['poll' => $poll->id])->assertSuccessful();
        foreach (['11111111-1111-4111-8111-111111111111', '22222222-2222-4222-8222-222222222222'] as $token) {
            $this->postJson(route('polls.vote', $poll), ['option_id' => $option, 'voter_token' => $token, 'period' => $poll->fresh()->voting_period])->assertOk();
        }
        $this->assertSame(1, $poll->votes()->count());
        $this->assertEquals(1, $poll->options()->sum('votes_count'));
        $this->getJson(route('polls.active'))->assertJsonPath('poll', null);
    }

    public function test_guest_identity_is_required_and_independent_of_ip_in_new_period(): void
    {
        $poll = $this->poll();
        $this->artisan('polls:reset-voting-once', ['poll' => $poll->id])->assertSuccessful();
        $data = ['option_id' => $poll->options->first()->id, 'period' => $poll->fresh()->voting_period];
        $this->postJson(route('polls.vote', $poll), $data)->assertUnprocessable();
        foreach (['11111111-1111-4111-8111-111111111111', '22222222-2222-4222-8222-222222222222'] as $token) {
            $this->postJson(route('polls.vote', $poll), $data + ['voter_token' => $token])->assertOk();
        }
        $this->assertSame(2, $poll->votes()->count());
        $this->assertEquals(2, $poll->options()->sum('votes_count'));
    }
}
