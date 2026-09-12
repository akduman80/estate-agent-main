<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\AgentConsultantResource\Pages;
use App\Models\AgentConsultant;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AgentConsultantResource extends Resource
{
    protected static ?string $model = AgentConsultant::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationLabel = 'Agent Danışmanları';

    protected static ?string $modelLabel = 'Agent danışman ilişkisi';

    protected static ?string $pluralModelLabel = 'Agent danışmanları';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('agent_id')
                ->label('Agent')
                ->options(fn (): array => User::query()->where('role', UserRole::AGENT)->orderBy('name')->pluck('name', 'id')->all())
                ->searchable()->preload()->required(),
            Forms\Components\Select::make('consultant_id')
                ->label('Danışman')
                ->options(fn (): array => User::query()->where('role', UserRole::CONSULTANT)->orderBy('name')->pluck('name', 'id')->all())
                ->searchable()->preload()->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('agent.name')->label('Agent')->searchable(),
                Tables\Columns\TextColumn::make('consultant.name')->label('Danışman')->searchable(),
                Tables\Columns\TextColumn::make('status')->label('Durum')->badge(),
                Tables\Columns\TextColumn::make('assigned_at')->label('Atanma')->dateTime('d.m.Y H:i'),
            ])
            ->actions([
                Tables\Actions\Action::make('revoke')->label('Bağlantıyı kaldır')->color('danger')->requiresConfirmation()
                    ->visible(fn (AgentConsultant $record): bool => $record->status === 'active')
                    ->action(fn (AgentConsultant $record) => $record->update(['status' => 'revoked', 'revoked_at' => now()])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAgentConsultants::route('/'),
            'create' => Pages\CreateAgentConsultant::route('/create'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['agent', 'consultant']);
    }
}
