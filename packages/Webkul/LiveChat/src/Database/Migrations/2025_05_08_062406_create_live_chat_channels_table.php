<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('live_chat_channels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->index(); // 'facebook', 'Channex.io'
            $table->json('config')->nullable(); // Lưu trữ API keys, tokens, page_id, webhook_secret, etc.
            $table->boolean('status')->default(true)->index(); // Kênh đang hoạt động hay không
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live_chat_channels');
    }
};
