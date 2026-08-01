<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

use App\Models\CalendarEvent;
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

    public function deleteEventAction(): Action
    {
        return Action::make('deleteEventAction')
            ->requiresConfirmation()
            ->modalHeading('¿Eliminar Evento?')
            ->modalDescription('¿Estás seguro de que deseas eliminar este evento permanentemente del calendario?')
            ->modalSubmitActionLabel('Sí, eliminar')
            ->color('danger')
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

    public static function getNavigationSort(): ?int
    {
        return 1;
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
