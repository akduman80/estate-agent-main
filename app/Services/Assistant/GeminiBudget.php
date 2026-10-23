<?php

namespace App\Services\Assistant;

use Illuminate\Support\Facades\Cache;
use RuntimeException;

class GeminiBudget
{
    public function reserve(int $inputBytes): void
    {
        $day = now()->format('Y-m-d');
        $identity = auth()->id() ? 'user:'.auth()->id() : 'ip:'.request()->ip();
        $user = 'assistant:gemini:user:'.$day.':'.hash('sha256', $identity);
        $calls = 'assistant:gemini:calls:'.$day;
        $reserved = 'assistant:gemini:reserved:'.$day;
        // UTF-8 bytes overestimate normal text tokens; count every outbound byte and output allowance.
        $tokens = $inputBytes + max(128, min(2400, (int) config('assistant.gemini.max_output_tokens', 1200)));
        $lock = Cache::lock('assistant:gemini:budget:'.$day, 5);
        if (! $lock->get()) throw new RuntimeException('Gemini yoğun. Biraz sonra tekrar deneyin.');
        try {
            foreach ([$user, $calls, $reserved] as $key) Cache::add($key, 0, now()->addDays(2));
            if (Cache::get($user) >= max(0, (int) config('assistant.gemini.user_daily_limit')) || Cache::get($calls) >= max(0, (int) config('assistant.gemini.server_daily_limit')) || Cache::get($reserved) + $tokens > max(0, (int) config('assistant.gemini.daily_token_budget'))) {
                throw new RuntimeException('Günlük yapay zekâ kullanım sınırına ulaşıldı. İlan arama ve belge araçlarını kullanmaya devam edebilirsiniz.');
            }
            Cache::increment($user); Cache::increment($calls); Cache::increment($reserved, $tokens);
        } finally { $lock->release(); }
    }

    public function record(array $usage): void
    {
        foreach (['total_input_tokens' => 'input', 'total_output_tokens' => 'output', 'total_thought_tokens' => 'thought'] as $field => $name) {
            $key = 'assistant:gemini:'.$name.':'.now()->format('Y-m-d');
            Cache::add($key, 0, now()->addDays(2));
            Cache::increment($key, max(0, min(1000000, (int) ($usage[$field] ?? 0))));
        }
    }
}
