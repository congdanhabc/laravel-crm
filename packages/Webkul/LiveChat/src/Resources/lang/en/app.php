<?php

return [
    /**
     * ==============================
     * Live Chat Module - General
     * ==============================
     */
    'layouts' => [
        'live_chat'          => 'Live Chat',
        'channel_manager'    => 'Configure Channels', // Hoặc 'Configuration'
        'title'              => 'Live Chat',
        'chat_bot'           => 'Configure Ai Chatbot'
        // 'description'     => 'Manage conversations and settings', // Ví dụ mô tả trang
    ],

    /**
     * ==============================
     * Channel Configuration Section
     * ==============================
     * (Giữ nguyên các key và giá trị từ lần cập nhật trước cho phần cấu hình kênh)
     */
    'channels' => [
        'title'             => 'Channel Configuration',
        'channel'           => 'Channel',
        'channels'          => 'Channels',
        'channel_resource'  => 'channel',

        'index' => [
            'title'                 => 'Channels',
            'add-channel-btn-title' => 'Add Channel',
        ],

        'create' => [
            'title'                 => 'Create Channel',
            'breadcrumb'            => 'Create',
            'save-btn-title'        => 'Save Channel',
            'general'               => 'General Information',
            'credentials'           => 'Credentials & Connection',
            'config'                => 'Configuration',
            'additional'            => 'Additional Information',
            'name'                  => 'Channel Name',
            'name_placeholder'      => 'Enter the name for this channel',
            'type'                  => 'Channel Type',
            'default'               => 'Choose Type',
            'type_facebook'         => 'Facebook Messenger',
            'type_channex'          => 'Channex.io',
            'facebook' => [
                'page_id'           => 'Facebook Page ID',
                'page_access_token' => 'Facebook Page Access Token',
                'app_secret'        => 'Facebook App Secret',
                'connect_btn'       => 'Connect',
            ],
            'channex' => []
        ],

        'edit' => [
            'title'         => 'Edit Channel',
            'breadcrumb'    => 'Edit',
        ],

        'create-success'    => 'Channel created successfully.',
        'update-success'    => 'Channel updated successfully.',
        'delete-success'    => 'Channel deleted successfully.',
        'delete-failed'     => 'Failed to delete channel.',

        'datagrid' => [
            'id'                => 'ID',
            'name'              => 'Channel Name',
            'type'              => 'Type',
            'status'            => 'Status',
            'created_at'        => 'Created At',
            'active'            => 'Active',
            'inactive'          => 'Inactive',
            'type_messenger'    => 'Facebook Messenger',
            'type_channex'      => 'Channex.io',
            'update_status'     => 'Update Status',
            'creator'           => 'Created By',
            'channel_resource'  => 'channel',
        ],

        'validation' => [
            'invalid_fb_credentials' => 'Facebook verification failed. Please check your Page ID and Page Access Token.',
        ],

        'webhook' => [
            'verification_failed' => 'Webhook verification failed.',
            'invalid_signature'   => 'Invalid webhook signature.',
        ],

        'channex' => [
            'api_error'      => 'Error communicating with Channex API.',
            'config_missing' => 'Channex API credentials not configured.',
        ],
    ],

    /**
     * ==============================
     * ACL (Access Control List)
     * ==============================
     */
    'acl' => [
        'live_chat'         => 'Live Chat',
        'channels'          => 'Configure Channels', // Quyền cho phần cấu hình
        'view'              => 'View Chat Interface', // **THÊM MỚI:** Quyền xem giao diện chat
        'reply'             => 'Reply to Conversations', // **THÊM MỚI:** Quyền trả lời
        'configure'         => 'Configure Channels', // **SỬA/THAY THẾ:** Quyền cấu hình kênh (thay cho 'channels' ở trên nếu muốn rõ hơn)
        'create'            => 'Create Channel', // Giữ hoặc gộp vào 'configure'
        'edit'              => 'Edit Channel',   // Giữ hoặc gộp vào 'configure'
        'delete'            => 'Delete Channel', // Giữ hoặc gộp vào 'configure'
    ],

    /**
     * ==============================
     * Chat Interface Specific Text
     * ==============================
     */
    'chat_interface' => [
        // Titles & Headers
        'title'             => 'Live Conversations', // Có thể trùng với layouts.live_chat
        'conversations'     => 'Conversations', // **THÊM MỚI HOẶC XÁC NHẬN**

        // Conversation List
        'no_conversations'  => 'No conversations yet.', // **THÊM MỚI HOẶC XÁC NHẬN**
        'search_placeholder'=> 'Search conversations...', // **THÊM MỚI** (Nếu có ô search)

        // Chat Panel
        'select_conversation' => 'Select a conversation to start chatting.', // **THÊM MỚI HOẶC XÁC NHẬN**
        'loading_messages'  => 'Loading messages...', // **THÊM MỚI HOẶC XÁC NHẬN**
        'type_your_reply'   => 'Type your reply...', // **THÊM MỚI HOẶC XÁC NHẬN**
        'send'              => 'Send', // **THÊM MỚI HOẶC XÁC NHẬN**
        'end_chat'          => 'End Chat', // **THÊM MỚI** (Cho nút kết thúc chat)
        'ended_chat_message'=> 'This chat has ended.', // **THÊM MỚI** (Thông báo khi chat kết thúc)

        // Agent Status (Ví dụ)
        'status_online'     => 'Status: Online',    // **THÊM MỚI**
        'status_offline'    => 'Status: Offline',   // **THÊM MỚI**
        'go_offline'        => 'Go Offline',        // **THÊM MỚI**
        'go_online'         => 'Go Online',         // **THÊM MỚI**

        // Messages & Notifications (Dùng trong JS hoặc backend)
        'message_sent'      => 'Message sent successfully.', // **THÊM MỚI**
        'error_loading'     => 'Error loading messages.',    // **THÊM MỚI**
        'error_sending'     => 'Error sending message.',     // **THÊM MỚI**
        'new_message_from'  => 'New message from :name', // **THÊM MỚI** (Cho notification)

        'loading_conversations' => 'Loading conversations...',
        'no_messages'           => 'No messages in this conversation yet.',
        'error_loading_conversations' => 'Error loading conversations.',
        'message_sent_successfully' => 'Message sent successfully.',
        'select_conversation_title' => 'Select a Conversation',
        'select_conversation_description' => 'Choose a conversation from the list on the left to start chatting.',
        'confirm_end_chat'      => 'Are you sure you want to end this chat?',
        'chat_ended_successfully' => 'Chat ended successfully.',
        'error_ending_chat'     => 'Error ending chat.',
// ...
    ],
];
