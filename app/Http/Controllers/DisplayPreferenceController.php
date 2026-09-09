<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DisplayPreferenceController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate(['featured_listing_count' => ['required', 'integer', 'in:3,6']]);
        $request->user()->update($validated);

        return response()->json(['saved' => true]);
    }
}
