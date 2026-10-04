<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Portal\Resources\ListingResource\Pages\CreateListing;
use App\Filament\Portal\Resources\ListingResource\Pages\EditListing;
use App\Models\Listing;
use App\Models\User;
use App\Services\EidsService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ListingPropertyIdTest extends TestCase
{
    use RefreshDatabase;

    public function test_property_id_survives_form_creation_edit_and_reload_as_text(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SUPERVISOR]);
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(CreateListing::class)
            ->assertFormFieldExists('eids_document_no', fn ($field): bool => $field->getType() === 'text' && $field->getInputMode() === 'numeric')
            ->fillForm([
                'user_id' => $admin->id,
                'title' => 'Taşınmaz ID testi',
                'category' => 'Arsa',
                'type' => 'Satılık',
                'price' => '55',
                'square_meters' => '55',
                'eids_document_no' => '94536593',
                'city_id' => 'İstanbul',
                'district_id' => 'Kadıköy',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $listing = Listing::query()->sole();
        $this->assertSame('94536593', $listing->eids_document_no);

        foreach (['94536593', '94536590', '94536593', '0094536593', '9007199254740993', '12345678901234567890123456789'] as $id) {
            Livewire::test(EditListing::class, ['record' => $listing->getRouteKey()])
                ->fillForm(['eids_document_no' => $id])
                ->call('save')
                ->assertHasNoFormErrors();

            $this->assertSame($id, $listing->fresh()->eids_document_no);
            Livewire::test(EditListing::class, ['record' => $listing->getRouteKey()])
                ->assertFormSet(['eids_document_no' => $id]);
        }
    }

    public function test_form_rejects_decimal_and_scientific_notation_ids(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SUPERVISOR]);
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        foreach (['94536593.0', '9.4536593e7', '-94536593', '94536593abc'] as $id) {
            Livewire::test(CreateListing::class)
                ->fillForm(['eids_document_no' => $id])
                ->call('create')
                ->assertHasFormErrors(['eids_document_no' => 'regex']);
        }
        $this->assertDatabaseCount('listings', 0);
    }

    public function test_eids_payload_never_rounds_property_ids_even_with_low_float_precision(): void
    {
        $method = new \ReflectionMethod(EidsService::class, 'requestPayload');
        $service = app(EidsService::class);
        $precision = ini_get('serialize_precision');
        ini_set('serialize_precision', '7');

        try {
            foreach (['94536593', '0094536593', '9007199254740993', '12345678901234567890123456789'] as $id) {
                $listing = new Listing(['eids_document_no' => $id]);
                $payload = $method->invoke($service, $listing);
                $encoded = json_decode(json_encode($payload, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
                $this->assertSame($id, (string) $encoded['TasinmazId']);
                $this->assertFalse(is_float($payload['TasinmazId']));
            }
        } finally {
            ini_set('serialize_precision', $precision);
        }
    }
}
