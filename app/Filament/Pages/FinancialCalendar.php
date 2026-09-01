<?php

namespace App\Filament\Pages;

use App\Support\CrmNavigation;
use Filament\Pages\Page;

use App\Models\BudgetPlanItem;
use App\Models\CalendarEvent;
use App\Enums\BudgetStatus;
use App\Enums\QuoteCurrency;
use App\Support\MoneyFormatter;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;

class FinancialCalendar extends Page
{
    protected string $view = 'filament.pages.financial-calendar';

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-calendar-days';
    }

    public static function getNavigationLabel(): string
    {
        return 'Calendario Financiero';
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Calendario Financiero';
    }

    public function getSubheading(): ?string
    {
        return 'Agenda pagos, vencimientos y revisa partidas pendientes de tus presupuestos.';
    }

    /**
     * @return array<string, mixed>
     */
    public function getCalendarStatsProperty(): array
    {
        $user = Auth::user();
        if ($user === null) {
            return [];
        }

        $now = now();
        $events = CalendarEvent::query()->where('user_id', $user->id);

        $thisMonth = (clone $events)
            ->whereBetween('start_date', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
            ->count();

        $upcoming = (clone $events)
            ->where('start_date', '>=', $now->startOfDay())
            ->count();

        $pendingTotal = BudgetPlanItem::query()
            ->where('is_paid', false)
            ->where('amount', '>', 0)
            ->whereHas('budgetPlan', fn ($query) => $query->forUser($user)->where('status', BudgetStatus::Issued))
            ->sum('amount');

        return [
            'this_month' => $thisMonth,
            'upcoming' => $upcoming,
            'pending_total' => 'B/. '.number_format((float) $pendingTotal, 2),
            'pending_count' => count($this->pendingBudgetItems),
        ];
    }

    /**
     * @return array<int, array{date: string, title: string, meta: string}>
     */
    public function getUpcomingEventsListProperty(): array
    {
        return CalendarEvent::query()
            ->where('user_id', Auth::id())
            ->where('start_date', '>=', now()->startOfDay())
            ->orderBy('start_date')
            ->limit(6)
            ->get()
            ->map(function (CalendarEvent $event): array {
                $amount = $event->amount ? 'B/. '.number_format((float) $event->amount, 2) : 'Sin monto';

                return [
                    'date' => $event->start_date->translatedFormat('d M'),
                    'title' => $event->title,
                    'meta' => $amount,
                    'id' => $event->id,
                ];
            })
            ->all();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createEvent')
                ->label('Nuevo Evento')
                ->icon('heroicon-o-plus')
                ->form([
                    TextInput::make('title')
                        ->label('Título')
                        ->required(),
                    DateTimePicker::make('start_date')
                        ->label('Fecha y Hora')
                        ->required(),
                    TextInput::make('amount')
                        ->label('Monto (Opcional)')
                        ->numeric()
                        ->prefix('B/.'),
                    Textarea::make('description')
                        ->label('Descripción/Detalles'),
                ])
                ->action(function (array $data) {
                    CalendarEvent::create(array_merge($data, [
                        'user_id' => Auth::id(),
                        'is_all_day' => true,
                    ]));
                    return redirect()->to(request()->header('Referer') ?: '/admin/financial-calendar');
                }),
        ];
    }

    public function viewEventAction(): Action
    {
        return Action::make('viewEventAction')
            ->label('Detalles del Pago')
            ->modalHeading(fn (array $arguments) => CalendarEvent::find($arguments['eventId'] ?? null)?->title ?? 'Detalle del Pago')
            ->modalContent(function (array $arguments) {
                $event = CalendarEvent::where('user_id', Auth::id())->find($arguments['eventId'] ?? null);
                if (!$event) {
                    return new \Illuminate\Support\HtmlString('<p class="text-gray-500">Evento no encontrado.</p>');
                }

                $amountFormatted = $event->amount ? 'B/. ' . number_format($event->amount, 2) : 'No especificado';
                $dateFormatted = $event->start_date ? $event->start_date->format('d/m/Y') : '-';
                $description = e($event->description ?: 'Sin observaciones o detalles adicionales.');

                return new \Illuminate\Support\HtmlString("
                    <div class='space-y-4 text-sm'>
                        <div class='p-4 bg-blue-50 dark:bg-blue-950/40 rounded-xl border border-blue-100 dark:border-blue-900/50'>
                            <p class='text-xs text-blue-600 dark:text-blue-400 font-semibold uppercase tracking-wider'>Monto Programado</p>
                            <p class='text-2xl font-extrabold text-blue-700 dark:text-blue-300 mt-1'>{$amountFormatted}</p>
                        </div>
                        <div class='grid grid-cols-2 gap-4'>
                            <div>
                                <p class='text-xs text-gray-500 dark:text-gray-400 font-semibold uppercase tracking-wider'>Fecha de Vencimiento</p>
                                <p class='text-sm font-medium text-gray-900 dark:text-gray-100 mt-1'>{$dateFormatted}</p>
                            </div>
                        </div>
                        <div class='pt-2 border-t border-gray-100 dark:border-gray-800'>
                            <p class='text-xs text-gray-500 dark:text-gray-400 font-semibold uppercase tracking-wider'>Notas / Descripción</p>
                            <p class='text-sm text-gray-700 dark:text-gray-300 mt-1 whitespace-pre-line bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg border border-gray-100 dark:border-gray-800'>{$description}</p>
                        </div>
                    </div>
                ");
            })
            ->modalSubmitActionLabel('Eliminar Pago')
            ->modalSubmitAction(fn (Action $action) => $action->color('danger')->icon('heroicon-o-trash'))
            ->action(function (array $arguments) {
                $eventId = $arguments['eventId'] ?? null;
                if ($eventId) {
                    $event = CalendarEvent::where('user_id', Auth::id())->find($eventId);
                    if ($event) {
                        $event->delete();
                    }
                }
                return redirect()->to(request()->header('Referer') ?: '/admin/financial-calendar');
            });
    }

    public static function getNavigationGroup(): ?string
    {
        return CrmNavigation::INICIO;
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }


    public function getPendingBudgetItemsProperty(): array
    {
        $user = Auth::user();

        if ($user === null) {
            return [];
        }

        return BudgetPlanItem::query()
            ->with('budgetPlan:id,budget_number,title')
            ->where('is_paid', false)
            ->where('amount', '>', 0)
            ->whereHas('budgetPlan', fn ($query) => $query->forUser($user)->where('status', BudgetStatus::Issued))
            ->orderByDesc('amount')
            ->limit(8)
            ->get()
            ->map(function (BudgetPlanItem $item): array {
                $plan = $item->budgetPlan;
                $currency = QuoteCurrency::resolve($plan?->currency);

                return [
                    'concept' => (string) $item->concept,
                    'amount' => MoneyFormatter::format((float) $item->amount, $currency),
                    'budget' => $plan?->budget_number ?? 'Presupuesto',
                    'url' => $plan
                        ? \App\Filament\Resources\BudgetPlans\BudgetPlanResource::getUrl('view', [
                            'record' => $plan,
                            'tab' => 'items',
                        ])
                        : null,
                ];
            })
            ->all();
    }

    public function getEventsProperty()
    {
        return CalendarEvent::where('user_id', Auth::id())
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title . ($event->amount ? ' (B/. ' . number_format($event->amount, 2) . ')' : ''),
                    'start' => $event->start_date->toIso8601String(),
                    'end' => $event->end_date ? $event->end_date->toIso8601String() : null,
                    'allDay' => $event->is_all_day,
                    'extendedProps' => [
                        'description' => $event->description,
                        'amount' => $event->amount,
                    ]
                ];
            })->values()->toArray();
    }
}
