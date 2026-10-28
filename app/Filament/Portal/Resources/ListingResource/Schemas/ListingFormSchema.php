<?php

/** Created by ramazanorak, Date: 8.08.2026 */

namespace App\Filament\Portal\Resources\ListingResource\Schemas;

use App\Enums\UserRole;
use App\Filament\Portal\Resources\ListingResource\Schemas\Steps\BasicInfoStep;
use App\Filament\Portal\Resources\ListingResource\Schemas\Steps\EidsAndLocationStep;
use App\Filament\Portal\Resources\ListingResource\Schemas\Steps\MediaStep;
use App\Filament\Portal\Resources\ListingResource\Schemas\Steps\RealEstateDetailsStep;
use App\Models\Listing;
use App\Models\Setting;
use App\Models\User;
use App\Support\ListingCategories;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\View;
use Filament\Forms\Get;
use Filament\Forms\Set;

class ListingFormSchema
{
    public static function make(): array
    {
        $basic = BasicInfoStep::make()->getChildComponents();
        $classification = $basic[4]->getChildComponents();
        $details = RealEstateDetailsStep::make()->getChildComponents();
        $measurements = $details[0]->getChildComponents();
        $official = EidsAndLocationStep::fields();
        $section = fn (string $title, array $fields, string $id) => Section::make($title)
            ->schema($fields)->id($id)->columnSpanFull();

        return [
            View::make('filament.listing-form-guide')->columnSpanFull(),
            $section('Kategori ve işlem türü', [
                $basic[0], $basic[1],
                View::make('filament.listing-quick-categories'),
                Select::make('category_group')->label('Ana kategori')->options(array_combine(array_keys(ListingCategories::groups()), array_keys(ListingCategories::groups())))
                    ->dehydrated(false)->live()->searchable()
                    ->disabled(fn (?Listing $record): bool => (bool) $record?->published_at)
                    ->afterStateHydrated(fn (Set $set, Get $get) => $set('category_group', ListingCategories::group($get('category'))))
                    ->afterStateUpdated(fn (Set $set) => $set('category', null)),
                Grid::make(['default' => 1, 'md' => 3])->schema([$classification[0], $classification[2], $classification[1]]),
                Placeholder::make('category_path')->label('Seçilen kategori')
                    ->content(fn (Get $get): string => implode(' › ', array_filter(['Emlak', ListingCategories::group($get('category')), $get('type'), $get('category')]))),
            ], 'listing-category'),
            $section('İlan başlığı, açıklama ve fiyat', [
                $basic[2]->placeholder('Taşınmazınızı anlatan bir başlık yazın'), $basic[3],
                Grid::make(['default' => 1, 'md' => 2])->schema([
                    $classification[3]->label('Fiyat')->suffix(null),
                    Select::make('currency')->label('Para birimi')->options(fn (?Listing $record): array => ['TRY' => 'TL', 'USD' => 'USD', 'EUR' => 'EUR'] + ($record?->currency ? [$record->currency => $record->currency] : []))->default('TRY')->required(),
                ]),
            ], 'listing-description'),
            $section('Taşınmaz bilgileri', [
                Grid::make(['default' => 1, 'md' => 2])->schema(array_slice($measurements, 0, 2)),
                $details[1], $details[2], $details[3],
                Grid::make(['default' => 1, 'md' => 3])->visible(fn (Get $get): bool => in_array($get('category'), ListingCategories::HOMES, true))->schema([
                    TextInput::make('form_details.open_area')->label('Açık alan m²')->numeric()->minValue(0),
                    TextInput::make('form_details.bathrooms')->label('Banyo sayısı')->integer()->minValue(0)->maxValue(50),
                    Select::make('form_details.kitchen')->label('Mutfak tipi')->options(['Açık' => 'Açık', 'Kapalı' => 'Kapalı']),
                    Select::make('form_details.balcony')->label('Balkon')->options(['Var' => 'Var', 'Yok' => 'Yok']),
                    Select::make('form_details.energy_class')->label('Enerji kimlik belgesi')->options(array_combine(range('A', 'G'), range('A', 'G'))),
                    Toggle::make('form_details.in_site')->label('Site içerisinde mi?')->live(),
                ]),
                TextInput::make('form_details.rental_period')->label('Kira dönemi')->maxLength(100)->visible(fn (Get $get): bool => $get('type') === 'Kiralık'),
            ], 'listing-property'),
            $section('Tapu, taşınmaz ve yetki bilgileri', [...array_slice($measurements, 2), $details[4], $details[5], $official[0], $official[1]], 'listing-deed'),
            $section('Adres ve harita', [
                $official[3],
                \Filament\Forms\Components\Section::make('Mevcut harita kaydı ve ek bilgiler')->schema([
                \Filament\Forms\Components\Hidden::make('latitude')->rules(['nullable', 'numeric', 'between:-90,90']),
                \Filament\Forms\Components\Hidden::make('longitude')->rules(['nullable', 'numeric', 'between:-180,180']),
                \Filament\Forms\Components\ViewField::make('location_picker')->view('filament.forms.components.project-location-picker'),
                \Filament\Forms\Components\RichEditor::make('mapped_address')->label('Harita adresi')->maxLength(1000),
                \Filament\Forms\Components\Repeater::make('extra_fields')->label('Ek bilgiler')->defaultItems(0)->schema([
                    \Filament\Forms\Components\Hidden::make('_mobileKey'),
                    \Filament\Forms\Components\TextInput::make('ad')->label('Bilgi adı')->required()->maxLength(100),
                    \Filament\Forms\Components\RichEditor::make('deger')->label('Değer')->required()->maxLength(2000),
                ])->columns(2),
            ])->collapsed(),
                Textarea::make('form_details.address')->label('Açık adres (yalnız yetkili panelde)')->maxLength(1000)->rows(2),
                Grid::make(['default' => 1, 'md' => 2])->schema([
                    TextInput::make('form_details.latitude')->label('Enlem')->numeric()->minValue(-90)->maxValue(90),
                    TextInput::make('form_details.longitude')->label('Boylam')->numeric()->minValue(-180)->maxValue(180),
                ]),
                View::make('filament.listing-location-picker'),
                Toggle::make('form_details.hide_location')->label('Haritadaki kesin konumu gizle')->helperText('Koordinatlar ve açık adres ziyaretçilerle paylaşılmaz.'),
            ], 'listing-address'),
            $section('Detaylı özellikler', static::featureGroups(), 'listing-features'),
            $section('Fotoğraflar, video ve sanal tur', MediaStep::make()->getChildComponents(), 'listing-media'),
            $section('İlan Notum', [Textarea::make('private_note')->label('Özel not')->rows(3)->maxLength(10000)->helperText('Yalnız bu ilanı yönetmeye yetkili kişiler görebilir. İlan ziyaretçilerine ve önizlemeye aktarılmaz.')], 'listing-note'),
            $section('İletişim ve görünürlük tercihleri', [
                Placeholder::make('account_contact')->label('Hesap iletişim bilgileri')->content(function (Get $get, ?Listing $record): string {
                    $owner = $record?->user ?? (auth()->user()?->role === UserRole::SUPERVISOR ? User::find($get('user_id')) : auth()->user());

                    return implode(' · ', array_filter([$owner?->name, $owner?->businessProfile?->business_phone, $owner?->phone])) ?: 'Önce ilan sahibini seçin.';
                })->helperText('Telefon değişikliği için Hesap Güvenliği sayfasındaki mevcut doğrulama sürecini kullanın.'),
                Toggle::make('form_details.hide_phone')->label('Telefon numaram ilanda yayınlanmasın'),
                Placeholder::make('messaging_information')->label('Mesajlar')->content('Bu projede kullanıcılar arası ilan mesajlaşması bulunmuyor. Telefonu gizlediğinizde WhatsApp bağlantısı da gösterilmez.'),
            ], 'listing-contact'),
            $section('Doğrulama ve yayın durumu', [$official[2], $official[4]], 'listing-verification'),
        ];
    }

