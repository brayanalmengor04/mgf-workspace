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
            font-family: ui-sans-serif, system-ui, sans-serif;
        }
        
        .chatbot-window {
            width: 350px;
            height: 500px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            border: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            margin-bottom: 16px;
            transition: all 0.3s ease;
        }

        .dark .chatbot-window {
            background-color: #1f2937;
            border-color: #374151;
        }

        .chatbot-header {
            background-color: #2563eb;
            color: #ffffff;
            padding: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chatbot-header-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: bold;
        }

        .chatbot-close-btn {
            background: none;
            border: none;
            color: #ffffff;
            cursor: pointer;
            opacity: 0.8;
            padding: 4px;
        }
        .chatbot-close-btn:hover { opacity: 1; }

        .chatbot-messages {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            background-color: #f9fafb;
        }
        .dark .chatbot-messages { background-color: #111827; }

        .chatbot-msg-row {
            display: flex;
            width: 100%;
        }
        .chatbot-msg-row.user { justify-content: flex-end; }
        .chatbot-msg-row.model { justify-content: flex-start; }

        .chatbot-bubble {
            max-width: 85%;
            padding: 12px;
            font-size: 14px;
            line-height: 1.4;
        }
        .chatbot-bubble.user {
            background-color: #dbeafe;
            color: #1e40af;
            border-radius: 12px 12px 0 12px;
        }
        .dark .chatbot-bubble.user {
            background-color: #1e3a8a;
            color: #dbeafe;
        }
        
        .chatbot-bubble.model {
            background-color: #ffffff;
            color: #374151;
            border: 1px solid #e5e7eb;
            border-radius: 12px 12px 12px 0;
        }
        .dark .chatbot-bubble.model {
            background-color: #1f2937;
            color: #e5e7eb;
            border-color: #374151;
        }

        .chatbot-input-area {
            padding: 12px;
            background-color: #ffffff;
            border-top: 1px solid #e5e7eb;
            display: flex;
            gap: 8px;
        }
        .dark .chatbot-input-area {
            background-color: #1f2937;
            border-color: #374151;
        }

        .chatbot-input {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            background-color: #f3f4f6;
            color: #111827;
            outline: none;
        }
        .dark .chatbot-input {
            background-color: #111827;
            border-color: #374151;
            color: #f9fafb;
        }
        .chatbot-input:focus { border-color: #2563eb; }

        .chatbot-submit {
            background-color: #2563eb;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .chatbot-submit:hover { background-color: #1d4ed8; }
        .chatbot-submit:disabled { opacity: 0.5; cursor: not-allowed; }

        .chatbot-fab {
            background-color: #2563eb;
            color: white;
            border: none;
            border-radius: 50%;
            width: 56px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
            cursor: pointer;
            transition: transform 0.2s;
        }
        .chatbot-fab:hover {
            background-color: #1d4ed8;
            transform: scale(1.05);
        }
        .chatbot-icon { width: 24px; height: 24px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>

    <div class="chatbot-container">
        @if($isOpen)
            <div class="chatbot-window">
                <div class="chatbot-header">
                    <div class="chatbot-header-title">
                        <svg class="chatbot-icon" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                        Asistente IA
                    </div>
                    <button wire:click="toggleChat" class="chatbot-close-btn">
                        <svg class="chatbot-icon" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="chatbot-messages" id="chatbot-messages">
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
                    
                    <div wire:loading wire:target="fetchAiResponse" style="width: 100%;">
                        <div class="chatbot-msg-row model">
                            <div class="chatbot-bubble model" style="opacity: 0.7; display: flex; align-items: center; gap: 8px;">
                                <svg class="animate-spin" style="width: 16px; height: 16px; animation: spin 1s linear infinite;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Analizando...
                            </div>
                        </div>
                    </div>
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
