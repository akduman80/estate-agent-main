<?php

namespace App\Services\Assistant;

use App\Services\TurkeyAddressService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/** Provider-neutral boundary: a configured gateway returns validated read-only intent JSON. */
class IntentGateway
{
    public function configured(): bool
    {
        return filled(config('assistant.endpoint')) && filled(config('assistant.key')) && filled(config('assistant.model'));
    }

    public function analyze(string $message): ?array
    {
        if (! $this->configured()) {
            return null;
        }
        // Never send authentication, contact, identity or document party information to a model.
        if (preg_match('/(?:\d[\s()+.\-]*){6,}|eids|otp|şifre|sifre|parola|kimlik|telefon|e-?posta|kiracı|kiraci|ev sahibi|kiraya veren|iban|@/iu', $message)) {
            return null;
        }
        $url = (string) config('assistant.endpoint');
        if (parse_url($url, PHP_URL_SCHEME) !== 'https' || parse_url($url, PHP_URL_USER) || parse_url($url, PHP_URL_PASS)) {
            return null;
        }
        $response = Http::withToken(config('assistant.key'))->acceptJson()->asJson()
            ->connectTimeout(3)->timeout(8)->withoutRedirecting()
            ->post($url, [
                'model' => config('assistant.model'), 'input' => $message,
                'output_schema' => [
                    'intent' => ['search', 'compare', 'open', 'details', 'documents', 'create', 'calculator', 'help'],
                    'filters' => ['city' => 'string', 'district' => 'string', 'type' => ['Satılık', 'Kiralık'], 'category' => ['Daire', 'Müstakil Ev', 'Arsa', 'Tarla', 'İşyeri'], 'rooms' => 'N+N', 'max_price' => 'positive number', 'sort' => ['price_asc', 'price_desc']],
                    'ordinal' => 'integer between 1 and 12',
                ],
            ]);
        if (! $response->successful() || strlen($response->body()) > 32000) {
            return null;
        }
        $data = $response->json();
        if (! is_array($data)) {
            return null;
        }
        $validator = Validator::make($data, [
            'intent' => ['required', Rule::in(['search', 'compare', 'open', 'details', 'documents', 'create', 'calculator', 'help'])],
            'filters' => ['present', 'array:city,district,type,category,rooms,max_price,sort'],
            'filters.city' => ['sometimes', 'string', 'max:80', Rule::in(array_values(TurkeyAddressService::getCities()))],
            'filters.district' => ['sometimes', 'string', 'max:80'],
            'filters.type' => ['sometimes', Rule::in(['Satılık', 'Kiralık'])],
            'filters.category' => ['sometimes', Rule::in(['Daire', 'Müstakil Ev', 'Arsa', 'Tarla', 'İşyeri'])],
            'filters.rooms' => ['sometimes', 'regex:/^\d{1,2}\+\d{1,2}$/'],
            'filters.max_price' => ['sometimes', 'numeric', 'min:0', 'max:1000000000000'],
            'filters.sort' => ['sometimes', Rule::in(['price_asc', 'price_desc'])],
            'ordinal' => ['required', 'integer', 'between:1,12'],
        ]);

        return $validator->fails() ? null : $validator->validated();
    }
}
