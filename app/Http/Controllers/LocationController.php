<?php

namespace App\Http\Controllers;

use App\Services\TurkeyAddressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocationController extends Controller
{
    public function districts(Request $request): JsonResponse
    {
        $data = $request->validate([
            'city' => ['required', 'string', Rule::in(array_keys(TurkeyAddressService::getCities()))],
        ]);

        return response()->json([
            'data' => TurkeyAddressService::getDistricts($data['city']),
        ]);
    }

    public function neighborhoods(Request $request): JsonResponse
    {
        $city = (string) $request->input('city');
        $data = $request->validate([
            'city' => ['required', 'string', Rule::in(array_keys(TurkeyAddressService::getCities()))],
            'district' => ['required', 'string', Rule::in(array_keys(TurkeyAddressService::getDistricts($city)))],
        ]);

        return response()->json([
            'data' => TurkeyAddressService::getNeighborhoods($data['city'], $data['district']),
        ]);
    }
}
