<?php

namespace App\Services\Assistant;

class GeminiPrivacy
{
    public function sensitive(string $text): bool
    {
        // Contract parties and credentials stay in the existing local workflow.
        return preg_match('/(?:\b(?:otp|parola|şifre|sifre|kimlik|iban|telefon)\b|[\w.+-]+@[\w.-]+\.[a-z]{2,}|(?:\+?90[\s-]*)?0?5\d{2}[\s-]*\d{3}[\s-]*\d{2}[\s-]*\d{2}|\b\d{11}\b|\bTR\s*\d{2}[\s\d]{20,}|(?:kiracı|kiraci|ev sahibi|kiraya veren|adım|adim|ad soyad)\s*[:=]?\s+[\p{L}]{2,}\s+[\p{L}]{2,})/iu', $text) === 1;
    }

    public function history(array $history): array
    {
        $safe = [];
        foreach (array_slice($history, -6) as $turn) {
            if (! is_array($turn) || ! is_string($turn['text'] ?? null) || $this->sensitive($turn['text'])) continue;
            $safe[] = ['type' => 'user_input', 'content' => (($turn['user'] ?? false) === true ? 'Önceki kullanıcı mesajı: ' : 'Önceki asistan metni (doğrulanmamış bağlam): ').mb_substr($turn['text'], 0, 1000)];
        }
        return $safe;
    }
}
