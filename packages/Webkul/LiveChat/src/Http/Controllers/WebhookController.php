<?php

namespace Webkul\LiveChat\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log; // Để ghi log debug
use Webkul\LiveChat\Repositories\ConversationRepository;
use Webkul\LiveChat\Repositories\MessageRepository;
use Webkul\LiveChat\Repositories\ChannelRepository;
use Webkul\LiveChat\Models\Conversation; // Import model
use Webkul\LiveChat\Models\Message;   // Import model
use Webkul\LiveChat\Events\NewMessageReceived;

class WebhookController extends Controller // Kế thừa Controller gốc của package nếu có, hoặc của Laravel
{
    public function __construct(
        protected ConversationRepository $conversationRepository,
        protected MessageRepository $messageRepository,
        protected ChannelRepository $channelRepository
    ) {
    }

    /**
     * Xác thực Webhook (Ví dụ cho Facebook Messenger).
     */
    public function verify(Request $request)
    {
        $mode = $request->input('hub_mode');
        $token = $request->input('hub_verify_token');
        $challenge = $request->input('hub_challenge');
        Log::info('Webhook Verification Attempt:', $request->all());

        // Lấy verify token đã cấu hình cho kênh Facebook từ CSDL hoặc config
        $channel = $this->channelRepository->findOneByField('type', 'facebook'); // Hoặc tìm theo ID cụ thể
        $configuredVerifyToken = config('services.facebook.verify_token');

        if ($mode && $token) {
            if ($mode === 'subscribe' && $token === $configuredVerifyToken) {
                Log::info('Webhook verified successfully!');
                return response($challenge, 200) // Chỉ có biến $challenge
                   ->header('Content-Type', 'text/plain'); // Set Content-Type là text/plain
            } else {
                Log::error('Webhook verification failed: Invalid token or mode.');
                return response('Forbidden', 403);
            }
        }
        Log::warning('Webhook verification attempt with missing parameters.');
        return response('Forbidden', 403);
    }

    /**
     * Xử lý sự kiện từ Webhook (Ví dụ cho Facebook Messenger).
     */
    public function handle(Request $request)
    {
        $payload = $request->all();
        Log::info('Received webhook payload: ', $payload);

        // 1. Xác thực chữ ký (RẤT QUAN TRỌNG - bỏ qua nếu không có)
        // $signature = $request->header('X-Hub-Signature-256');
        // $appSecret = config('services.facebook.app_secret'); // Hoặc lấy từ config của Channel
        // if (!$this->isValidSignature($signature, $request->getContent(), $appSecret)) {
        //     Log.error('Invalid webhook signature.');
        //     return response('Invalid signature', 403);
        // }

        // 2. Xử lý payload từ Facebook
        if (isset($payload['object']) && $payload['object'] === 'page') {
            foreach ($payload['entry'] as $entry) {
                foreach ($entry['messaging'] as $event) {
                    if (isset($event['message']) && !isset($event['message']['is_echo'])) { // Tin nhắn mới, không phải echo từ page
                        $senderPSID = $event['sender']['id']; // Page-Scoped ID của người gửi
                        $recipientPSID = $event['recipient']['id']; // ID của Page nhận tin nhắn
                        $messageText = $event['message']['text'] ?? null; // Nội dung tin nhắn
                        $messageId = $event['message']['mid'] ?? null; // ID tin nhắn của Facebook
                        // Có thể có attachments (hình ảnh, file, ...)

                        if ($messageText) {
                            $this->processIncomingMessage($senderPSID, $recipientPSID, $messageText, $messageId, 'facebook');
                        }
                    } elseif (isset($event['delivery'])) {
                        // Xử lý sự kiện tin nhắn đã được giao (delivery)
                        Log::info('Message delivery event: ', $event['delivery']);
                    } elseif (isset($event['read'])) {
                        // Xử lý sự kiện tin nhắn đã được đọc (read)
                        Log::info('Message read event: ', $event['read']);
                    } else {
                        Log::info('Received unhandled webhook event type: ', $event);
                    }
                }
            }
        }

        return response('EVENT_RECEIVED', 200);
    }

