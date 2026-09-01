<?php

namespace App\Services;

use App\Models\AssistantConversation;
use App\Models\AssistantMessage;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class AssistantConversationService
{
    private const SESSION_KEY = 'assistant_active_conversation_id';

    public function maxConversations(): int
    {
        return (int) config('services.gemini.chat.max_conversations', 5);
    }

    public function maxApiMessages(): int
    {
        return (int) config('services.gemini.chat.max_api_messages', 8);
    }

    public function summarizeAfterMessages(): int
    {
        return (int) config('services.gemini.chat.summarize_after_messages', 12);
    }

    /**
     * @return Collection<int, AssistantConversation>
     */
    public function listForUser(User $user): Collection
    {
        return AssistantConversation::query()
            ->forUser($user)
            ->orderByDesc('last_message_at')
            ->limit($this->maxConversations())
            ->get();
    }

    public function activeConversationId(): ?int
    {
        $id = Session::get(self::SESSION_KEY);

        return is_numeric($id) ? (int) $id : null;
    }

    public function setActiveConversationId(?int $conversationId): void
    {
        if ($conversationId === null) {
            Session::forget(self::SESSION_KEY);

            return;
        }

        Session::put(self::SESSION_KEY, $conversationId);
    }

    public function resolveActiveConversation(User $user): AssistantConversation
    {
        $id = $this->activeConversationId();

        if ($id !== null) {
            $existing = AssistantConversation::query()
                ->forUser($user)
                ->whereKey($id)
                ->first();

            if ($existing !== null) {
                return $existing;
            }
        }

        return $this->createConversation($user);
    }

    public function createConversation(User $user): AssistantConversation
    {
        $count = AssistantConversation::query()->forUser($user)->count();

        if ($count >= $this->maxConversations()) {
            $oldest = AssistantConversation::query()
                ->forUser($user)
                ->orderBy('last_message_at')
                ->orderBy('id')
                ->first();

            $oldest?->delete();
        }

        $conversation = AssistantConversation::query()->create([
            'user_id' => $user->id,
            'last_message_at' => now(),
        ]);

        $this->setActiveConversationId($conversation->id);

        return $conversation;
    }

    public function switchConversation(User $user, int $conversationId): AssistantConversation
    {
        $conversation = AssistantConversation::query()
            ->forUser($user)
            ->whereKey($conversationId)
            ->firstOrFail();

        $this->setActiveConversationId($conversation->id);

        return $conversation;
    }

    public function clearConversation(AssistantConversation $conversation): void
    {
        $conversation->messages()->delete();
        $conversation->update([
            'title' => null,
            'summary' => null,
            'last_message_at' => now(),
        ]);

        $this->seedWelcomeIfEmpty($conversation);
    }

    public function deleteConversation(User $user, int $conversationId): ?AssistantConversation
    {
        $conversation = AssistantConversation::query()
            ->forUser($user)
            ->whereKey($conversationId)
            ->first();

        if ($conversation === null) {
            return null;
        }

        $wasActive = $this->activeConversationId() === $conversation->id;
        $conversation->delete();

        if (! $wasActive) {
            return AssistantConversation::query()
                ->forUser($user)
                ->whereKey($this->activeConversationId())
                ->first();
        }

        $next = AssistantConversation::query()
            ->forUser($user)
            ->orderByDesc('last_message_at')
            ->first();

        if ($next !== null) {
            $this->setActiveConversationId($next->id);

            return $next;
        }

        $this->setActiveConversationId(null);

        return $this->createConversation($user);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function messagesForUi(AssistantConversation $conversation): array
    {
        return $conversation->messages()
            ->orderBy('created_at')
            ->get()
            ->map(fn (AssistantMessage $message): array => [
                'role' => $message->role,
                'content' => $message->content,
                'actions' => $message->metadata['actions'] ?? null,
                'budget_number' => $message->metadata['budget_number'] ?? null,
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function appendMessage(
        AssistantConversation $conversation,
        string $role,
        string $content,
        ?array $metadata = null,
    ): AssistantMessage {
        if ($role === 'user') {
            $this->maybeUpdateTitle($conversation, $content);
        }

        $message = $conversation->messages()->create([
            'role' => $role,
            'content' => $content,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);

        $conversation->update(['last_message_at' => now()]);

        return $message;
    }

    /**
     * @return array<int, array{role: string, parts: array<int, array{text: string}>}>
     */
    public function buildApiHistory(AssistantConversation $conversation): array
    {
        $messages = $conversation->messages()
            ->orderByDesc('created_at')
            ->limit($this->maxApiMessages())
            ->get()
            ->reverse()
            ->values();

        return $messages->map(fn (AssistantMessage $message): array => [
            'role' => $message->role === 'user' ? 'user' : 'model',
            'parts' => [['text' => $message->content]],
        ])->all();
    }

    public function maybeSummarize(
        AssistantConversation $conversation,
        GeminiService $geminiService,
    ): void {
        $messageCount = $conversation->messages()->count();

        if ($messageCount <= $this->summarizeAfterMessages()) {
            return;
        }

        $toSummarize = $conversation->messages()
            ->orderBy('created_at')
            ->limit(max(0, $messageCount - $this->maxApiMessages()))
            ->get();

        if ($toSummarize->isEmpty()) {
            return;
        }

        $transcript = $toSummarize->map(fn (AssistantMessage $m): string => strtoupper($m->role).': '.$m->content)
            ->implode("\n");

        $summary = $geminiService->generateContent(
            [
                [
                    'role' => 'user',
                    'parts' => [[
                        'text' => "Resume en español (máximo 8 líneas) la conversación previa entre usuario y asistente financiero. Conserva decisiones, montos y acciones pendientes.\n\n{$transcript}",
                    ]],
                ],
            ],
            'Eres un asistente que comprime historiales de chat para ahorrar tokens.',
            'summarize',
        );

        if (trim($summary) === '' || str_starts_with($summary, 'Error:')) {
            return;
        }

        $conversation->update(['summary' => trim($summary)]);
    }

    public function welcomeMessage(): array
    {
        return [
            'role' => 'model',
            'content' => '¡Hola! Soy tu asistente financiero de MGF. Puedo ayudarte con presupuestos, ahorros, cotizaciones y enviar comprobantes. Elige una sugerencia abajo o escríbeme lo que necesitas.',
        ];
    }

    public function seedWelcomeIfEmpty(AssistantConversation $conversation): void
    {
        if ($conversation->messages()->exists()) {
            return;
        }

        $welcome = $this->welcomeMessage();
        $this->appendMessage($conversation, $welcome['role'], $welcome['content']);
    }

    public function displayTitle(AssistantConversation $conversation): string
    {
        if (filled($conversation->title)) {
            return $conversation->title;
        }

        return 'Nueva consulta';
    }

    public function maybeUpdateTitle(AssistantConversation $conversation, ?string $latestUserMessage = null): void
    {
        $firstUserMessage = $conversation->messages()
            ->where('role', 'user')
            ->orderBy('created_at')
            ->value('content');

        $source = $firstUserMessage ?? $latestUserMessage;

        if (! filled($source)) {
            return;
        }

        $title = $this->deriveTitle($source);

        if ($conversation->title !== $title) {
            $conversation->update(['title' => $title]);
        }
    }

    public function deriveTitle(string $content): string
    {
        $text = trim($content);

        $commands = [
            '/mis_ahorros' => 'Consulta de ahorros',
            '/mi_ultimo_presupuesto' => 'Análisis de presupuesto',
            '/proximo_ahorro' => 'Plan de ahorro',
            '/recomendaciones' => 'Recomendaciones financieras',
            '/mis_cotizaciones' => 'Mis cotizaciones',
            '/help' => 'Ayuda del asistente',
        ];

        if (isset($commands[$text])) {
            return $commands[$text];
        }

        $lower = mb_strtolower($text);

        if (preg_match('/\b(cotizaci[oó]n|cotizar|presupuesto de venta)\b/u', $lower)) {
            return $this->trimTitle('Cotizaciones');
        }

        if (preg_match('/\b(presupuesto|comprobante|gasto|ingreso)\b/u', $lower)) {
            return $this->trimTitle($this->extractTopicPhrase($text, 'Presupuesto'));
        }

        if (preg_match('/\b(ahorro|ahorrar|meta de ahorro|cuenta de ahorro)\b/u', $lower)) {
            return $this->trimTitle($this->extractTopicPhrase($text, 'Ahorros'));
        }

        if (preg_match('/\b(enviar|whatsapp|correo|compartir)\b/u', $lower)) {
            return 'Enviar presupuesto';
        }

        if (preg_match('/\b(calendario|agendar|evento|pago)\b/u', $lower)) {
            return $this->trimTitle('Calendario financiero');
        }

        return $this->trimTitle($text);
    }

    private function extractTopicPhrase(string $text, string $prefix): string
    {
        $clean = preg_replace('/\s+/u', ' ', trim($text)) ?? $text;
        $clean = preg_replace('/^[^\p{L}\p{N}]+/u', '', $clean) ?? $clean;

        if (mb_strlen($clean) <= 42) {
            return $prefix.': '.$clean;
        }

        return $prefix;
    }

    private function trimTitle(string $text): string
    {
        $title = trim($text);
        $title = preg_replace('/\s+/u', ' ', $title) ?? $title;

        return Str::limit($title, 52);
    }
}
