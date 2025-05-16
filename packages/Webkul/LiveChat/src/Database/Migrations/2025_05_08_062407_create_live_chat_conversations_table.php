<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Webkul\LiveChat\Models\Conversation; // Import model để dùng constant

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('live_chat_conversations', function (Blueprint $table) {
            $table->id();

            // Thông tin khách truy cập
            $table->string('visitor_id')->index(); // ID định danh visitor (session, PSID, etc.)
            $table->string('visitor_name')->nullable();
            $table->string('visitor_email')->nullable()->index();

            // Liên kết
            $table->foreignId('live_chat_channel_id') // Sử dụng tên bảng đầy đủ
                  ->constrained('live_chat_channels') // Tên bảng channels
                  ->onDelete('cascade'); // Xóa conversation nếu channel bị xóa

            $table->unsignedInteger('user_id')->nullable(); // <<=== Khai báo kiểu chính xác
            $table->foreign('user_id')                          // <<=== Định nghĩa khóa ngoại riêng
                ->references('id')
                ->on('users')                              // Bảng users
                ->onDelete('set null');

            // Liên kết với Person trong Krayin (tùy chọn)
            // $table->foreignId('person_id')->nullable()
            //      ->constrained('persons') // Bảng persons của Krayin
            //      ->onDelete('set null');

            // Trạng thái & Thông tin meta
            $table->string('status')->default(Conversation::STATUS_PENDING)->index(); // pending, open, closed, spam
            $table->timestamp('last_reply_at')->nullable()->index(); // Thời gian tin nhắn cuối
            $table->text('last_message_preview')->nullable(); // Xem trước tin nhắn cuối
            $table->string('last_answered_by')->default(Conversation::ANSWERED_BY_PENDING)->index(); // pending, bot, human

            // Trường cho AI/Bot
            $table->boolean('human_takeover_required')->default(false)->index();
            $table->boolean('marked_for_training')->default(false)->index();

            $table->timestamps(); // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live_chat_conversations');
    }
};
