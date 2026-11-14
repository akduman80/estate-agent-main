<?php

namespace App\Filament\Portal\Resources\ListingResource\Pages;

use App\Enums\UserRole;
use App\Filament\Portal\Resources\ListingResource;
use App\Models\AgentConsultant;
use App\Models\ListingFormDraft;
use App\Models\User;
use App\Services\ListingQuotaService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateListing extends CreateRecord
{
    use Concerns\HasListingWorkspace;

    protected ?bool $hasDatabaseTransactions = true;

    protected static string $resource = ListingResource::class;

    public function mount(): void
    {
        $user = auth()->user();

        if ($user?->role !== UserRole::SUPERVISOR && app(ListingQuotaService::class)->hasReachedLimit($user)) {
            Notification::make()
                ->danger()
                ->title('Aktif ilan limitinize ulaştınız.')
                ->send();

            $this->redirect(ListingResource::getUrl('index'));

            return;
        }

        parent::mount();
        $this->startWorkspace();
        if (session()->has('project-map-transfer.listing')) {
            $this->form->fill(\App\Support\ProjectMapTransfer::apply($this->data ?? [], 'listing'));
        }
    }

    protected function getFormActions(): array
    {
        return $this->workspaceActions();
    }

    protected function getForms(): array
    {
        return ['form' => ListingResource::form($this->makeForm()->operation('create')->model($this->record ?? $this->getModel())->statePath('data')->columns(1))];
    }

    public function create(bool $another = false): void
    {
        if ($this->isCreating) {
            return;
        }
        $this->authorizeAccess();
        $this->isCreating = true;
        try {
            DB::transaction(function (): void {
                // The server-side draft key makes retries of the same create request idempotent.
                $draft = ListingFormDraft::query()->whereKey($this->draftId)->where('user_id', auth()->id())->lockForUpdate()->first();
                if (! $draft) {
                    $draft = ListingFormDraft::create(['id' => $this->draftId, 'user_id' => auth()->id(), 'version' => 0]);
                }
                if ($draft->version !== $this->draftVersion) {
                    throw ValidationException::withMessages(['draft' => 'Taslak başka bir sekmede güncellendi. Girdileriniz korunuyor.']);
                }
                if ($draft->listing_id) {
                    $this->record = ListingResource::getEloquentQuery()->findOrFail($draft->listing_id);
                    $this->form->model($this->record);
                }
                if ($this->record) {
                    $this->assertWorkspaceUnchanged();
                }
                $state = $this->form->getState();
                $data = $this->validatedWorkspaceData($this->record ? [...$this->record->attributesToArray(), 'form_details' => $this->record->form_details, ...$state] : $state);
                if ($this->record) {
                    unset($data['user_id'], $data['owner_agent_id']);
                    $this->record->update($data);
                } else {
                    $this->record = $this->handleRecordCreation($this->mutateFormDataBeforeCreate($data));
                }
                $this->form->model($this->record)->saveRelationships();
                $draft->listing_id = $this->record->id;
                $draft->payload = null;
                $draft->save();
            });
            $this->record = $this->record->fresh();
            $this->recordStamp = $this->listingStamp($this->record);
            $this->rememberData();
            $this->getCreatedNotification()?->send();
        } finally {
            $this->isCreating = false;
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $authenticatedUser = auth()->user();
        $owner = match ($authenticatedUser->role) {
            UserRole::SUPERVISOR => User::query()->find($data['user_id'] ?? null),
            UserRole::CONSULTANT => AgentConsultant::query()
                ->where('consultant_id', $authenticatedUser->id)
                ->where('agent_id', $data['owner_agent_id'] ?? 0)
                ->where('status', 'active')
                ->with('agent')
                ->first()?->agent,
            default => $authenticatedUser,
        };

        $isPlatformOwner = $authenticatedUser->role === UserRole::SUPERVISOR
            && $owner?->id === $authenticatedUser->id;

        if (! $owner || (! $isPlatformOwner && $owner->role !== UserRole::AGENT && ! in_array($owner->role, [UserRole::INDIVIDUAL], true))) {
            throw ValidationException::withMessages(['user_id' => 'Geçerli bir bireysel üye veya emlakçı seçmelisiniz.']);
        }

        app(ListingQuotaService::class)->assertAvailable($owner);

        $data['user_id'] = $owner->id;
        $data['created_by_id'] = $authenticatedUser->id;
        unset($data['owner_agent_id']);
        $data['slug'] = Str::slug($data['title']).'-'.rand(1000, 9999);
        $data['is_active'] = false;
        $data['publication_status'] = 'draft';
        $data['source_type'] = match ($owner->role) {
            UserRole::INDIVIDUAL => 'owner',
            UserRole::AGENT => 'real_estate_office',
            default => 'zydemlak',
        };
        $data['business_profile_id'] = $owner->role === UserRole::AGENT ? $owner->businessProfile?->id : null;
        $data['from_who'] = match ($data['source_type']) {
            'owner' => 'Sahibinden',
            'real_estate_office' => 'Emlak Ofisinden',
            default => 'ZYDEMLAK',
        };

        return $data;
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->warning()
            ->title('İlan taslak olarak kaydedildi')
            ->body($this->record?->eids_exempt
                ? 'Tapusuz ilan için EİDS onayı gerekmiyor. İlanı yönetici onayına gönderebilirsiniz.'
                : 'EİDS sorgusunu tamamlayıp ilanı yönetici onayına gönderebilirsiniz.');
    }
}
