<?php

namespace App\Livewire;

use App\Models\BudgetPlan;
use App\Models\BudgetPlanItem;
use App\Models\UserBudgetRule;
use App\Services\AssistantResponseNormalizer;
use App\Services\Budgets\BudgetShareService;
use App\Services\FinancialContextService;
use App\Services\GeminiService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

class ChatbotWidget extends Component
{
    public bool $isOpen = false;

    public string $message = '';

    /** @var array<int, array<string, mixed>> */
    public array $chatHistory = [];

    public bool $isLoading = false;

    /** @var array{budget_number: string, channel: string}|null */
    public ?array $pendingSend = null;

    public function toggleChat()
    {
        $this->isOpen = ! $this->isOpen;
        if ($this->isOpen && empty($this->chatHistory)) {
            $this->chatHistory[] = [
                'role' => 'model',
                'content' => '¡Hola! Soy tu asistente financiero. ¿En qué te puedo ayudar hoy? Puedo analizar tus gastos, generar presupuestos o enviártelos por WhatsApp o correo.',
            ];
        }
    }

    public function sendMessage()
    {
        $userMessage = trim($this->message);
        if ($userMessage === '') {
            return;
        }

        $this->chatHistory[] = [
            'role' => 'user',
            'content' => $userMessage,
        ];

        $this->message = '';

        if ($this->pendingSend !== null) {
            $this->handlePendingSendInput($userMessage);

            return;
        }

        if ($this->shouldInitiateSendBudgetFlow($userMessage)) {
            $this->initiateSendBudgetFlow();

            return;
        }

        $this->isLoading = true;
        $this->dispatch('trigger-api');
    }

    public function handleQuickAction(string $channel, string $budgetNumber): void
    {
        if (! in_array($channel, ['whatsapp', 'gmail'], true)) {
            return;
        }

        $this->pendingSend = [
            'budget_number' => $budgetNumber,
            'channel' => $channel,
        ];

        $this->chatHistory[] = [
            'role' => 'model',
            'content' => $channel === 'whatsapp'
                ? 'Escribe el número de WhatsApp con prefijo **+507** (ejemplo: +507 6542-5xxx).'
                : 'Indícame el correo electrónico al que quieres enviar el presupuesto.',
        ];
    }

