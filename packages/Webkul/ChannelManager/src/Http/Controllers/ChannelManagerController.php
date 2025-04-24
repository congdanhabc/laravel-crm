<?php

namespace Webkul\ChannelManager\Http\Controllers; // Đảm bảo đúng namespace

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Webkul\Admin\Http\Controllers\Controller; // Kế thừa Controller admin gốc
use Webkul\ChannelManager\Repositories\ChannelRepository; // <<== SỬ DỤNG REPOSITORY
use Webkul\ChannelManager\DataGrids\ChannelManagerDataGrid; // Sử dụng DataGrid


// Optional: Sử dụng Form Request để validate
// use Webkul\ChannelManager\Http\Requests\ChannelFormRequest;

class ChannelManagerController extends Controller
{
    /**
     * Chứa instance của ChannelRepository.
     *
     * @var \Webkul\ChannelManager\Repositories\ChannelRepository
     */
    protected $channelRepository;
    protected $rules = [
        'name'   => 'required|string|max:255',
        'type'   => 'required|string|in:facebook,channex',
        'status' => 'sometimes|boolean',
        'credentials.fb_page_id'          => 'required_if:type,facebook|nullable|string|max:255',
        'credentials.fb_page_access_token'=> 'required_if:type,facebook|nullable|string',
        'credentials.fb_app_secret'       => 'required_if:type,facebook|nullable|string',
    ];

    /**
     * Khởi tạo Controller, inject Repository.
     *
     * @param \Webkul\ChannelManager\Repositories\ChannelRepository $channelRepository
     */
    public function __construct(ChannelRepository $channelRepository) // <<== INJECT REPOSITORY
    {
        $this->channelRepository = $channelRepository;

        // Optional: Set middleware phân quyền (ACL) nếu cần
        // request()->request->add(['entity_type' => 'channels']); // Ví dụ cho ACL của Krayin
    }

    /**
     * Hiển thị trang danh sách Channel.
     *
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse
     */
    public function index()
    {
    if (request()->ajax()) {
        try {
            // 1. Resolve (lấy) instance của DataGrid class
            $dataGrid = app(ChannelManagerDataGrid::class); // Đảm bảo namespace đúng

            // 2. Gọi phương thức getResponse() để lấy JsonResponse
            return $dataGrid->process();

        } catch (\Exception $e) {
            Log::error("Error generating DataGrid JSON response: " . $e->getMessage());
            // Trả về lỗi JSON để Vue component biết
            return response()->json(['message' => 'Error loading data.'], 500);
        }
    }

    // Trả về view cho request thông thường
    return view('channelmanager::index');
    }

