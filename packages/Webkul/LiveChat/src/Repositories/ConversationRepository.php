<?php
namespace Webkul\LiveChat\Repositories;

use Illuminate\Container\Container as App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event; // Để bắn event nếu cần từ Repo
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Webkul\Core\Eloquent\Repository;
use Webkul\LiveChat\Models\Conversation as ConversationModel; // Model cụ thể
use Webkul\LiveChat\Contracts\Conversation as ConversationContract;
use Webkul\LiveChat\Contracts\Message as MessageContract; // Nếu cần tương tác với Message
use Webkul\LiveChat\Models\Message as MessageModel; // Model cụ thể

class ConversationRepository extends Repository
{

    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model(): string
    {
        return ConversationContract::class; // Sử dụng Contract
    }

    /**
     * Tạo một cuộc hội thoại mới.
     *
     * @param array $data Dữ liệu hội thoại (visitor_id, channel_id, visitor_name...)
     * @return \Webkul\LiveChat\Contracts\Conversation
     */
    public function createConversation(array $data): ConversationContract
    {
        // Đặt các giá trị mặc định
        $data['status'] = $data['status'] ?? ConversationModel::STATUS_PENDING;
        $data['last_answered_by'] = $data['last_answered_by'] ?? ConversationModel::ANSWERED_BY_PENDING;
        $data['human_takeover_required'] = $data['human_takeover_required'] ?? false;
        $data['marked_for_training'] = $data['marked_for_training'] ?? false;
        $data['last_reply_at'] = Carbon::now(); // Gán thời điểm tạo làm last_reply_at ban đầu

        $conversation = $this->create($data);

        // Event::dispatch('live_chat.conversation.created', $conversation); // Ví dụ bắn event

        return $conversation;
    }

    /**
     * Tìm cuộc hội thoại đang mở hoặc đang chờ của một khách truy cập cụ thể trên một kênh.
     *
     * @param string $visitorExternalId ID bên ngoài của khách (ví dụ: PSID Facebook)
     * @param int $channelId ID của kênh
     * @return \Webkul\LiveChat\Contracts\Conversation|null
     */
    public function findActiveConversationByVisitor(string $visitorExternalId, int $channelId): ?ConversationContract
    {
        return $this->model
            ->where('visitor_id', $visitorExternalId) // Giả sử visitor_id lưu external ID
            ->where('live_chat_channel_id', $channelId)
            ->whereIn('status', [ConversationModel::STATUS_OPEN, ConversationModel::STATUS_PENDING])
            ->first();
    }

    /**
     * Lấy các cuộc hội thoại dựa trên bộ lọc (filter).
     * Hữu ích cho API endpoint lấy danh sách hội thoại.
     *
     * @param array $filters Các điều kiện lọc (ví dụ: status, agent_id, search_term)
     * @param int $perPage Số lượng item mỗi trang
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getFilteredConversations(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        // Lọc theo trạng thái
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        // Lọc theo agent được gán
        if (isset($filters['agent_id'])) { // Có thể là null nếu muốn lấy các hội thoại chưa gán
            $query->where('user_id', $filters['agent_id']);
        }

        // Lọc theo kênh
        if (!empty($filters['channel_id'])) {
            $query->where('channel_id', $filters['channel_id']);
        }

        // Tìm kiếm (ví dụ: theo tên khách hoặc nội dung tin nhắn cuối)
        if (!empty($filters['search_term'])) {
            $searchTerm = '%' . $filters['search_term'] . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('visitor_name', 'like', $searchTerm)
                  ->orWhere('last_message_preview', 'like', $searchTerm);
            });
        }

        // Eager load các quan hệ cần thiết để tránh N+1 query
        $query->with(['agent:id,name', 'channel:id,name', 'lastMessage']); // Chỉ lấy các cột cần thiết từ agent, channel

        // Sắp xếp: Mặc định theo tin nhắn mới nhất
        $sortBy = $filters['sort_by'] ?? 'last_reply_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }


    /**
     * Gán một agent cho cuộc hội thoại và cập nhật trạng thái.
     *
     * @param int|\Webkul\LiveChat\Contracts\Conversation $conversation
     * @param int $agentId
     * @return \Webkul\LiveChat\Contracts\Conversation
     */
    public function assignAgent(int|ConversationContract $conversation, int $agentId): ConversationContract
    {
        if (!$conversation instanceof ConversationContract) {
            $conversation = $this->findOrFail($conversation);
        }

        // Chỉ assign nếu chưa có agent hoặc agent hiện tại khác
        if ($conversation->user_id !== $agentId || $conversation->status === ConversationModel::STATUS_PENDING) {
            $conversation = $this->update([
                'user_id' => $agentId,
                'status'  => ConversationModel::STATUS_OPEN, // Chuyển sang open khi có agent
            ], $conversation->id);

            // Event::dispatch('live_chat.conversation.assigned', $conversation);
        }
        return $conversation;
    }

    /**
     * Đóng một cuộc hội thoại.
     *
     * @param int|\Webkul\LiveChat\Contracts\Conversation $conversation
     * @param string|null $closedBy ('agent', 'visitor', 'system') - tùy chọn
     * @return \Webkul\LiveChat\Contracts\Conversation
     */
    public function closeConversation(int|ConversationContract $conversation, ?string $closedBy = null): ConversationContract
    {
        if (!$conversation instanceof ConversationContract) {
            $conversation = $this->findOrFail($conversation);
        }

        if ($conversation->status !== ConversationModel::STATUS_CLOSED) {
            $updateData = ['status' => ConversationModel::STATUS_CLOSED];
            // if ($closedBy) $updateData['closed_by_type'] = $closedBy; // Thêm cột này nếu cần
            $conversation = $this->update($updateData, $conversation->id);
            // Event::dispatch('live_chat.conversation.closed', $conversation);
        }
        return $conversation;
    }

