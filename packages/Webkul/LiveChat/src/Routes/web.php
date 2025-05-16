<?php

use Illuminate\Support\Facades\Route;
use Webkul\LiveChat\Http\Controllers\ChannelManagerController;
use Webkul\LiveChat\Http\Controllers\WebhookController;
use Webkul\LiveChat\Http\Controllers\LiveChatController;

Route::group(['middleware' => ['web', 'user']], function () {

    // Nhóm chính cho module Live Chat
    Route::prefix(config('app.admin_url') . '/live-chat')->name('admin.live_chat.')->group(function () {

        Route::get('/', [LiveChatController::class, 'index'])->name('index');


        // --- Nhóm con cho phần cấu hình kênh (Channel Manager) ---
        Route::prefix('channel_manager')->name('channel_manager.')->group(function () {
            Route::get('/', [ChannelManagerController::class, 'index'])->name('index');

            Route::get('/create', [ChannelManagerController::class, 'create'])->name('create');

            Route::post('/', [ChannelManagerController::class, 'store'])->name('store'); // Thường POST về '/' hoặc '/create'

            Route::get('/edit/{id}', [ChannelManagerController::class, 'edit'])->name('edit');

            Route::put('/edit/{id}', [ChannelManagerController::class, 'update'])->name('update');

            Route::delete('/{id}', [ChannelManagerController::class, 'destroy'])->name('delete'); // Cách RESTful hơn

            // Route::delete('/mass-delete', [ChannelManagerController::class, 'massDestroy'])->name('mass_delete');

            // Route::put('/mass-update', [ChannelManagerController::class, 'massUpdate'])->name('mass_update');
        });
    });
});

Route::prefix('live-chat/webhook')
    ->group(function () {
        // Route GET để Facebook xác thực
        Route::get('messenger', [WebhookController::class, 'verify'])
            ->name('live_chat.webhook.messenger.verify');

        // Route POST để nhận sự kiện tin nhắn
        Route::post('messenger', [WebhookController::class, 'handle'])
            ->name('live_chat.webhook.messenger.handle');
});
