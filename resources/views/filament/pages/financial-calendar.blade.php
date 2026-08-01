<x-filament-panels::page>
    <div wire:ignore>
        <div id="calendar" class="w-full h-[800px] bg-white dark:bg-gray-900 rounded-xl shadow p-4 border border-gray-200 dark:border-gray-800"></div>
    </div>

    <!-- Include FullCalendar JS & CSS -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js'></script>
    @script
    <script>
        var calendarEl = document.getElementById('calendar');
        var eventsData = @json($this->events);

        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            locale: 'es',
            events: eventsData,
            eventColor: '#2563eb',
            eventClick: function(info) {
                $wire.mountAction('viewEventAction', { eventId: info.event.id });
            }
        });
        calendar.render();
    </script>
    @endscript
</x-filament-panels::page>
