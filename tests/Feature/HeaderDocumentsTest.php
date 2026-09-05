<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HeaderDocumentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_documents_are_shown_in_the_main_navigation_in_sort_order(): void
    {
        $this->storeDocuments([
            ['title' => 'Tahliye Taahhütnamesi', 'file' => 'user-documents/tahliye.pdf', 'sort' => 20, 'is_active' => true],
            ['title' => 'Gizli Belge', 'file' => 'user-documents/gizli.pdf', 'sort' => 0, 'is_active' => false],
            ['title' => 'Kira Sözleşmesi', 'file' => 'user-documents/kira.pdf', 'sort' => 10, 'is_active' => true],
        ]);

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Dokümanlar')
            ->assertSeeInOrder(['Kira Sözleşmesi', 'Tahliye Taahhütnamesi'])
            ->assertDontSee('Gizli Belge')
            ->assertSee(route('documents.download', ['index' => 0]), false)
            ->assertSee(route('documents.download', ['index' => 1]), false);
    }

    public function test_document_download_uses_the_document_title_as_filename(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('user-documents/random-name.pdf', 'PDF content');
        $this->storeDocuments([
            ['title' => 'Kira Sözleşmesi', 'file' => 'user-documents/random-name.pdf', 'sort' => 0, 'is_active' => true],
        ]);

        $this->get(route('documents.download', ['index' => 0]))
            ->assertOk()
            ->assertDownload('kira-sozlesmesi.pdf');
    }

    public function test_inactive_document_cannot_be_downloaded(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('user-documents/gizli.pdf', 'PDF content');
        $this->storeDocuments([
            ['title' => 'Gizli Belge', 'file' => 'user-documents/gizli.pdf', 'sort' => 0, 'is_active' => false],
        ]);

        $this->get('/dokumanlar/0/indir')->assertNotFound();
    }

    private function storeDocuments(array $documents): void
    {
        Setting::updateOrCreate(['key' => 'user_documents'], ['value' => $documents]);
        Setting::clearCache('user_documents');
    }
}
