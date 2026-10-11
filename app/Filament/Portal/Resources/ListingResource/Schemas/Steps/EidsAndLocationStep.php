<?php

namespace App\Filament\Portal\Resources\ListingResource\Schemas\Steps;

use App\Models\Listing;
use App\Services\TurkeyAddressService;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\HtmlString;

class EidsAndLocationStep
{
    public static function make(): Wizard\Step
    {
        return Wizard\Step::make('EİDS & Konum')
            ->icon('heroicon-o-shield-check')
            ->description('Yetki ve adres doğrulama')
            ->schema(static::fields());
    }

    /**
     * EİDS bilgileri ilan formunda ayrı bir sekme olmadan taşınmaz detayları içinde gösterilir.
     * Doğrulama, kullanıcı yayınlama isteğinde bulunduğunda yapılır.
     */
    public static function fields(): array
    {
        return [
            Hidden::make('eids_status')->dehydrated(false),

            TextInput::make('eids_document_no')
                ->label('Tapu Taşınmaz ID')
                ->placeholder('Örn: 94536593')
                ->type('text')
                ->inputMode('numeric')
                ->regex('/\A[0-9]+\z/')
                ->maxLength(255)
                ->validationMessages(['regex' => 'Taşınmaz ID yalnızca rakamlardan oluşmalıdır.'])
                ->helperText('EİDS kontrolü “İlanı Onaya Gönder” seçildiğinde yapılır.')
                ->required(fn (Get $get, ?Listing $record): bool => ! Listing::formIsEidsExempt($get('type'), $record))
                ->visible(fn (Get $get, ?Listing $record): bool => ! Listing::formIsEidsExempt($get('type'), $record)),

            Placeholder::make('eids_status_summary')
                ->label('EİDS Doğrulama Sonucu')
                ->content(fn (Get $get, ?Listing $record) => new HtmlString(static::statusHtml($get, $record)))
                ->columnSpanFull(),

            Grid::make(['default' => 1, 'md' => 3])
                ->schema([
                    Select::make('city_id')
                        ->label('İl')
                        ->options(TurkeyAddressService::getCities())
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function (Set $set): void {
                            $set('district_id', null);
                            $set('neighborhood_id', null);
                        })
                        ->disabled(fn (?Listing $record): bool => $record?->eids_status === 'verified')
                        ->required(),

                    Select::make('district_id')
                        ->label('İlçe')
                        ->options(fn (Get $get) => TurkeyAddressService::getDistricts($get('city_id')))
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(fn (Set $set) => $set('neighborhood_id', null))
                        ->disabled(fn (?Listing $record): bool => $record?->eids_status === 'verified')
                        ->required(),

                    Select::make('neighborhood_id')
                        ->label('Mahalle / Köy')
                        ->options(fn (Get $get) => TurkeyAddressService::getNeighborhoods(
                            $get('city_id'),
                            $get('district_id'),
                        ))
                        ->searchable()
                        ->disabled(fn (?Listing $record): bool => $record?->eids_status === 'verified')
                        ->placeholder('Mahalle veya köy seçin'),
                ]),

            Grid::make(['default' => 1, 'md' => 3])
                ->schema([
                    Placeholder::make('eids_city')->label('EİDS İl')->content(fn (?Listing $record) => $record?->eidsVerification?->city ?: '-'),
                    Placeholder::make('eids_district')->label('EİDS İlçe')->content(fn (?Listing $record) => $record?->eidsVerification?->district ?: '-'),
                    Placeholder::make('eids_neighborhood')->label('EİDS Mahalle')->content(fn (?Listing $record) => $record?->eidsVerification?->neighborhood ?: '-'),
                    Placeholder::make('eids_block')->label('EİDS Ada')->content(fn (?Listing $record) => $record?->eidsVerification?->block ?: '-'),
                    Placeholder::make('eids_parcel')->label('EİDS Parsel')->content(fn (?Listing $record) => $record?->eidsVerification?->parcel ?: '-'),
                    Placeholder::make('eids_expires_at')->label('EİDS İlan Süresi')->content(fn (?Listing $record) => $record?->eidsVerification?->expires_at?->format('d.m.Y H:i') ?: '-'),
                ]),
        ];
    }

    private static function statusHtml(Get $get, ?Listing $record): string
    {
        if (Listing::formIsEidsExempt($get('type'), $record)) {
            return '<div style="padding:10px;border-radius:6px;background:#eff6ff;color:#1e40af;">Tapusuz ilan: EİDS doğrulaması ve Tapu Taşınmaz ID gerekmiyor. Yayın için yönetici onayı gereklidir.</div>';
        }

        $status = $get('eids_status') ?: 'pending';
        $labels = [
            'pending' => ['Beklemede', '#92400e', '#fffbeb'],
            'verified' => ['Doğrulandı', '#166534', '#f0fdf4'],
            'rejected' => ['Reddedildi', '#991b1b', '#fef2f2'],
        ];

        [$label, $color, $background] = $labels[$status] ?? [$status, '#374151', '#f9fafb'];
        $error = $record?->eidsVerification?->error_message;
        $verifiedAt = $record?->eidsVerification?->verified_at?->format('d.m.Y H:i');
        $draftWarning = $status !== 'verified'
            ? '<br><strong>Yayın durumu:</strong> Bu ilan EİDS onayı almadığı için taslaklarda duracaktır ve sitede yayınlanmayacaktır.'
            : '<br><strong>Yayın durumu:</strong> EİDS onayı mevcut; yayın için ayrıca yönetici onayı gereklidir.';

        return sprintf(
            '<div style="background:%s;border:1px solid %s22;color:%s;padding:10px;border-radius:6px;font-size:13px;"><strong>%s</strong>%s%s%s</div>',
            $background,
            $color,
            $color,
            e($label),
            $verifiedAt ? '<br>Doğrulama zamanı: '.e($verifiedAt) : '',
            $error ? '<br>'.e($error) : '',
            $draftWarning
        );
    }
}
