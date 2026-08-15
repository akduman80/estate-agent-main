<?php

namespace App\Filament\Portal\Resources;

use App\Enums\UserRole;
use App\Filament\Portal\Resources\ListingResource\Pages;
use App\Filament\Portal\Resources\ListingResource\Schemas\ListingFormSchema;
use App\Models\Listing;
use App\Models\User;
use Filament\Forms\Form;
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
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
        if ($user && ($user->role === UserRole::AGENT || $user->role === UserRole::AGENT->value)) {
            return $query->where('user_id', $user->id);
        }

        // SUPERVISOR tüm ilanları görür
        return $query;
    }
}