    /**
     * Hiển thị form tạo mới Channel.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function create()
    {
        return view('channelmanager::create');
    }

    public function store(Request $request)
    {
        Log::debug('--- Channel Store Action Started ---');
        Log::debug('Request Data:', $request->all());

        try {
            $validatedData = $this->validate($request, $this->rules);
            Log::debug('Basic Validation Passed. Validated Data:', $validatedData);

            $validatedData['status'] = $request->input('status', 1);
            $validatedData['credentials'] = $request->input('credentials', []);

            if ($validatedData['type'] === 'facebook') {
                $pageId = $validatedData['credentials']['fb_page_id'] ?? null;
                $accessToken = $validatedData['credentials']['fb_page_access_token'] ?? null;
                if ($pageId && $accessToken) {
                    Log::debug('Attempting to verify Facebook credentials...');
                    $isValid = $this->verifyFacebookCredentials($pageId, $accessToken);
                    Log::debug('Facebook Verification Result:', [$isValid]); // Xem kết quả trả về
                    if (!$isValid) {
                        Log::warning('Facebook Validation Failed, throwing exception.');
                        throw ValidationException::withMessages([
                            'credentials.fb_page_access_token' => __('channelmanager::app.validation.invalid_fb_credentials')
                        ]);
                    }
                    Log::debug('Facebook Credentials Verified.');
                }
            }

            Log::debug('Attempting to create channel in repository...');
            $this->channelRepository->create($validatedData);
            Log::debug('Channel created successfully in repository.');

            session()->flash('success', __('channelmanager::app.create-success'));
            Log::debug('Redirecting to index page...');
            return redirect()->route('admin.channelmanager.index');

        } catch (ValidationException $e) {
            Log::warning('Validation Exception caught:', $e->errors());
            // Laravel sẽ tự xử lý redirect về form với lỗi
            // Không cần return ở đây nếu muốn Laravel tự xử lý
            // return redirect()->back()->withErrors($e->errors())->withInput();
            throw $e; // Ném lại để Laravel xử lý chuẩn
        } catch (\Exception $e) {
            Log::error('Generic Exception caught in store action: ' . $e->getMessage());
            session()->flash('error', __('admin::app.error.something-went-wrong'));
            // Redirect về trang index hoặc back tùy logic
            return redirect()->back()->withInput();
        }
    }

    /**
     * Helper method để kiểm tra thông tin Facebook Page ID và Access Token.
     *
     * @param string $pageId
     * @param string $accessToken
     * @return bool True nếu hợp lệ, False nếu không.
     */
    protected function verifyFacebookCredentials(string $pageId, string $accessToken): bool
    {
        $graphUrl = "https://graph.facebook.com/v22.0";
        $endpoint = '/me';

        try {
            Log::info("Verifying Facebook credentials via /me endpoint for expected Page ID: {$pageId}");

            $response = Http::timeout(15) // Tăng timeout một chút nếu cần
                          ->get("{$graphUrl}{$endpoint}", [
                              'fields'       => 'id,name', // Yêu cầu các trường cần thiết để xác nhận
                              'access_token' => $accessToken,
                          ]);

            // Log chi tiết để debug
            Log::debug('Facebook API Verification Request URL:', ["{$graphUrl}{$endpoint}?fields=id,name&access_token=TOKEN_HIDDEN"]); // Giấu token trong log URL
            Log::debug('Facebook API Verification Response Status:', [$response->status()]);
            // Chỉ log body nếu có lỗi hoặc khi debug sâu, vì nó có thể chứa thông tin page
            if (!$response->successful() || config('app.debug')) {
                 Log::debug('Facebook API Verification Response Body:', [$response->body()]);
            }


            if ($response->successful()) {
                $data = $response->json();

                // Kiểm tra lỗi trong JSON response của Graph API
                if (isset($data['error'])) {
                     Log::error('Facebook Graph API returned an error during verification:', $data['error']);
                     return false;
                }

                // Kiểm tra xem ID trả về có tồn tại và khớp với Page ID đã nhập không
                if (isset($data['id']) && $data['id'] === $pageId) {
                    Log::info("Facebook token verified successfully. API returned matching Page ID: {$data['id']} and Name: " . ($data['name'] ?? 'N/A'));
                    return true; // Xác thực thành công!
                } else {
                    Log::warning('Facebook token seems valid, but the returned ID does not match the provided Page ID.', [
                        'expected_page_id' => $pageId,
                        'returned_page_id' => $data['id'] ?? 'Not Found',
                        'returned_page_name' => $data['name'] ?? 'Not Found',
                    ]);
                    return false; // ID không khớp
                }
            } else {
                // Lỗi HTTP (ví dụ: 400 Bad Request nếu token sai định dạng, 401 Unauthorized nếu token hết hạn/sai)
                Log::error('Facebook API request failed with HTTP status during verification: ' . $response->status());
                return false;
            }

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Could not connect to Facebook Graph API during verification: ' . $e->getMessage());
            return false; // Lỗi kết nối
        } catch (\Exception $e) {
            Log::error('An unexpected error occurred during Facebook credential verification: ' . $e->getMessage());
            return false; // Lỗi khác
        }
    }

