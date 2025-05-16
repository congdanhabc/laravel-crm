<?php

namespace Webkul\LiveChat\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

// Import Contracts
use Webkul\LiveChat\Contracts\Channel as ChannelContract;
use Webkul\LiveChat\Contracts\Conversation as ConversationContract;
use Webkul\LiveChat\Contracts\Message as MessageContract;

// Import Models (Implementations for Model Contracts)
use Webkul\LiveChat\Models\Channel as ChannelModel;
use Webkul\LiveChat\Models\Conversation as ConversationModel;
use Webkul\LiveChat\Models\Message as MessageModel;

// Import Repository Implementations
use Webkul\LiveChat\Repositories\ChannelRepository;
use Webkul\LiveChat\Repositories\ConversationRepository;
use Webkul\LiveChat\Repositories\MessageRepository;
class LiveChatServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');

        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'live_chat');

        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'live_chat');

        $this->publishes([
            __DIR__.'/../Resources/assets' => public_path('vendor/webkul/livechat/assets'),
        ], 'public');

        Event::listen('admin.layout.head.after', function($viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('live_chat::components.layouts.style');
        });
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerConfig();
        $this->app->bind(ChannelContract::class, ChannelModel::class);
        $this->app->bind(ConversationContract::class, ConversationModel::class);
        $this->app->bind(MessageContract::class, MessageModel::class);
        $this->app->bind(ChannelRepository::class, ChannelRepository::class);
        $this->app->bind(ConversationRepository::class, ConversationRepository::class);
        $this->app->bind(MessageRepository::class, MessageRepository::class);
    }

    /**
     * Register package config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/menu.php', 'menu.admin'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/acl.php', 'acl'
        );
    }
}
