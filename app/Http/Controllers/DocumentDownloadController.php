<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Support\NativeLeaseDocument;

class DocumentDownloadController extends Controller
{
    public function __invoke(int $index): StreamedResponse|BinaryFileResponse
    {
        $documents = collect(Setting::get('user_documents', []))
            ->filter(fn ($document) => is_array($document) && ($document['is_active'] ?? true) && filled($document['title'] ?? null) && filled($document['file'] ?? null))
            ->sortBy(fn (array $document) => (int) ($document['sort'] ?? 0))->values();

        $document = $documents->get($index);

        abort_unless(is_array($document) && ($document['is_active'] ?? true) && filled($document['file'] ?? null), 404);

        $path = (string) $document['file'];
        abort_unless(Storage::disk('public')->exists($path), 404);

        // Match exact known sources, never titles or mutable list indexes.
        $hash = hash('sha256', Storage::disk('public')->get($path));
        $replacement = match ($hash) {
            NativeLeaseDocument::HASH => 'konut-kira-kefilsiz.pdf',
            '0b354332825d65b42717d973b2a26e67d2afdd8f3653513e26491e5fa93ddfe7' => 'isyeri-kira-kefilsiz.pdf',
            default => null,
        };
        if ($replacement !== null) {
            return response()->download(resource_path('documents/'.$replacement), Str::slug((string) $document['title']).'.pdf', ['Content-Type' => 'application/pdf']);
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $downloadName = Str::slug((string) $document['title']).($extension !== '' ? '.'.strtolower($extension) : '');

        return Storage::disk('public')->download($path, $downloadName);
    }
}
