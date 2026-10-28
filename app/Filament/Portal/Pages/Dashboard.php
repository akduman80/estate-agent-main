<?php

namespace App\Filament\Portal\Pages;

use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Panelim';

    protected static ?string $title = 'Üye Paneli';

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('verifyEidsIdentity')
                ->label(auth()->user()?->hasValidEidsIdentity() ? 'EİDS Kimlik Doğrulamasını Yenile' : 'EİDS Kimliğimi Doğrula')
                ->icon('heroicon-o-shield-check')
                ->url(route('account.eids.start')),
        ];
    }

    public function getSubheading(): ?string
    {
        return 'İlanlarınızın EİDS ve yayın durumunu buradan takip edebilirsiniz.';
    }
}
