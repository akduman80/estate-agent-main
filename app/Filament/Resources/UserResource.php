<?php

namespace App\Filament\Resources;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Filament\Portal\Resources\ListingResource;
use App\Services\ListingQuotaService;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Üyeler';

    protected static ?string $modelLabel = 'Üye';

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Ad Soyad')->searchable(),
            Tables\Columns\TextColumn::make('email')->label('E-posta')->searchable(),
            Tables\Columns\TextColumn::make('phone')->label('Telefon'),
            Tables\Columns\TextColumn::make('role')->label('Üyelik Türü')->badge(),
            Tables\Columns\IconColumn::make('email_verified_at')->label('E-posta')
                ->getStateUsing(fn (User $record): bool => filled($record->email_verified_at))->boolean(),
            Tables\Columns\TextColumn::make('account_status')->label('Hesap Durumu')->badge(),
            Tables\Columns\TextColumn::make('listing_limit')
                ->label('Aktif İlan Limiti')
                ->getStateUsing(function (User $record): string {
                    $quota = app(ListingQuotaService::class);
                    $limit = $quota->effectiveLimit($record);

                    return $limit === null ? '-' : $quota->activeCount($record).' / '.$limit;
                }),
        ])->filters([
            Tables\Filters\SelectFilter::make('role')->options([
                UserRole::INDIVIDUAL->value => 'Bireysel', UserRole::AGENT->value => 'Emlak İşletmesi',
                UserRole::EDITOR->value => 'Editör', UserRole::SUPERVISOR->value => 'Yönetici',
            ]),
            Tables\Filters\SelectFilter::make('account_status')->options([
                'active' => 'Aktif', 'pending_email' => 'E-posta Bekliyor', 'pending_approval' => 'Onay Bekliyor',
                'rejected' => 'Reddedildi', 'suspended' => 'Askıda',
            ]),
        ])->actions([
            Tables\Actions\Action::make('setListingLimit')
                ->label('İlan Limitini Ayarla')
                ->icon('heroicon-o-adjustments-horizontal')
                ->visible(fn (User $record): bool => in_array($record->role, [UserRole::INDIVIDUAL, UserRole::AGENT], true))
                ->fillForm(fn (User $record): array => ['listing_limit_override' => $record->listing_limit_override])
                ->form([
                    TextInput::make('listing_limit_override')
                        ->label('Özel aktif ilan limiti')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(10000)
                        ->nullable()
                        ->helperText('Boş bırakılırsa bireysel üyede 10, emlakçıda 100 kullanılır.'),
                ])
                ->action(fn (User $record, array $data) => $record->update([
                    'listing_limit_override' => filled($data['listing_limit_override'])
                        ? (int) $data['listing_limit_override']
                        : null,
                ])),
            Tables\Actions\Action::make('createListingForUser')
                ->label('Kullanıcı Adına İlan Oluştur')
                ->icon('heroicon-o-plus-circle')
                ->visible(fn (User $record): bool => $record->account_status === AccountStatus::ACTIVE
                    && in_array($record->role, [UserRole::INDIVIDUAL, UserRole::AGENT], true))
                ->url(fn (User $record): string => route('filament.admin.resources.listings.create', ['owner' => $record->id])),
            Tables\Actions\Action::make('suspend')->label('Askıya Al')->color('danger')->requiresConfirmation()
                ->visible(fn (User $record): bool => $record->role !== UserRole::SUPERVISOR && $record->account_status !== AccountStatus::SUSPENDED)
                ->action(fn (User $record) => $record->update(['account_status' => AccountStatus::SUSPENDED])),
            Tables\Actions\Action::make('activate')->label('Aktifleştir')->color('success')->requiresConfirmation()
                ->visible(fn (User $record): bool => $record->account_status === AccountStatus::SUSPENDED)
                ->action(fn (User $record) => $record->update(['account_status' => AccountStatus::ACTIVE])),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListUsers::route('/')];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
