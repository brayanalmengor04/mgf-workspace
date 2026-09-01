<?php

namespace App\Livewire;

use App\Models\AssistantConversation;
use App\Models\BudgetPlan;
use App\Models\BudgetPlanItem;
use App\Models\SavingsAccount;
use App\Models\UserBudgetRule;
use App\Services\AssistantContextCacheService;
use App\Services\AssistantConversationService;
use App\Services\AssistantResponseCacheService;
use App\Services\AssistantResponseNormalizer;
use App\Services\Budgets\BudgetShareService;
use App\Services\GeminiService;
use App\Services\Savings\SavingsLedgerService;
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

    public ?int $conversationId = null;

    /** @var array<int, array{id: int, title: string}> */
    public array $conversationOptions = [];

    public function mount(AssistantConversationService $conversationService): void
    {
        $this->bootstrapConversation($conversationService);
    }

    public function toggleChat(AssistantConversationService $conversationService): void
    {
        $this->isOpen = ! $this->isOpen;

        if ($this->isOpen) {
            $this->bootstrapConversation($conversationService);
        }
    }

    public function startNewConversation(AssistantConversationService $conversationService): void
    {
        $user = Auth::user();
        if ($user === null) {
            return;
        }

        $conversation = $conversationService->createConversation($user);
        $conversationService->seedWelcomeIfEmpty($conversation);
        $this->syncConversationState($conversationService, $conversation);
        $this->pendingSend = null;
        $this->message = '';
    }

    public function selectConversation(int|string $conversationId, AssistantConversationService $conversationService): void
    {
        $user = Auth::user();
        if ($user === null) {
            return;
        }

        $conversation = $conversationService->switchConversation($user, (int) $conversationId);
        $this->syncConversationState($conversationService, $conversation);
        $this->pendingSend = null;
    }

    public function clearConversation(AssistantConversationService $conversationService): void
    {
        $user = Auth::user();
        if ($user === null) {
            return;
        }

        $conversation = $this->activeConversation($conversationService);
        $conversationService->clearConversation($conversation);
        $this->syncConversationState($conversationService, $conversation->fresh());
        $this->pendingSend = null;
        $this->message = '';
    }

    public function deleteConversation(int $conversationId, AssistantConversationService $conversationService): void
    {
        $user = Auth::user();
        if ($user === null) {
            return;
        }

        $conversation = $conversationService->deleteConversation($user, $conversationId);
        if ($conversation === null) {
            return;
        }

        $conversationService->seedWelcomeIfEmpty($conversation);
        $this->syncConversationState($conversationService, $conversation);
        $this->pendingSend = null;
        $this->message = '';
    }

    public function sendSuggestedPrompt(string $prompt, AssistantConversationService $conversationService): void
    {
        $prompt = trim($prompt);
        if ($prompt === '') {
            return;
        }

        $this->message = $prompt;
        $this->sendMessage($conversationService);
    }

    public function sendMessage(AssistantConversationService $conversationService)
    {
        $userMessage = trim($this->message);
        if ($userMessage === '') {
            return;
        }

        $conversation = $this->activeConversation($conversationService);
        $conversationService->appendMessage($conversation, 'user', $userMessage);
        $this->chatHistory = $conversationService->messagesForUi($conversation);
        $this->refreshConversationOptions($conversationService);

        $this->message = '';

        if ($this->pendingSend !== null) {
            $this->handlePendingSendInput($userMessage, $conversationService);

            return;
        }

        if ($this->shouldInitiateSendBudgetFlow($userMessage)) {
            $this->initiateSendBudgetFlow(conversationService: $conversationService);

            return;
        }

        $this->isLoading = true;
        $this->dispatch('trigger-api');
    }

    public function handleQuickAction(string $channel, string $budgetNumber, AssistantConversationService $conversationService): void
    {
        if (! in_array($channel, ['whatsapp', 'gmail'], true)) {
            return;
        }

        $this->pendingSend = [
            'budget_number' => $budgetNumber,
            'channel' => $channel,
        ];

        $this->storeModelMessage([
            'role' => 'model',
            'content' => $channel === 'whatsapp'
                ? 'Escribe el número de WhatsApp con prefijo **+507** (ejemplo: +507 6542-5xxx).'
                : 'Indícame el correo electrónico al que quieres enviar el presupuesto.',
        ], $conversationService);
    }

    #[\Livewire\Attributes\On('trigger-api')]
    public function fetchAiResponse(
        GeminiService $geminiService,
        AssistantContextCacheService $contextCache,
        AssistantResponseCacheService $responseCache,
        \App\Services\FinancialSystemPrompt $promptService,
        AssistantResponseNormalizer $responseNormalizer,
        AssistantConversationService $conversationService,
    ) {
        try {
            $user = Auth::user();
            $conversation = $this->activeConversation($conversationService);
            $apiHistory = $conversationService->buildApiHistory($conversation);

            $userMessage = '';
            for ($i = count($this->chatHistory) - 1; $i >= 0; $i--) {
                if ($this->chatHistory[$i]['role'] === 'user') {
                    $userMessage = $this->chatHistory[$i]['content'];
                    break;
                }
            }

            if ($user !== null && $userMessage !== '') {
                $cachedResponse = $responseCache->get($user, $userMessage);
                if ($cachedResponse !== null) {
                    $this->storeModelMessage([
                        'role' => 'model',
                        'content' => $cachedResponse,
                    ], $conversationService);

                    return;
                }
            }

            $financialSummary = $user
                ? $contextCache->getCompactSummary($user)
                : 'No hay datos de usuario disponibles.';

            $systemInstruction = $promptService->getSystemInstruction($user)
                ."\n\n".$financialSummary;

            if (filled($conversation->summary)) {
                $systemInstruction .= "\n\nResumen de la conversación previa:\n".$conversation->summary;
            }

            if (! $geminiService->isConfigured()) {
                $this->storeModelMessage([
                    'role' => 'model',
                    'content' => $geminiService->missingKeyMessage(),
                ], $conversationService);

                return;
            }

            $response = $geminiService->generateContent($apiHistory, $systemInstruction);

            $actionMessage = $this->processResponseAction($response, $contextCache);

            if ($actionMessage !== null) {
                $this->storeModelMessage($actionMessage, $conversationService);
                $conversationService->maybeSummarize($conversation->fresh(), $geminiService);

                return;
            }

            $processedResponse = $responseNormalizer->normalize($response);

            if ($processedResponse === '') {
                $processedResponse = $this->generateFallbackResponse($userMessage);
            }

            if ($user !== null && $userMessage !== '') {
                $responseCache->put($user, $userMessage, $processedResponse);
            }

            $this->storeModelMessage([
                'role' => 'model',
                'content' => $processedResponse,
            ], $conversationService);

            $conversationService->maybeSummarize($conversation->fresh(), $geminiService);
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function processResponseAction(string $response, ?AssistantContextCacheService $contextCache = null): ?array
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
                $contextCache?->invalidate(Auth::user());

                return [
                    'role' => 'model',
                    'content' => ($intro !== '' ? $intro."\n\n" : '').'✅ **¡Tu presupuesto ha sido creado exitosamente!** Puedes revisarlo en la pestaña de Presupuestos.',
                ];
            }
        }

        if ($actionData['action'] === 'create_savings_account') {
            $accountData = $actionData['account'] ?? null;
            if ($accountData && Auth::check()) {
                try {
                    $account = $this->createSavingsAccountFromData($accountData);
                    $contextCache?->invalidate(Auth::user());

                    return [
                        'role' => 'model',
                        'content' => ($intro !== '' ? $intro."\n\n" : '')
                            ."✅ **Cuenta de ahorro «{$account->name}» creada.** Revisa el módulo de Ahorros para ver el detalle.",
                    ];
                } catch (\Throwable $exception) {
                    return [
                        'role' => 'model',
                        'content' => '❌ No se pudo crear la cuenta de ahorro: '.$exception->getMessage(),
                    ];
                }
            }
        }

        if ($actionData['action'] === 'deposit_to_savings') {
            if (Auth::check()) {
                try {
                    $transaction = $this->depositToSavingsFromData($actionData);
                    $contextCache?->invalidate(Auth::user());
                    $amount = number_format((float) $transaction->amount, 2);

                    return [
                        'role' => 'model',
                        'content' => ($intro !== '' ? $intro."\n\n" : '')
                            ."✅ **Depósito de B/. {$amount} registrado** en la cuenta de ahorro.",
                    ];
                } catch (\Throwable $exception) {
                    return [
                        'role' => 'model',
                        'content' => '❌ No se pudo registrar el depósito: '.$exception->getMessage(),
                    ];
                }
            }
        }

        if ($actionData['action'] === 'add_to_budget') {
            $budgetNumber = $actionData['budget_number'] ?? null;
            $itemsData = $actionData['items'] ?? [];
            if ($budgetNumber && ! empty($itemsData) && Auth::check()) {
                $success = $this->addToBudgetFromData($budgetNumber, $itemsData);
                if ($success) {
                    $contextCache?->invalidate(Auth::user());
                }

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

    protected function initiateSendBudgetFlow(?string $budgetNumber = null, ?AssistantConversationService $conversationService = null): void
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
            if ($conversationService !== null) {
                $this->storeModelMessage([
                    'role' => 'model',
                    'content' => 'No tienes presupuestos para enviar. ¿Quieres que te ayude a crear uno?',
                ], $conversationService);
            }

            return;
        }

        $message = [
            'role' => 'model',
            'content' => "¿Por dónde quieres recibir el presupuesto **{$plan->budget_number}** ({$plan->title})?",
            'actions' => [
                ['id' => 'whatsapp', 'label' => 'WhatsApp'],
                ['id' => 'gmail', 'label' => 'Gmail'],
            ],
            'budget_number' => $plan->budget_number,
        ];

        if ($conversationService !== null) {
            $this->storeModelMessage($message, $conversationService);

            return;
        }

        $this->chatHistory[] = $message;
    }

    protected function handlePendingSendInput(string $input, AssistantConversationService $conversationService): void
    {
        $user = Auth::user();
        if ($user === null || $this->pendingSend === null) {
            return;
        }

        $plan = $this->findOwnedBudget($this->pendingSend['budget_number']);
        if ($plan === null) {
            $this->storeModelMessage([
                'role' => 'model',
                'content' => '❌ No encontré ese presupuesto.',
            ], $conversationService);
            $this->pendingSend = null;

            return;
        }

        $shareService = app(BudgetShareService::class);

        if ($this->pendingSend['channel'] === 'whatsapp') {
            $shareService->preparePdfForShare($plan);
            $links = $shareService->whatsAppLinks($plan, $input, $user);
            $this->pendingSend = null;
            $this->storeModelMessage([
                'role' => 'model',
                'content' => '✅ PDF generado. Compartiendo por WhatsApp…',
            ], $conversationService);
            $this->dispatch('share-whatsapp-document', links: $links);

            return;
        }

        if (! filter_var($input, FILTER_VALIDATE_EMAIL)) {
            $this->storeModelMessage([
                'role' => 'model',
                'content' => '⚠️ Ese correo no parece válido. Escríbelo de nuevo, por favor.',
            ], $conversationService);

            return;
        }

        try {
            $shareService->sendEmail($plan, $input, $user);
            $this->pendingSend = null;
            $this->storeModelMessage([
                'role' => 'model',
                'content' => "✅ Presupuesto enviado a **{$input}** con el PDF adjunto.",
            ], $conversationService);
        } catch (\Throwable) {
            $this->storeModelMessage([
                'role' => 'model',
                'content' => '❌ No se pudo enviar el correo. Verifica la configuración SMTP o inténtalo más tarde.',
            ], $conversationService);
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

        app(\App\Services\Budgets\BudgetPlanFactory::class)->createDraftFromArray($budgetData, $user);
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
                ."• **Cuentas de ahorro** (crear metas y registrar depósitos)\n"
                ."• **Reglas personales** de ahorro (solo para tu cuenta)\n"
                ."• **Enviar presupuestos** por WhatsApp o correo\n"
                ."• **Cotizaciones** y análisis de las más recientes\n"
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

    protected function bootstrapConversation(AssistantConversationService $conversationService): void
    {
        $user = Auth::user();
        if ($user === null) {
            return;
        }

        $conversation = $conversationService->resolveActiveConversation($user);
        $conversationService->seedWelcomeIfEmpty($conversation);
        $this->syncConversationState($conversationService, $conversation);
    }

    protected function syncConversationState(
        AssistantConversationService $conversationService,
        AssistantConversation $conversation,
    ): void {
        $this->conversationId = $conversation->id;
        $this->chatHistory = $conversationService->messagesForUi($conversation);
        $this->refreshConversationOptions($conversationService);
    }

    protected function refreshConversationOptions(AssistantConversationService $conversationService): void
    {
        $user = Auth::user();
        if ($user === null) {
            $this->conversationOptions = [];

            return;
        }

        $this->conversationOptions = $conversationService->listForUser($user)
            ->map(fn (AssistantConversation $conversation): array => [
                'id' => $conversation->id,
                'title' => $conversationService->displayTitle($conversation),
                'active' => $conversation->id === $this->conversationId,
                'time' => $conversation->last_message_at?->diffForHumans(short: true),
            ])
            ->all();
    }

    protected function activeConversation(AssistantConversationService $conversationService): AssistantConversation
    {
        $user = Auth::user();
        if ($user === null) {
            throw new \RuntimeException('Usuario no autenticado.');
        }

        if ($this->conversationId !== null) {
            $existing = AssistantConversation::query()
                ->forUser($user)
                ->whereKey($this->conversationId)
                ->first();

            if ($existing !== null) {
                return $existing;
            }
        }

        $conversation = $conversationService->resolveActiveConversation($user);
        $this->conversationId = $conversation->id;

        return $conversation;
    }

    /**
     * @param  array<string, mixed>  $message
     */
    protected function storeModelMessage(array $message, AssistantConversationService $conversationService): void
    {
        $conversation = $this->activeConversation($conversationService);
        $metadata = [];

        if (! empty($message['actions'])) {
            $metadata['actions'] = $message['actions'];
        }

        if (! empty($message['budget_number'])) {
            $metadata['budget_number'] = $message['budget_number'];
        }

        $conversationService->appendMessage(
            $conversation,
            'model',
            (string) $message['content'],
            $metadata === [] ? null : $metadata,
        );

        $this->chatHistory = $conversationService->messagesForUi($conversation);
        $this->refreshConversationOptions($conversationService);
    }

    protected function createSavingsAccountFromData(array $data): SavingsAccount
    {
        $user = Auth::user();
        if ($user === null) {
            throw new \RuntimeException('Usuario no autenticado.');
        }

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new \InvalidArgumentException('El nombre de la meta es obligatorio.');
        }

        return DB::transaction(function () use ($data, $user, $name): SavingsAccount {
            $account = SavingsAccount::query()->create([
                'user_id' => $user->id,
                'name' => $name,
                'bank_alias' => filled($data['bank_alias'] ?? null) ? $data['bank_alias'] : null,
                'bank_last_four' => filled($data['bank_last_four'] ?? null) ? $data['bank_last_four'] : null,
                'currency' => $data['currency'] ?? 'USD',
                'period' => $data['period'] ?? 'biweekly',
                'target_per_period' => isset($data['target_per_period']) ? (float) $data['target_per_period'] : null,
                'goal_amount' => isset($data['goal_amount']) ? (float) $data['goal_amount'] : null,
                'current_balance' => 0,
                'pending_replenishment' => 0,
                'is_active' => true,
            ]);

            $openingBalance = (float) ($data['opening_balance'] ?? 0);
            if ($openingBalance > 0) {
                app(SavingsLedgerService::class)->recordOpening(
                    account: $account,
                    amount: $openingBalance,
                    notes: 'Apertura vía asistente IA',
                );
            }

            return $account->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function depositToSavingsFromData(array $data): \App\Models\SavingsTransaction
    {
        $user = Auth::user();
        if ($user === null) {
            throw new \RuntimeException('Usuario no autenticado.');
        }

        $amount = (float) ($data['amount'] ?? 0);
        if ($amount <= 0) {
            throw new \InvalidArgumentException('El monto del depósito debe ser mayor que cero.');
        }

        $account = $this->resolveSavingsAccount($user, $data);
        if ($account === null) {
            throw new \InvalidArgumentException('No encontré la cuenta de ahorro indicada.');
        }

        return app(SavingsLedgerService::class)->recordDeposit(
            account: $account,
            amount: $amount,
            notes: filled($data['notes'] ?? null) ? (string) $data['notes'] : 'Depósito vía asistente IA',
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function resolveSavingsAccount(\App\Models\User $user, array $data): ?SavingsAccount
    {
        $query = SavingsAccount::query()->forUser($user)->active();

        if (isset($data['account_id'])) {
            return $query->whereKey((int) $data['account_id'])->first();
        }

        $accountName = trim((string) ($data['account_name'] ?? ''));
        if ($accountName !== '') {
            $exact = (clone $query)->where('name', $accountName)->first();
            if ($exact !== null) {
                return $exact;
            }

            return (clone $query)
                ->where('name', 'like', '%'.$accountName.'%')
                ->orderByDesc('updated_at')
                ->first();
        }

        return $query->orderByDesc('updated_at')->first();
    }

    /**
     * @return array<int, array{label: string, prompt: string}>
     */
    public function getSuggestedPromptsProperty(): array
    {
        return [
            ['label' => 'Presupuesto reciente', 'prompt' => '/mi_ultimo_presupuesto'],
            ['label' => 'Crear presupuesto', 'prompt' => 'Ayúdame a crear un presupuesto quincenal. Pregúntame ingresos y gastos principales.'],
            ['label' => 'Mis ahorros', 'prompt' => '/mis_ahorros'],
            ['label' => 'Crear ahorro', 'prompt' => 'Quiero crear una cuenta de ahorro nueva. Pregúntame los datos que necesitas.'],
            ['label' => 'Cotizaciones', 'prompt' => '/mis_cotizaciones'],
            ['label' => 'Enviar presupuesto', 'prompt' => 'Quiero enviar mi presupuesto más reciente'],
        ];
    }

    public function render()
    {
        return view('livewire.chatbot-widget');
    }
}
