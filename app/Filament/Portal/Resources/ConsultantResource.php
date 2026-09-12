<?php

namespace App\Filament\Portal\Resources;

use App\Enums\UserRole;
use App\Filament\Portal\Resources\ConsultantResource\Pages;
use App\Models\AgentConsultant;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Password;

class ConsultantResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Danışmanlar';

    protected static ?string $modelLabel = 'Danışman';

    protected static ?string $pluralModelLabel = 'Danışmanlar';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::AGENT;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('first_name')->label('Ad')->required()->maxLength(100),
            Forms\Components\TextInput::make('last_name')->label('Soyad')->required()->maxLength(100),
            Forms\Components\TextInput::make('email')->label('E-posta')->email()->required()->maxLength(255),
            Forms\Components\TextInput::make('phone')->label('Telefon')->tel()->required()->maxLength(24),
            Forms\Components\TextInput::make('password')->label('Geçici şifre')->password()->revealable()->required()->rule(Password::min(10)->letters()->mixedCase()->numbers()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(static::consultantQuery())
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Ad Soyad')->searchable(),
                Tables\Columns\TextColumn::make('email')->label('E-posta')->searchable(),
                Tables\Columns\TextColumn::make('phone')->label('Telefon'),
                Tables\Columns\TextColumn::make('listings_count')->label('İlan Sayısı')->counts('createdListings'),
                Tables\Columns\TextColumn::make('created_at')->label('Eklenme')->dateTime('d.m.Y H:i'),
            ])
            ->actions([
                Tables\Actions\Action::make('deactivate')
                    ->label('Bağlantıyı kaldır')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        AgentConsultant::query()
                            ->where('agent_id', auth()->id())
                            ->where('consultant_id', $record->id)
                            ->where('status', 'active')
                            ->update(['status' => 'revoked', 'revoked_at' => now()]);
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConsultants::route('/'),
            'create' => Pages\CreateConsultant::route('/create'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return static::consultantQuery();
    }

    private static function consultantQuery(): Builder
    {
        return User::query()
            ->where('role', UserRole::CONSULTANT)
            ->whereHas('consultantAssignments', fn (Builder $query) => $query
                ->where('agent_id', auth()->id())
                ->where('status', 'active'));
    }
}
