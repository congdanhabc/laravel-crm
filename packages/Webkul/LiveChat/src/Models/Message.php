<?php

namespace Webkul\LiveChat\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Webkul\User\Models\UserProxy;
use Webkul\LiveChat\Models\ConversationProxy;
use Webkul\LiveChat\Contracts\Message as MessageContract;

class Message extends Model implements MessageContract
{
    protected $table = 'live_chat_messages';

    // Constants cho sender_type (giữ nguyên)
    public const SENDER_VISITOR = 'visitor';
    public const SENDER_AGENT   = 'agent';
    public const SENDER_BOT     = 'bot';

    protected $fillable = [
        'live_chat_conversation_id', // Đảm bảo khớp tên cột khóa ngoại
        'sender_type',
        'user_id',
        'content',
        'read_at',
        'is_bot_suggestion',
        'used_for_training',
        'metadata',
    ];

    protected $casts = [
        'read_at'             => 'datetime',
        'is_bot_suggestion'   => 'boolean',
        'used_for_training'   => 'boolean',
        'metadata'            => 'array', // Quan trọng: Chuyển đổi JSON
    ];

    // --- Relationships ---

    public function conversation()
    {
        // Chỉ rõ khóa ngoại nếu tên cột không theo chuẩn `conversation_id`
        return $this->belongsTo(Conversation::class, 'live_chat_conversation_id');
    }

    public function agent()
    {
        // Chỉ trả về user nếu sender_type là 'agent' và user_id có giá trị
        return $this->belongsTo(User::class, 'user_id');
    }

    // --- Helper Methods (giữ nguyên) ---
    public function isFromVisitor(): bool
    {
        // So sánh giá trị của cột 'sender_type' với hằng số đã định nghĩa
        return $this->sender_type === self::SENDER_VISITOR;
    }
    public function isFromAgent(): bool
    {
        // So sánh giá trị của cột 'sender_type' với hằng số
        return $this->sender_type === self::SENDER_AGENT;
    }
    public function isFromBot(): bool
    {
        // So sánh giá trị của cột 'sender_type' với hằng số
        return $this->sender_type === self::SENDER_BOT;
    }
}
