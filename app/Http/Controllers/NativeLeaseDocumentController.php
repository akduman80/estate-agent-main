<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\NativeLeaseDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class NativeLeaseDocumentController extends Controller
{
    public function __invoke(Request $request, NativeLeaseDocument $documents)
    {
        abort_if(strlen($request->getContent()) > 16000, 413);
        $data = $request->validate(['values' => ['present', 'array:'.implode(',', NativeLeaseDocument::KEYS)], 'values.*' => ['nullable', 'string', 'max:240']]);
        $values = array_map(fn ($value) => trim((string) $value), $data['values']);
        foreach (['landlordIdentity', 'tenantIdentity'] as $key) {
            $identity = $values[$key] ?? '';
            if ($identity !== '' && ! $this->validIdentity($identity)) return response()->json(['message' => 'T.C. kimlik numarası geçersiz.'], 422);
        }
        // The client cannot choose a filesystem path, upload a document, or run commands.
        $source = null;
        foreach (Setting::get('user_documents', []) as $item) {
            if (! is_array($item) || ! ($item['is_active'] ?? true)) continue;
            $path = (string) ($item['file'] ?? '');
            if (! str_ends_with(strtolower($path), '.docx') || ! Storage::disk('public')->exists($path)) continue;
            if (Storage::disk('public')->size($path) > 2 * 1024 * 1024) continue;
            $candidate = Storage::disk('public')->get($path);
            if (hash('sha256', $candidate) === NativeLeaseDocument::HASH) { $source = $candidate; break; }
        }
        abort_if($source === null, 404);
        try {
            $docx = $documents->fill($source, $values);
            $pdf = $documents->convert($docx);
            return response()->json(['pdf' => base64_encode($pdf), 'docx' => base64_encode($docx)])->header('Cache-Control', 'no-store, private');
        } catch (\Throwable) {
            // Do not report request values, paths, converter output or personal data.
            return response()->json(['message' => 'Belge oluşturulamadı veya dönüştürücü meşgul. Kısa süre sonra tekrar deneyin.'], 503)->header('Cache-Control', 'no-store, private');
        }
    }

    private function validIdentity(string $value): bool
    {
        if (! preg_match('/^[1-9][0-9]{10}$/D', $value)) return false;
        $digits = array_map('intval', str_split($value));
        $odd = $digits[0]+$digits[2]+$digits[4]+$digits[6]+$digits[8];
        $even = $digits[1]+$digits[3]+$digits[5]+$digits[7];
        return (($odd*7-$even)%10+10)%10 === $digits[9] && array_sum(array_slice($digits, 0, 10))%10 === $digits[10];
    }
}
