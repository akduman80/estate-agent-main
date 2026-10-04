<?php

namespace App\Filament\Portal\Resources\ListingResource\Schemas\Steps;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\AgentConsultant;
use App\Models\Listing;
use App\Models\Setting;
use App\Models\User;
use App\Rules\ListingDecimalPrecision;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Illuminate\Support\HtmlString;

class BasicInfoStep
{
    public static function make(): Wizard\Step
    {
        return Wizard\Step::make('Temel Bilgiler')
            ->icon('heroicon-o-pencil-square')
            ->description('Başlık, kategori ve fiyat')
            ->schema([
                Select::make('user_id')
                    ->label('İlan Sahibi')
                    ->options(fn (): array => static::getOwnerOptions())
                    ->getOptionLabelUsing(fn ($value): ?string => static::getOwnerLabel($value))
                    ->searchable()
                    ->preload()
                    ->default(fn () => request()->integer('owner') ?: null)
                    ->visible(fn (): bool => auth()->user()?->role === UserRole::SUPERVISOR)
                    ->disabled(fn (?Listing $record): bool => (bool) $record?->exists)
                    ->required(fn (): bool => auth()->user()?->role === UserRole::SUPERVISOR)
                    ->extraAttributes(['class' => 'listing-owner-select'])
                    ->columnSpanFull(),

                Select::make('owner_agent_id')
                    ->label('İlan sahibi agent')
                    ->options(fn (): array => AgentConsultant::query()
                        ->where('consultant_id', auth()->id())
                        ->where('status', 'active')
                        ->with('agent')
                        ->get()
                        ->pluck('agent.name', 'agent.id')
                        ->all())
                    ->visible(fn (): bool => auth()->user()?->role === UserRole::CONSULTANT)
                    ->required(fn (): bool => auth()->user()?->role === UserRole::CONSULTANT)
                    ->searchable()
                    ->preload()
                    ->columnSpanFull(),

                TextInput::make('title')
                    ->label('İlan Başlığı')
                    ->placeholder('Örn: ZİYADE EMLAKTAN KİRALIK 3+1 LÜKS DEREBAHÇE DAİRESİ')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                RichEditor::make('description')
                    ->label('Açıklama')
                    ->extraInputAttributes(['data-listing-description-editor' => 'true'])
                    ->columnSpanFull(),

                Grid::make(['default' => 1, 'md' => 3])
                    ->schema([
                        Select::make('category')
                            ->label('Gayrimenkul Türü')
                            ->options(static::getDynamicOptions('options_category', [
                                'Daire', 'Müstakil Ev', 'Arsa', 'Tarla', 'İşyeri',
                            ]))
                            ->live() // Seçim yapıldığında diğer adımları reaktif etkiler
                            ->disabled(fn (?Listing $record): bool => (bool) $record?->published_at)
                            ->required(),

                        Select::make('subcategory')
                            ->label('Son Alt Kategori')
                            ->options(static::getDynamicOptions('options_subcategory', [
                                'Standart', 'Lüks', 'Müstakil', 'Ticari', 'Yatırımlık',
                            ]))
                            ->searchable()
                            ->disabled(fn (?Listing $record): bool => (bool) ($record?->published_at && $record->published_at->copy()->addHours(48)->isPast())),

                        Select::make('type')
                            ->label('İlan Tipi')
                            ->options(static::getDynamicOptions('options_type', ['Satılık', 'Kiralık']))
                            ->live() // Kiralık/Satılık durumuna göre de reaktif tetikleme
                            ->disabled(fn (?Listing $record): bool => (bool) $record?->published_at)
                            ->required(),

                        TextInput::make('price')
                            ->label('Fiyat (TL)')
                            ->numeric()
                            ->rules([new ListingDecimalPrecision])
                            ->required()
                            ->suffix('TL'),
                    ]),

                Placeholder::make('eids_price_warning')
                    ->label('')
                    ->content(new HtmlString('
                        <div style="background-color: #fff8e1; border-left: 4px solid #ffa000; padding: 10px; font-size: 12px; color: #555; border-radius: 4px; margin-top: 5px;">
                            <strong>Ticaret Bakanlığı</strong>, ilgili mevzuat gereğince ilanlardaki fiyat artışlarını yakından takip etmektedir.
                            <strong>Piyasa koşullarına uygun olmayan fiyat artışı yapılan ilanlara ilişkin bilgilerin</strong> Ticaret Bakanlığı tarafından talep edildiğini ve
                            <strong>aykırılık tespit edilen ilanlar için 858.620 TL\'ye varan idari para cezası uygulanabileceğini</strong> önemle hatırlatırız.
                        </div>
                    '))
                    ->columnSpanFull(),
            ]);
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

    protected static function getOwnerOptions(): array
    {
        $platformOption = auth()->user()?->role === UserRole::SUPERVISOR
            ? [auth()->id() => 'Ziyade Emlak (ZYDEMLAK adına)']
            : [];

        return $platformOption + User::query()
            ->whereIn('role', [UserRole::INDIVIDUAL, UserRole::AGENT])
            ->where('account_status', AccountStatus::ACTIVE)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    protected static function getOwnerLabel(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $value = (int) $value;

        if (auth()->user()?->role === UserRole::SUPERVISOR && $value === auth()->id()) {
            return 'Ziyade Emlak (ZYDEMLAK adına)';
        }

        return User::query()
            ->whereKey($value)
            ->value('name');
    }
}
