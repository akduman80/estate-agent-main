<?php

namespace App\Http\Controllers;

use App\Support\DocumentTemplateStore;
use App\Support\NativeDocxTemplate;
use App\Support\NativeLeaseDocument;
use Illuminate\Http\Request;

class NativeTemplateDocumentController extends Controller
{
    public function __invoke(Request $request, string $id, int $version, DocumentTemplateStore $store, NativeDocxTemplate $templates, NativeLeaseDocument $converter)
    {
        abort_if(strlen($request->getContent()) > 500000, 413);
        $entry = $store->all()[$id] ?? null;
        abort_unless($entry && $entry['active'] === $version, 404);
        [$revision, $source] = $store->revision($id, $version);
        abort_unless($revision['format'] === 'docx', 404);
        $fields = $revision['mapping']['fields'];
        $data = $request->validate(['values'=>['present','array:'.implode(',',array_column($fields,'key'))], 'values.*'=>['nullable','string','max:2000']]);
        $values = [];
        foreach ($fields as $field) {
            $value = ($field['locked'] ?? false) ? '' : trim((string) ($data['values'][$field['key']] ?? ''));
            // Required fields remain visible as completion warnings in the form.
            // Blank values are permitted for partial document preview and printing.
            if ($field['kind'] === 'identity' && $value !== '') {
                $d = array_map('intval', str_split($value));
                if (! preg_match('/^[1-9][0-9]{10}$/D',$value) || ((($d[0]+$d[2]+$d[4]+$d[6]+$d[8])*7-($d[1]+$d[3]+$d[5]+$d[7]))%10+10)%10 !== $d[9] || array_sum(array_slice($d,0,10))%10 !== $d[10]) return response()->json(['message'=>'T.C. kimlik numarası geçersiz.'],422)->header('Cache-Control','no-store, private');
            }
            $values[$field['key']] = $value;
        }
        try {
            $docx = $templates->fill($source, $values);
            $pdf = $converter->convert($docx);
            return response()->json(['pdf'=>base64_encode($pdf),'docx'=>base64_encode($docx)])->header('Cache-Control','no-store, private');
        } catch (\Throwable) {
            return response()->json(['message'=>'Belge oluşturulamadı veya dönüştürücü meşgul. Kısa süre sonra tekrar deneyin.'],503)->header('Cache-Control','no-store, private');
        }
    }
}
