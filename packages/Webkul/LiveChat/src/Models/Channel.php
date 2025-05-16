<?php

namespace Webkul\LiveChat\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\LiveChat\Contracts\Channel as ChannelContract;
use Webkul\LiveChat\Models\ConversationProxy;

class Channel extends Model implements ChannelContract
{
    protected $table = 'live_chat_channels';

    protected $fillable = [
        'name',
        'type',
        'config',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'config' => 'array', // Quan trọng: Chuyển đổi JSON sang array và ngược lại
        'status' => 'boolean',
    ];

    /**
     * Get the conversations for the channel.
     */
    public function conversations()
    {
        return $this->hasMany(Conversation::class, 'live_chat_channel_id'); // Chỉ rõ khóa ngoại
    }
}
