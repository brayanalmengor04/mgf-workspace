<x-filament-panels::page>
    @php
        $stats = $this->calendarStats;
        $pendingItems = $this->pendingBudgetItems;
        $upcomingEvents = $this->upcomingEventsList;
    @endphp

    <div class="mgf-crm mgf-crm-calendar-page" style="display:flex;flex-direction:column;gap:1.25rem;">
        <div class="mgf-crm-grid mgf-crm-grid--stats">
            <x-crm.stat-card label="Eventos este mes" :value="(string) ($stats['this_month'] ?? 0)" />
            <x-crm.stat-card label="Próximos eventos" :value="(string) ($stats['upcoming'] ?? 0)" />
            <x-crm.stat-card
                label="Pendiente en presupuestos"
                :value="$stats['pending_total'] ?? '—'"
                :delta="['text' => ($stats['pending_count'] ?? 0).' partidas por pagar', 'tone' => 'neutral']"
                value-tone="warning"
            />
        </div>

        <div class="mgf-crm-calendar-layout">
            <x-crm.panel title="Calendario financiero" subtitle="Haz clic en un evento para ver detalles o eliminarlo">
                <div wire:ignore class="mgf-crm-calendar">
                    <div id="mgf-financial-calendar"></div>
                </div>
            </x-crm.panel>

            <div class="mgf-crm-calendar-aside">
                <x-crm.panel title="Próximos vencimientos" subtitle="Eventos programados">
                    <x-crm.schedule-list :events="$upcomingEvents" />
                </x-crm.panel>

                <x-crm.panel title="Pagos pendientes" subtitle="Ítems sin marcar en presupuestos emitidos">
                    @if (count($pendingItems) > 0)
                        <div class="mgf-crm-pending-list">
                            @foreach ($pendingItems as $item)
                                <a href="{{ $item['url'] ?? '#' }}" class="mgf-crm-pending-item">
                                    <div class="mgf-crm-pending-item__main">
                                        <span class="mgf-crm-pending-item__concept">{{ $item['concept'] }}</span>
                                        <span class="mgf-crm-pending-item__budget">{{ $item['budget'] }}</span>
                                    </div>
                                    <span class="mgf-crm-pending-item__amount">{{ $item['amount'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <p class="mgf-crm-empty-note">No hay partidas pendientes. ¡Buen trabajo!</p>
                    @endif
                </x-crm.panel>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    @script
    <script>
        const calendarEl = document.getElementById('mgf-financial-calendar');
        const eventsData = @json($this->events);

        if (calendarEl && typeof FullCalendar !== 'undefined') {
            const isDark = document.documentElement.classList.contains('dark');

            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,listWeek',
                },
                buttonText: {
                    today: 'Hoy',
                    month: 'Mes',
                    week: 'Semana',
                    list: 'Lista',
                },
                locale: 'es',
                events: eventsData,
                eventColor: '#465fff',
                eventTextColor: '#ffffff',
                height: 'auto',
                contentHeight: 520,
                dayMaxEvents: 3,
                nowIndicator: true,
                eventClick(info) {
                    $wire.mountAction('viewEventAction', { eventId: info.event.id });
                },
                eventDidMount(info) {
                    if (isDark) {
                        info.el.style.borderColor = 'rgba(70, 95, 255, 0.35)';
                    }
                },
            });

            calendar.render();
        }
    </script>
    @endscript
</x-filament-panels::page>
