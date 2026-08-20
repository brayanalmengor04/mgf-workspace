<?php

namespace App\Providers\Filament;

use AlizHarb\ActivityLog\ActivityLogPlugin;
use App\Filament\Auth\Login;
use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\AvailableBalanceTrendChartWidget;
use App\Filament\Widgets\FinancialHealthWidget;
use App\Filament\Widgets\FinancialOverviewWidget;
use App\Filament\Widgets\PlatformStatsWidget;
use App\Filament\Widgets\ProviderOnboardingWidget;
use App\Filament\Widgets\QuotesOverviewWidget;
use App\Support\AdminViewMode;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
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
            ->passwordReset()
            ->simplePageMaxContentWidth(Width::Full)
            ->brandName(fn (): string => (string) config('app.brand'))
            ->brandLogo(asset('images/brand/mgf-logo.svg'))
            ->darkModeBrandLogo(asset('images/brand/mgf-logo-dark.svg'))
            ->brandLogoHeight('2.25rem')
            ->favicon(asset('favicon.ico'))
            ->colors([
                'primary' => Color::Amber,
            ])
            ->userMenuItems([
                Action::make('toggleViewMode')
                    ->label(fn (): string => AdminViewMode::isProviderPreview()
                        ? 'Volver a vista administrador'
                        : 'Ver como proveedor')
                    ->icon(fn () => AdminViewMode::isProviderPreview()
                        ? Heroicon::OutlinedShieldCheck
                        : Heroicon::OutlinedEye)
                    ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false)
                    ->sort(10)
                    ->action(function (): void {
                        AdminViewMode::toggle();

                        redirect()->to(Dashboard::getUrl());
                    }),
            ])
            ->plugins([
                FilamentPwaPlugin::make()
                    ->themeColor('#0f172a')
                    ->appTitle((string) config('app.brand')),
                ActivityLogPlugin::make()
                    ->label(fn (): string => auth()->user()?->canViewGlobalData() ? 'Auditoría' : 'Mi bitácora')
                    ->pluralLabel(fn (): string => auth()->user()?->canViewGlobalData() ? 'Auditoría' : 'Mi bitácora')
                    ->navigationGroup(fn (): string => auth()->user()?->canViewGlobalData() ? 'Configuración' : 'Cotizaciones')
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
                FinancialOverviewWidget::class,
                FinancialHealthWidget::class,
                AvailableBalanceTrendChartWidget::class,
                QuotesOverviewWidget::class,
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
                PanelsRenderHook::TOPBAR_BEFORE,
                fn (): string => AdminViewMode::isProviderPreview()
                    ? view('filament.admin.view-mode-banner')->render()
                    : '',
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => '<meta name="robots" content="noindex, nofollow">'
                    .'<link rel="stylesheet" href="'.asset('css/filament-wizard.css?v=5').'">'
                    .(request()->is('admin/login')
                        ? '<link rel="stylesheet" href="'.asset('css/filament-login.css?v=3').'">'
                        : ''),
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                function (): string {
                    $html = '';

                    if (! request()->is('admin/login')) {
                        $html .= \Illuminate\Support\Facades\Blade::render("@livewire('chatbot-widget')");
                    }

                    $html .= view('filament.pwa.service-worker')->render();

                    return $html;
                }
            );
    }
}
