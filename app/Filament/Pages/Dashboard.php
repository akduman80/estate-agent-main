<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Genel Bakış';

    protected static ?string $title = 'Yönetim Paneli';

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    public function getSubheading(): ?string
    {
        return 'Yayın, üyelik ve içerik onaylarında bekleyen işlemleri buradan takip edebilirsiniz.';
    }
}
