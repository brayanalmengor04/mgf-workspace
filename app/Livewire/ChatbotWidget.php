<?php

namespace App\Livewire;

use App\Models\BudgetPlan;
use App\Models\BudgetPlanItem;
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
    public array $chatHistory = [];
    public bool $isLoading = false;

    public function toggleChat()
    {
        $this->isOpen = !$this->isOpen;
        if ($this->isOpen && empty($this->chatHistory)) {
            $this->chatHistory[] = [
                'role' => 'model',
                'content' => '¡Hola! Soy tu asistente financiero. ¿En qué te puedo ayudar hoy? Puedo analizar tus gastos o ayudarte a generar un nuevo presupuesto.'
            ];
        }
    }

    public function sendMessage()
    {
        $userMessage = trim($this->message);
        if (empty($userMessage)) return;

        $this->chatHistory[] = [
            'role' => 'user',
            'content' => $userMessage
        ];
        
        $this->message = '';

        // Trigger AI processing in a separate request so the UI updates instantly
        $this->dispatch('trigger-api');
    }

    #[\Livewire\Attributes\On('trigger-api')]
    public function fetchAiResponse(GeminiService $geminiService, FinancialContextService $contextService, \App\Services\FinancialSystemPrompt $promptService)
    {
        // API format history
        $apiHistory = [];
        foreach ($this->chatHistory as $msg) {
            // Only send past messages that are actually from the conversation
            $role = $msg['role'] === 'user' ? 'user' : 'model';
            $apiHistory[] = [
                'role' => $role,
                'parts' => [['text' => $msg['content']]]
            ];
        }

        // The very last message in apiHistory is the user's latest query
        // The one we just added in sendMessage()

        // Add Financial Context as System Instruction
        $user = Auth::user();
        $financialSummary = $user ? $contextService->getFourMonthSummary($user) : 'No hay datos de usuario disponibles.';
        
        $systemInstruction = $promptService->getSystemInstruction($user) . "\n\nResumen Histórico del Usuario:\n" . $financialSummary;

        $response = $geminiService->generateContent($apiHistory, $systemInstruction);

        // Process potential JSON action
        $processedResponse = $this->processResponseAction($response);

        $this->chatHistory[] = [
            'role' => 'model',
            'content' => $processedResponse
        ];
    }

    protected function processResponseAction(string $response): string
    {
        // Extract JSON from markdown code block if present
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

        if ($jsonString) {
            $actionData = json_decode($jsonString, true);

            if (json_last_error() === JSON_ERROR_NONE && isset($actionData['action'])) {
                if ($actionData['action'] === 'create_budget') {
                    $budgetData = $actionData['budget'] ?? null;
                    if ($budgetData && Auth::check()) {
                        $this->createBudgetFromData($budgetData);
                        $response = str_replace($jsonString, '', $response);
                        $response = preg_replace('/```json\s*```/s', '', $response);
                        $response .= "\n\n✅ **¡Tu presupuesto ha sido creado exitosamente en el sistema!** Puedes revisarlo en la pestaña de Presupuestos.";
                    }
                } elseif ($actionData['action'] === 'add_to_budget') {
                    $budgetNumber = $actionData['budget_number'] ?? null;
                    $itemsData = $actionData['items'] ?? [];
                    if ($budgetNumber && !empty($itemsData) && Auth::check()) {
                        $success = $this->addToBudgetFromData($budgetNumber, $itemsData);
                        $response = str_replace($jsonString, '', $response);
                        $response = preg_replace('/```json\s*```/s', '', $response);
                        if ($success) {
                            $response .= "\n\n✅ **¡Los nuevos pagos se han anexado exitosamente a tu comprobante {$budgetNumber}!** Los totales han sido recalculados.";
                        } else {
                            $response .= "\n\n❌ Hubo un error al intentar anexar los pagos. Verifica que el comprobante {$budgetNumber} realmente exista.";
                        }
                    }
                } elseif ($actionData['action'] === 'create_calendar_events') {
                    $eventsData = $actionData['events'] ?? [];
                    if (!empty($eventsData) && Auth::check()) {
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
                        $response = str_replace($jsonString, '', $response);
                        $response = preg_replace('/```json\s*```/s', '', $response);
                        $response .= "\n\n🗓️ **¡He agendado los eventos en tu Calendario Financiero exitosamente!** Puedes ir a revisarlo en el menú principal.";
                    }
                }
            }
        }

        return $response;
    }

    protected function createBudgetFromData(array $budgetData)
    {
        $user = Auth::user();
        if (!$user) return;

        DB::transaction(function () use ($budgetData, $user) {
            $totalAllocated = collect($budgetData['items'])->sum('amount');
            $remaining = ($budgetData['net_income'] ?? 0) - $totalAllocated;

            $plan = BudgetPlan::create([
                'budget_number' => 'BUD-' . strtoupper(Str::random(6)),
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
        if (!$user) return false;

        $plan = BudgetPlan::where('created_by', $user->id)
            ->where('budget_number', $budgetNumber)
            ->first();

        if (!$plan) return false;

        DB::transaction(function () use ($plan, $itemsData) {
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

    public function render()
    {
        return view('livewire.chatbot-widget');
    }
}
