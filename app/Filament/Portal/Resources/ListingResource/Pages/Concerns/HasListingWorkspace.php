<?php

namespace App\Filament\Portal\Resources\ListingResource\Pages\Concerns;

use App\Enums\UserRole;
use App\Filament\Portal\Resources\ListingResource;
use App\Filament\Portal\Resources\ListingResource\Pages\CreateListing;
use App\Models\Listing;
use App\Models\ListingFormDraft;
use App\Models\ListingImage;
use App\Models\User;
use App\Services\ListingFormDraftService;
use App\Services\ListingPublicationService;
use App\Support\ListingCategories;
use App\Support\ListingFormData;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;

trait HasListingWorkspace
{
    #[Locked]
    public string $draftId = '';

    #[Locked]
    public int $draftVersion = 0;

    #[Locked]
    public ?string $recordStamp = null;

    #[Locked]
    public ?string $lastDraftSavedAt = null;

    public string $draftStatus = 'Henüz kaydedilmedi';

    protected function hasUnsavedDataChangesAlert(): bool
    {
        return true;
    }

    protected function startWorkspace(): void
    {
        $record = $this->record instanceof Listing ? $this->record : null;
        $query = ListingFormDraft::query()->where('user_id', auth()->id());
        if (request()->filled('taslak')) {
            $query->whereKey(request()->string('taslak')->toString());
        } else {
            $query->where('listing_id', $record?->id);
        }
        $draft = request()->boolean('yeni') && ! request()->filled('taslak') ? null : $query->latest('updated_at')->first();
        if ($draft?->listing_id && ! $record) {
            $record = ListingResource::getEloquentQuery()->findOrFail($draft->listing_id);
            $this->record = $record;
            $this->form->model($record)->fill([...$record->attributesToArray(), 'form_details' => $record->form_details, 'private_note' => $record->private_note]);
        }
        if ($draft && $record && $draft->listing_id !== $record->id) {
            abort(403);
        }
        $this->draftId = $draft?->id ?? (string) Str::uuid();
        $this->draftVersion = $draft?->version ?? 0;
        $this->recordStamp = $record ? $this->listingStamp($record) : null;
        if ($draft?->payload) {
            $this->form->fill([...($this->data ?? []), ...$draft->payload]);
            $this->lastDraftSavedAt = $draft->updated_at->format('d.m.Y H:i:s');
            $this->draftStatus = 'Kaydedilmiş taslak yüklendi';
        }
    }

    protected function listingStamp(Listing $listing): string
    {
        return hash('sha256', json_encode([$listing->getRawOriginal(), $listing->images()->get()->toArray()]));
    }

    protected function assertWorkspaceUnchanged(): void
    {
        if ($this->record instanceof Listing && $this->recordStamp !== null
            && ! hash_equals($this->recordStamp, $this->listingStamp($this->record->fresh()))) {
            throw ValidationException::withMessages(['draft' => 'İlan başka bir sekmede değiştirildi. Bu sayfadaki girdiler korunuyor; kaydetmeden önce diğer sekmeyi kontrol edin.']);
        }
    }

    public function saveDraft(bool $automatic = false): void
    {
        $this->authorizeAccess();
        $this->draftStatus = 'Kaydediliyor';
        try {
            $this->assertWorkspaceUnchanged();
            $draft = app(ListingFormDraftService::class)->save($this->draftId, $this->draftVersion, $this->form->getRawState(), $this->record instanceof Listing ? $this->record : null);
            $this->draftVersion = $draft->version;
            $this->lastDraftSavedAt = $draft->updated_at->format('d.m.Y H:i:s');
            $this->draftStatus = 'Kaydedildi';
            // Do not fill the entire form: ongoing typing and upload ordering stay untouched.
            if (! $automatic) {
                Notification::make()->success()->title('Taslak kaydedildi')->send();
            }
        } catch (ValidationException $exception) {
            $this->draftStatus = 'Kaydedilemedi — girdileriniz bu sayfada korunuyor';
            throw $exception;
        } catch (\Throwable $exception) {
            $this->draftStatus = 'Kaydedilemedi — girdileriniz bu sayfada korunuyor';
            report($exception);
            Notification::make()->danger()->title('Taslak kaydedilemedi. Bağlantınızı kontrol edin.')->send();
        }
    }

    public function selectQuickCategory(string $category, string $type): void
    {
        $record = $this->record instanceof Listing ? $this->record : null;
        if ($record?->published_at) {
            return;
        }
        abort_unless(in_array([$category, $type], [['Arsa', 'Satılık'], ['Daire', 'Kiralık'], ['Daire', 'Satılık'], ['Müstakil Ev', 'Satılık'], ['Dükkan & Mağaza', 'Kiralık']], true), 422);
        $this->data['category'] = $category;
        $this->data['category_group'] = ListingCategories::group($category);
        $this->data['type'] = $type;
    }

    public function moveListingPhoto(string $key, int $direction): void
    {
        $this->authorizeAccess();
        $files = $this->data['uploaded_images'] ?? [];
        $keys = array_keys($files);
        $position = array_search($key, $keys, true);
        if ($position === false) {
            return;
        }
        $target = $direction === 0 ? 0 : $position + ($direction < 0 ? -1 : 1);
        if ($target < 0 || $target >= count($keys)) {
            return;
        }
        array_splice($keys, $position, 1);
        array_splice($keys, $target, 0, [$key]);
        $this->data['uploaded_images'] = array_replace(array_flip($keys), $files);
    }

