<?php

namespace App\Http\Controllers;

use App\Filament\Portal\Resources\ListingResource;
use Filament\Facades\Filament;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectMapTransferController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'target' => ['required', 'in:listing,project'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'address' => ['nullable', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:160'],
            'district' => ['nullable', 'string', 'max:160'],
            'neighborhood' => ['nullable', 'string', 'max:160'],
            'block' => ['nullable', 'regex:/^\d{1,12}$/'],
            'parcel' => ['nullable', 'regex:/^\d{1,12}$/'],
            'area' => ['nullable', 'string', 'max:160'],
            'quality' => ['nullable', 'string', 'max:160'],
        ]);
        $panel = $data['target'] === 'project' ? 'projects' : 'portal';
        abort_unless($request->user()->canAccessPanel(Filament::getPanel($panel)), 403);
        if ($data['target'] === 'listing') {
            abort_unless(ListingResource::canCreate(), 403);
        }
        $request->session()->put('project-map-transfer.'.$data['target'], $data);

        return response()->json(['url' => $data['target'] === 'project' ? url('/projeler-app/projects/create') : url('/portal/listings/create')]);
    }
}
