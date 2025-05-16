<?php

namespace Webkul\LiveChat\Http\Controllers\Api; // Namespace cho API Controller

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Webkul\Admin\Http\Controllers\Controller; // Kế thừa base Controller (hoặc App\Http\Controllers\Controller)
use Webkul\LiveChat\Repositories\ConversationRepository;
// Import API Resources
use Webkul\LiveChat\Resources\ConversationResource;
use Webkul\LiveChat\Models\Conversation; // Import Model để dùng Route Model Binding

class ConversationController extends Controller
{
    /**
     * Conversation Repository instance.
     *
     * @var \Webkul\LiveChat\Repositories\ConversationRepository
     */
    protected $conversationRepository;

    /**
     * Create a new controller instance.
     *
     * @param \Webkul\LiveChat\Repositories\ConversationRepository $conversationRepository
     * @return void
     */
    public function __construct(ConversationRepository $conversationRepository)
    {
        $this->conversationRepository = $conversationRepository;

        // Áp dụng middleware xác thực API cho tất cả các phương thức trong controller này
        // Đảm bảo bạn đã cấu hình API guard ('sanctum', 'api', hoặc 'user') đúng
        // Nếu dùng 'user', cần đảm bảo request từ frontend gửi kèm session/cookie
        $this->middleware('auth:sanctum'); // Hoặc 'auth:api' hoặc 'auth:user'
    }

    /**
     * Lấy danh sách các cuộc hội thoại với bộ lọc và phân trang.
     * Xử lý cho route: GET /admin/api/live-chat/conversations
     * Route name: admin.live_chat.api.conversations.index
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Lấy agent đang đăng nhập
        $agent = Auth::user();

        // Xây dựng bộ lọc - ví dụ: chỉ lấy conversation của agent này hoặc các conversation đang chờ
        $filters = $request->query(); // Lấy các query params làm bộ lọc

        // Ví dụ: Nếu không phải admin, chỉ cho xem conversation của mình hoặc đang pending
        // if (!$agent->hasRole('admin')) { // Giả sử có role
        //     $filters['agent_or_pending'] = $agent->id; // Thêm bộ lọc tùy chỉnh
        // }
        // --> Bạn cần thêm logic xử lý bộ lọc này trong ConversationRepository@getFilteredConversations

        $conversations = $this->conversationRepository->getFilteredConversations(
            $filters,
            $request->input('limit', 15) // Số lượng mỗi trang
        );

        // Trả về collection resource (tự động xử lý pagination)
        return response()->json(ConversationResource::collection($conversations));
    }

    /**
     * Lấy thông tin chi tiết của một cuộc hội thoại, bao gồm cả tin nhắn.
     * Xử lý cho route: GET /admin/api/live-chat/conversations/{conversation}
     * Route name: admin.live_chat.api.conversations.show
     *
     * @param \Illuminate\Http\Request $request
     * @param \Webkul\LiveChat\Models\Conversation $conversation (Route Model Binding)
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        // Kiểm tra quyền truy cập (ví dụ: agent được gán hoặc admin)
        $agent = Auth::user();
        // if ($conversation->user_id !== $agent->id && !$agent->hasRole('admin')) {
        //     return response()->json(['error' => 'Unauthorized'], 403);
        // }

        // Eager load các quan hệ cần thiết trước khi trả về resource
        // Repository nên xử lý việc này hoặc load ở đây
        $conversation->loadMissing(['messages.agent:id,name', 'agent:id,name', 'channel:id,name']);

        // Trả về single resource
        return response()->json(new ConversationResource($conversation));
    }

    /**
     * Đóng một cuộc hội thoại.
     * Xử lý cho route: POST /admin/api/live-chat/conversations/{conversation}/close
     * Route name: admin.live_chat.api.conversations.close
     *
     * @param \Illuminate\Http\Request $request
     * @param \Webkul\LiveChat\Models\Conversation $conversation
     * @return \Illuminate\Http\JsonResponse
     */
    public function close(Request $request, Conversation $conversation): JsonResponse
    {
        // Kiểm tra quyền
        $agent = Auth::user();
        // if ($conversation->user_id !== $agent->id && !$agent->hasRole('admin')) {
        //      return response()->json(['error' => 'Unauthorized'], 403);
        // }

        if ($conversation->status === Conversation::STATUS_CLOSED) {
             return response()->json(['message' => 'Conversation already closed.'], 400);
        }

        $closedConversation = $this->conversationRepository->closeConversation($conversation, 'agent'); // Ghi nhận agent đóng

        return response()->json([
            'success' => true,
            'message' => __('livechat::app.chat_interface.chat_ended_successfully'), // Lấy từ lang file
            'data'    => new ConversationResource($closedConversation) // Trả về trạng thái mới
        ]);
    }

    /**
     * (Tùy chọn) Gán một cuộc hội thoại cho agent hiện tại hoặc agent khác.
     * Xử lý cho route: POST /admin/api/live-chat/conversations/{conversation}/assign
     * Route name: admin.live_chat.api.conversations.assign
     *
     * @param \Illuminate\Http\Request $request
     * @param \Webkul\LiveChat\Models\Conversation $conversation
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignAgent(Request $request, Conversation $conversation): JsonResponse
    {
        $agentToAssignId = $request->input('agent_id', Auth::id()); // Gán cho chính mình nếu không chỉ định

        // Kiểm tra quyền của người thực hiện assign (ví dụ: admin hoặc agent hiện tại nếu đang pending)
        $currentAgent = Auth::user();
        // if (!$currentAgent->hasRole('admin') && $conversation->user_id !== null && $conversation->user_id !== $currentAgent->id) {
        //     return response()->json(['error' => 'Unauthorized to assign this conversation'], 403);
        // }

        // Kiểm tra agent được gán có tồn tại không (nếu cần)
        // $agentExists = \Webkul\User\Models\User::where('id', $agentToAssignId)->exists();
        // if (!$agentExists) {
        //     return response()->json(['error' => 'Target agent not found'], 404);
        // }

        $assignedConversation = $this->conversationRepository->assignAgent($conversation, $agentToAssignId);

        // Bắn event thông báo conversation đã được assign (nếu cần)
        // event(new ConversationAssigned($assignedConversation, $currentAgent));

        return response()->json([
            'success' => true,
            'message' => 'Conversation assigned successfully.',
            'data'    => new ConversationResource($assignedConversation)
        ]);
    }

    /**
     * (Tùy chọn) Đánh dấu/Bỏ đánh dấu cuộc hội thoại cho việc huấn luyện bot.
     * Xử lý cho route: POST /admin/api/live-chat/conversations/{conversation}/mark-training
     * Route name: admin.live_chat.api.conversations.mark_training
     *
     * @param \Illuminate\Http\Request $request
     * @param \Webkul\LiveChat\Models\Conversation $conversation
     * @return \Illuminate\Http\JsonResponse
     */
    public function markForTraining(Request $request, Conversation $conversation): JsonResponse
    {
        // Kiểm tra quyền (ví dụ: chỉ admin)
        // if (!Auth::user()->hasRole('admin')) {
        //     return response()->json(['error' => 'Unauthorized'], 403);
        // }

        $mark = filter_var($request->input('mark', true), FILTER_VALIDATE_BOOLEAN); // Lấy trạng thái muốn đặt

        $updatedConversation = $this->conversationRepository->markForTraining($conversation, $mark);

        return response()->json([
            'success' => true,
            'message' => $mark ? 'Conversation marked for training.' : 'Conversation unmarked for training.',
            'data'    => new ConversationResource($updatedConversation)
        ]);
    }
}
