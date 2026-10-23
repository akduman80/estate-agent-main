<?php

namespace App\Services\Assistant;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Official Google Interactions REST API. Credentials never enter prompts or URLs. */
class GeminiClient
{
    public function configured(): bool
    {
        return is_string(config('assistant.gemini.key')) && trim(config('assistant.gemini.key')) !== '' && preg_match('/^gemini-[a-z0-9.\-]+$/', (string) config('assistant.gemini.model')) === 1;
    }

    public function generate(array $input, array $tools, string $system): array
    {
        if (! $this->configured()) throw new RuntimeException('Gemini bağlantısı henüz etkin değil. İlan arama ve belge araçları kullanılabilir.');
        $body = [
            'model' => config('assistant.gemini.model'), 'store' => false,
            'input' => $input, 'system_instruction' => $system,
            'generation_config' => ['max_output_tokens' => max(128, min(2400, (int) config('assistant.gemini.max_output_tokens', 1200)))],
        ];
        if ($tools) $body['tools'] = $tools;
        app(GeminiBudget::class)->reserve(strlen(json_encode($body)));
        try {
            $response = Http::withHeaders(['x-goog-api-key' => config('assistant.gemini.key')])->acceptJson()->asJson()
                ->connectTimeout(3)->timeout(max(3, min(20, (int) config('assistant.gemini.timeout', 12))))->withoutRedirecting()
                ->post('https://generativelanguage.googleapis.com/v1beta/interactions', $body);
        } catch (\Throwable) {
            $this->error();
            throw new RuntimeException('Gemini bağlantısı zamanında yanıt vermedi. Yeniden deneyebilir veya mevcut ilan ve belge araçlarını kullanabilirsiniz.');
        }
        if (! $response->successful() || strlen($response->body()) > 256000) {
            $this->error();
            throw new RuntimeException($response->status() === 429 ? 'Gemini kotası dolu veya hizmet yoğun. Biraz sonra tekrar deneyin.' : 'Gemini şu anda yanıt veremiyor. Yerel ilan ve belge araçları çalışmaya devam ediyor.');
        }
        $data = $response->json();
        if (! is_array($data) || ! in_array($data['status'] ?? '', ['completed', 'requires_action'], true) || ! is_array($data['steps'] ?? null)) {
            $this->error(); throw new RuntimeException('Gemini kullanılabilir bir yanıt vermedi. Sorunuzu yeniden yazabilirsiniz.');
        }
        app(GeminiBudget::class)->record(is_array($data['usage'] ?? null) ? $data['usage'] : []);
        return $data;
    }

    private function error(): void
    {
        $key = 'assistant:gemini:errors:'.now()->format('Y-m-d');
        \Illuminate\Support\Facades\Cache::add($key, 0, now()->addDays(2));
        \Illuminate\Support\Facades\Cache::increment($key);
    }
}
