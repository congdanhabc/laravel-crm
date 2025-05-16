<?php

namespace Webkul\LiveChat\Events; // Namespace của module

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast; // Quan trọng để broadcasting
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Webkul\LiveChat\Contracts\Message as MessageContract;
use Webkul\LiveChat\Contracts\Conversation as ConversationContract;
use Webkul\LiveChat\Resources\MessageResource; // Sử dụng API Resource
use Illuminate\Support\Str; // Để dùng Str::limit

class NewMessageReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Đối tượng tin nhắn mới nhận được.
     *
     * @var \Webkul\LiveChat\Contracts\Message
     */
    public MessageContract $message;

    /**
     * Đối tượng cuộc hội thoại liên quan.
     *
     * @var \Webkul\LiveChat\Contracts\Conversation
     */
    public ConversationContract $conversation;

    /**
     * Tạo một instance event mới.
     *
     * @param \Webkul\LiveChat\Contracts\Message $message
     * @param \Webkul\LiveChat\Contracts\Conversation $conversation
     * @return void
     */
    public function __construct(MessageContract $message, ConversationContract $conversation)
    {
        $this->message = $message;
        $this->conversation = $conversation;

        /** @var \Webkul\LiveChat\Models\Message $messageModel */
        $messageModel = $this->message;
        $messageModel->loadMissing('agent');
    }

    /**
     * Lấy các kênh mà sự kiện sẽ được phát sóng trên đó.
     * Phát sóng trên kênh riêng tư của cuộc hội thoại.
     *
     * @return \Illuminate\Broadcasting\PrivateChannel|array
     */
    public function broadcastOn(): PrivateChannel|array
    {
        // Sử dụng PrivateChannel để chỉ những người dùng đã được xác thực
        // và có quyền truy cập kênh này (thông qua routes/channels.php) mới nhận được.
        return new PrivateChannel('livechat.conversation.' . $this->conversation->id);
    }

    /**
     * Tên của sự kiện khi được phát sóng.
     * JavaScript sẽ lắng nghe tên này.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'message.new'; // Ví dụ: 'message.new'
    }

    /**
     * Dữ liệu sẽ được phát sóng cùng với sự kiện.
     * Sử dụng API Resource để chuẩn hóa dữ liệu trả về.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            // Dữ liệu tin nhắn đầy đủ (đã qua Resource)
            'message' => new MessageResource($this->message),

            // ID cuộc hội thoại để JS biết cập nhật đúng cửa sổ
            'conversation_id' => $this->conversation->id,

            // Dữ liệu xem trước để cập nhật danh sách hội thoại bên trái
            'conversation_preview' => [
                'id'                   => $this->conversation->id,
                'visitor_name'         => $this->conversation->visitor_name ?? ('Visitor ' . $this->conversation->visitor_id),
                'last_message_content' => Str::limit(strip_tags($this->message->content ?? ''), 35), // Cắt ngắn và loại bỏ HTML
                'last_message_time'    => $this->message->created_at?->toIso8601String(), // Định dạng chuẩn cho JS
                'unread_count'         => 1, // Giả định đây là tin nhắn mới chưa đọc cho agent
            ]
        ];
    }
}
