<?php

namespace App\Filament\Widgets;

use App\Models\SiteVisit;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class SiteTrafficOverview extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $today = SiteVisit::query()->whereDate('visited_at', today());
        $lastSevenDays = SiteVisit::query()->where('visited_at', '>=', now()->subDays(6)->startOfDay());
        $lastThirtyDays = SiteVisit::query()->where('visited_at', '>=', now()->subDays(29)->startOfDay());
        $previousThirtyDays = SiteVisit::query()
            ->whereBetween('visited_at', [
                now()->subDays(59)->startOfDay(),
                now()->subDays(30)->endOfDay(),
            ]);

        $lastThirtyCount = (clone $lastThirtyDays)->count();
        $previousThirtyCount = $previousThirtyDays->count();
        $change = $previousThirtyCount > 0
            ? round((($lastThirtyCount - $previousThirtyCount) / $previousThirtyCount) * 100)
            : null;

        return [
            Stat::make('Bugünkü Ziyaret', (clone $today)->count())
                ->description((clone $today)->distinct('visitor_hash')->count('visitor_hash').' tekil ziyaretçi')
                ->descriptionIcon('heroicon-m-eye')
                ->color('info'),
            Stat::make('Son 7 Gün', (clone $lastSevenDays)->count())
                ->description((clone $lastSevenDays)->distinct('visitor_hash')->count('visitor_hash').' tekil ziyaretçi')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('success'),
            Stat::make('Son 30 Gün', $lastThirtyCount)
                ->description($change === null ? 'Karşılaştırma için önceki dönem yok' : (($change >= 0 ? '+' : '').$change.'% önceki 30 güne göre'))
                ->descriptionIcon($change === null || $change >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($change === null || $change >= 0 ? 'success' : 'danger'),
            Stat::make('En Son Ziyaret', $this->latestVisitLabel())
                ->description('Herkese açık site sayfaları')
                ->descriptionIcon('heroicon-m-clock')
                ->color('gray'),
        ];
    }

    private function latestVisitLabel(): string
    {
        $visitedAt = SiteVisit::query()->latest('visited_at')->value('visited_at');

        if (! $visitedAt) {
            return 'Henüz yok';
        }

        return Carbon::parse($visitedAt)->diffForHumans();
    }
}
