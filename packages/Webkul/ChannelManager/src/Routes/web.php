<?php

use Illuminate\Support\Facades\Route;
use Webkul\ChannelManager\Http\Controllers\ChannelManagerController;
use Webkul\ChannelManager\Http\Controllers\WebhookController;

Route::group(['middleware' => ['web', 'user']], function () {
    Route::prefix(config('app.admin_url') . '/channelmanager')->group(function () {
        // ... các route cho index, create, edit, store, update ...
        Route::get('/',[ChannelManagerController::class, 'index'])->name('admin.channelmanager.index');
        Route::get('/create', [ChannelManagerController::class, 'create'])->name('admin.channelmanager.create');
        Route::post('/create', [ChannelManagerController::class, 'store'])->name('admin.channelmanager.store');
        Route::get('/edit/{id}', [ChannelManagerController::class, 'edit'])->name('admin.channelmanager.edit');
        Route::put('/edit/{id}', [ChannelManagerController::class, 'update'])->name('admin.channelmanager.update');
        Route::delete('/delete/{id}', [ChannelManagerController::class, 'destroy'])->name('admin.channelmanager.delete');
    });
});

Route::prefix('channelmanager/webhook')
    ->group(function () {
        // Route GET để Facebook xác thực
        Route::get('messenger', [WebhookController::class, 'verify'])
            ->name('channelmanager.webhook.messenger.verify');

        // Route POST để nhận sự kiện tin nhắn
        Route::post('messenger', [WebhookController::class, 'handle'])
            ->name('channelmanager.webhook.messenger.handle');
});
