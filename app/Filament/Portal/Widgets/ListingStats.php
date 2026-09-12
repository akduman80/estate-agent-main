<?php

namespace App\Filament\Portal\Widgets;

use App\Enums\UserRole;
use App\Models\Listing;
use App\Services\ListingQuotaService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class ListingStats extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $query = $this->listingQuery();
        $total = (clone $query)->count();
        $published = (clone $query)->where('publication_status', 'approved')->where('is_active', true)->count();
        $waiting = (clone $query)->where('publication_status', 'pending_approval')->count();
        $draft = (clone $query)->whereIn('publication_status', ['draft', 'rejected'])->count();
        $user = auth()->user();
        $quotaService = app(ListingQuotaService::class);
        $limit = $user ? $quotaService->effectiveLimit($user) : null;
        $activeCount = $user ? $quotaService->activeCount($user) : 0;

        return [
            Stat::make('Toplam İlan', $total)
                ->description('Panelinizdeki tüm ilanlar')
                ->descriptionIcon('heroicon-m-rectangle-stack')
                ->color('gray'),
            Stat::make('Yayındaki İlan', $published)
                ->description('EİDS ve yönetici onayı tamamlandı')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
            Stat::make('Onay Bekleyen', $waiting)
                ->description('Yönetici incelemesinde')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
            Stat::make('Taslak / Düzeltme', $draft)
                ->description('Tamamlanması gereken ilanlar')
                ->descriptionIcon('heroicon-m-pencil-square')
                ->color('danger'),
            Stat::make('Aktif İlan Limiti', $limit === null ? 'Sınırsız' : $activeCount.' / '.$limit)
                ->description($user?->listing_limit_override === null ? 'Üyelik türü varsayılanı' : 'Supervisor tarafından belirlenen özel limit')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($limit !== null && $activeCount >= $limit ? 'danger' : 'primary'),
        ];
    }

    private function listingQuery(): Builder
    {
        $query = Listing::query();
        $user = auth()->user();

        if ($user && in_array($user->role, [UserRole::AGENT, UserRole::INDIVIDUAL], true)) {
            $query->where('user_id', $user->getKey());
        }

        if ($user?->role === UserRole::CONSULTANT) {
            $query->where('created_by_id', $user->getKey());
        }

        return $query;
    }
}
