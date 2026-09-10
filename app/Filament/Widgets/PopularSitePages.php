<?php

namespace App\Filament\Widgets;

use App\Models\SiteVisit;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class PopularSitePages extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Popüler Site Sayfaları')
            ->description('Son 30 gündeki herkese açık sayfa ziyaretleri')
            ->query($this->popularPagesQuery())
            ->columns([
                Tables\Columns\TextColumn::make('path')
                    ->label('Sayfa')
                    ->searchable()
                    ->url(fn ($record): string => url($record->path), true),
                Tables\Columns\TextColumn::make('visits_count')
                    ->label('Ziyaret')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('unique_visitors_count')
                    ->label('Tekil')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_visited_at')
                    ->label('Son Ziyaret')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('visits_count', 'desc')
            ->paginated(false);
    }

    private function popularPagesQuery(): Builder
    {
        return SiteVisit::query()
            ->select('path')
            ->selectRaw('MIN(id) as id')
            ->selectRaw('COUNT(*) as visits_count')
            ->selectRaw('COUNT(DISTINCT visitor_hash) as unique_visitors_count')
            ->selectRaw('MAX(visited_at) as last_visited_at')
            ->where('visited_at', '>=', now()->subDays(29)->startOfDay())
            ->groupBy('path')
            ->limit(10);
    }
}
