<?php

use Illuminate\Support\Facades\Route;
// Import các API Controller của bạn
use Webkul\LiveChat\Http\Controllers\Api\ConversationController as ApiConversationController;
use Webkul\LiveChat\Http\Controllers\Api\MessageController as ApiMessageController;
// (Có thể cần thêm các controller khác cho agent status, etc.)

/**
 * --------------------------------------------------------------------------
 * Live Chat API Routes
 * --------------------------------------------------------------------------
 *
 * Đây là nơi bạn định nghĩa các API endpoint cho module Live Chat.
 * Các route này thường stateless và trả về JSON.
 * Chúng sẽ được JavaScript ở phía client gọi để tương tác real-time.
 */

Route::group([
    'middleware' => ['api', 'auth:sanctum'], // Hoặc 'auth:api' tùy cấu hình API guard của bạn
                                            // 'auth:sanctum' hoặc 'auth:api' để bảo vệ API
                                            // Nếu API này chỉ dùng nội bộ cho admin đã login, 'auth:user' (Krayin admin guard) cũng có thể được xem xét
    'prefix'     => config('app.admin_url') . '/api/live-chat', // Tiền tố URL, ví dụ: /admin/api/live-chat
    'as'         => 'admin.live_chat.api.' // Tiền tố tên route, ví dụ: admin.live_chat.api.
], function () {

    /**
     * Conversation API Routes
     * Tiền tố URL: /admin/api/live-chat/conversations
     * Tiền tố tên route: admin.live_chat.api.conversations.
     */
    Route::group([
        'prefix' => 'conversations',
        'as'     => 'conversations.'
    ], function () {
        // GET /admin/api/live-chat/conversations
        // Lấy danh sách các cuộc hội thoại (có thể kèm filter, pagination)
        Route::get('/', [ApiConversationController::class, 'index'])->name('index');

        // GET /admin/api/live-chat/conversations/{conversation}
        // Lấy chi tiết một cuộc hội thoại (bao gồm visitor info, và danh sách tin nhắn)
        Route::get('/{conversation}', [ApiConversationController::class, 'show'])->name('show');

        // POST /admin/api/live-chat/conversations/{conversation}/close
        // Đóng một cuộc hội thoại
        Route::post('/{conversation}/close', [ApiConversationController::class, 'close'])->name('close');

        // (Tùy chọn) POST /admin/api/live-chat/conversations/{conversation}/assign
        // Gán cuộc hội thoại cho một agent
        // Route::post('/{conversation}/assign', [ApiConversationController::class, 'assignAgent'])->name('assign');

        // (Tùy chọn) POST /admin/api/live-chat/conversations/{conversation}/mark-training
        // Đánh dấu cuộc hội thoại để huấn luyện bot
        // Route::post('/{conversation}/mark-training', [ApiConversationController::class, 'markForTraining'])->name('mark_training');


        /**
         * Message API Routes (Nested under Conversation)
         * Tiền tố URL: /admin/api/live-chat/conversations/{conversation}/messages
         * Tiền tố tên route: admin.live_chat.api.conversations.messages.
         */
        Route::group([
            'prefix' => '/{conversation}/messages',
            'as'     => 'messages.'
        ], function () {
            // GET /admin/api/live-chat/conversations/{conversation}/messages
            // Lấy danh sách tin nhắn của một cuộc hội thoại (có thể kèm pagination)
            // Route này trùng với ConversationController@show nếu bạn gộp logic, nếu tách thì dùng MessageController
            Route::get('/', [ApiMessageController::class, 'index'])->name('index');

            // POST /admin/api/live-chat/conversations/{conversation}/messages
            // Gửi một tin nhắn mới từ agent
            Route::post('/', [ApiMessageController::class, 'store'])->name('store');
        });
    });


    /**
     * (Tùy chọn) Agent Status API Routes
     * Tiền tố URL: /admin/api/live-chat/agent
     * Tiền tố tên route: admin.live_chat.api.agent.
     */
    /*
    Route::group([
        'prefix' => 'agent',
        'as'     => 'agent.'
    ], function () {
        // GET /admin/api/live-chat/agent/status
        // Lấy trạng thái hiện tại của agent
        // Route::get('/status', [ApiAgentController::class, 'getStatus'])->name('status.get');

        // POST /admin/api/live-chat/agent/status
        // Cập nhật trạng thái của agent (online, offline, away)
        // Route::post('/status', [ApiAgentController::class, 'updateStatus'])->name('status.update');
    });
    */

});
