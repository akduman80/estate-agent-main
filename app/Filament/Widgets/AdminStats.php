<?php

namespace App\Filament\Widgets;

use App\Enums\BusinessApprovalStatus;
use App\Models\BusinessProfile;
use App\Models\Listing;
use App\Models\Post;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStats extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $pendingListings = Listing::query()->where('publication_status', 'pending_approval')->count();
        $pendingPosts = Post::query()->where('status', 'in_review')->count();
        $pendingBusinesses = BusinessProfile::query()->where('approval_status', BusinessApprovalStatus::PENDING->value)->count();

        return [
            Stat::make('Yayındaki İlanlar', Listing::query()->published()->count())
                ->description('EİDS ve yayın onayı tamamlananlar')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
            Stat::make('Onay Bekleyen İlanlar', $pendingListings)
                ->description($pendingListings ? 'İnceleme gerektiren ilan var' : 'Bekleyen ilan bulunmuyor')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingListings ? 'warning' : 'success'),
            Stat::make('İncelemedeki Haberler', $pendingPosts)
                ->description($pendingPosts ? 'Editörden gelen içerikler' : 'Bekleyen haber bulunmuyor')
                ->descriptionIcon('heroicon-m-newspaper')
                ->color($pendingPosts ? 'warning' : 'success'),
            Stat::make('İşletme Onayları', $pendingBusinesses)
                ->description('Toplam '.User::query()->count().' kayıtlı üye')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color($pendingBusinesses ? 'warning' : 'gray'),
        ];
    }
}
