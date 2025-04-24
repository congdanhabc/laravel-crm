<?php

namespace Webkul\ChannelManager\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

class ChannelManagerServiceProvider extends ServiceProvider
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

        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'channelmanager');

        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'channelmanager');

        Event::listen('admin.layout.head.after', function($viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('channelmanager::components.layouts.style');
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
        $this->app->bind(
            \Webkul\ChannelManager\Contracts\Channel::class, // Interface (Contract)
            \Webkul\ChannelManager\Repositories\ChannelRepository::class // Implementation (Repository)
        );
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
