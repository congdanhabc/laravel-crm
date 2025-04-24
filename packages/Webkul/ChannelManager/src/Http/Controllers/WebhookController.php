<?php

namespace Webkul\ChannelManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt; // Quan trọng nếu mã hóa credentials
use Illuminate\Routing\Controller;
use Webkul\Contact\Repositories\PersonRepository;
use Webkul\Lead\Repositories\LeadRepository;
use Webkul\Activity\Repositories\ActivityRepository;
use Webkul\User\Repositories\UserRepository;
use Webkul\ChannelManager\Repositories\ChannelRepository; // Quan trọng
// use Webkul\ChannelManager\Jobs\ProcessMessengerMessage;

class WebhookController extends Controller
{
    /**
     * Global Verify Token (chỉ dùng cho xác thực ban đầu).
     * @var string
     */
    protected $globalVerifyToken;

    // Bỏ App Secret khỏi đây vì sẽ lấy theo kênh

    protected $personRepository;
    protected $leadRepository;
    protected $activityRepository;
    protected $userRepository;
    protected $channelRepository; // Repository để tìm kênh

    public function __construct(
        PersonRepository $personRepository,
        LeadRepository $leadRepository,
        ActivityRepository $activityRepository,
        UserRepository $userRepository,
        ChannelRepository $channelRepository // Inject ChannelRepository
    ) {
        // Chỉ lấy Global Verify Token từ config
        $this->globalVerifyToken = config('services.facebook.verify_token');

        $this->personRepository = $personRepository;
        $this->leadRepository = $leadRepository;
        $this->activityRepository = $activityRepository;
        $this->userRepository = $userRepository;
        $this->channelRepository = $channelRepository; // Gán repository

        if (empty($this->globalVerifyToken)) {
            Log::error('Global Facebook Verify Token is not configured in config/services.php or .env file.');
        }
        // Không cần kiểm tra App Secret ở đây nữa
    }

    /**
     * Xác thực Webhook với Facebook (Dùng Global Verify Token).
     */
    public function verify(Request $request)
    {
        $mode = $request->input('hub_mode');
        $token = $request->input('hub_verify_token'); // Token Facebook gửi đến
        $challenge = $request->input('hub_challenge');

        Log::info('Webhook Verification Attempt (Using Global Token):', $request->all());
        Log::info('Configured Global Verify Token:', [$this->globalVerifyToken]);

        if ($mode && $token) {
            // So sánh với Global Verify Token
            if ($mode === 'subscribe' && $token === $this->globalVerifyToken) {
                Log::info('Webhook Verified (Global Token)! Responding with challenge.');
                return response($challenge, 200);
            } else {
                Log::warning('Webhook Verification Failed: Invalid Mode or Global Token mismatch.', [
                    'received_token' => $token,
                    'expected_token' => $this->globalVerifyToken,
                    'mode' => $mode
                ]);
                return response('Forbidden', 403);
            }
        }
        Log::warning('Webhook Verification Failed: Missing hub.mode or hub.verify_token parameter.');
        return response('Bad Request', 400);
    }

    /**
     * Xử lý sự kiện từ Facebook (Dùng App Secret theo kênh).
     */
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $data = json_decode($payload, true);
        $signature = $request->header('X-Hub-Signature-256');

        Log::info('Messenger Webhook Event Received:', $data);

        // Kiểm tra cấu trúc cơ bản và object là 'page'
        if (!isset($data['object']) || $data['object'] !== 'page' || !isset($data['entry']) || !is_array($data['entry'])) {
             Log::warning('Received webhook data is not for a page object or has unexpected structure.');
             return response('Bad Request - Invalid Payload Structure', 400);
        }

