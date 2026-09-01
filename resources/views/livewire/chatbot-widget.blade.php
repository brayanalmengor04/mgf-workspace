<div>
    <style>
        .chatbot-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 99999;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            font-family: 'Instrument Sans', 'Outfit', ui-sans-serif, system-ui, sans-serif;
        }

        .chatbot-window {
            width: 520px;
            height: 580px;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 24px 48px rgba(15, 23, 42, 0.14), 0 0 0 1px rgba(148, 163, 184, 0.12);
            display: flex;
            flex-direction: row;
            overflow: hidden;
            margin-bottom: 16px;
            animation: chatbotSlideIn 0.28s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }

        .chatbot-window.sidebar-collapsed {
            width: 380px;
        }

        .dark .chatbot-window {
            background: #111827;
            box-shadow: 0 24px 48px rgba(0, 0, 0, 0.45), 0 0 0 1px rgba(71, 85, 105, 0.35);
        }

        /* ── Sidebar ── */
        .chatbot-sidebar {
            width: 188px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            background: #f4f6fb;
            border-right: 1px solid #e2e8f0;
            transition: width 0.22s ease, opacity 0.22s ease;
            overflow: hidden;
        }

        .dark .chatbot-sidebar {
            background: #0f172a;
            border-color: #1e293b;
        }

        .chatbot-window.sidebar-collapsed .chatbot-sidebar {
            width: 52px;
        }

        .chatbot-sidebar-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 10px 8px;
            gap: 6px;
            min-height: 44px;
        }

        .chatbot-sidebar-brand {
            font-size: 13px;
            font-weight: 700;
            color: #1e293b;
            letter-spacing: -0.02em;
            white-space: nowrap;
            overflow: hidden;
        }

        .dark .chatbot-sidebar-brand { color: #f1f5f9; }

        .chatbot-window.sidebar-collapsed .chatbot-sidebar-brand {
            opacity: 0;
            width: 0;
        }

        .chatbot-sidebar-toggle {
            background: transparent;
            border: none;
            color: #64748b;
            cursor: pointer;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: background 0.15s, color 0.15s;
        }

        .chatbot-sidebar-toggle:hover {
            background: rgba(70, 95, 255, 0.1);
            color: #465fff;
        }

        .chatbot-new-chat-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0 10px 10px;
            padding: 9px 12px;
            border: none;
            border-radius: 10px;
            background: #ffffff;
            color: #1e293b;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
            border: 1px solid #e2e8f0;
            transition: all 0.15s ease;
            white-space: nowrap;
            overflow: hidden;
        }

        .chatbot-new-chat-btn:hover {
            border-color: #465fff;
            color: #3641f5;
            background: #f5f7ff;
        }

        .dark .chatbot-new-chat-btn {
            background: #1e293b;
            border-color: #334155;
            color: #e2e8f0;
        }

        .chatbot-window.sidebar-collapsed .chatbot-new-chat-btn {
            margin: 0 8px 10px;
            padding: 9px;
            justify-content: center;
        }

        .chatbot-window.sidebar-collapsed .chatbot-new-chat-label {
            display: none;
        }

        .chatbot-recents-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #94a3b8;
            padding: 4px 14px 6px;
            white-space: nowrap;
        }

        .chatbot-window.sidebar-collapsed .chatbot-recents-label {
            display: none;
        }

        .chatbot-conv-list {
            flex: 1;
            overflow-y: auto;
            padding: 0 8px 10px;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .chatbot-conv-list::-webkit-scrollbar { width: 4px; }
        .chatbot-conv-list::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 999px;
        }

        .chatbot-conv-row {
            display: flex;
            align-items: center;
            gap: 2px;
            border-radius: 10px;
            min-width: 0;
        }

        .chatbot-conv-row:hover .chatbot-conv-delete {
            opacity: 1;
        }

        .chatbot-conv-row.is-active {
            background: rgba(70, 95, 255, 0.14);
        }

        .dark .chatbot-conv-row.is-active {
            background: rgba(70, 95, 255, 0.2);
        }

        .chatbot-conv-item {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 1px;
            flex: 1;
            min-width: 0;
            padding: 8px 6px 8px 10px;
            border: none;
            border-radius: 10px;
            background: transparent;
            color: #334155;
            font-size: 13px;
            font-weight: 500;
            text-align: left;
            cursor: pointer;
            transition: background 0.15s ease;
            overflow: hidden;
        }

        .chatbot-conv-item:hover {
            background: rgba(70, 95, 255, 0.08);
        }

        .chatbot-conv-row.is-active .chatbot-conv-item {
            color: #3641f5;
            background: transparent;
        }

        .dark .chatbot-conv-item { color: #cbd5e1; }
        .dark .chatbot-conv-row.is-active .chatbot-conv-item { color: #a5b4fc; }

        .chatbot-conv-delete {
            flex-shrink: 0;
            width: 28px;
            height: 28px;
            margin-right: 4px;
            border: none;
            border-radius: 8px;
            background: transparent;
            color: #94a3b8;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.15s, background 0.15s, color 0.15s;
        }

        .chatbot-conv-delete:hover {
            background: rgba(239, 68, 68, 0.12);
            color: #ef4444;
        }

        .chatbot-conv-row:focus-within .chatbot-conv-delete {
            opacity: 1;
        }

        .chatbot-conv-title {
            width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            line-height: 1.3;
        }

        .chatbot-conv-time {
            font-size: 10px;
            font-weight: 400;
            color: #94a3b8;
        }

        .chatbot-window.sidebar-collapsed .chatbot-conv-list {
            display: none;
        }

        .chatbot-window.sidebar-collapsed .chatbot-sidebar-head {
            justify-content: center;
            padding: 12px 8px 8px;
        }

        .chatbot-window.sidebar-collapsed .chatbot-sidebar-toggle svg {
            transform: rotate(180deg);
        }

        /* ── Main panel ── */
        .chatbot-main {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            background: #ffffff;
        }

        .dark .chatbot-main { background: #111827; }

        .chatbot-main-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 12px 14px;
            border-bottom: 1px solid #e2e8f0;
            background: #ffffff;
        }

        .dark .chatbot-main-header {
            background: #111827;
            border-color: #1e293b;
        }

        .chatbot-main-title-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
            flex: 1;
        }

        .chatbot-main-title {
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dark .chatbot-main-title { color: #f1f5f9; }

        .chatbot-online-dot {
            width: 7px;
            height: 7px;
            background: #34d399;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .chatbot-main-actions {
            display: flex;
            align-items: center;
            gap: 4px;
            flex-shrink: 0;
        }

        .chatbot-icon-btn {
            background: transparent;
            border: none;
            color: #64748b;
            cursor: pointer;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.15s, color 0.15s;
        }

        .chatbot-icon-btn:hover {
            background: #f1f5f9;
            color: #1e293b;
        }

        .dark .chatbot-icon-btn:hover {
            background: #1e293b;
            color: #f1f5f9;
        }

        .chatbot-messages {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
            scroll-behavior: smooth;
        }

        .dark .chatbot-messages {
            background: linear-gradient(180deg, #0b1220 0%, #0f172a 100%);
        }

        .chatbot-messages::-webkit-scrollbar { width: 5px; }
        .chatbot-messages::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 999px;
        }

        .chatbot-msg-row {
            display: flex;
            align-items: flex-end;
            gap: 8px;
            width: 100%;
        }

        .chatbot-msg-row.user { justify-content: flex-end; }
        .chatbot-msg-row.model { justify-content: flex-start; }

        .chatbot-avatar {
            width: 28px;
            height: 28px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 700;
            flex-shrink: 0;
            background: linear-gradient(135deg, #465fff, #3641f5);
            color: #fff;
        }

        .chatbot-bubble {
            max-width: 82%;
            padding: 11px 14px;
            font-size: 13.5px;
            line-height: 1.55;
        }

        .chatbot-bubble.user {
            background: #465fff;
            color: #ffffff;
            border-radius: 16px 16px 4px 16px;
            box-shadow: 0 4px 14px rgba(70, 95, 255, 0.28);
        }

        .chatbot-bubble.model {
            background: #ffffff;
            color: #334155;
            border: 1px solid #e2e8f0;
            border-radius: 16px 16px 16px 4px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        }

        .dark .chatbot-bubble.model {
            background: #1e293b;
            color: #e2e8f0;
            border-color: #334155;
        }

        .chatbot-bubble.model p:first-child { margin-top: 0; }
        .chatbot-bubble.model p:last-child { margin-bottom: 0; }

        .chatbot-suggestions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            padding: 0 4px 4px;
        }

        .chatbot-suggestion-chip {
            border: 1px solid #dbe3f0;
            background: #ffffff;
            color: #334155;
            border-radius: 999px;
            padding: 7px 12px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.18s ease;
        }

        .chatbot-suggestion-chip:hover {
            border-color: #465fff;
            color: #3641f5;
            background: #f5f7ff;
        }

        .dark .chatbot-suggestion-chip {
            background: #1e293b;
            border-color: #475569;
            color: #e2e8f0;
        }

        .chatbot-quick-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        .chatbot-quick-btn {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            color: #0f172a;
            border-radius: 999px;
            padding: 7px 12px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }

        .chatbot-quick-btn:hover {
            border-color: #465fff;
            color: #3641f5;
            background: #eef2ff;
        }

        .chatbot-bubble pre {
            background: #f1f5f9 !important;
            padding: 10px;
            border-radius: 8px;
            overflow-x: auto;
            margin: 8px 0;
            font-size: 12px;
            border: 1px solid #e2e8f0;
        }

        .chatbot-input-area {
            padding: 12px 14px 14px;
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
        }

        .dark .chatbot-input-area {
            background: #111827;
            border-color: #1e293b;
        }

        .chatbot-input-form {
            display: flex;
            gap: 8px;
            width: 100%;
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 6px 6px 6px 12px;
        }

        .dark .chatbot-input-form {
            background: #0f172a;
            border-color: #334155;
        }

        .chatbot-input {
            flex: 1;
            border: none;
            background: transparent;
            font-size: 14px;
            color: #0f172a;
            outline: none;
            min-width: 0;
        }

        .dark .chatbot-input { color: #f8fafc; }
        .chatbot-input::placeholder { color: #94a3b8; }

        .chatbot-submit {
            background: linear-gradient(135deg, #465fff, #3641f5);
            color: white;
            border: none;
            border-radius: 10px;
            width: 36px;
            height: 36px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(70, 95, 255, 0.35);
        }

        .chatbot-submit:disabled { opacity: 0.55; cursor: not-allowed; }

        .chatbot-fab {
            background: linear-gradient(135deg, #465fff, #3641f5);
            color: white;
            border: none;
            border-radius: 50%;
            width: 58px;
            height: 58px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 28px rgba(70, 95, 255, 0.45);
            cursor: pointer;
            transition: transform 0.25s ease;
        }

        .chatbot-fab:hover { transform: scale(1.06) translateY(-2px); }

        .chatbot-icon {
            width: 18px;
            height: 18px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
            flex-shrink: 0;
        }

        .chatbot-icon-lg {
            width: 22px;
            height: 22px;
        }

        .typing-indicator {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .typing-indicator span {
            width: 6px;
            height: 6px;
            background-color: #465fff;
            border-radius: 50%;
            animation: chatbotBounce 1.4s infinite ease-in-out both;
        }

        .typing-indicator span:nth-child(1) { animation-delay: -0.32s; }
        .typing-indicator span:nth-child(2) { animation-delay: -0.16s; }

        @keyframes chatbotBounce {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1); }
        }

        @keyframes chatbotSlideIn {
            from { transform: translateY(16px) scale(0.97); opacity: 0; }
            to { transform: translateY(0) scale(1); opacity: 1; }
        }

        @media (max-width: 720px) {
            .chatbot-container {
                bottom: 0;
                right: 0;
                width: 100%;
                height: 0;
            }

            .chatbot-container.is-open {
                height: 100%;
                top: 0;
                left: 0;
                align-items: stretch;
            }

            .chatbot-container.is-open .chatbot-window {
                width: 100vw;
                height: 100vh;
                border-radius: 0;
                margin-bottom: 0;
            }

            .chatbot-container.is-open .chatbot-window.sidebar-collapsed {
                width: 100vw;
            }

            .chatbot-sidebar {
                position: absolute;
                left: 0;
                top: 0;
                bottom: 0;
                z-index: 2;
                box-shadow: 4px 0 24px rgba(0,0,0,0.12);
            }

            .chatbot-window.sidebar-collapsed .chatbot-sidebar {
                width: 0;
                border: none;
            }

            .chatbot-container.is-open .chatbot-fab { display: none; }

            .chatbot-container:not(.is-open) .chatbot-fab {
                position: fixed;
                bottom: 24px;
                right: 24px;
            }
        }
    </style>

    @php
        $activeTitle = collect($conversationOptions)->firstWhere('active', true)['title'] ?? 'Nueva consulta';
    @endphp

    <div class="chatbot-container {{ $isOpen ? 'is-open' : '' }}"
         x-data="{
            sidebarOpen: localStorage.getItem('mgf-chat-sidebar') === 'open',
            toggleSidebar() {
                this.sidebarOpen = !this.sidebarOpen;
                localStorage.setItem('mgf-chat-sidebar', this.sidebarOpen ? 'open' : 'collapsed');
            },
            async shareWhatsAppDocument(links) {
                if (!links) return;
                if (navigator.share) {
                    try {
                        const response = await fetch(links.pdf_url);
                        if (response.ok) {
                            const blob = await response.blob();
                            const file = new File([blob], links.filename, { type: 'application/pdf' });
                            const shareData = { files: [file], text: links.text };
                            if (!navigator.canShare || navigator.canShare(shareData)) {
                                await navigator.share(shareData);
                                return;
                            }
                        }
                    } catch (error) {}
                }
                const isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
                window.location.href = links.app;
                if (!isMobile) {
                    setTimeout(() => { window.location.href = links.web; }, 600);
                }
            }
         }"
         x-on:share-whatsapp-document.window="shareWhatsAppDocument($event.detail?.links)">
        @if($isOpen)
            <div class="chatbot-window" :class="{ 'sidebar-collapsed': !sidebarOpen }">
                {{-- Sidebar estilo ChatGPT --}}
                <aside class="chatbot-sidebar">
                    <div class="chatbot-sidebar-head">
                        <span class="chatbot-sidebar-brand">Asistente MGF</span>
                        <button type="button" class="chatbot-sidebar-toggle" @click="toggleSidebar()" :title="sidebarOpen ? 'Contraer barra' : 'Expandir barra'">
                            <svg class="chatbot-icon" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
                        </button>
                    </div>

                    <button type="button" class="chatbot-new-chat-btn" wire:click="startNewConversation" title="Nuevo chat">
                        <svg class="chatbot-icon" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                        <span class="chatbot-new-chat-label">Nuevo chat</span>
                    </button>

                    <div class="chatbot-recents-label">Recientes</div>

                    <div class="chatbot-conv-list">
                        @forelse($conversationOptions as $option)
                            <div
                                class="chatbot-conv-row {{ ($option['active'] ?? false) ? 'is-active' : '' }}"
                                wire:key="conv-row-{{ $option['id'] }}"
                            >
                                <button
                                    type="button"
                                    class="chatbot-conv-item"
                                    wire:click="selectConversation({{ $option['id'] }})"
                                    title="{{ $option['title'] }}"
                                >
                                    <span class="chatbot-conv-title">{{ $option['title'] }}</span>
                                    @if(!empty($option['time']))
                                        <span class="chatbot-conv-time">{{ $option['time'] }}</span>
                                    @endif
                                </button>
                                <button
                                    type="button"
                                    class="chatbot-conv-delete"
                                    wire:click.stop="deleteConversation({{ $option['id'] }})"
                                    wire:confirm="¿Eliminar esta conversación?"
                                    title="Eliminar conversación"
                                >
                                    <svg class="chatbot-icon" style="width:14px;height:14px;" viewBox="0 0 24 24"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg>
                                </button>
                            </div>
                        @empty
                            <p style="font-size:12px;color:#94a3b8;padding:8px 10px;margin:0;">Sin conversaciones aún</p>
                        @endforelse
                    </div>
                </aside>

                {{-- Panel principal --}}
                <div class="chatbot-main">
                    <div class="chatbot-main-header">
                        <div class="chatbot-main-title-wrap">
                            <button
                                type="button"
                                class="chatbot-icon-btn"
                                @click="toggleSidebar()"
                                x-show="!sidebarOpen"
                                title="Mostrar conversaciones"
                            >
                                <svg class="chatbot-icon" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                            </button>
                            <span class="chatbot-online-dot"></span>
                            <span class="chatbot-main-title">{{ $activeTitle }}</span>
                        </div>
                        <div class="chatbot-main-actions">
                            <button
                                type="button"
                                class="chatbot-icon-btn"
                                wire:click="clearConversation"
                                wire:confirm="¿Limpiar todos los mensajes de este chat?"
                                title="Limpiar chat"
                            >
                                <svg class="chatbot-icon" viewBox="0 0 24 24"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg>
                            </button>
                            <button type="button" class="chatbot-icon-btn" wire:click="startNewConversation" title="Nuevo chat">
                                <svg class="chatbot-icon" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                            <button type="button" class="chatbot-icon-btn" wire:click="toggleChat" title="Cerrar">
                                <svg class="chatbot-icon" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="chatbot-messages" id="chatbot-messages"
                         x-data
                         x-init="
                            $el.scrollTop = $el.scrollHeight;
                            $watch('$wire.chatHistory', () => { setTimeout(() => { $el.scrollTop = $el.scrollHeight; }, 50); });
                            $watch('$wire.isLoading', () => { setTimeout(() => { $el.scrollTop = $el.scrollHeight; }, 50); });
                         ">
                        @foreach($chatHistory as $msg)
                            <div class="chatbot-msg-row {{ $msg['role'] === 'user' ? 'user' : 'model' }}">
                                @if($msg['role'] !== 'user')
                                    <div class="chatbot-avatar">IA</div>
                                @endif
                                <div class="chatbot-bubble {{ $msg['role'] === 'user' ? 'user' : 'model' }}">
                                    @if($msg['role'] === 'user')
                                        {{ $msg['content'] }}
                                    @else
                                        {!! Str::markdown($msg['content']) !!}
                                        @if(!empty($msg['actions']) && !empty($msg['budget_number']))
                                            <div class="chatbot-quick-actions">
                                                @foreach($msg['actions'] as $action)
                                                    <button
                                                        type="button"
                                                        class="chatbot-quick-btn"
                                                        wire:click="handleQuickAction('{{ $action['id'] }}', '{{ $msg['budget_number'] }}')"
                                                        wire:loading.attr="disabled"
                                                    >
                                                        {{ $action['label'] }}
                                                    </button>
                                                @endforeach
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @endforeach

                        @if(count($chatHistory) <= 1 && ! $isLoading)
                            <div class="chatbot-suggestions">
                                @foreach($this->suggestedPrompts as $suggestion)
                                    <button
                                        type="button"
                                        class="chatbot-suggestion-chip"
                                        wire:click="sendSuggestedPrompt(@js($suggestion['prompt']))"
                                        wire:loading.attr="disabled"
                                    >
                                        {{ $suggestion['label'] }}
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        @if($isLoading)
                            <div class="chatbot-msg-row model">
                                <div class="chatbot-avatar">IA</div>
                                <div class="chatbot-bubble model" style="display:flex;align-items:center;gap:8px;">
                                    <div class="typing-indicator">
                                        <span></span><span></span><span></span>
                                    </div>
                                    <span style="font-size:12px;color:#64748b;font-weight:500;">Pensando…</span>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="chatbot-input-area">
                        <form wire:submit.prevent="sendMessage" class="chatbot-input-form">
                            <input
                                type="text"
                                wire:model="message"
                                placeholder="Escribe tu mensaje…"
                                class="chatbot-input"
                                wire:loading.attr="disabled"
                                wire:target="sendMessage, fetchAiResponse, sendSuggestedPrompt"
                            >
                            <button
                                type="submit"
                                class="chatbot-submit"
                                wire:loading.attr="disabled"
                                wire:target="sendMessage, fetchAiResponse, sendSuggestedPrompt"
                                aria-label="Enviar"
                            >
                                <svg style="width:16px;height:16px;transform:rotate(90deg);" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <button wire:click="toggleChat" class="chatbot-fab" type="button" aria-label="{{ $isOpen ? 'Cerrar asistente' : 'Abrir asistente' }}">
            @if($isOpen)
                <svg class="chatbot-icon chatbot-icon-lg" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
            @else
                <svg class="chatbot-icon chatbot-icon-lg" viewBox="0 0 24 24"><path d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
            @endif
        </button>
    </div>
</div>
