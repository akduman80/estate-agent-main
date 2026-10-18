<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Support\DocumentTemplateStore;
use Filament\Pages\Page;
use Livewire\WithFileUploads;
use Livewire\Attributes\Locked;

class DocumentTemplates extends Page
{
    use WithFileUploads;
    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';
    protected static ?string $navigationGroup = 'İçerik';
    protected static ?string $navigationLabel = 'Belge Şablonları';
    protected static ?string $title = 'Belge Şablonları';
    protected static string $view = 'filament.pages.document-templates';
    public $upload;
    public string $titleInput = '';
    public string $category = '';
    public string $mapping = '{"pageSizes":[],"fields":[]}';
    public string $message = '';
    #[Locked] public ?string $templateId = null;
    #[Locked] public ?int $savedVersion = null;
    #[Locked] public ?string $tested = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->isActive() && $user->email_verified_at && in_array($user->role, [UserRole::SUPERVISOR, UserRole::SUPERVISOR->value], true);
    }
    private function authorizeManager(): void { abort_unless(static::canAccess(), 403); }
    public function saveTemplate(): void
    {
        $this->authorizeManager();
        $this->validate(['titleInput' => 'required|string|max:120', 'category' => 'string|max:80', 'upload' => ($this->savedVersion ? 'nullable' : 'required').'|file|max:12288|mimes:pdf,docx', 'mapping' => 'required|json|max:250000']);
        $mapping = json_decode($this->mapping, true, 64, JSON_THROW_ON_ERROR);
        if (! is_array($mapping)) throw \Illuminate\Validation\ValidationException::withMessages(['mapping' => 'Alan eşlemesi nesne olmalı.']);
        if ($this->upload) { $format = strtolower($this->upload->getClientOriginalExtension()); $bytes = file_get_contents($this->upload->getRealPath()); }
        else { [$previous, $bytes] = app(DocumentTemplateStore::class)->revision($this->templateId, $this->savedVersion); $format = $previous['format']; }
        $revision = app(DocumentTemplateStore::class)->save($this->titleInput, $this->category, $format, $bytes, $mapping, $this->templateId);
        $this->templateId = $revision['id']; $this->savedVersion = $revision['version']; $this->tested = null;
        $this->upload?->delete(); $this->upload = null;
        $this->message = 'Taslak sürüm kaydedildi. PDF testini başarıyla tamamladıktan sonra bu sürümü yayınlayabilirsiniz.';
    }
    public function confirmTest(string $digest): void
    {
        $this->authorizeManager();
        $r = app(DocumentTemplateStore::class)->all()[$this->templateId]['versions'][($this->savedVersion ?? 0) - 1] ?? null;
        abort_unless($r && hash_equals($r['hash'], $digest) && $r['format'] === 'pdf' && json_decode($this->mapping, true) === $r['mapping'], 422);
        $this->tested = $digest; $this->message = 'Tarayıcı çıktı testi geçti. Kaydedilen sürüm yayınlanabilir.';
    }
    public function publishVersion(): void
    {
        $this->authorizeManager();
        $r = app(DocumentTemplateStore::class)->all()[$this->templateId]['versions'][($this->savedVersion ?? 0) - 1] ?? null;
        abort_unless($r && $this->tested && hash_equals($r['hash'], $this->tested) && json_decode($this->mapping, true) === $r['mapping'] && $this->titleInput === $r['title'] && $this->category === $r['category'], 422);
        app(DocumentTemplateStore::class)->publish($this->templateId, $this->savedVersion);
        $this->message = 'Test edilmiş sürüm yayınlandı.';
    }
    public function withdraw(string $id): void
    {
        $this->authorizeManager(); app(DocumentTemplateStore::class)->publish($id, null); $this->message = 'Şablon yayından kaldırıldı. Özel sürüm geçmişi korundu.';
    }
    public function newTemplate(): void
    {
        $this->authorizeManager(); $this->upload?->delete(); $this->reset('templateId','savedVersion','tested','titleInput','category','mapping','upload','message');
    }
    public function selectVersion(string $id, int $version): array
    {
        $this->authorizeManager();
        [$r, $bytes] = app(DocumentTemplateStore::class)->revision($id, $version);
        $this->templateId = $id; $this->savedVersion = $version; $this->tested = null;
        $this->titleInput = $r['title']; $this->category = $r['category']; $this->mapping = json_encode($r['mapping'], JSON_UNESCAPED_UNICODE);
        $this->upload?->delete(); $this->upload = null;
        return $r + ['bytes' => base64_encode($bytes)];
    }
    public function versions(): array { $this->authorizeManager(); return app(DocumentTemplateStore::class)->all(); }
}