    #[\Livewire\Attributes\On('trigger-api')]
    public function fetchAiResponse(
        GeminiService $geminiService,
        FinancialContextService $contextService,
        \App\Services\FinancialSystemPrompt $promptService,
        AssistantResponseNormalizer $responseNormalizer,
    ) {
        try {
            $apiHistory = [];
            foreach ($this->chatHistory as $msg) {
                $role = $msg['role'] === 'user' ? 'user' : 'model';
                $apiHistory[] = [
                    'role' => $role,
                    'parts' => [['text' => (string) ($msg['content'] ?? '')]],
                ];
            }

            $user = Auth::user();
            $financialSummary = $user ? $contextService->getFourMonthSummary($user) : 'No hay datos de usuario disponibles.';
            $savingsSummary = $user ? $contextService->getSavingsSummary($user) : '';

            $systemInstruction = $promptService->getSystemInstruction($user)
                ."\n\nResumen Histórico del Usuario (solo sus presupuestos):\n".$financialSummary;

            if ($savingsSummary !== '') {
                $systemInstruction .= "\n\n".$savingsSummary;
            }

            $response = $geminiService->generateContent($apiHistory, $systemInstruction);

            $userMessage = '';
            for ($i = count($this->chatHistory) - 1; $i >= 0; $i--) {
                if ($this->chatHistory[$i]['role'] === 'user') {
                    $userMessage = $this->chatHistory[$i]['content'];
                    break;
                }
            }

            $actionMessage = $this->processResponseAction($response);

            if ($actionMessage !== null) {
                $this->chatHistory[] = $actionMessage;

                return;
            }

            $processedResponse = $responseNormalizer->normalize($response);

            if ($processedResponse === '') {
                $processedResponse = $this->generateFallbackResponse($userMessage);
            }

            $this->chatHistory[] = [
                'role' => 'model',
                'content' => $processedResponse,
            ];
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function processResponseAction(string $response): ?array
    {
        $jsonString = null;
        if (preg_match('/```json\s*(.*?)\s*```/s', $response, $matches)) {
            $jsonString = $matches[1];
        } else {
            $jsonStart = strpos($response, '{');
            $jsonEnd = strrpos($response, '}');
            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
            }
        }

        if ($jsonString === null) {
            return null;
        }

        $actionData = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! isset($actionData['action'])) {
            return null;
        }

        $intro = trim(str_replace($jsonString, '', $response));
        $intro = preg_replace('/```json\s*```/s', '', $intro) ?? $intro;
        $intro = trim($intro);

        if ($actionData['action'] === 'save_budget_rule' && Auth::check()) {
            $rule = trim((string) ($actionData['rule'] ?? ''));
            if ($rule !== '') {
                UserBudgetRule::query()->create([
                    'user_id' => Auth::id(),
                    'content' => $rule,
                ]);
            }

            return [
                'role' => 'model',
                'content' => ($intro !== '' ? $intro."\n\n" : '').'✅ **Regla guardada en tu cuenta.** Solo tú verás esta regla en futuros presupuestos.',
            ];
        }

        if ($actionData['action'] === 'request_send_budget' && Auth::check()) {
            $budgetNumber = (string) ($actionData['budget_number'] ?? '');
            $plan = $this->findOwnedBudget($budgetNumber);

            if ($plan === null) {
                return [
                    'role' => 'model',
                    'content' => '❌ No encontré ese presupuesto en tu cuenta.',
                ];
            }

            return [
                'role' => 'model',
                'content' => (string) ($actionData['message'] ?? "¿Por dónde quieres recibir el presupuesto **{$plan->budget_number}**?"),
                'actions' => [
                    ['id' => 'whatsapp', 'label' => 'WhatsApp'],
                    ['id' => 'gmail', 'label' => 'Gmail'],
                ],
                'budget_number' => $plan->budget_number,
            ];
        }

        if ($actionData['action'] === 'create_budget') {
            $budgetData = $actionData['budget'] ?? null;
            if ($budgetData && Auth::check()) {
                $this->createBudgetFromData($budgetData);

                return [
                    'role' => 'model',
                    'content' => ($intro !== '' ? $intro."\n\n" : '').'✅ **¡Tu presupuesto ha sido creado exitosamente!** Puedes revisarlo en la pestaña de Presupuestos.',
                ];
            }
        }

        if ($actionData['action'] === 'add_to_budget') {
            $budgetNumber = $actionData['budget_number'] ?? null;
            $itemsData = $actionData['items'] ?? [];
            if ($budgetNumber && ! empty($itemsData) && Auth::check()) {
                $success = $this->addToBudgetFromData($budgetNumber, $itemsData);

                return [
                    'role' => 'model',
                    'content' => ($intro !== '' ? $intro."\n\n" : '')
                        .($success
                            ? "✅ **¡Los pagos se anexaron a tu comprobante {$budgetNumber}!**"
                            : "❌ No se pudo anexar al comprobante {$budgetNumber}."),
                ];
            }
        }

        if ($actionData['action'] === 'create_calendar_events') {
            $eventsData = $actionData['events'] ?? [];
            if (! empty($eventsData) && Auth::check()) {
                foreach ($eventsData as $event) {
                    \App\Models\CalendarEvent::create([
                        'user_id' => Auth::id(),
                        'title' => $event['title'] ?? 'Evento Generado por IA',
                        'description' => $event['description'] ?? null,
                        'start_date' => $event['start_date'] ?? now(),
                        'amount' => $event['amount'] ?? null,
                        'is_all_day' => true,
                    ]);
                }

                return [
                    'role' => 'model',
                    'content' => ($intro !== '' ? $intro."\n\n" : '').'🗓️ **¡Eventos agendados en tu Calendario Financiero!**',
                ];
            }
        }

        return null;
    }

