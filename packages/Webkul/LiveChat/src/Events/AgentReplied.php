<?php

namespace Webkul\LiveChat\Events; // Namespace của module

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Webkul\LiveChat\Contracts\Message as MessageContract;
use Webkul\LiveChat\Contracts\Conversation as ConversationContract;
use Webkul\LiveChat\Resources\MessageResource;
use Illuminate\Support\Str;

class AgentReplied implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Tin nhắn mà agent đã gửi.
     *
     * @var \Webkul\LiveChat\Contracts\Message
     */
    public MessageContract $message;

    /**
     * Cuộc hội thoại liên quan.
     *
     * @var \Webkul\LiveChat\Contracts\Conversation
     */
    public ConversationContract $conversation;

    /**
     * Create a new event instance.
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
     * Get the channels the event should broadcast on.
     * Gửi đến cùng kênh private của conversation.
     *
     * @return \Illuminate\Broadcasting\PrivateChannel|array
     */
    public function broadcastOn(): PrivateChannel|array
    {
        return new PrivateChannel('livechat.conversation.' . $this->conversation->id);
    }

    /**
     * Tên của sự kiện khi được phát sóng.
     * Đặt tên khác với NewMessageReceived để JS phân biệt.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'message.agent.replied'; // Ví dụ: 'message.agent.replied'
    }

    /**
     * Dữ liệu sẽ được phát sóng.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'message' => new MessageResource($this->message),
            'conversation_id' => $this->conversation->id,
            'conversation_preview' => [
                'id'                   => $this->conversation->id,
                'visitor_name'         => $this->conversation->visitor_name ?? ('Visitor ' . $this->conversation->visitor_id),
                'last_message_content' => Str::limit(strip_tags($this->message->content ?? ''), 35),
                'last_message_time'    => $this->message->created_at?->toIso8601String(),
                'unread_count'         => 0, // Agent trả lời thì không tăng unread cho agent khác (có thể tùy chỉnh logic này)
            ]
        ];
    }
}
