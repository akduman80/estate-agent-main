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

    public function test_named_poll_preflight_can_resolve_a_unique_title_without_changing_votes(): void
    {
        $poll = $this->poll();
        $poll->update(['title' => 'WEB SİTEMİZ SİZCE OLMUŞ MU?']);
        $migration = require database_path('migrations/2026_10_16_000004_identify_and_backup_named_site_poll_once.php');
        $migration->up();
        $report = \App\Services\PollResetAudit::read();
        $this->assertSame(1, $report['match_count']);
        $this->assertSame($poll->id, $report['poll_id']);
        $this->assertTrue($report['backup_verified']);
        $this->assertNull($poll->fresh()->voting_period);
    }

    public function test_verified_live_reset_migration_is_scoped_and_cannot_repeat_the_reset(): void
    {
        $poll = $this->poll();
        $this->assertSame(1, $poll->id);
        $poll->update(['title' => 'WEB SİTEMİZ SİZCE OLMUŞMU ?']);
        $other = $this->poll();
        $option = $poll->options->first()->id;
        $this->postJson(route('polls.vote', $poll), ['option_id' => $option])->assertOk();
        $this->postJson(route('polls.vote', $other), ['option_id' => $other->options->first()->id])->assertOk();
        $prepare = require database_path('migrations/2026_10_16_000004_identify_and_backup_named_site_poll_once.php');
        $prepare->up();
        $reset = require database_path('migrations/2026_10_16_000005_reset_verified_site_poll_one_once.php');
        $reset->up();
        $report = \App\Services\PollResetAudit::read();
        $this->assertTrue($report['reset_completed']);
        $this->assertTrue($report['zero_votes_verified_at_reset']);
        $this->assertSame(0, $poll->votes()->count());
        $this->assertSame(1, $other->votes()->count());
        $this->postJson(route('polls.vote', $poll), [
            'option_id' => $option, 'period' => $report['period'],
            'voter_token' => '11111111-1111-4111-8111-111111111111',
        ])->assertOk();
        $reset->up();
        $this->assertSame(1, $poll->votes()->count());
        $this->assertSame($report['period'], $poll->fresh()->voting_period);
    }

    public function test_authorized_start_change_preserves_the_period_votes_and_end_date(): void
    {
        $this->freezeTime();
        $poll = $this->poll();
        $period = '32b5e655-e019-46e5-b9bb-6542f9f7a107';
        $poll->update(['voting_period' => $period, 'starts_at' => now()->addHour(), 'ends_at' => now()->addMonth()]);
        $end = $poll->ends_at->toIso8601String();
        \App\Services\PollResetAudit::write(['reset_completed' => true]);
        $migration = require database_path('migrations/2026_10_16_000006_start_verified_site_poll_now_once.php');
        $migration->up();
        $this->assertSame(now()->timestamp, $poll->fresh()->starts_at->timestamp);
        $this->assertSame($end, $poll->fresh()->ends_at->toIso8601String());
        $this->assertSame($period, $poll->fresh()->voting_period);
        $this->postJson(route('polls.vote', $poll), [
            'option_id' => $poll->options->first()->id, 'period' => $period,
            'voter_token' => '11111111-1111-4111-8111-111111111111',
        ])->assertOk();
        $migration->up();
        $this->assertSame(1, $poll->votes()->count());
    }

    public function test_live_preflight_requires_a_unique_question_and_preserves_votes(): void
    {
        $poll = $this->poll();
        $poll->update(['question' => '<p>WEB SİTEMİZ SİZCE OLMUŞ MU?</p>']);
        $this->postJson(route('polls.vote', $poll), ['option_id' => $poll->options->first()->id])->assertOk();
        $migration = require database_path('migrations/2026_10_16_000002_backup_requested_site_poll_once.php');
        $migration->up();
        $report = \App\Services\PollResetAudit::read();
        $this->assertSame(1, $report['match_count']);
        $this->assertSame($poll->id, $report['poll_id']);
        $this->assertTrue($report['backup_verified']);
        $this->assertFalse($report['reset_completed']);
        $backup = json_decode(Storage::disk('local')->get($report['backup_path']), true);
        $this->assertCount(1, $backup['votes']);
        $this->assertSame(1, $poll->votes()->count());
        $other = $this->poll();
        $other->update(['question' => 'Web sitemiz sizce olmuş mu?']);
        $migration->up();
        $this->assertSame(2, \App\Services\PollResetAudit::read()['match_count']);
        $this->assertSame(1, $poll->votes()->count());
        $this->assertNull($poll->fresh()->voting_period);
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
