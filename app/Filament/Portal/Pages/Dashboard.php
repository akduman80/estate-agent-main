<?php

namespace App\Filament\Portal\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Panelim';

    protected static ?string $title = 'Üye Paneli';

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    public function getSubheading(): ?string
    {
        return 'İlanlarınızın EİDS ve yayın durumunu buradan takip edebilirsiniz.';
    }
}
