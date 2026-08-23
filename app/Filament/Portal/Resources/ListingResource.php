<?php

namespace App\Filament\Portal\Resources;

use App\Enums\UserRole;
use App\Filament\Portal\Resources\ListingResource\Pages;
use App\Filament\Portal\Resources\ListingResource\Schemas\ListingFormSchema;
use App\Models\Listing;
use App\Models\User;
use App\Services\ListingPublicationService;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListingResource extends Resource
{
    protected static ?string $model = Listing::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    // --- TÜRKÇE ETİKET YÖNETİMİ ---
    protected static ?string $modelLabel = 'İlan';

    protected static ?string $pluralModelLabel = 'İlanlar';

    protected static ?string $navigationLabel = 'İlanlar';

    protected static ?string $breadcrumb = 'İlanlar';

    public static function form(Form $form): Form
    {
        return $form->schema(ListingFormSchema::make());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('İlan Başlığı')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('price')->label('Fiyat')->money('TRY')->sortable(),
                Tables\Columns\TextColumn::make('eids_document_no')->label('Taşınmaz No'),
                Tables\Columns\TextColumn::make('source_label')->label('Kaynak')->badge(),
                Tables\Columns\TextColumn::make('publication_status')
                    ->label('Yayın Durumu')->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'approved' => 'Yayında',
                        'pending_approval' => 'Yönetici Onayında',
                        'rejected' => 'Reddedildi',
                        default => 'Taslak',
                    }),
                Tables\Columns\TextColumn::make('eids_status')
                    ->label('EİDS')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'verified' => 'Doğrulandı',
                        'rejected' => 'Reddedildi',
                        default => 'Beklemede',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'verified' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('created_at')->label('Tarih')->dateTime('d.m.Y H:i'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Onayla')->icon('heroicon-o-check-circle')->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Listing $record): bool => auth()->user()?->role === UserRole::SUPERVISOR && $record->publication_status === 'pending_approval')
                    ->action(function (Listing $record): void {
                        app(ListingPublicationService::class)->approve($record, auth()->user());
                        Notification::make()->success()->title('İlan yayınlandı')->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Reddet')->icon('heroicon-o-x-circle')->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Listing $record): bool => auth()->user()?->role === UserRole::SUPERVISOR && $record->publication_status === 'pending_approval')
                    ->action(function (Listing $record): void {
                        app(ListingPublicationService::class)->reject($record, auth()->user());
                        Notification::make()->warning()->title('İlan reddedildi')->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListListings::route('/'),
            'create' => Pages\CreateListing::route('/create'),
            'edit' => Pages\EditListing::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        /** @var User $user */
        $user = auth()->user();

        // Eğer kullanıcı AGENT ise sadece kendi ilanlarını görsün
        if ($user && in_array($user->role, [UserRole::AGENT, UserRole::INDIVIDUAL], true)) {
            return $query->where('user_id', $user->id);
        }

        // SUPERVISOR tüm ilanları görür
        return $query;
    }
}
