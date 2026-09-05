<?php

namespace App\Filament\Portal\Widgets;

use App\Enums\UserRole;
use App\Filament\Portal\Resources\ListingResource;
use App\Models\Listing;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentListings extends BaseWidget
{
    protected static ?string $heading = 'Son İlanlarım';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->listingQuery())
            ->defaultSort('updated_at', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('images.image_path')
                    ->label('')
                    ->circular()
                    ->limit(1)
                    ->defaultImageUrl(asset('fe-assets/assets/img/demo/property-1.jpg')),
                Tables\Columns\TextColumn::make('title')
                    ->label('İlan')
                    ->searchable()
                    ->limit(45)
                    ->description(fn (Listing $record): string => collect([$record->city_name, $record->district_name])->filter()->join(' / ')),
                Tables\Columns\TextColumn::make('price')
                    ->label('Fiyat')
                    ->money('TRY')
                    ->sortable(),
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
                Tables\Columns\TextColumn::make('publication_status')
                    ->label('Yayın')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'approved' => 'Yayında',
                        'pending_approval' => 'Onayda',
                        'rejected' => 'Düzeltme Gerekli',
                        'closed' => 'Kapalı',
                        default => 'Taslak',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending_approval' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Güncelleme')
                    ->since()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Düzenle'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('newListing')
                    ->label('Yeni İlan Ver')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->visible(fn (): bool => ListingResource::canCreate())
                    ->url(ListingResource::getUrl('create')),
            ])
            ->emptyStateHeading('Henüz ilanınız yok')
            ->emptyStateDescription('İlk ilanınızı oluşturarak EİDS doğrulama sürecine başlayın.')
            ->emptyStateIcon('heroicon-o-home-modern')
            ->paginated([5]);
    }

    private function listingQuery(): Builder
    {
        $query = Listing::query()->with(['images', 'eidsVerification']);
        $user = auth()->user();

        if ($user && in_array($user->role, [UserRole::AGENT, UserRole::INDIVIDUAL], true)) {
            $query->where('user_id', $user->getKey());
        }

        return $query;
    }
}