    // --- Action update() cũng cần thêm logic kiểm tra tương tự ---
    public function update(Request $request, $id) // Nên thay bằng FormRequest
    {
        // ... (lấy channel cũ, validate các trường khác) ...

        $validatedData = $this->validate($request, $this->rules); // Các $rules tương tự store
        $validatedData['status'] = $request->input('status', 0);
        $validatedData['credentials'] = $request->input('credentials', []);

        // === KIỂM TRA FACEBOOK NẾU TYPE LÀ facebook ===
        if ($validatedData['type'] === 'facebook') {
            $pageId = $validatedData['credentials']['fb_page_id'] ?? null;
            $accessToken = $validatedData['credentials']['fb_page_access_token'] ?? null;

            // Chỉ kiểm tra nếu access token được cung cấp (người dùng có thể chỉ sửa tên)
            // Hoặc bạn có thể luôn kiểm tra nếu muốn đảm bảo token vẫn hợp lệ
            if ($pageId && $accessToken) { // Cần cả hai để kiểm tra
                // Lấy channel hiện tại để so sánh xem token có thay đổi không (tùy chọn)
                // $existingChannel = $this->channelRepository->find($id);
                // $existingToken = $existingChannel->credentials['fb_page_access_token'] ?? null;
                // if ($accessToken !== $existingToken) { // Chỉ kiểm tra nếu token mới
                    $isValid = $this->verifyFacebookCredentials($pageId, $accessToken);
                    if (!$isValid) {
                        throw ValidationException::withMessages([
                            'credentials.fb_page_access_token' => __('channelmanager::app.validation.invalid_fb_credentials')
                        ]);
                    }
                    Log::info("Facebook credentials re-verified successfully for Page ID: {$pageId} during update.");
                // }
            } else if ($pageId && !$accessToken) {
                // Nếu có Page ID nhưng không có token mới -> giữ lại token cũ? Cần xác định logic
                // Lấy token cũ từ DB và gán lại vào $validatedData nếu bạn không muốn xóa nó
                // $existingChannel = $this->channelRepository->find($id);
                // $validatedData['credentials']['fb_page_access_token'] = $existingChannel->credentials['fb_page_access_token'] ?? null;
            }
        }
        // =================================================

        // Lưu dữ liệu
        try {
            $this->channelRepository->update($validatedData, $id);
            session()->flash('success', __('channelmanager::app.update-success'));
        } catch (\Exception $e) {
            // Xử lý lỗi (bao gồm cả ValidationException từ check FB)
            if ($e instanceof ValidationException) {
                // ValidationException đã ném lỗi, chỉ cần redirect về
                return redirect()->back()->withErrors($e->errors())->withInput();
            }
            session()->flash('error', __('admin::app.error.something-went-wrong'));
            Log::error('Channel Update Error: ' . $e->getMessage(), ['id' => $id, 'data' => $validatedData, 'exception' => $e]);
        }

        return redirect()->route('admin.channelmanager.index');
    }
    public function destroy($id) // <<<=== THÊM PHƯƠNG THỨC NÀY
    {
        // Tìm channel trước khi xóa để đảm bảo nó tồn tại và tránh lỗi không mong muốn
        $channel = $this->channelRepository->find($id); // Dùng find() thay vì findOrFail() để xử lý linh hoạt hơn

        if (!$channel) {
            // Trả về lỗi nếu không tìm thấy
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['message' => __('admin::app.error.not-found', ['resource' => __('channelmanager::app.channel')])], 404);
            }
            session()->flash('error', __('admin::app.error.not-found', ['resource' => __('channelmanager::app.channel')]));
            return redirect()->back();
        }

        try {
            // Gọi phương thức delete của Repository
            $this->channelRepository->delete($id);

            // Trả về JSON nếu là AJAX request (từ DataGrid)
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'message' => __('channelmanager::app.delete-success'), // Key dịch của bạn
                ], 200);
            }

            session()->flash('success', __('channelmanager::app.delete-success'));

        } catch (\Exception $e) {
            Log::error('Channel Delete Error: ' . $e->getMessage(), ['id' => $id, 'exception' => $e]);

            // Trả về JSON lỗi nếu là AJAX request
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'message' => __('admin::app.error.something-went-wrong'),
                ], 500);
            }

            session()->flash('error', __('admin::app.error.something-went-wrong'));
        }

        // Chuyển hướng về trang index nếu là request thường (ít khi xảy ra với delete từ DataGrid)
        return redirect()->route('admin.channelmanager.index');
    }
}
