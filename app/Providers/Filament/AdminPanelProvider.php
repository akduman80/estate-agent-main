<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Support\AdminNavigation;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->darkMode()
            ->navigation(fn (\Filament\Navigation\NavigationBuilder $builder) => AdminNavigation::build($builder))
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn () => view('filament.admin.theme-switcher'),
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => view('partials.pwa', ['pwaAdmin' => true]),
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => view('filament.project-location-picker-assets'),
            )
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn () => view('filament.admin.typography'),
            )
            ->renderHook(
                PanelsRenderHook::SCRIPTS_AFTER,
                fn () => view('filament.listing-rich-editor-formatting'),
            )
            ->brandName('ZYDEMLAK')
            ->brandLogo(asset('fe-assets/assets/img/logo.png'))
            ->brandLogoHeight('3rem')
            ->homeUrl(fn (): string => route('home'))
            ->renderHook(
                PanelsRenderHook::PAGE_START,
                fn () => view('filament.panel-back-button', [
                    'dashboardUrl' => url('/admin'),
                    'label' => '← Admin paneline dön',
                    'panelPath' => 'admin',
                ]),
            )
            ->login(Login::class)
            ->userMenuItems([
                'website' => MenuItem::make()->label('Siteyi Görüntüle')->url(fn (): string => route('home'))->icon('heroicon-o-globe-alt'),
                'security' => MenuItem::make()->label('Hesap Güvenliği')->url(fn (): string => route('account.security'))->icon('heroicon-o-shield-check'),
            ])
            ->colors([
                'primary' => Color::Orange,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverResources(in: app_path('Filament/Editor/Resources'), for: 'App\\Filament\\Editor\\Resources')
            ->discoverResources(in: app_path('Filament/Portal/Resources'), for: 'App\\Filament\\Portal\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