    /**
     * Xử lý tin nhắn đến từ một kênh cụ thể.
     */
    protected function processIncomingMessage(string $visitorExternalId, string $channelExternalId, string $messageContent, ?string $externalMessageId, string $channelType)
    {
        // 2a. Tìm kênh dựa trên channelExternalId (ví dụ: Page ID của Facebook)
        // === SỬA LẠI CÁCH TÌM KIẾM ===
        Log::debug("Finding channel by Type: {$channelType} and External ID (Page ID): {$channelExternalId}");
        try {
            $channel = $this->channelRepository->getModel() // Lấy Eloquent Builder
                          ->where('type', $channelType)
                          ->where('status', 1) // Luôn kiểm tra status
                          // Đảm bảo cách truy vấn JSON đúng
                          ->whereJsonContains('config->fb_page_id', $channelExternalId)
                          // Hoặc ->where('config->page_id', $channelExternalId)
                          ->first(); // <<=== Dùng first() để lấy một bản ghi

            if ($channel) {
                 Log::debug("Found matching channel:", ['id' => $channel->id, 'name' => $channel->name]);
            }

        } catch (\Exception $e) {
             Log::error("Error querying channel in processIncomingMessage: " . $e->getMessage());
             $channel = null; // Đặt channel thành null nếu có lỗi DB
        }
        // ============================

        if (!$channel) {
            // Log lỗi và return nếu không tìm thấy kênh
            Log::error("Channel not found for external ID: {$channelExternalId} and type: {$channelType}");
            return; // Dừng xử lý nếu không có kênh hợp lệ
        }


        // 2b. Tìm hoặc tạo cuộc hội thoại
        // visitorExternalId là ID của người dùng trên nền tảng đó (ví dụ: PSID của Facebook)
        // Chúng ta cần một cách để liên kết visitorExternalId với một visitor_id nội bộ nếu cần,
        // hoặc đơn giản là dùng visitorExternalId làm visitor_id trong bảng conversations.
        // Ở đây, giả sử visitor_id trong bảng conversations chính là external ID.
        $conversation = $this->conversationRepository->findActiveConversationByVisitor($visitorExternalId, $channel->id);

        if (!$conversation) {
            // Tạo cuộc hội thoại mới
            // Cần lấy thêm thông tin visitor nếu có (ví dụ: gọi API của Facebook để lấy tên)
            $visitorName = "Visitor {$visitorExternalId}"; // Tên tạm thời
            // $visitorProfile = $this->fetchVisitorProfileFromFacebook($visitorExternalId, $channel->config['page_access_token']);
            // if ($visitorProfile) $visitorName = $visitorProfile['first_name'] . ' ' . $visitorProfile['last_name'];

            $conversation = $this->conversationRepository->createConversation([
                'visitor_id'    => $visitorExternalId, // Hoặc một ID visitor nội bộ
                'visitor_name'  => $visitorName,
                'live_chat_channel_id'    => $channel->id,
                'status'        => Conversation::STATUS_PENDING, // Trạng thái chờ xử lý
                // Thêm các trường khác nếu cần
            ]);
            Log::info("New conversation created: {$conversation->id} for visitor {$visitorExternalId}");
        }

        // 2c. Lưu tin nhắn vào CSDL
        $message = $this->messageRepository->createMessage([
            'sender_type'   => Message::SENDER_VISITOR,
            'content'       => $messageContent,
            'live_chat_conversation_id' => $conversation->id,
            // 'external_id' => $externalMessageId, // Thêm cột này vào model & migration nếu muốn lưu ID gốc
            // 'metadata'    => ['platform_message_id' => $externalMessageId], // Hoặc lưu trong metadata
        ], $conversation, Conversation::ANSWERED_BY_PENDING); // Conversation được cập nhật là đang chờ trả lời

        Log::info("Message from visitor {$visitorExternalId} saved to conversation {$conversation->id}: {$messageContent}");

        // 2d. **QUAN TRỌNG:** Bắn sự kiện để thông báo cho client (giao diện admin)
        // rằng có tin nhắn mới hoặc hội thoại mới.
        // Event này sẽ được Pusher/WebSocket lắng nghe và cập nhật UI.
        // event(new NewMessageReceived($message, $conversation));
        // Hoặc
        broadcast(new NewMessageReceived($message, $conversation))->toOthers();

        // 2e. (Tương lai) Gọi Gemini để thử trả lời
        // if ($conversation->status === Conversation::STATUS_PENDING || $conversation->last_answered_by === Conversation::ANSWERED_BY_BOT) {
        //     // $botResponse = app(GeminiService::class)->getResponse($messageContent, $conversation);
        //     // if ($botResponse) {
        //     //     $this->messageRepository->createMessage([
        //     //         'sender_type' => Message::SENDER_BOT,
        //     //         'content'     => $botResponse['text'],
        //     //         'metadata'    => ['confidence' => $botResponse['confidence']],
        //     //     ], $conversation, Conversation::ANSWERED_BY_BOT);
        //     //     // Bắn sự kiện tin nhắn mới từ bot
        //     //     // event(new BotMessageSent(...));
        //     // } else {
        //     //     // Bot không trả lời được, đánh dấu cần người
        //     //     $this->conversationRepository->markHumanTakeoverRequired($conversation->id);
        //     //     // Bắn sự kiện cần agent
        //     // }
        // }
    }

    /**
     * (Tùy chọn) Xác thực chữ ký từ Facebook.
     */
    // protected function isValidSignature(?string $signature, string $payload, string $appSecret): bool
    // {
    //     if (!$signature) {
    //         return false;
    //     }
    //     [$algo, $hash] = explode('=', $signature, 2);
    //     if ($algo !== 'sha256') { // Facebook dùng sha256 từ 07/2020, trước đó là sha1
    //         // Hoặc sha1 nếu bạn dùng app cũ
    //         Log::warning("Webhook signature algorithm mismatch. Expected sha256, got {$algo}");
    //         return false;
    //     }
    //     $expectedHash = hash_hmac($algo, $payload, $appSecret);
    //     return hash_equals($expectedHash, $hash);
    // }

    /**
     * (Tùy chọn) Lấy thông tin người dùng từ Facebook.
     */
    // protected function fetchVisitorProfileFromFacebook(string $psid, string $pageToken)
    // {
    //     try {
    //         $client = new \GuzzleHttp\Client();
    //         $response = $client->get("https://graph.facebook.com/{$psid}", [
    //             'query' => [
    //                 'fields' => 'first_name,last_name,profile_pic',
    //                 'access_token' => $pageToken,
    //             ]
    //         ]);
    //         return json_decode($response->getBody()->getContents(), true);
    //     } catch (\Exception $e) {
    //         Log::error("Failed to fetch Facebook profile for PSID {$psid}: " . $e->getMessage());
    //         return null;
    //     }
    // }
}
