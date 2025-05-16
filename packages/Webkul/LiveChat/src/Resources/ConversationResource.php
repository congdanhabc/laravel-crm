<?php

namespace Webkul\LiveChat\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use Webkul\Admin\Http\Resources\UserResource;
use Webkul\LiveChat\Http\Resources\ChannelResource; // Tạo resource này nếu cần
use Webkul\LiveChat\Resources\MessageResource; // Resource đã tạo

class ConversationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'visitor_id'           => $this->visitor_id,
            'visitor_name'         => $this->visitor_name,
            'visitor_email'        => $this->visitor_email,
            'live_chat_channel_id' => $this->live_chat_channel_id,
            'user_id'              => $this->user_id,
            'person_id'            => $this->person_id,
            'status'               => $this->status,
            'last_reply_at'        => $this->last_reply_at?->toIso8601String(),
            'last_message_preview' => $this->last_message_preview,
            'last_answered_by'     => $this->last_answered_by,
            'human_takeover_required'=> (bool) $this->human_takeover_required,
            'marked_for_training'  => (bool) $this->marked_for_training,
            'created_at'           => $this->created_at->toIso8601String(),
            'updated_at'           => $this->updated_at->toIso8601String(),

            // Include các quan hệ nếu đã được load
            'agent' => $this->whenLoaded('agent', function () {
                // Chỉ trả về thông tin cần thiết của agent
                return new UserResource($this->agent); // Sử dụng UserResource của Krayin
                // Hoặc chỉ trả về mảng đơn giản:
                // return $this->agent ? ['id' => $this->agent->id, 'name' => $this->agent->name] : null;
            }),
            'channel' => $this->whenLoaded('channel', function () {
                return $this->channel ? ['id' => $this->channel->id, 'name' => $this->channel->name, 'type' => $this->channel->type] : null;
                // Hoặc new ChannelResource($this->channel); nếu bạn tạo ChannelResource
            }),
            // Chỉ include messages khi gọi API show, không nên include trong danh sách index
            'messages' => $this->whenLoaded('messages', function () {
                 return MessageResource::collection($this->messages);
            }),
             'last_message' => $this->whenLoaded('lastMessage', function() {
                 return new MessageResource($this->lastMessage);
             }),
        ];
    }
}