    protected function workspaceActions(): array
    {
        return [
            Action::make('saveDraft')->label('Taslak Kaydet')->color('gray')->action(fn () => $this->saveDraft()),
            Action::make('preview')->label('Önizle')->color('gray')->icon('heroicon-o-eye')
                ->modalHeading('İlan önizlemesi — henüz yayınlanmadı')->modalWidth('7xl')
                ->modalContent(fn () => view('filament.listing-preview', ['html' => $this->previewHtml()]))
                ->modalSubmitAction(false)->modalCancelActionLabel('Forma dön'),
            Action::make('submitForApproval')->label('İlanı Onaya Gönder')->color('success')->icon('heroicon-o-paper-airplane')
                ->visible(fn (): bool => ! $this->record || in_array($this->record->publication_status, ['draft', 'rejected'], true))
                ->form([Checkbox::make('terms')->label('İlan bilgilerini ve yayınlandıktan sonra değiştirilemeyecek resmî alanları kontrol ettim. ZYD Emlak ilan kurallarını kabul ediyorum.')->accepted()->required()->default(false),
                    Placeholder::make('rules_link')->label('İlan verme kuralları')->content(fn () => new HtmlString('<a href="'.e(url('/sozlesmeler/uyelik-sozlesmesi')).'" target="_blank" rel="noopener">ZYD Emlak üyelik ve ilan kurallarını aç</a>'))])
                ->modalHeading('Yönetici onayına gönder')
                ->modalDescription('EİDS doğrulaması ve mevcut hesap/yetki kontrolleri tamamlandıktan sonra ilan yönetici onayına gönderilir. Bu işlem doğrudan yayınlamaz.')
                ->action(fn (array $data) => $this->submitWorkspace((bool) ($data['terms'] ?? false))),
        ];
    }

    protected function validatedWorkspaceData(array $data, bool $publishing = false): array
    {
        try {
            return ListingFormData::validate($data, $publishing);
        } catch (ValidationException $exception) {
            $this->dispatch('form-validation-error', livewireId: $this->getId());
            throw ValidationException::withMessages(collect($exception->errors())->mapWithKeys(fn ($messages, $field) => ['data.'.$field => $messages])->all());
        }
    }

    public function submitWorkspace(bool $termsAccepted = false): void
    {
        $this->authorizeAccess();
        if (! $termsAccepted) {
            throw ValidationException::withMessages(['terms' => 'İlan kurallarını kabul etmelisiniz.']);
        }
        $this->assertWorkspaceUnchanged();
        $this->validatedWorkspaceData($this->form->getRawState(), true);
        if ($this instanceof CreateListing) {
            $this->create();
        } else {
            $this->save(false, false);
        }
        $record = $this->record->fresh()->load('eidsVerification');
        if (! in_array($record->publication_status, ['draft', 'rejected'], true)) {
            return;
        }
        try {
            $failure = null;
            DB::transaction(function () use ($record, $termsAccepted, &$failure): void {
                $locked = ListingResource::getEloquentQuery()->whereKey($record->id)->lockForUpdate()->firstOrFail();
                if (! in_array($locked->publication_status, ['draft', 'rejected'], true)) {
                    return;
                }
                try {
                    app(ListingPublicationService::class)->submit($locked, auth()->user(), $termsAccepted);
                } catch (ValidationException $exception) {
                    $failure = $exception;
                }
            });
            if ($failure) {
                throw $failure;
            }
            Notification::make()->success()->title('İlan #'.$record->id.' yönetici onayına gönderildi')->send();
        } catch (ValidationException $exception) {
            Notification::make()->warning()->title('İlan taslakta kaldı')->body(implode(' ', Arr::flatten($exception->errors())))
                ->actions(isset($exception->errors()['eids_identity']) ? [
                    \Filament\Notifications\Actions\Action::make('renewEidsIdentity')
                        ->label('EİDS Kimliğimi Doğrula')->url(route('account.eids.start')),
                ] : [])->send();
            $this->record = $record->fresh();
            $this->recordStamp = $this->listingStamp($this->record);
            $this->data['eids_status'] = $this->record->eids_status;
            throw $exception;
        }
        $this->record = $record->fresh();
        $this->recordStamp = $this->listingStamp($this->record);
        $this->data['eids_status'] = $this->record->eids_status;
    }

    protected function previewHtml(): string
    {
        $this->saveDraft(true);
        if ($this->draftStatus !== 'Kaydedildi') {
            throw ValidationException::withMessages(['draft' => 'Güncel önizleme hazırlanamadı. Taslak kayıt hatasını giderip tekrar deneyin.']);
        }
        $draft = ListingFormDraft::query()->whereKey($this->draftId)->where('user_id', auth()->id())->firstOrFail();
        $data = ListingFormData::clean($draft->payload ?? []);
        $listing = new Listing(Arr::except($data, ['private_note', 'uploaded_images', 'user_id', 'owner_agent_id']));
        $listing->title = $listing->title ?: 'İlan önizlemesi';
        $listing->slug = 'onizleme';
        $listing->mobile_video_paths = $this->record?->mobile_video_paths;
        $listing->eids_status = 'pending';
        $listing->source_type = $this->record?->source_type ?? 'zydemlak';
        $listing->setRelation('images', collect(array_values($data['uploaded_images'] ?? []))->map(fn ($path) => new ListingImage(['image_path' => $path])));
        $listing->setRelation('eidsVerification', null);
        $owner = $this->record?->user ?? auth()->user();
        if (auth()->user()?->role === UserRole::SUPERVISOR) {
            $owner = User::find($data['user_id'] ?? auth()->id());
        }
        $listing->setRelation('user', $owner);
        $listing->setRelation('businessProfile', $owner?->businessProfile);

        return view('listings.show', ['listing' => $listing, 'similarListings' => collect(), 'isPreview' => true])->render();
    }
}
