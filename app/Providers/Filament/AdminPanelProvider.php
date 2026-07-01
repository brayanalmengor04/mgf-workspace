<?php

namespace App\Providers\Filament;

use AlizHarb\ActivityLog\ActivityLogPlugin;
use App\Filament\Auth\Login;
use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\BudgetOverviewWidget;
use App\Filament\Widgets\BudgetPlansOverviewWidget;
use App\Filament\Widgets\PlatformStatsWidget;
use App\Filament\Widgets\ProviderOnboardingWidget;
use App\Filament\Widgets\QuotesOverviewWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use JeffersonGoncalves\Filament\Pwa\FilamentPwaPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->brandName(fn (): string => (string) config('app.brand'))
            ->colors([
                'primary' => Color::Amber,
            ])
            ->plugins([
                FilamentPwaPlugin::make()
                    ->themeColor('#f59e0b')
                    ->appTitle((string) config('app.brand')),
                ActivityLogPlugin::make()
                    ->label(fn (): string => auth()->user()?->isProvider() ? 'Mi bitácora' : 'Auditoría')
                    ->pluralLabel(fn (): string => auth()->user()?->isProvider() ? 'Mi bitácora' : 'Auditoría')
                    ->navigationGroup(fn (): string => auth()->user()?->isProvider() ? 'Cotizaciones' : 'Configuración')
                    ->navigationSort(99),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                PlatformStatsWidget::class,
                ProviderOnboardingWidget::class,
                QuotesOverviewWidget::class,
                BudgetOverviewWidget::class,
                BudgetPlansOverviewWidget::class,
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => '<meta name="robots" content="noindex, nofollow">'
                    .'<link rel="stylesheet" href="'.asset('css/filament-wizard.css?v=2').'">',
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => (request()->is('admin/login')
                    ? view('filament.pwa.install-snippet')->render()
                    : '')
                    .view('filament.pwa.service-worker')->render(),
            );
    }
}