        // Duyệt qua từng entry (thường chỉ có 1 entry cho mỗi request)
        foreach ($data['entry'] as $entry) {
            $pageId = $entry['id'] ?? null; // Lấy Page ID từ entry

            if (!$pageId) {
                Log::warning('Webhook entry missing Page ID.');
                continue; // Bỏ qua entry này nếu không có Page ID
            }

            // === TÌM KÊNH VÀ APP SECRET TƯƠNG ỨNG ===
            $channel = $this->findChannelByPageId($pageId);

            if (!$channel) {
                Log::warning("Received webhook for unknown or inactive Page ID: {$pageId}. Ignoring entry.");
                continue; // Bỏ qua nếu không tìm thấy kênh hoặc kênh không hoạt động
            }

            // Lấy App Secret từ credentials của kênh tìm được
            // Giả sử bạn lưu trong credentials['fb_app_secret']
            // !! Quan trọng: Xử lý giải mã nếu bạn đã mã hóa credentials !!
            $channelCredentials = $channel->credentials; // Đã được cast thành array bởi Model
            // $channelCredentials = json_decode(Crypt::decryptString($channel->encrypted_credentials), true); // Ví dụ nếu mã hóa

            $channelAppSecret = $channelCredentials['fb_app_secret'] ?? null;

            if (!$channelAppSecret) {
                Log::error("App Secret not configured for Channel ID: {$channel->id} (Page ID: {$pageId}). Cannot verify signature.");
                continue; // Bỏ qua entry này
            }
            // =========================================

            // 1. Xác thực chữ ký VỚI App Secret của kênh này
            if (!$signature) {
                Log::warning("Webhook for Page ID {$pageId}: Missing X-Hub-Signature-256 header.");
                continue; // Bỏ qua entry này
            }
            if (!str_starts_with($signature, 'sha256=')) {
                 Log::warning("Webhook for Page ID {$pageId}: Invalid signature format.");
                 continue;
            }
            $hash = substr($signature, 7);
            $expectedHash = hash_hmac('sha256', $payload, $channelAppSecret); // Dùng channelAppSecret

            if (!hash_equals($hash, $expectedHash)) {
                Log::warning("Webhook for Page ID {$pageId}: Invalid Signature.", ['received_hash' => $hash]);
                continue; // Chữ ký không hợp lệ cho kênh này
            }

            // --- Chữ ký hợp lệ, xử lý các event messaging ---
            Log::info("Webhook signature verified for Page ID: {$pageId} (Channel ID: {$channel->id})");
            if (!isset($entry['messaging']) || !is_array($entry['messaging'])) {
                continue;
            }

            foreach ($entry['messaging'] as $event) {
                if (isset($event['message']['text']) && !isset($event['message']['is_echo'])) {
                    $senderId = $event['sender']['id'];
                    $messageText = $event['message']['text'];
                    $timestamp = isset($event['timestamp']) ? floor($event['timestamp'] / 1000) : time();

                    try {
                        // Xử lý trực tiếp hoặc dispatch Job
                        $this->processIncomingMessage($senderId, $messageText, $timestamp, $event, $channel); // Truyền cả $channel nếu cần
                         // ProcessMessengerMessage::dispatch($senderId, $messageText, $timestamp, $event, $channel->id); // Truyền channel ID vào Job
                         Log::info("Processing/Dispatched message for PSID: {$senderId} via Page ID: {$pageId}");

                    } catch (\Exception $e) {
                        Log::error("Error processing/dispatching message for PSID {$senderId} (Page ID: {$pageId}): " . $e->getMessage(), [
                            'exception' => $e
                        ]);
                    }
                } else {
                    Log::info("Ignored non-text or echo message event for Page ID: {$pageId}.", ['event_type' => array_keys($event)[0] ?? 'unknown']);
                }
            }
        } // Kết thúc vòng lặp entry

