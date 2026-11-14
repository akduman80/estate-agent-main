<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProjectMapRegionsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'level' => ['required', 'in:province,district,neighborhood'],
            'parent' => ['nullable', 'required_unless:level,province', 'regex:/^\d{1,12}$/'],
        ]);
        $path = match ($data['level']) {
            'province' => 'ilListe',
            'district' => 'ilceListe-'.$data['parent'],
            'neighborhood' => 'mahalleListe-'.$data['parent'],
        };
        // The supplied native app uses these national TKGM numeric identifiers.
        // maksIdariYapi now returns GUIDs that the parcel-by-number API rejects.
        $rows = Cache::remember('project-map-regions:native-v1:'.$path, now()->addDay(), function () use ($path): array {
            $regions = json_decode(file_get_contents(resource_path('data/project-map-regions.json')), true, 512, JSON_THROW_ON_ERROR);
            abort_unless(isset($regions[$path]), 404, 'İdari bölge bulunamadı.');

            return array_map(fn ($row) => ['id' => $row['id'], 'name' => $row['ad']], $regions[$path]);
        });

        return response()->json(['regions' => $rows]);
    }
}
