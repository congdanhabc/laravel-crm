<?php

namespace Webkul\LiveChat\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Webkul\User\Models\UserProxy;
use Webkul\LiveChat\Models\ChannelProxy;
use Webkul\LiveChat\Models\MessageProxy;
use Webkul\Contact\Models\PersonProxy; // Dùng Proxy của Krayin
use Webkul\LiveChat\Contracts\Conversation as ConversationContract;

class Conversation extends Model implements ConversationContract
{
    protected $table = 'live_chat_conversations';

    // Constants cho status và answered_by (giữ nguyên như trước)
    public const STATUS_PENDING = 'pending';
    public const STATUS_OPEN    = 'open';
    public const STATUS_CLOSED  = 'closed';
    public const STATUS_SPAM    = 'spam';
    public const ANSWERED_BY_PENDING = 'pending';
    public const ANSWERED_BY_BOT     = 'bot';
    public const ANSWERED_BY_HUMAN   = 'human';

    protected $fillable = [
        'visitor_id',
        'visitor_name',
        'visitor_email',
        'live_chat_channel_id', // Đảm bảo khớp tên cột khóa ngoại
        'user_id',
        'person_id',
        'status',
        'last_reply_at',
        'last_message_preview',
        'last_answered_by',
        'human_takeover_required',
        'marked_for_training',
    ];

    protected $casts = [
        'last_reply_at'           => 'datetime',
        'human_takeover_required' => 'boolean',
        'marked_for_training'     => 'boolean',
    ];

    // --- Relationships ---

    public function agent()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function channel()
    {
        return $this->belongsTo(Channel::class, 'live_chat_channel_id'); // Chỉ rõ khóa ngoại
    }

    public function messages()
    {
        // Chỉ rõ khóa ngoại nếu tên cột không theo chuẩn `conversation_id`
        return $this->hasMany(Message::class, 'live_chat_conversation_id')->orderBy('created_at', 'asc');
    }

    public function lastMessage()
    {
        // Chỉ rõ khóa ngoại nếu cần
        return $this->hasOne(Message::class, 'live_chat_conversation_id')->ofMany('created_at', 'max');
    }

    public function person()
    {
        return $this->belongsTo(PersonProxy::class, 'person_id');
    }

    // --- Scopes (giữ nguyên) ---
    public function scopeIsOpen($query) { /* ... */ }
    public function scopeIsPending($query) { /* ... */ }
    public function scopeIsClosed($query) { /* ... */ }
}
