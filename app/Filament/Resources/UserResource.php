<?php

namespace App\Filament\Resources;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
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