        // Luôn trả về 200 OK
        return response('EVENT_RECEIVED', 200);
    }

    /**
     * Xử lý logic nghiệp vụ cho tin nhắn đến.
     * (Có thể nhận thêm $channel nếu cần thông tin cấu hình kênh)
     *
     * @param string $senderId
     * @param string $messageText
     * @param int $timestamp
     * @param array $fullEvent
     * @param \Webkul\ChannelManager\Contracts\Channel|null $channel Kênh liên quan (tùy chọn)
     * @return void
     */
    protected function processIncomingMessage(string $senderId, string $messageText, int $timestamp, array $fullEvent = [], ?\Webkul\ChannelManager\Contracts\Channel $channel = null): void
    {
        // Logic tìm/tạo Contact và tạo Activity giữ nguyên như trước
        Log::info("Processing message from PSID: {$senderId}, Text: \"{$messageText}\" (Channel: " . ($channel->name ?? 'N/A') . ")");

        $contact = $this->findOrCreateContact($senderId, $messageText);
        if (!$contact) { /* ... log error and return ... */ return; }

        $personId = ($contact instanceof \Webkul\Contact\Contracts\Person) ? $contact->id : null;
        $leadId = ($contact instanceof \Webkul\Lead\Contracts\Lead) ? $contact->id : null;

        $adminUser = $this->userRepository->first();
        $adminUserId = $adminUser ? $adminUser->id : null;

        try {
            $activityData = [
                'title'         => 'Messenger Message Received',
                'type'          => 'message',
                'comment'       => $messageText,
                'additional'    => json_encode([
                    'source'    => 'messenger',
                    'psid'      => $senderId,
                    'timestamp' => $timestamp,
                    'channel_id'=> $channel->id ?? null, // Lưu ID kênh nếu có
                    'page_id'   => $channel->credentials['fb_page_id'] ?? null // Lưu Page ID nếu có
                ]),
                'schedule_from' => null,
                'schedule_to'   => null,
                'user_id'       => $adminUserId,
                'person_id'     => $personId,
                'lead_id'       => $leadId,
            ];
            $this->activityRepository->create($activityData);
             Log::info("Successfully created activity for " . ($personId ? "Person ID: {$personId}" : "Lead ID: {$leadId}"));
        } catch (\Exception $e) {
             Log::error("Error creating activity for PSID {$senderId}: " . $e->getMessage(), ['exception' => $e]);
        }
    }

    /**
     * Tìm kiếm Person/Lead dựa trên PSID. Nếu không tìm thấy, tạo Lead mới.
     * (Giữ nguyên logic này)
     */
    protected function findOrCreateContact(string $psid, string $messageText)
    {
        // ... (logic tìm Person, tìm Lead, tạo Lead mới như trước) ...
        // Đảm bảo logic tạo mới có lưu PSID vào custom field/cột
        $person = $this->personRepository->findOneWhere(['messenger_psid' => $psid]);
        if ($person) return $person;

        $lead = $this->leadRepository->findOneWhere(['messenger_psid' => $psid]); // Giả định Lead cũng có cột này
        if ($lead) return $lead;

        // Tạo Lead mới...
        try {
            $adminUser = $this->userRepository->first();
            $newLead = $this->leadRepository->create([
                'title'            => "Lead from Messenger ($psid)",
                'description'      => substr($messageText, 0, 200) . (strlen($messageText) > 200 ? '...' : ''),
                'lead_value'       => 0,
                'user_id'          => $adminUser->id ?? null,
                'person' => [
                    'name' => "Messenger User ($psid)",
                    'messenger_psid' => $psid, // Lưu PSID
                ],
            ]);
            Log::info("Created new Lead ID: {$newLead->id} for PSID: {$psid}");
            return $newLead;
        } catch (\Exception $e) {
            Log::error("Failed to create new Lead for PSID {$psid}: " . $e->getMessage(), ['exception' => $e]);
            return null;
        }
    }

    /**
     * Helper: Tìm Channel đang active dựa trên Page ID từ credentials.
     *
     * @param string $pageId
     * @return \Webkul\ChannelManager\Contracts\Channel|null
     */
    protected function findChannelByPageId(string $pageId): ?\Webkul\ChannelManager\Contracts\Channel
    {
        // Cách truy vấn JSON phụ thuộc vào CSDL và phiên bản Laravel/Eloquent
        // Ví dụ cho MySQL 5.7+ / PostgreSQL:
        try {
            return $this->channelRepository->getModel()
                ->where('type', 'facebook') // Chỉ tìm kênh messenger
                ->where('status', 1) // Chỉ kênh đang active
                ->where('credentials->fb_page_id', $pageId) // Truy vấn JSON
                ->first();
        } catch (\Exception $e) {
            // Có thể lỗi nếu driver DB không hỗ trợ JSON query hoặc cú pháp sai
            Log::error("Database error finding channel by Page ID {$pageId}: " . $e->getMessage());
            // Fallback hoặc cách truy vấn khác nếu cần
            // Ví dụ lấy tất cả rồi lọc bằng PHP (không hiệu quả):
            // $channels = $this->channelRepository->findWhere(['type' => 'messenger', 'status' => 1]);
            // foreach ($channels as $channel) {
            //     if (($channel->credentials['fb_page_id'] ?? null) === $pageId) {
            //         return $channel;
            //     }
            // }
            return null;
        }
    }
}