    /**
     * Cập nhật thông tin tin nhắn cuối cùng và trạng thái trả lời cho cuộc hội thoại.
     *
     * @param int|\Webkul\LiveChat\Contracts\Conversation $conversation
     * @param \Webkul\LiveChat\Contracts\Message $message
     * @param string|null $answeredBy Ai trả lời (bot/human/pending)
     * @param bool $resetHumanTakeover Nếu true, reset cờ human_takeover_required
     * @return bool
     */
    public function updateLastMessageDetails(
        int|ConversationContract $conversation,
        MessageContract $message,
        ?string $answeredBy = null,
        bool $resetHumanTakeover = false
    ): ConversationContract
    {
        if (!$conversation instanceof ConversationContract) {
            $conversation = $this->findOrFail($conversation->id ?? $conversation); // Handle cả ID và object
        }

        $updateData = [
            'last_reply_at'        => $message->created_at, // Hoặc Carbon::now()
            'last_message_preview' => mb_substr(strip_tags($message->content ?? ''), 0, 150), // Tăng giới hạn preview
        ];

        if ($answeredBy) {
             $updateData['last_answered_by'] = $answeredBy;
        }

        if ($resetHumanTakeover) {
            $updateData['human_takeover_required'] = false;
        }

        // Nếu cuộc hội thoại đang pending và có tin nhắn (từ khách hoặc bot trả lời), chuyển sang open
        // Trừ khi tin nhắn là từ khách và chưa có agent nào.
        if ($conversation->status === ConversationModel::STATUS_PENDING) {
            if ($message->sender_type === MessageModel::SENDER_BOT || ($message->sender_type === MessageModel::SENDER_AGENT && $conversation->user_id)) {
                $updateData['status'] = ConversationModel::STATUS_OPEN;
            }
            // Nếu là tin nhắn đầu tiên từ khách và chưa có agent, vẫn giữ pending
        }


        return $this->update($updateData, $conversation->id);
    }

     /**
      * Đánh dấu cuộc hội thoại cần người can thiệp.
      *
      * @param int|\Webkul\LiveChat\Contracts\Conversation $conversation
      * @return \Webkul\LiveChat\Contracts\Conversation
      */
     public function markHumanTakeoverRequired(int|ConversationContract $conversation): ConversationContract
     {
        if (!$conversation instanceof ConversationContract) {
            $conversation = $this->findOrFail($conversation);
        }
        if (!$conversation->human_takeover_required) {
             $conversation = $this->update([
                'human_takeover_required' => true,
                'last_answered_by'        => ConversationModel::ANSWERED_BY_PENDING, // Chờ người trả lời
            ], $conversation->id);
            // Event::dispatch('live_chat.conversation.human_takeover_required', $conversation);
        }
        return $conversation;
     }

     /**
      * Đánh dấu cuộc hội thoại hữu ích cho việc huấn luyện bot.
      *
      * @param int|\Webkul\LiveChat\Contracts\Conversation $conversation
      * @param bool $flag
      * @return \Webkul\LiveChat\Contracts\Conversation
      */
     public function markForTraining(int|ConversationContract $conversation, bool $flag = true): ConversationContract
     {
         if (!$conversation instanceof ConversationContract) {
            $conversation = $this->findOrFail($conversation);
        }
        if ($conversation->marked_for_training !== $flag) {
            $conversation = $this->update(['marked_for_training' => $flag], $conversation->id);
            // Event::dispatch('live_chat.conversation.marked_for_training', $conversation);
        }
        return $conversation;
     }

    /**
     * Lấy số lượng tin nhắn chưa đọc cho một agent.
     * (Logic này có thể phức tạp và cần tối ưu hóa)
     *
     * @param int $agentId
     * @return int
     */
    // public function getAgentUnreadCount(int $agentId): int
    // {
    //     // Cách 1: Đếm các conversation được assign cho agent và có tin nhắn mới nhất từ visitor chưa được agent đọc.
    //     // Cần thêm một trường `agent_last_read_at` vào Conversation model.
    //     return $this->model
    //         ->where('user_id', $agentId)
    //         ->where('status', ConversationModel::STATUS_OPEN)
    //         ->whereHas('messages', function ($query) {
    //             $query->where('sender_type', MessageModel::SENDER_VISITOR)
    //                   ->whereNull('read_at'); // Hoặc so sánh với agent_last_read_at của conversation
    //         })
    //         ->count();

    //     // Cách 2: Duy trì một bảng riêng `conversation_user_reads`
    // }

    /**
     * Lấy tất cả các cuộc hội thoại đang chờ hoặc mở mà chưa được đọc bởi agent cụ thể.
     * Dùng để thông báo hoặc đánh dấu.
     *
     * @param int $agentId
     * @return Collection
     */
    // public function getUnassignedOrUnreadByAgent(int $agentId): Collection
    // {
    //     return $this->model->where(function ($query) use ($agentId) {
    //             $query->where('status', ConversationModel::STATUS_PENDING) // Các case pending
    //                   ->orWhere(function ($q) use ($agentId) { // Các case của agent này mà có tin nhắn mới từ khách
    //                       $q->where('user_id', $agentId)
    //                         ->where('status', ConversationModel::STATUS_OPEN)
    //                         ->whereColumn('last_reply_at', '>', 'agent_last_viewed_at'); // Cần cột agent_last_viewed_at
    //                   });
    //         })
    //         ->orderBy('last_reply_at', 'desc')
    //         ->get();
    // }
}
