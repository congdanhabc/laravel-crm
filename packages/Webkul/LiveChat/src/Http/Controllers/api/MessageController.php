<?php

namespace Webkul\LiveChat\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth; // Để lấy agent hiện tại
use Webkul\LiveChat\Resources\MessageResource; // Tạo resource này
use Webkul\Admin\Http\Controllers\Controller; // Kế thừa Controller của Krayin/Bagisto
use Webkul\LiveChat\Repositories\ConversationRepository;
use Webkul\LiveChat\Repositories\MessageRepository;
use Webkul\LiveChat\Models\Conversation;
use Webkul\LiveChat\Models\Message;
use Webkul\LiveChat\Events\AgentReplied;

class MessageController extends Controller
{
    public function __construct(
        protected ConversationRepository $conversationRepository,
        protected MessageRepository $messageRepository
    ) {
        // $this->middleware('auth:api'); // Hoặc sanctum, hoặc user nếu API chỉ cho admin đã login
    }

    /**
     * Lấy danh sách tin nhắn của một cuộc hội thoại.
     * (Đã có trong ConversationController@show, nhưng có thể tách riêng nếu muốn)
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $conversationId
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, int $conversationId)
    {
        $conversation = $this->conversationRepository->findOrFail($conversationId);

        // Kiểm tra quyền truy cập của agent vào cuộc hội thoại này (nếu cần)
        // if ($conversation->user_id !== Auth::id() && !Auth::user()->hasRole('administrator')) {
        //     return response()->json(['error' => 'Unauthorized'], 403);
        // }

        $messages = $this->messageRepository->getConversationMessages($conversationId, $request->input('limit', 20));

        return MessageResource::collection($messages);
    }


    /**
     * Lưu một tin nhắn mới từ agent.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $conversationId ID của cuộc hội thoại
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, int $conversationId)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $conversation = $this->conversationRepository->findOrFail($conversationId);
        $agent = Auth::guard('user')->user(); // Lấy admin user đang đăng nhập

        // Kiểm tra xem agent có quyền reply vào conversation này không
        if ($conversation->user_id !== $agent->id && ($conversation->status === Conversation::STATUS_PENDING || is_null($conversation->user_id))) {
            // Nếu conversation đang pending hoặc chưa có ai nhận, tự động assign cho agent này
            $this->conversationRepository->assignAgent($conversation->id, $agent->id);
            $conversation->refresh(); // Lấy lại thông tin conversation đã được assign
        } elseif ($conversation->user_id !== $agent->id) {
            return response()->json(['error' => 'You are not assigned to this conversation.'], 403);
        }

        if ($conversation->status === Conversation::STATUS_CLOSED) {
            return response()->json(['error' => 'This conversation is closed.'], 400);
        }

        $message = $this->messageRepository->createMessage([
            'sender_type'   => Message::SENDER_AGENT,
            'user_id'       => $agent->id,
            'content'       => $request->input('message'),
        ], $conversation, Conversation::ANSWERED_BY_HUMAN);

        // Bắn event để thông báo cho visitor (qua kênh webhook nếu có) và các agent khác
        // event(new AgentReplied($message, $conversation));
        broadcast(new AgentReplied($message, $conversation))->toOthers(); // Gửi cho các agent khác

        // (Tương lai) Nếu tin nhắn này là câu trả lời cho câu hỏi mà bot không trả lời được,
        // đánh dấu cặp câu hỏi-đáp này để bot học
        // $previousMessage = $conversation->messages()->where('sender_type', Message::SENDER_VISITOR)->latest()->first();
        // if ($previousMessage && $conversation->human_takeover_required) {
        //     app(TrainingService::class)->addTrainingPair($previousMessage->content, $message->content);
        //     $this->conversationRepository->update(['human_takeover_required' => false], $conversation->id);
        // }

        return new MessageResource($message); // Trả về tin nhắn vừa tạo
    }
}