    protected function shouldInitiateSendBudgetFlow(string $userMessage): bool
    {
        return (bool) preg_match('/(env[ií]a|manda|comparte|mandar|enviar).*(presupuesto|comprobante|pdf)/iu', $userMessage);
    }

    protected function initiateSendBudgetFlow(?string $budgetNumber = null): void
    {
        $user = Auth::user();
        if ($user === null) {
            return;
        }

        $plan = $budgetNumber !== null
            ? $this->findOwnedBudget($budgetNumber)
            : BudgetPlan::query()
                ->where('created_by', $user->id)
                ->orderByDesc('created_at')
                ->first();

        if ($plan === null) {
            $this->chatHistory[] = [
                'role' => 'model',
                'content' => 'No tienes presupuestos para enviar. ¿Quieres que te ayude a crear uno?',
            ];

            return;
        }

        $this->chatHistory[] = [
            'role' => 'model',
            'content' => "¿Por dónde quieres recibir el presupuesto **{$plan->budget_number}** ({$plan->title})?",
            'actions' => [
                ['id' => 'whatsapp', 'label' => 'WhatsApp'],
                ['id' => 'gmail', 'label' => 'Gmail'],
            ],
            'budget_number' => $plan->budget_number,
        ];
    }

    protected function handlePendingSendInput(string $input): void
    {
        $user = Auth::user();
        if ($user === null || $this->pendingSend === null) {
            return;
        }

        $plan = $this->findOwnedBudget($this->pendingSend['budget_number']);
        if ($plan === null) {
            $this->chatHistory[] = [
                'role' => 'model',
                'content' => '❌ No encontré ese presupuesto.',
            ];
            $this->pendingSend = null;

            return;
        }

        $shareService = app(BudgetShareService::class);

        if ($this->pendingSend['channel'] === 'whatsapp') {
            $shareService->preparePdfForShare($plan);
            $links = $shareService->whatsAppLinks($plan, $input, $user);
            $this->pendingSend = null;
            $this->chatHistory[] = [
                'role' => 'model',
                'content' => '✅ PDF generado. Compartiendo por WhatsApp…',
            ];
            $this->dispatch('share-whatsapp-document', links: $links);

            return;
        }

        if (! filter_var($input, FILTER_VALIDATE_EMAIL)) {
            $this->chatHistory[] = [
                'role' => 'model',
                'content' => '⚠️ Ese correo no parece válido. Escríbelo de nuevo, por favor.',
            ];

            return;
        }

        try {
            $shareService->sendEmail($plan, $input, $user);
            $this->pendingSend = null;
            $this->chatHistory[] = [
                'role' => 'model',
                'content' => "✅ Presupuesto enviado a **{$input}** con el PDF adjunto.",
            ];
        } catch (\Throwable) {
            $this->chatHistory[] = [
                'role' => 'model',
                'content' => '❌ No se pudo enviar el correo. Verifica la configuración SMTP o inténtalo más tarde.',
            ];
        }
    }

    protected function findOwnedBudget(string $budgetNumber): ?BudgetPlan
    {
        $user = Auth::user();
        if ($user === null) {
            return null;
        }

        return BudgetPlan::query()
            ->where('created_by', $user->id)
            ->where('budget_number', $budgetNumber)
            ->first();
    }

