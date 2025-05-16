<?php

namespace Webkul\LiveChat\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\User\Http\Resources\User as UserResource; // Để lấy thông tin agent

class MessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender_type'     => $this->sender_type,
            'user_id'         => $this->user_id,
            'agent'           => $this->when($this->sender_type === 'agent' && $this->agent, function() {
                                    return [ // Chỉ lấy thông tin cần thiết của agent
                                        'id' => $this->agent->id,
                                        'name' => $this->agent->name,
                                        // 'avatar_url' => $this->agent->avatar_url, // Nếu có
                                    ];
                                }),
            'content'         => $this->content,
            'read_at'         => $this->read_at ? $this->read_at->toIso8601String() : null,
            'created_at'      => $this->created_at->toIso8601String(),
            'metadata'        => $this->metadata,
        ];
    }
}
