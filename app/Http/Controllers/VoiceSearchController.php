<?php

namespace App\Http\Controllers;

use App\Services\Assistant\IntentAnalyzer;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class VoiceSearchController extends Controller
{
    public function __invoke(Request $request, IntentAnalyzer $analyzer)
    {
        $data = $request->validate(['text' => ['required', 'string', 'max:1000']]);
        $intent = $analyzer->analyze($data['text']);
        $filters = Arr::only($intent['filters'], ['city', 'district', 'neighborhood', 'type', 'category', 'min_price', 'max_price', 'min_area', 'max_area', 'sort']);

        return response()->json(['filters' => $filters, 'ambiguous' => $intent['ambiguous'], 'unsupported' => $intent['unsupported'], 'url' => route('listings.index', $filters)]);
    }
}