    private static function featureGroups(): array
    {
        $fields = [Toggle::make('form_details.facade_declined')
            ->label('Cephe seçimi yapmak istemiyorum')->live()
            ->afterStateUpdated(fn (Set $set, $state) => $state ? $set('facades', []) : null)];
        foreach (['interior' => 'İç özellikler', 'exterior' => 'Dış özellikler', 'neighborhood' => 'Muhit', 'transport' => 'Ulaşım', 'view' => 'Manzara', 'housing' => 'Konut tipi', 'accessible' => 'Engelliye ve yaşlıya uygunluk'] as $key => $label) {
            $fields[] = Section::make($label)->collapsible()->collapsed()->schema([
                Toggle::make('form_details.features.'.$key.'.declined')->label('Seçim yapmak istemiyorum')->live()
                    ->afterStateUpdated(fn (Set $set, $state) => $state ? $set('form_details.features.'.$key.'.values', []) : null),
                TagsInput::make('form_details.features.'.$key.'.values')->label($label)
                    ->suggestions(fn (): array => array_values(static::getDynamicOptions('options_listing_'.$key, [])))
                    ->disabled(fn (Get $get): bool => (bool) $get('form_details.features.'.$key.'.declined'))
                    ->helperText('Taşınmazın gerçek özelliklerini yazıp Enter ile ekleyin. Varsa sitenin tanımlı önerileri gösterilir.'),
            ]);
        }

        return $fields;
    }

    protected static function getDynamicOptions(string $key, array $defaultOptions): array
    {
        $settings = Setting::get($key, $defaultOptions);
        if (empty($settings) || ! is_array($settings)) {
            $settings = $defaultOptions;
        }

        $formatted = [];
        foreach ($settings as $option) {
            $formatted[$option] = $option;
        }

        return $formatted;
    }
}
