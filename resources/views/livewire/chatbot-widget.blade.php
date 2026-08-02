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
            font-family: 'Outfit', 'Inter', ui-sans-serif, system-ui, sans-serif;
        }
        
        .chatbot-window {
            width: 380px;
            height: 600px;
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12), 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(229, 231, 235, 0.8);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            margin-bottom: 16px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: slideIn 0.25s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }

        .dark .chatbot-window {
            background-color: rgba(31, 41, 55, 0.95);
            border-color: rgba(55, 65, 81, 0.8);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .chatbot-header {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #ffffff;
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
        }

        .dark .chatbot-header {
            background: linear-gradient(135deg, #1e40af, #1e3a8a);
        }

        .chatbot-header-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            font-size: 16px;
            letter-spacing: -0.01em;
        }

        .chatbot-header-status {
            width: 8px;
            height: 8px;
            background-color: #10b981;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 8px #10b981;
            margin-left: 4px;
        }

        .chatbot-close-btn {
            background: rgba(255, 255, 255, 0.15);
            border: none;
            color: #ffffff;
            cursor: pointer;
            border-radius: 50%;
            padding: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        .chatbot-close-btn:hover { 
            background: rgba(255, 255, 255, 0.25);
            transform: rotate(90deg);
        }

        .chatbot-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            background-color: #f8fafc;
            scroll-behavior: smooth;
        }
        .dark .chatbot-messages { background-color: #0f172a; }

        /* Custom Scrollbar */
        .chatbot-messages::-webkit-scrollbar {
            width: 6px;
        }
        .chatbot-messages::-webkit-scrollbar-track {
            background: transparent;
        }
        .chatbot-messages::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        .dark .chatbot-messages::-webkit-scrollbar-thumb {
            background: #475569;
        }

        .chatbot-msg-row {
            display: flex;
            width: 100%;
        }
        .chatbot-msg-row.user { justify-content: flex-end; }
        .chatbot-msg-row.model { justify-content: flex-start; }

        .chatbot-bubble {
            max-width: 85%;
            padding: 12px 16px;
            font-size: 14px;
            line-height: 1.5;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
            transition: all 0.2s ease;
        }
        .chatbot-bubble.user {
            background: linear-gradient(135deg, #dbeafe, #eff6ff);
            color: #1e40af;
            border-radius: 16px 16px 2px 16px;
            border: 1px solid rgba(191, 219, 254, 0.5);
        }
        .dark .chatbot-bubble.user {
            background: linear-gradient(135deg, #1e3a8a, #172554);
            color: #dbeafe;
            border-color: rgba(30, 58, 138, 0.5);
        }
        
        .chatbot-bubble.model {
            background-color: #ffffff;
            color: #334155;
            border: 1px solid #e2e8f0;
            border-radius: 16px 16px 16px 2px;
        }
        .dark .chatbot-bubble.model {
            background-color: #1e293b;
            color: #f1f5f9;
            border-color: #334155;
        }

        /* Message code formatting */
        .chatbot-bubble pre {
            background-color: #f1f5f9 !important;
            padding: 12px;
            border-radius: 8px;
            overflow-x: auto;
            margin: 8px 0;
            font-size: 13px;
            border: 1px solid #e2e8f0;
        }
        .dark .chatbot-bubble pre {
            background-color: #0f172a !important;
            border-color: #334155;
        }
        .chatbot-bubble code {
            font-family: 'Fira Code', 'Courier New', monospace;
            font-size: 13px;
        }

        .chatbot-input-area {
            padding: 16px 20px;
            background-color: #ffffff;
            border-top: 1px solid #e2e8f0;
            display: flex;
            gap: 10px;
        }
        .dark .chatbot-input-area {
            background-color: #1e293b;
            border-color: #334155;
        }

        .chatbot-input {
            flex: 1;
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font-size: 14px;
            background-color: #f8fafc;
            color: #0f172a;
            outline: none;
            transition: all 0.2s ease;
        }
        .dark .chatbot-input {
            background-color: #0f172a;
            border-color: #334155;
            color: #f8fafc;
        }
        .chatbot-input:focus { 
            border-color: #2563eb; 
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
            background-color: #ffffff;
        }
        .dark .chatbot-input:focus {
            background-color: #0f172a;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
        }

        .chatbot-submit {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            border: none;
            border-radius: 10px;
            width: 40px;
            height: 40px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.2);
        }
        .chatbot-submit:hover { 
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3);
        }
        .chatbot-submit:active {
            transform: translateY(0);
        }
        .chatbot-submit:disabled { 
            opacity: 0.5; 
            cursor: not-allowed; 
            transform: none;
            box-shadow: none;
        }

        .chatbot-fab {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            border: none;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .chatbot-fab:hover {
            transform: scale(1.08) translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.5);
        }
        .chatbot-fab:active {
            transform: scale(0.98) translateY(0);
        }
        .chatbot-icon { 
            width: 26px; 
            height: 26px; 
            fill: none; 
            stroke: currentColor; 
            stroke-width: 2; 
            stroke-linecap: round; 
            stroke-linejoin: round; 
        }

        /* Typing Indicator */
        .typing-indicator {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .typing-indicator span {
            width: 6px;
            height: 6px;
            background-color: #2563eb;
            border-radius: 50%;
            display: inline-block;
            animation: bounce 1.4s infinite ease-in-out both;
        }
        .typing-indicator span:nth-child(1) { animation-delay: -0.32s; }
        .typing-indicator span:nth-child(2) { animation-delay: -0.16s; }
        .dark .typing-indicator span { background-color: #3b82f6; }

        @keyframes bounce {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1.0); }
        }

        @keyframes slideIn {
            from {
                transform: translateY(20px) scale(0.95);
                opacity: 0;
            }
            to {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
        }

        @keyframes slideInMobile {
            from {
                transform: translateY(100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Mobile Responsive */
        @media (max-width: 640px) {
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
                bottom: 0;
                right: 0;
                align-items: stretch;
            }
            .chatbot-container.is-open .chatbot-window {
                width: 100vw;
                height: 100vh;
                border-radius: 0;
                margin-bottom: 0;
                border: none;
                flex: 1;
                animation: slideInMobile 0.3s cubic-bezier(0.4, 0, 0.2, 1) forwards;
            }
            .chatbot-container.is-open .chatbot-fab {
                display: none;
            }
            .chatbot-container:not(.is-open) .chatbot-fab {
                position: fixed;
                bottom: 24px;
                right: 24px;
            }
        }
    </style>

    <div class="chatbot-container {{ $isOpen ? 'is-open' : '' }}">
        @if($isOpen)
            <div class="chatbot-window">
                <div class="chatbot-header">
                    <div class="chatbot-header-title">
                        <svg class="chatbot-icon" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                        Asistente IA
                        <span class="chatbot-header-status"></span>
                    </div>
                    <button wire:click="toggleChat" class="chatbot-close-btn">
                        <svg class="chatbot-icon" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
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
                            <div class="chatbot-bubble {{ $msg['role'] === 'user' ? 'user' : 'model' }}">
                                @if($msg['role'] === 'user')
                                    {{ $msg['content'] }}
                                @else
                                    {!! Str::markdown($msg['content']) !!}
                                @endif
                            </div>
                        </div>
                    @endforeach
                    
                    @if($isLoading)
                        <div class="chatbot-msg-row model">
                            <div class="chatbot-bubble model" style="opacity: 0.8; display: flex; align-items: center; gap: 8px;">
                                <div class="typing-indicator">
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                </div>
                                <span style="font-size: 13px; color: #64748b; font-weight: 500;">Pensando...</span>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="chatbot-input-area">
                    <form wire:submit.prevent="sendMessage" style="display:flex; gap:8px; width:100%;">
                        <input type="text" wire:model="message" placeholder="Escribe un mensaje..." class="chatbot-input" wire:loading.attr="disabled" wire:target="sendMessage, fetchAiResponse">
                        <button type="submit" class="chatbot-submit" wire:loading.attr="disabled" wire:target="sendMessage, fetchAiResponse">
                            <svg style="width:16px; height:16px; transform:rotate(90deg);" viewBox="0 0 20 20" fill="currentColor">
                              <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        @endif

        <button wire:click="toggleChat" class="chatbot-fab">
            @if($isOpen)
                <svg class="chatbot-icon" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" /></svg>
            @else
                <svg class="chatbot-icon" viewBox="0 0 24 24"><path d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" /></svg>
            @endif
        </button>
    </div>
</div>
