<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Webkul\LiveChat\Models\Message; // Import model để dùng constant

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('live_chat_messages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('live_chat_conversation_id') // Tên bảng conversation
                  ->constrained('live_chat_conversations') // Tên bảng conversation
                  ->onDelete('cascade'); // Xóa message nếu conversation bị xóa

            $table->string('sender_type')->index(); // visitor, agent, bot

            $table->unsignedInteger('user_id')->nullable(); // <<=== Khai báo kiểu chính xác
            $table->foreign('user_id')                          // <<=== Định nghĩa khóa ngoại riêng
                ->references('id')
                ->on('users')                              // Bảng users
                ->onDelete('set null');

            $table->text('content'); // Nội dung tin nhắn

            $table->timestamp('read_at')->nullable(); // Thời gian người nhận đã đọc

            // Trường cho AI/Bot
            $table->boolean('is_bot_suggestion')->default(false);
            $table->boolean('used_for_training')->default(false)->index();
            $table->json('metadata')->nullable(); // Lưu trữ thông tin thêm: confidence, external_id...

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live_chat_messages');
    }
};