    protected function createBudgetFromData(array $budgetData): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        DB::transaction(function () use ($budgetData, $user): void {
            $totalAllocated = collect($budgetData['items'])->sum('amount');
            $remaining = ($budgetData['net_income'] ?? 0) - $totalAllocated;

            $plan = BudgetPlan::create([
                'budget_number' => 'BUD-'.strtoupper(Str::random(6)),
                'status' => \App\Enums\BudgetStatus::Draft,
                'title' => $budgetData['title'] ?? 'Presupuesto Generado por IA',
                'period' => $budgetData['period'] ?? 'biweekly',
                'currency' => $budgetData['currency'] ?? 'PAB',
                'net_income' => $budgetData['net_income'] ?? 0,
                'total_allocated' => $totalAllocated,
                'remaining_balance' => $remaining,
                'created_by' => $user->id,
            ]);

            $sortOrder = 1;
            foreach ($budgetData['items'] as $item) {
                BudgetPlanItem::create([
                    'budget_plan_id' => $plan->id,
                    'category_type' => $item['category_type'] ?? 'fixed_expense',
                    'sort_order' => $sortOrder++,
                    'concept' => $item['concept'] ?? 'Item',
                    'amount' => $item['amount'] ?? 0,
                    'notes' => $item['notes'] ?? null,
                ]);
            }
        });
    }

    protected function addToBudgetFromData(string $budgetNumber, array $itemsData): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        $plan = BudgetPlan::where('created_by', $user->id)
            ->where('budget_number', $budgetNumber)
            ->first();

        if (! $plan) {
            return false;
        }

        DB::transaction(function () use ($plan, $itemsData): void {
            $maxSort = $plan->items()->max('sort_order') ?? 0;

            $addedAmount = 0;
            foreach ($itemsData as $item) {
                $maxSort++;
                $amount = $item['amount'] ?? 0;
                $addedAmount += $amount;

                BudgetPlanItem::create([
                    'budget_plan_id' => $plan->id,
                    'category_type' => $item['category_type'] ?? 'fixed_expense',
                    'sort_order' => $maxSort,
                    'concept' => $item['concept'] ?? 'Item Anexado por IA',
                    'amount' => $amount,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            $plan->total_allocated += $addedAmount;
            $plan->remaining_balance = $plan->net_income - $plan->total_allocated;
            $plan->save();
        });

        return true;
    }

    protected function generateFallbackResponse(string $userQuery): string
    {
        $query = mb_strtolower(trim($userQuery));

        if (preg_match('/(en qu[eé] puedes ayudarme|qu[eé] puedes hacer|qu[eé] sabes hacer|help|ayuda)/i', $query)) {
            return "Puedo ayudarte con:\n\n"
                ."• **Presupuestos quincenales** (crear o ampliar comprobantes)\n"
                ."• **Reglas personales** de ahorro (solo para tu cuenta)\n"
                ."• **Enviar presupuestos** por WhatsApp o correo\n"
                ."• **Calendario financiero** (agendar pagos)\n"
                ."• **Análisis** de tus últimos presupuestos\n\n"
                ."Escribe `/help` para ver todos los comandos.";
        }

        if (preg_match('/(hola|buenas|buenos dias|tardes|noches|saludos|hello|hi)/i', $query)) {
            return '¡Hola! Soy tu asistente financiero de MGF. Estoy aquí para ayudarte con presupuestos, ahorros y tu calendario. ¿En qué te puedo ayudar hoy? Escribe `/help` para ver los comandos.';
        }

        if (preg_match('/(presupuesto|budget|gastar|gasto|ahorro|ahorrar)/i', $query)) {
            return 'Puedo ayudarte a estructurar un presupuesto quincenal. Cuéntame tus ingresos y gastos, o dime si quieres usar una regla personal con "Añade esta regla: ...".';
        }

        if (preg_match('/(calendario|evento|agendar|fecha|pago)/i', $query)) {
            return 'Puedo agendar tus pagos en el Calendario Financiero. Indícame fecha, descripción y monto para programarlo.';
        }

        return 'Como tu asistente de MGF, puedo ayudarte con presupuestos, enviarlos por WhatsApp o correo, y tu calendario. Escribe `/help` para ver opciones o cuéntame qué necesitas.';
    }

    public function render()
    {
        return view('livewire.chatbot-widget');
    }
}
