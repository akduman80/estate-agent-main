<?php

namespace App\Http\Controllers;

use App\Models\Poll;
use App\Models\PollOption;
use App\Models\PollVote;
use App\Services\PollVoter;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PollVoteController extends Controller
{
    public function active(Request $request): JsonResponse
    {
        $poll = Poll::query()->currentlyActive()->whereHas('options')
            ->latest('starts_at')->orderByDesc('id')->get()
            ->first(fn (Poll $candidate) => ! $candidate->votes()
                ->whereIn('voter_hash', PollVoter::hashes($request, $candidate->voting_period))->exists());

        return response()->json(['poll' => $poll ? [
            'id' => $poll->id,
            'period' => $poll->voting_period ?? 'legacy',
            'question' => trim(html_entity_decode(strip_tags($poll->question), ENT_QUOTES | ENT_HTML5, 'UTF-8')),
            'ends_at' => $poll->ends_at?->toIso8601String(),
            'url' => route('polls.vote', $poll),
            'options' => $poll->options->map(fn ($option) => ['id' => $option->id, 'text' => $option->text]),
        ] : null])->header('Cache-Control', 'no-store, private');
    }

    public function __invoke(Request $request, Poll $poll): RedirectResponse|JsonResponse
    {
        abort_unless(Poll::query()->currentlyActive()->whereKey($poll->getKey())->exists(), 404);

        $data = $request->validate([
            'voter_token' => [$poll->voting_period && ! $request->user() ? 'required' : 'nullable', 'uuid'],
            'option_id' => [
                'required',
                Rule::exists('poll_options', 'id')->where('poll_id', $poll->getKey()),
            ],
        ], [
            'option_id.required' => 'Lütfen bir seçenek belirleyin.',
            'option_id.exists' => 'Seçilen anket seçeneği geçerli değil.',
        ]);

        $hashes = PollVoter::hashes($request, $poll->voting_period);
        $voterHash = $hashes[0];

        try {
            $created = DB::transaction(function () use ($request, $poll, $data, $voterHash, $hashes): bool {
                $locked = Poll::query()->whereKey($poll->getKey())->lockForUpdate()->firstOrFail();
                abort_if($locked->voting_period !== $poll->voting_period
                    || ($locked->voting_period && $request->input('period') !== $locked->voting_period),
                    409, 'Anketin oylama dönemi değişti. Sayfayı yenileyip tekrar deneyin.');
                abort_unless(Poll::query()->currentlyActive()->whereKey($poll->id)->exists(), 404);
                if ($poll->votes()->whereIn('voter_hash', $hashes)->exists()) {
                    return false;
                }
                $vote = PollVote::query()->firstOrCreate([
                    'poll_id' => $poll->getKey(),
                    'voter_hash' => $voterHash,
                ], [
                    'poll_option_id' => $data['option_id'],
                ]);

                if (! $vote->wasRecentlyCreated) {
                    return false;
                }

                PollOption::query()
                    ->where('poll_id', $poll->getKey())
                    ->whereKey($data['option_id'])
                    ->increment('votes_count');

                return true;
            });
        } catch (QueryException $exception) {
            if (($exception->errorInfo[0] ?? null) !== '23000') {
                throw $exception;
            }

            if (! $poll->votes()->whereIn('voter_hash', $hashes)->exists()) {
                throw $exception;
            }
            $created = false;
        }

        $request->session()->put('poll_voted_'.$poll->getKey(), true);

        if ($request->expectsJson()) {
            return response()->json(['recorded' => true, 'message' => $created
                ? 'Oyunuz kaydedildi. Teşekkür ederiz.' : 'Bu ankete daha önce oy verdiniz.']);
        }

        return back()->with(
            $created ? 'poll_status' : 'poll_info',
            $created ? 'Oyunuz kaydedildi. Teşekkür ederiz.' : 'Bu ankete daha önce oy verdiniz.'
        );
    }
}
