<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentDownloadController extends Controller
{
    public function __invoke(int $index): StreamedResponse
    {
        $documents = collect(Setting::get('user_documents', []))
            ->filter(fn ($document) => is_array($document) && ($document['is_active'] ?? true) && filled($document['title'] ?? null) && filled($document['file'] ?? null))
            ->sortBy(fn (array $document) => (int) ($document['sort'] ?? 0))->values();

        $document = $documents->get($index);

        abort_unless(is_array($document) && ($document['is_active'] ?? true) && filled($document['file'] ?? null), 404);

        $path = (string) $document['file'];
        abort_unless(Storage::disk('public')->exists($path), 404);

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $downloadName = Str::slug((string) $document['title']).($extension !== '' ? '.'.strtolower($extension) : '');

        return Storage::disk('public')->download($path, $downloadName);
    }
}
