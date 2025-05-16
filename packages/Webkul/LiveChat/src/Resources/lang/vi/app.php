<?php

return [
    /**
     * ==============================
     * Live Chat Module - Chung
     * ==============================
     */
    'layouts' => [
        'live_chat'          => 'Live Chat',
        'channel_manager'    => 'Cấu hình Kênh',
        'title'              => 'Live Chat',
        // 'description'     => 'Quản lý cuộc trò chuyện và cài đặt', // Ví dụ
    ],

    /**
     * ==============================
     * Phần Cấu hình Kênh
     * ==============================
     * (Giữ nguyên các key và giá trị từ lần cập nhật trước)
     */
    'channels' => [
        'title'             => 'Cấu hình Kênh',
        'channel'           => 'Kênh',
        'channels'          => 'Các kênh',
        'channel_resource'  => 'kênh',

        'index' => [
            'title'                 => 'Các kênh',
            'add-channel-btn-title' => 'Thêm Kênh',
        ],

        'create' => [
            'title'                 => 'Tạo Kênh Mới',
            'breadcrumb'            => 'Tạo mới',
            'save-btn-title'        => 'Lưu Kênh',
            'general'               => 'Thông tin chung',
            'credentials'           => 'Thông tin & Kết nối',
            'config'                => 'Cấu hình',
            'additional'            => 'Thông tin bổ sung',
            'name'                  => 'Tên Kênh',
            'name_placeholder'      => 'Nhập tên cho kênh này',
            'type'                  => 'Loại Kênh',
            'default'               => 'Chọn loại',
            'type_facebook'         => 'Facebook Messenger',
            'type_channex'          => 'Channex.io',
            'facebook' => [
                'page_id'           => 'ID Trang Facebook',
                'page_access_token' => 'Mã Truy cập Trang Facebook',
                'app_secret'        => 'Mã Bí mật Ứng dụng Facebook',
                'connect_btn'       => 'Kết nối',
            ],
            'channex' => []
        ],

        'edit' => [
            'title'         => 'Chỉnh sửa Kênh',
            'breadcrumb'    => 'Chỉnh sửa',
        ],

        'create-success'    => 'Đã tạo kênh thành công.',
        'update-success'    => 'Đã cập nhật kênh thành công.',
        'delete-success'    => 'Đã xóa kênh thành công.',
        'delete-failed'     => 'Xóa kênh thất bại.',

        'datagrid' => [
            'id'                => 'ID',
            'name'              => 'Tên Kênh',
            'type'              => 'Loại',
            'status'            => 'Trạng thái',
            'created_at'        => 'Ngày tạo',
            'active'            => 'Hoạt động',
            'inactive'          => 'Không hoạt động',
            'type_messenger'    => 'Facebook Messenger',
            'type_channex'      => 'Channex.io',
            'update_status'     => 'Cập nhật trạng thái',
            'creator'           => 'Người tạo',
            'channel_resource'  => 'kênh',
        ],

        'validation' => [
            'invalid_fb_credentials' => 'Xác thực Facebook thất bại. Vui lòng kiểm tra ID Trang và Mã Truy cập Trang của bạn.',
        ],

        'webhook' => [
            'verification_failed' => 'Xác thực Webhook thất bại.',
            'invalid_signature'   => 'Chữ ký webhook không hợp lệ.',
        ],

        'channex' => [
            'api_error'      => 'Lỗi giao tiếp với Channex API.',
            'config_missing' => 'Chưa cấu hình thông tin Channex API.',
        ],
    ],

    /**
     * ==============================
     * ACL (Quyền truy cập)
     * ==============================
     */
    'acl' => [
        'live_chat'         => 'Live Chat',
        'channels'          => 'Cấu hình Kênh', // Giữ hoặc đổi thành 'configure' bên dưới
        'view'              => 'Xem Giao diện Chat',    // **THÊM MỚI**
        'reply'             => 'Trả lời Hội thoại',     // **THÊM MỚI**
        'configure'         => 'Cấu hình Kênh',         // **SỬA/THAY THẾ**
        'create'            => 'Tạo Kênh',              // Giữ hoặc gộp
        'edit'              => 'Sửa Kênh',              // Giữ hoặc gộp
        'delete'            => 'Xóa Kênh',              // Giữ hoặc gộp
    ],

    /**
     * ==============================
     * Văn bản Giao diện Chat
     * ==============================
     */
    'chat_interface' => [
        // Tiêu đề & Header
        'title'             => 'Cuộc trò chuyện trực tiếp', // Có thể trùng layouts.live_chat
        'conversations'     => 'Cuộc hội thoại', // **THÊM MỚI HOẶC XÁC NHẬN**

        // Danh sách hội thoại
        'no_conversations'  => 'Chưa có cuộc hội thoại nào.', // **THÊM MỚI HOẶC XÁC NHẬN**
        'search_placeholder'=> 'Tìm kiếm cuộc hội thoại...', // **THÊM MỚI**

        // Khung Chat
        'select_conversation' => 'Chọn một cuộc hội thoại để bắt đầu trò chuyện.', // **THÊM MỚI HOẶC XÁC NHẬN**
        'loading_messages'  => 'Đang tải tin nhắn...', // **THÊM MỚI HOẶC XÁC NHẬN**
        'type_your_reply'   => 'Nhập nội dung trả lời...', // **THÊM MỚI HOẶC XÁC NHẬN**
        'send'              => 'Gửi', // **THÊM MỚI HOẶC XÁC NHẬN**
        'end_chat'          => 'Kết thúc Chat', // **THÊM MỚI**
        'ended_chat_message'=> 'Cuộc hội thoại này đã kết thúc.', // **THÊM MỚI**

        // Trạng thái Agent (Ví dụ)
        'status_online'     => 'Trạng thái: Trực tuyến',    // **THÊM MỚI**
        'status_offline'    => 'Trạng thái: Ngoại tuyến',   // **THÊM MỚI**
        'go_offline'        => 'Chuyển Ngoại tuyến',        // **THÊM MỚI**
        'go_online'         => 'Chuyển Trực tuyến',         // **THÊM MỚI**

        // Tin nhắn & Thông báo (Dùng trong JS hoặc backend)
        'message_sent'      => 'Đã gửi tin nhắn thành công.', // **THÊM MỚI**
        'error_loading'     => 'Lỗi tải tin nhắn.',           // **THÊM MỚI**
        'error_sending'     => 'Lỗi gửi tin nhắn.',          // **THÊM MỚI**
        'new_message_from'  => 'Tin nhắn mới từ :name',       // **THÊM MỚI**

        'loading_conversations' => 'Đang tải cuộc hội thoại...',
        'no_messages'           => 'Không có tin nhắn trong cuộc hội thoại này.',
        'error_loading_conversations' => 'Lỗi tải cuộc hội thoại.',
        'message_sent_successfully' => 'Tin nhắn đã gửi thành công.',
        'select_conversation_title' => 'Chọn cuộc hội thoại',
        'select_conversation_description' => 'Chọn cuộc hội thoại từ danh sách bên trái để bắt đầu trò chuyện.',
        'confirm_end_chat'      => 'Bạn có chắc chắn muốn kết thúc cuộc hội thoại này?',
        'chat_ended_successfully' => 'Cuộc hội thoại đã kết thúc thành công.',
        'error_ending_chat'     => 'Lỗi kết thúc cuộc hội thoại.',
    ],
];
