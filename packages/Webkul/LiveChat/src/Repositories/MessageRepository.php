<?php

namespace Webkul\LiveChat\Repositories;

use Illuminate\Container\Container as App;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB; // Nếu cần transaction phức tạp
use Webkul\Core\Eloquent\Repository;
use Webkul\LiveChat\Contracts\Message as MessageContract;
use Webkul\LiveChat\Contracts\Conversation as ConversationContract;
use Webkul\LiveChat\Models\Conversation as ConversationModel; // Dùng cho constant
use Webkul\LiveChat\Models\Message as MessageModel; // Dùng cho constant

class MessageRepository extends Repository
{
    /**
     * Chỉ định lớp Model
     *
     * @return string
     */
    public function model(): string
    {
        return MessageContract::class; // Sử dụng Contract
    }

    /**
     * Tạo tin nhắn mới và cập nhật thông tin cuộc hội thoại cha.
     *
     * @param array $data Dữ liệu tin nhắn (sender_type, content, user_id?, metadata?, ...)
     * @param ConversationContract $conversation Đối tượng Conversation cha
     * @param string|null $answeredBy Cập nhật trạng thái 'last_answered_by' của Conversation (nếu cần)
     * @param bool $resetHumanTakeover Reset cờ 'human_takeover_required' của Conversation (nếu cần)
     * @return MessageContract
     */
    public function createMessage(
        array $data,
        ConversationContract $conversation,
        ?string $answeredBy = null,
        bool $resetHumanTakeover = false
    ): MessageContract
    {
        // Đảm bảo conversation_id được gán
        $data['conversation_id'] = $conversation->id;

        // Đặt các giá trị mặc định nếu chưa có
        $data['is_bot_suggestion'] = $data['is_bot_suggestion'] ?? false;
        $data['used_for_training'] = $data['used_for_training'] ?? false;

        // Tạo tin nhắn
        $message = $this->create($data);

        // Lấy ConversationRepository để cập nhật conversation cha
        // Sử dụng app() để tránh inject vào constructor nếu chỉ dùng ở đây
        $conversationRepository = app(ConversationRepository::class);
        $conversationRepository->updateLastMessageDetails(
            $conversation, // Truyền đối tượng conversation
            $message,
            $answeredBy,
            $resetHumanTakeover
        );

        return $message;
    }

    /**
     * Lấy tin nhắn của một cuộc hội thoại, có phân trang và sắp xếp.
     *
     * @param int $conversationId
     * @param int $perPage Số lượng tin nhắn mỗi trang
     * @param string $order Hướng sắp xếp ('asc' = cũ nhất trước, 'desc' = mới nhất trước)
     * @return LengthAwarePaginator
     */
    public function getConversationMessagesPaginated(int $conversationId, int $perPage = 30, string $order = 'desc'): LengthAwarePaginator
    {
        // Sắp xếp DESC thường dùng để hiển thị trang cuối (mới nhất) trước
        // hoặc dùng cho "load older messages" (infinite scroll)
        return $this->model
            ->where('conversation_id', $conversationId)
            ->with(['agent:id,name']) // Eager load thông tin cơ bản của agent nếu có
            ->orderBy('created_at', $order)
            ->orderBy('id', $order) // Thêm sắp xếp theo ID để đảm bảo thứ tự nhất quán nếu created_at trùng
            ->paginate($perPage);
    }

    /**
     * Lấy tất cả tin nhắn của một cuộc hội thoại (không phân trang).
     * Thận trọng khi dùng với các hội thoại dài.
     *
     * @param int $conversationId
     * @param string $order
     * @return Collection
     */
    public function getAllConversationMessages(int $conversationId, string $order = 'asc'): Collection
    {
        // Sắp xếp ASC để hiển thị theo trình tự thời gian tự nhiên
        return $this->model
            ->where('conversation_id', $conversationId)
            ->with(['agent:id,name'])
            ->orderBy('created_at', $order)
            ->orderBy('id', $order)
            ->get();
    }

     /**
      * Đánh dấu các tin nhắn là đã đọc trong một cuộc hội thoại cho đến một thời điểm nào đó.
      * Thường dùng khi agent mở hoặc gửi tin nhắn vào cuộc hội thoại.
      *
      * @param int $conversationId
      * @param \Carbon\Carbon|null $timestamp Thời điểm đọc (mặc định là now())
      * @param string $readerType Ai đọc ('agent' hoặc 'visitor') - Quan trọng nếu cần phân biệt
      * @return int Số lượng tin nhắn được cập nhật
      */
    // public function markMessagesAsRead(int $conversationId, ?\Carbon\Carbon $timestamp = null, string $readerType = 'agent'): int
    // {
    //     $timestamp = $timestamp ?? now();

    //     $query = $this->model
    //         ->where('conversation_id', $conversationId)
    //         ->whereNull('read_at'); // Chỉ cập nhật tin nhắn chưa đọc

    //     // Chỉ đánh dấu đã đọc tin nhắn của đối phương
    //     if ($readerType === 'agent') {
    //         $query->where('sender_type', '!=', MessageModel::SENDER_AGENT);
    //     } else { // visitor đọc
    //         $query->where('sender_type', '!=', MessageModel::SENDER_VISITOR);
    //     }

    //     return $query->update(['read_at' => $timestamp]);
    // }


    /**
     * Tìm các cặp tin nhắn chưa được dùng để huấn luyện trong các cuộc hội thoại được đánh dấu.
     * Logic này có thể phức tạp và cần điều chỉnh theo chiến lược huấn luyện.
     *
     * @param int $limit Giới hạn số lượng cặp lấy ra
     * @return Collection
     */
    // public function findUntrainedMessagePairsForMarkedConversations(int $limit = 10): Collection
    // {
    //     // Logic ví dụ: Tìm các cuộc hội thoại được đánh dấu 'marked_for_training'
    //     // Sau đó trong mỗi cuộc hội thoại, tìm cặp tin nhắn (visitor -> agent) mà chưa được đánh dấu 'used_for_training'
    //     $conversationIds = app(ConversationRepository::class)
    //         ->findWhere(['marked_for_training' => true])
    //         ->pluck('id');

    //     if ($conversationIds->isEmpty()) {
    //         return new \Illuminate\Database\Eloquent\Collection();
    //     }

    //     // Lấy các tin nhắn từ các cuộc hội thoại đó
    //     // Logic tìm 'cặp' cần phức tạp hơn, đây chỉ là ví dụ lấy message chưa train
    //     return $this->model
    //         ->whereIn('conversation_id', $conversationIds)
    //         ->where('used_for_training', false)
    //         // Thêm điều kiện để chỉ lấy cặp Visitor -> Agent nếu cần
    //         ->orderBy('conversation_id')
    //         ->orderBy('created_at')
    //         ->limit($limit * 2) // Lấy nhiều hơn để có thể ghép cặp
    //         ->get();
    // }

    /**
     * Đánh dấu một hoặc nhiều tin nhắn là đã được sử dụng để huấn luyện.
     *
     * @param array|int $messageIds
     * @return int Số lượng tin nhắn được cập nhật
     */
    // public function markMessagesAsTrained(array|int $messageIds): int
    // {
    //     if (!is_array($messageIds)) {
    //         $messageIds = [$messageIds];
    //     }

    //     if (empty($messageIds)) {
    //         return 0;
    //     }

    //     return $this->model->whereIn('id', $messageIds)->update(['used_for_training' => true]);
    // }

}
