document.addEventListener('DOMContentLoaded', () => {
    // Kiểm tra config có tồn tại không
    if (typeof window.liveChatConfig === 'undefined') {
        console.error("Live Chat configuration (window.liveChatConfig) is missing!");
        // Có thể hiển thị thông báo lỗi trên UI ở đây
        const conversationListElFallback = document.getElementById("conversationList");
        if (conversationListElFallback) {
            conversationListElFallback.innerHTML = `<li class="p-6 text-center text-sm text-red-500">Configuration error. Live Chat cannot load.</li>`;
        }
        return;
    }

    const config = window.liveChatConfig;
    const currentAgentId = config.currentAgentId || null; // Lấy ID agent hiện tại từ config (cần truyền từ Blade)

    // DOM Elements
    const conversationListEl = document.getElementById("conversationList");
    const messagesEl = document.getElementById("messageList");
    const chatHeaderNameEl = document.getElementById("chatHeaderName");
    const chatHeaderAvatarEl = document.getElementById("chatHeaderAvatar");
    const chatHeaderStatusEl = document.getElementById("chatHeaderStatus");
    const messageInputEl = document.getElementById("messageInput");
    const sendButtonEl = document.getElementById("sendButton");
    const conversationSearchInputEl = document.getElementById("conversationSearchInput");
    const noConversationSelectedEl = document.getElementById("noConversationSelected");
    const chatAreaEl = document.getElementById("chatArea");
    const loadingMessagesEl = document.getElementById('loading-messages'); // Đảm bảo có element này trong Blade

    // Input ẩn để lưu ID conversation đang active (nếu cần cho form, không bắt buộc nếu chỉ dùng JS)
    let currentConversationIdInputEl = document.getElementById('current-conversation-id');
    if (!currentConversationIdInputEl) {
        currentConversationIdInputEl = document.createElement('input');
        currentConversationIdInputEl.type = 'hidden';
        currentConversationIdInputEl.id = 'current-conversation-id';
        document.body.appendChild(currentConversationIdInputEl); // Hoặc một form cụ thể
    }


    let activeConversationId = null;
    let conversationsCache = [];
    let echoInstance = null;
    let isLoadingInitialConversations = false;
    let queuedPusherEvents = [];

    // --- Hàm tiện ích ---
    function showLoading(listElement, messageKey = 'loading_conversations') {
        if (listElement) listElement.innerHTML = `<li class="p-6 text-center text-sm text-gray-400 dark:text-gray-500">${config.translations[messageKey]}</li>`;
    }

    function showNoData(listElement, messageKey = 'no_conversations') {
        if (listElement) listElement.innerHTML = `<li class="p-6 text-center text-sm text-gray-400 dark:text-gray-500">${config.translations[messageKey]}</li>`;
    }

    function showError(listElement, messageKey = 'error_loading_conversations') {
        if (listElement) listElement.innerHTML = `<li class="p-6 text-center text-sm text-red-500">${config.translations[messageKey]}</li>`;
    }

    async function apiCall(endpoint, method = 'GET', body = null) {
        const headers = {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        };
        if (method !== 'GET') {
            headers['Content-Type'] = 'application/json';
            // Lấy CSRF token từ meta tag nếu có, hoặc từ config nếu đã truyền
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || config.csrfToken;
            if (csrfToken) headers['X-CSRF-TOKEN'] = csrfToken;
        }
        const options = { method, headers };
        if (body) {
            options.body = JSON.stringify(body);
        }
        try {
            const response = await fetch(endpoint, options);
            const responseData = await response.json(); // Luôn thử parse JSON
            if (!response.ok) {
                throw responseData; // Ném lỗi với payload JSON từ server
            }
            return responseData;
        } catch (error) {
            console.error(`API call to ${endpoint} failed:`, error.message || error);
            throw error;
        }
    }

    function formatTimestamp(isoString, type = 'short') {
        if (!isoString) return '';
        try {
            const date = new Date(isoString);
            if (type === 'short') {
                return date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit', hour12: true });
            }
            return date.toLocaleString();
        } catch (e) {
            console.warn("Error formatting timestamp:", isoString, e);
            return '';
        }
    }

    function scrollToBottom(element) {
        if (element) setTimeout(() => { element.scrollTop = element.scrollHeight; }, 50);
    }

    function autoResizeTextarea(element) {
        if (!element) return;
        element.style.height = 'auto';
        const maxHeight = 120;
        element.style.height = Math.min(element.scrollHeight, maxHeight) + 'px';
    }

    function truncateText(text, maxLength) {
        if (!text) return "";
        return text.length > maxLength ? text.substring(0, maxLength) + "..." : text;
    }

    function escapeHtml(unsafe) {
        if (typeof unsafe !== 'string') return '';
        return unsafe
             .replace(/&/g, "&")
             .replace(/</g, "<")
             .replace(/>/g, ">")
             .replace(/"/g, """)
             .replace(/'/g, "'");
    }

    // --- Render danh sách hội thoại ---
    function renderConversationList(conversationsToRender) {
        console.log('--- START renderConversationList ---');
        console.log('Data to render:', conversationsToRender);

        if (!conversationListEl) {
            console.error("Element #conversationList not found!");
            return;
        }
        conversationListEl.innerHTML = ""; // Xóa "Loading..." hoặc danh sách cũ

        if (!conversationsToRender || conversationsToRender.length === 0) {
            console.log('No conversations to render, showing no data message.');
            showNoData(conversationListEl);
            return;
        }

        console.log(`Looping through ${conversationsToRender.length} conversations...`);
        conversationsToRender.forEach((conv) => {
            const li = document.createElement("li");
            li.className = `conversation-item flex cursor-pointer items-start gap-3 p-3 hover:bg-gray-100 dark:hover:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700`; // Thêm border-b
            if (conv.id === activeConversationId) {
                li.classList.add("active", "bg-blue-50", "dark:bg-blue-900/30", "border-l-2", "border-blue-500", "dark:border-blue-400");
                li.style.paddingLeft = 'calc(0.75rem - 2px)';
            }
            li.dataset.conversationId = conv.id;
            li.onclick = () => switchConversation(conv.id);

            const lastMessageData = conv.last_message;
            const visitorName = conv.visitor_name || `Visitor ${conv.visitor_id}`;
            const avatarUrl = `https://ui-avatars.com/api/?name=${encodeURIComponent(visitorName.charAt(0) || 'V')}&background=random&color=fff&size=40`;
            const lastMessageContent = lastMessageData ? truncateText(escapeHtml(lastMessageData.content), 30) : (conv.last_message_preview ? truncateText(escapeHtml(conv.last_message_preview), 30) : config.translations.no_messages);
            const lastMessageTimeFormatted = lastMessageData ? formatTimestamp(lastMessageData.created_at) : (conv.last_reply_at ? formatTimestamp(conv.last_reply_at) : '');
            const unreadCount = conv.unread_count || 0;

            li.innerHTML = `
              <img src="${avatarUrl}" alt="${visitorName}" class="h-10 w-10 flex-shrink-0 rounded-full" />
              <div class="min-w-0 flex-1">
                <div class="flex items-center justify-between">
                  <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">${escapeHtml(visitorName)}</p>
                  <p class="flex-shrink-0 text-xs text-gray-500 dark:text-gray-400">${lastMessageTimeFormatted}</p>
                </div>
                <div class="mt-0.5 flex items-center justify-between">
                  <p class="truncate text-xs text-gray-600 dark:text-gray-300">${lastMessageContent}</p>
                  ${unreadCount > 0 ? `<span class="unread-badge ml-2 flex-shrink-0 rounded-full bg-red-500 px-1.5 py-0.5 text-[10px] font-semibold text-white">${unreadCount}</span>` : ''}
                </div>
              </div>
            `;
            conversationListEl.appendChild(li);
        });
        console.log('--- END renderConversationList ---');
    }

    // --- Load danh sách hội thoại ban đầu ---
    async function loadInitialConversations() {
        if (isLoadingInitialConversations) return;
        isLoadingInitialConversations = true;
        showLoading(conversationListEl);

        try {
            const responseData = await apiCall(config.apiEndpoints.getConversations);
            console.log('API Response for conversations:', responseData);

            if (Array.isArray(responseData)) {
                conversationsCache = responseData;
            } else if (responseData && responseData.data && Array.isArray(responseData.data)) {
                conversationsCache = responseData.data; // Hỗ trợ cả response có phân trang
            } else {
                console.error("Unexpected API response structure for conversations:", responseData);
                conversationsCache = [];
            }

            renderConversationList(conversationsCache);
            isLoadingInitialConversations = false;
            processQueuedPusherEvents();

            if (conversationsCache.length > 0 && !activeConversationId) {
                const firstItem = conversationListEl.querySelector('.conversation-item');
                if (firstItem) {
                    // firstItem.click(); // Tạm thời bỏ tự động click để debug dễ hơn
                     console.log("First conversation item found, would click if uncommented.");
                }
            } else if (conversationsCache.length === 0) {
                noConversationSelectedEl.classList.remove('hidden');
                noConversationSelectedEl.classList.add('flex');
                chatAreaEl.classList.add('hidden');
                chatAreaEl.classList.remove('flex');
            }
        } catch (error) {
            showError(conversationListEl);
            isLoadingInitialConversations = false;
        }
    }

    // --- Xử lý các event từ Pusher trong hàng đợi ---
    function processQueuedPusherEvents() {
        console.log("Processing queued Pusher events:", queuedPusherEvents.length);
        while (queuedPusherEvents.length > 0) {
            const event = queuedPusherEvents.shift();
            handlePusherEvent(event.eventName, event.data);
        }
    }

    // --- Hàm chung để xử lý event từ Pusher ---
    function handlePusherEvent(eventName, eventData) {
        console.log(`Handling Pusher Event: ${eventName}`, JSON.parse(JSON.stringify(eventData))); // Log deep copy
        if (eventName === '.message.new' || eventName === '.message.agent.replied') {
            const isMyOwnMessage = eventName === '.message.agent.replied' && eventData.message && eventData.message.user_id === currentAgentId;

            if (eventData.conversation_id === activeConversationId && !isMyOwnMessage) {
                renderMessage(eventData.message);
                scrollToBottom(messagesEl);
            }
            const isNewUnread = eventName === '.message.new';
            updateConversationPreview(
                eventData.conversation_id,
                eventData.conversation_preview.last_message_content,
                eventData.conversation_preview.last_message_time,
                isNewUnread
            );
        }
        // Thêm xử lý cho các event khác (ví dụ: conversation created/assigned)
    }

    // --- Khởi tạo Laravel Echo listeners ---
    function initializeEchoListeners(conversationIdToListen) {
        if (echoInstance) {
            echoInstance.leave(`livechat.conversation.${activeConversationId}`); // Rời kênh cũ
            console.log(`Left Pusher channel: livechat.conversation.${activeConversationId}`);
        }

        if (!window.Echo) {
            console.error("Laravel Echo not initialized.");
            return;
        }

        // Cập nhật activeConversationId ở đây khi thực sự join kênh mới
        activeConversationId = conversationIdToListen;
        currentConversationIdInputEl.value = conversationIdToListen;

        console.log(`Joining Pusher channel: livechat.conversation.${conversationIdToListen}`);
        echoInstance = window.Echo.private(`livechat.conversation.${conversationIdToListen}`);

        echoInstance
            .listen('.message.new', (eventData) => {
                if (isLoadingInitialConversations) {
                    queuedPusherEvents.push({ eventName: '.message.new', data: eventData });
                    console.log("Queued .message.new while loading.");
                    return;
                }
                handlePusherEvent('.message.new', eventData);
            })
            .listen('.message.agent.replied', (eventData) => {
                if (isLoadingInitialConversations) {
                    queuedPusherEvents.push({ eventName: '.message.agent.replied', data: eventData });
                    console.log("Queued .message.agent.replied while loading.");
                    return;
                }
                handlePusherEvent('.message.agent.replied', eventData);
            })
            .error((error) => {
                console.error(`Pusher channel error for conversation.${conversationIdToListen}:`, error);
                if (error.status === 403) {
                    console.error("Pusher: Authorization failed. Check routes/channels.php.");
                }
            });
    }

    // --- Chuyển đổi hội thoại ---
    async function switchConversation(conversationId) {
        if (chatAreaEl.classList.contains('flex') && activeConversationId === conversationId) return; // Đang chọn rồi, không làm gì

        console.log(`Switching to conversation: ${conversationId}`);

        // Cập nhật UI cho item được chọn
        const allItems = conversationListEl.querySelectorAll('.conversation-item');
        allItems.forEach(item => {
            item.classList.remove('active', 'bg-blue-50', 'dark:bg-blue-900/30', 'border-l-2', 'border-blue-500', 'dark:border-blue-400');
            item.style.paddingLeft = '';
        });
        const selectedItem = conversationListEl.querySelector(`.conversation-item[data-conversation-id="${conversationId}"]`);
        if (selectedItem) {
            selectedItem.classList.add('active', 'bg-blue-50', 'dark:bg-blue-900/30', 'border-l-2', 'border-blue-500', 'dark:border-blue-400');
            selectedItem.style.paddingLeft = 'calc(0.75rem - 2px)';
            // Xóa badge unread khi chọn
            const unreadBadge = selectedItem.querySelector('.unread-badge');
            if (unreadBadge) unreadBadge.remove();
        }

        initializeEchoListeners(conversationId); // Join kênh Pusher cho conv mới

        noConversationSelectedEl.classList.add('hidden');
        noConversationSelectedEl.classList.remove('flex');
        chatAreaEl.classList.remove('hidden');
        chatAreaEl.classList.add('flex');

        if(chatHeaderNameEl) chatHeaderNameEl.textContent = "Loading...";
        if(chatHeaderAvatarEl) chatHeaderAvatarEl.src = `https://ui-avatars.com/api/?name=?&size=32&background=ccc&color=fff`;
        if(loadingMessagesEl) loadingMessagesEl.classList.remove('hidden');
        if(messagesEl) messagesEl.innerHTML = "";
        if(messageInputEl) {
            messageInputEl.value = "";
            autoResizeTextarea(messageInputEl);
            messageInputEl.focus();
        }

        try {
            const endpoint = config.apiEndpoints.getMessages.replace(':id', conversationId);
            const data = await apiCall(endpoint);

            const convDetails = data.conversation; // API nên trả về conversation object
            const visitorName = convDetails.visitor_name || `Visitor ${convDetails.visitor_id}`;
            if(chatHeaderNameEl) chatHeaderNameEl.textContent = escapeHtml(visitorName);
            if(chatHeaderAvatarEl) chatHeaderAvatarEl.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(visitorName.charAt(0) || 'V')}&background=random&color=fff&size=32`;
            if(chatHeaderStatusEl) chatHeaderStatusEl.textContent = convDetails.visitor_status || "Online";

            if(loadingMessagesEl) loadingMessagesEl.classList.add('hidden');
            if(messagesEl) messagesEl.innerHTML = ""; // Xóa lại nếu có gì đó trước loading
            if (data.messages && data.messages.length > 0) {
                data.messages.forEach(renderMessage);
            } else {
                showNoData(messagesEl, 'no_messages');
            }
            scrollToBottom(messagesEl);
        } catch (error) {
            if(loadingMessagesEl) loadingMessagesEl.classList.add('hidden');
            showError(messagesEl, 'error_loading_messages');
        }
    }

    // --- Render một tin nhắn ---
    function renderMessage(msg) {
        if(!messagesEl || !msg) return;

        const isAgent = msg.sender_type === 'agent' || !!msg.user_id;
        const agentNameInitial = msg.agent?.name?.charAt(0) || 'A'; // Lấy từ msg.agent nếu có
        const visitorNameInitial = chatHeaderNameEl?.textContent?.charAt(0) || 'V';
        const avatarSrc = isAgent ? `https://ui-avatars.com/api/?name=${encodeURIComponent(agentNameInitial)}&background=0D8ABC&color=fff&size=24`
                                  : `https://ui-avatars.com/api/?name=${encodeURIComponent(visitorNameInitial)}&background=random&color=fff&size=24`;
        const altText = isAgent ? (msg.agent?.name || 'Agent') : (chatHeaderNameEl?.textContent || 'Visitor');

        const messageWrapper = document.createElement('div');
        messageWrapper.className = `flex items-end gap-2 clear-both max-w-[75%] ${isAgent ? 'flex-row-reverse self-end' : 'self-start'}`;

        const avatarImgHtml = `<img src="${avatarSrc}" alt="${escapeHtml(altText)}" class="h-6 w-6 rounded-full flex-shrink-0 ${isAgent ? 'order-2' : 'order-1'}" />`;

        const messageBubbleHtml = `
            <div class="${isAgent ? 'order-1' : 'order-2'}">
                <p class="px-3 py-2 rounded-xl text-sm shadow break-words
                    ${isAgent ? 'rounded-br-none bg-blue-500 text-white' : 'rounded-tl-none bg-gray-200 text-gray-900 dark:bg-gray-600 dark:text-gray-50'}">
                    ${escapeHtml(msg.content)}
                </p>
                <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400 ${isAgent ? 'text-right' : 'text-left'}">
                    ${formatTimestamp(msg.created_at)}
                </span>
            </div>
        `;
        messageWrapper.innerHTML = avatarImgHtml + messageBubbleHtml;
        messagesEl.appendChild(messageWrapper);
    }

    // --- Gửi tin nhắn ---
    async function sendMessage() {
        const text = messageInputEl.value.trim();
        const conversationId = currentConversationIdInputEl.value;
        if (!text || !conversationId) return;

        const originalSendButtonContent = sendButtonEl.innerHTML;
        sendButtonEl.innerHTML = `<svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>`;
        sendButtonEl.disabled = true;
        messageInputEl.disabled = true;

        try {
            const endpoint = config.apiEndpoints.sendMessage.replace(':id', conversationId);
            const responseData = await apiCall(endpoint, 'POST', { message: text });

            if (responseData.data) { // Giả sử API trả về { data: messageObject }
                renderMessage(responseData.data);
                scrollToBottom(messagesEl);
                messageInputEl.value = "";
                autoResizeTextarea(messageInputEl);
                updateConversationPreview(conversationId, responseData.data.content, responseData.data.created_at, false);
            } else {
                console.error("Unexpected response after sending message:", responseData);
                alert(config.translations.error_sending_message + " (Invalid server response)");
            }
        } catch (error) {
            // Lỗi có thể là object { message: "...", errors: {...} } từ server
            const errorMessage = error.errors ? Object.values(error.errors).flat().join(' ') : (error.message || JSON.stringify(error));
            alert(config.translations.error_sending_message + `: ${errorMessage}`);
        } finally {
            sendButtonEl.innerHTML = `<svg class="h-5 w-5 rotate-90 transform" fill="currentColor" viewBox="0 0 20 20"><path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"></path></svg>`;
            sendButtonEl.disabled = false;
            messageInputEl.disabled = false;
            messageInputEl.focus();
        }
    }
    if (sendButtonEl) sendButtonEl.addEventListener("click", sendMessage);


    // --- Cập nhật preview trong danh sách hội thoại ---
    function updateConversationPreview(conversationId, lastMessageContent, lastMessageTime, isNewUnread = false) {
        const convItem = conversationListEl.querySelector(`.conversation-item[data-conversation-id="${conversationId}"]`);
        if (convItem) {
            const previewEl = convItem.querySelector('.text-xs.text-gray-600');
            const timeEl = convItem.querySelector('.flex-shrink-0.text-xs.text-gray-500');
            let unreadBadgeEl = convItem.querySelector('.unread-badge');

            if (previewEl) previewEl.textContent = truncateText(escapeHtml(lastMessageContent), 30);
            if (timeEl) timeEl.textContent = formatTimestamp(lastMessageTime);

            if (isNewUnread && String(conversationId) !== String(activeConversationId)) {
                let currentUnread = 0;
                if (unreadBadgeEl) {
                    currentUnread = parseInt(unreadBadgeEl.textContent) || 0;
                    unreadBadgeEl.textContent = currentUnread + 1;
                } else {
                    unreadBadgeEl = document.createElement('span');
                    unreadBadgeEl.className = 'unread-badge ml-2 flex-shrink-0 rounded-full bg-red-500 px-1.5 py-0.5 text-[10px] font-semibold text-white';
                    unreadBadgeEl.textContent = '1';
                    const lastMessageDiv = convItem.querySelector('.mt-0\\.5.flex');
                    if (lastMessageDiv) lastMessageDiv.appendChild(unreadBadgeEl);
                }
            } else if (String(conversationId) === String(activeConversationId) && unreadBadgeEl) {
                unreadBadgeEl.remove();
            }

            if (conversationListEl.firstChild !== convItem) {
                conversationListEl.prepend(convItem);
            }
        } else {
            // Nếu là hội thoại mới hoàn toàn, tải lại toàn bộ danh sách hoặc thêm item mới một cách thông minh
            console.warn(`Conversation item for ID ${conversationId} not found in list. Consider reloading or smarter append.`);
            loadInitialConversations(); // Tạm thời tải lại toàn bộ
        }
    }

    // --- Tìm kiếm hội thoại ---
    if (conversationSearchInputEl) {
        conversationSearchInputEl.addEventListener("input", (e) => {
            const searchTerm = e.target.value.toLowerCase();
            const filteredConversations = conversationsCache.filter(conv =>
                (conv.visitor_name || `Visitor ${conv.visitor_id}`).toLowerCase().includes(searchTerm) ||
                (conv.last_message && conv.last_message.content.toLowerCase().includes(searchTerm)) ||
                (conv.last_message_preview && conv.last_message_preview.toLowerCase().includes(searchTerm))
            );
            renderConversationList(filteredConversations);
        });
    }

    // --- Khởi tạo ---
    if (conversationListEl && messagesEl && chatAreaEl && noConversationSelectedEl) {
        loadInitialConversations();
    } else {
        console.error("One or more critical UI elements for Live Chat are missing from the DOM.");
    }
});
