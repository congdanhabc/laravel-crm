{{-- Sử dụng layout admin chính của Krayin --}}
<x-admin::layouts>
    <x-slot:title>
        @lang('live_chat::app.layouts.live_chat')
    </x-slot>

    {{-- Hook trước phần header --}}
    {!! view_render_event('admin.live_chat.index.header.before') !!}
    <div class="mb-3 flex items-center justify-between gap-4 max-sm:flex-wrap">
        <div class="grid gap-1.5">
            <p class="text-xl font-semibold text-gray-900 dark:text-gray-50">
                @lang('live_chat::app.layouts.live_chat')
            </p>
        </div>
        <div class="flex items-center gap-x-2.5">
            <a href="{{ route('admin.live_chat.channel_manager.index') }}"
               class="inline-flex items-center gap-x-1.5 rounded-md border border-transparent bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 dark:bg-blue-500 dark:hover:bg-blue-400">
                <i class="icon icon-setting text-base text-white"></i> {{-- Giả sử icon-setting có sẵn --}}
                <span>@lang('live_chat::app.layouts.channel_manager')</span>
            </a>
        </div>
    </div>
    {!! view_render_event('admin.live_chat.index.header.after') !!}

    {{-- Phần nội dung chính của Live Chat --}}
    {!! view_render_event('admin.live_chat.index.content.before') !!}

    {{-- Container chính, chiều cao cần điều chỉnh cho phù hợp với layout Krayin --}}
    <div class="flex h-[calc(100vh-165px)] overflow-hidden rounded-md border border-gray-200 bg-white text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">

        <!-- Sidebar: Danh sách hội thoại -->
        <aside id="sidebar" class="flex w-80 flex-shrink-0 flex-col border-r border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
            <!-- Header Sidebar -->
            <div class="flex h-14 flex-shrink-0 items-center justify-between border-b border-gray-200 px-4 dark:border-gray-700">
                <h1 class="text-base font-semibold">@lang('live_chat::app.chat_interface.conversations')</h1>
                <div class="flex gap-1">
                    <button class="rounded-full p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700" title="New Chat">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                    </button>
                    {{-- Nút settings có thể dẫn đến trang cấu hình chung của Live Chat nếu có --}}
                    <button class="rounded-full p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700" title="Settings">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                             <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01-.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
            <!-- Search -->
            <div class="flex-shrink-0 p-3">
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-4 w-4 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <input type="search" id="conversationSearchInput" placeholder="@lang('live_chat::app.chat_interface.search_placeholder')"
                           class="block w-full rounded-full border-gray-300 bg-gray-100 py-2 pl-10 pr-4 text-sm placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:placeholder-gray-400" />
                </div>
            </div>
            <!-- Conversation list -->
            <ul id="conversationList" class="m-0 flex-1 list-none divide-y divide-gray-100 overflow-y-auto p-0 custom-scrollbar dark:divide-gray-700">
                {{-- JS sẽ render danh sách vào đây --}}
                <li class="p-6 text-center text-sm text-gray-400">@lang('live_chat::app.chat_interface.loading_conversations')</li>
            </ul>
        </aside>

        <!-- Main chat area -->
        <main class="flex flex-1 flex-col bg-white dark:bg-gray-800">
            {{-- Placeholder khi chưa chọn chat --}}
            <div id="noConversationSelected" class="flex h-full flex-col items-center justify-center text-center text-gray-400 dark:text-gray-500">
                <svg class="mb-4 h-20 w-20 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 5.523-4.477 10-10 10S1 17.523 1 12 5.477 2 11 2s10 4.477 10 10z"></path></svg>
                <p class="text-lg font-medium">@lang('live_chat::app.chat_interface.select_conversation_title')</p>
                <p class="text-sm">@lang('live_chat::app.chat_interface.select_conversation_description')</p>
            </div>

            {{-- Nội dung chat (ban đầu ẩn) --}}
            <div id="chatArea" class="hidden h-full flex-col"> {{-- JS sẽ đổi thành 'flex' --}}
                <!-- Header của khung chat -->
                <header id="chatHeader" class="flex h-14 flex-shrink-0 items-center justify-between border-b border-gray-200 bg-white px-4 dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-center gap-3">
                        <img id="chatHeaderAvatar" src="https://ui-avatars.com/api/?name=?&size=32&background=random&color=fff" alt="" class="h-8 w-8 rounded-full">
                        <div>
                            <h2 id="chatHeaderName" class="text-sm font-semibold"></h2>
                            <p id="chatHeaderStatus" class="text-xs text-green-500">Online</p>
                        </div>
                    </div>
                    <div class="flex gap-1">
                        <button class="rounded-full p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700" title="Call">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.308 1.154a11.034 11.034 0 005.37 5.37l1.154-2.308a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                        </button>
                        <button class="rounded-full p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700" title="Video Call">
                             <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                        </button>
                        <button class="rounded-full p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700" title="Conversation Info">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </button>
                    </div>
                </header>

                <!-- Messages -->
                <section id="messageList" class="flex flex-1 flex-col gap-3 overflow-y-auto p-4 custom-scrollbar"></section>

                <!-- Composer -->
                <footer class="flex-shrink-0 border-t border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-center gap-2">
                        {{-- Các nút tiện ích khác --}}
                        <button id="utilityAction1" class="rounded-full p-2 text-blue-500 hover:bg-blue-500/10 dark:hover:bg-blue-500/20" title="More Actions">
                             <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7" /></svg>
                        </button>

                        <input id="messageInput" type="text" placeholder="@lang('live_chat::app.chat_interface.type_your_reply')"
                               class="flex-1 rounded-full border-gray-300 bg-gray-100 px-4 py-2 text-sm placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:placeholder-gray-400" />

                        <button id="emojiButton" class="rounded-full p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700" title="Emoji">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.25-7.25a.75.75 0 00-1.5 0v.5c0 .414.336.75.75.75h.008a.75.75 0 00.75-.75v-.5zm-5 0a.75.75 0 00-1.5 0v.5c0 .414.336.75.75.75H6a.75.75 0 00.75-.75v-.5zM10 14a3.5 3.5 0 003.5-3.5H6.5A3.5 3.5 0 0010 14z" clip-rule="evenodd" /></svg>
                        </button>
                        <button id="sendButton" class="rounded-full bg-blue-500 p-2 text-white hover:bg-blue-600 disabled:opacity-50" title="Send">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                        </button>
                    </div>
                </footer>
            </div>
        </main>
    </div>
    {!! view_render_event('admin.live_chat.index.content.after') !!}

    @pushOnce('styles')
        <style>
            .custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
            .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
            .custom-scrollbar::-webkit-scrollbar-thumb { @apply bg-gray-300 dark:bg-gray-600 rounded; }
            .custom-scrollbar::-webkit-scrollbar-thumb:hover { @apply bg-gray-400 dark:bg-gray-500; }
            #messageInput:placeholder-shown + #sendButton { @apply opacity-50 cursor-not-allowed; }
            .conversation-item.active { @apply bg-blue-100 dark:bg-blue-500/20; }
        </style>
    @endPushOnce

    @pushOnce('scripts')
        {{-- Link đến file JS của bạn --}}
        <script type="module" src="{{ asset('vendor/webkul/livechat/assets/js/messenger-chat.js') }}"></script>

        {{-- Truyền các URL API và text dịch vào JavaScript --}}
        <script>
            window.liveChatConfig = {
                baseUrl: "{{ url('/') }}",
                adminUrl: "{{ config('app.admin_url') }}", // Lấy từ config
                apiEndpoints: {
                    getConversations: "{{ route('admin.live_chat.api.conversations.index') }}", // ĐỊNH NGHĨA ROUTE NÀY
                    getMessages: "{{ route('admin.live_chat.api.conversations.messages.index', ['conversation' => ':id']) }}", // :id sẽ được thay thế
                    sendMessage: "{{ route('admin.live_chat.api.conversations.messages.store', ['conversation' => ':id']) }}",
                    // Thêm các endpoint khác nếu cần (close, search...)
                },
                pusher: { // Nếu dùng Pusher/WebSocket
                    key: "{{ config('broadcasting.connections.pusher.key') }}",
                    cluster: "{{ config('broadcasting.connections.pusher.options.cluster') }}",
                    // ...
                },
                translations: {
                    loading_conversations: "@lang('live_chat::app.chat_interface.loading_conversations')",
                    no_conversations: "@lang('live_chat::app.chat_interface.no_conversations')",
                    loading_messages: "@lang('live_chat::app.chat_interface.loading_messages')",
                    no_messages: "@lang('live_chat::app.chat_interface.no_messages')",
                    error_loading_conversations: "@lang('live_chat::app.chat_interface.error_loading_conversations')",
                    error_loading_messages: "@lang('live_chat::app.chat_interface.error_loading_messages')",
                    error_sending_message: "@lang('live_chat::app.chat_interface.error_sending_message')",
                    message_sent_successfully: "@lang('live_chat::app.chat_interface.message_sent_successfully')",
                    // Thêm các text khác
                },
                defaultAgentAvatar: "https://ui-avatars.com/api/?name=A&background=0D8ABC&color=fff", // Avatar mặc định cho agent
                // csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}' // Nếu không có meta tag sẵn
            };
        </script>
    @endPushOnce
</x-admin::layouts>
