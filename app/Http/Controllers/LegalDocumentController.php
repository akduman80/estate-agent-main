<?php

namespace App\Http\Controllers;

use App\Models\LegalDocument;
use Illuminate\Contracts\View\View;

class LegalDocumentController extends Controller
{
    public function show(LegalDocument $document): View
    {
        abort_unless($document->is_active, 404);

        return view('legal.show', compact('document'));
    }
}
