<?php

namespace App\Http\Controllers;

use App\Models\Poll;
use App\Models\PollOption;
use App\Models\PollVote;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PollVoteController extends Controller
{
    public function __invoke(Request $request, Poll $poll): RedirectResponse
    {
        abort_unless(Poll::query()->currentlyActive()->whereKey($poll->getKey())->exists(), 404);

        $data = $request->validate([
            'option_id' => [
                'required',
                Rule::exists('poll_options', 'id')->where('poll_id', $poll->getKey()),
            ],
        ], [
            'option_id.required' => 'Lütfen bir seçenek belirleyin.',
            'option_id.exists' => 'Seçilen anket seçeneği geçerli değil.',
        ]);

        $voterHash = hash_hmac(
            'sha256',
            implode('|', [(string) $request->ip(), (string) $request->userAgent()]),
            (string) config('app.key')
        );

        try {
            $created = DB::transaction(function () use ($poll, $data, $voterHash): bool {
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

            $created = false;
        }

        $request->session()->put('poll_voted_'.$poll->getKey(), true);

        return back()->with(
            $created ? 'poll_status' : 'poll_info',
            $created ? 'Oyunuz kaydedildi. Teşekkür ederiz.' : 'Bu ankete daha önce oy verdiniz.'
        );
    }
}
