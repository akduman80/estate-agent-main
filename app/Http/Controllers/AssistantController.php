<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\Assistant\AssistantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AssistantController extends Controller
{
    public function __invoke(Request $request, AssistantService $assistant)
    {
        abort_unless(config('assistant.enabled') && Setting::get('assistant_enabled', true), 503, 'Asistan şu anda kapalı.');
        $input = $request->validate(['message' => ['required', 'string', 'max:2000'], 'reset' => ['sometimes', 'boolean'], 'use_remote' => ['sometimes', 'boolean']]);
        $key = 'assistant:daily:'.hash('sha256', $request->session()->getId()).':'.now()->format('Y-m-d');
        Cache::add($key, 0, now()->addDay());
        abort_if(Cache::increment($key) > (int) Setting::get('assistant_daily_limit', config('assistant.daily_limit', 200)), 429);
        $context = $request->boolean('reset') ? [] : $request->session()->get('assistant.context', []);
        $countKey = 'assistant:requests:'.now()->format('Y-m-d');
        Cache::add($countKey, 0, now()->addDays(2));
        Cache::increment($countKey);
        try {
            $result = $assistant->reply($input['message'], $context, Setting::get('assistant_tools', ['search', 'compare', 'open', 'details', 'documents', 'create', 'calculator', 'help']), $request->boolean('use_remote'));
        } catch (\Throwable) {
            $errorKey = 'assistant:errors:'.now()->format('Y-m-d');
            Cache::add($errorKey, 0, now()->addDays(2));
            Cache::increment($errorKey);

            return response()->json(['message' => 'Asistana şu anda ulaşılamıyor. Mevcut site işlevlerini kullanabilirsiniz.'], 503)->header('Cache-Control', 'no-store');
        }
        // Store only filters and public record IDs; never conversation text or personal fields.
        $request->session()->put('assistant.context', $context);

        return response()->json($result)->header('Cache-Control', 'no-store');
    }

    public function feedback(Request $request)
    {
        abort_unless(config('assistant.enabled') && Setting::get('assistant_enabled', true), 503);
        $input = $request->validate(['rating' => ['required', 'in:helpful,unhelpful']]);
        $key = 'assistant:feedback:'.$input['rating'].':'.now()->format('Y-m-d');
        Cache::add($key, 0, now()->addDays(2));
        Cache::increment($key);

        return response()->json(['received' => true])->header('Cache-Control', 'no-store');
    }

    public function destroy(Request $request)
    {
        $request->session()->forget('assistant.context');

        return response()->json(['cleared' => true])->header('Cache-Control', 'no-store');
    }
}
