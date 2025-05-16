<?php

namespace Webkul\LiveChat\Http\Controllers; // Hoặc ...\Admin nếu file nằm trong Admin

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Webkul\Admin\Http\Controllers\Controller; // Kế thừa Controller admin
use Webkul\LiveChat\Repositories\ChannelRepository;
use Webkul\LiveChat\DataGrids\ChannelManagerDataGrid; // Đảm bảo namespace DataGrid đúng
use Webkul\LiveChat\Models\Channel; // Import Model để dùng Route Model Binding

class ChannelManagerController extends Controller // Giữ nguyên tên class
{
    /**
     * Chứa instance của ChannelRepository.
     *
     * @var \Webkul\LiveChat\Repositories\ChannelRepository
     */
    protected $channelRepository;

    /**
     * Quy tắc validation cơ bản.
     * Nên chuyển sang Form Request cho các rule phức tạp hơn.
     *
     * @var array
     */
    protected $rules = [
        'name'   => 'required|string|max:191',
        'type'   => 'required|string|in:facebook,channex', // Cập nhật các type hợp lệ
        'status' => 'sometimes|boolean',
        'credentials' => 'nullable|array',
        'credentials.fb_page_id'              => 'required_if:type,facebook|nullable|string|max:191',
        'credentials.fb_page_access_token'    => 'nullable|string', // Rule cơ bản cho update
        'credentials.fb_app_secret'           => 'nullable|string', // Thêm rule cho app_secret nếu cần
        // Thêm config cho 'web' nếu có, ví dụ:
        // 'credentials.web.widget_color' => 'required_if:type,web|nullable|string',
    ];

    /**
     * Khởi tạo Controller, inject Repository.
     *
     * @param \Webkul\LiveChat\Repositories\ChannelRepository $channelRepository
     */
    public function __construct(ChannelRepository $channelRepository)
    {
        $this->channelRepository = $channelRepository;
        // Middleware ACL thường được xử lý ở route, nhưng có thể thêm ở đây nếu cần
    }

    /**
     * Hiển thị trang danh sách Channel.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function index()
    {
        if (!bouncer()->hasPermission('live_chat.channel_manager.view')) {
            abort(403, 'This action is unauthorized.');
        }

        if (request()->ajax()) {
            try {
                return app(ChannelManagerDataGrid::class)->toJson();
            } catch (\Exception $e) {
                Log::error("DataGrid Error: " . $e->getMessage());
                return response()->json(['message' => 'Error loading data.'], 500);
            }
        }
        // Trả về view với namespace đúng
        return view('live_chat::channel_manager.index');
    }

    /**
     * Hiển thị form tạo mới Channel.
     *
     * @return \Illuminate\View\View
     */
    public function create(): View
    {
        if (!bouncer()->hasPermission('live_chat.channel_manager.create')) {
            abort(403, 'This action is unauthorized.');
        }
        return view('live_chat::channel_manager.create');
    }

    /**
     * Lưu kênh mới vào CSDL.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        if (!bouncer()->hasPermission('live_chat.channel_manager.create')) {
            abort(403, 'This action is unauthorized.');
        }

        // Điều chỉnh rules cho store (access token là bắt buộc khi tạo mới)
        $storeRules = $this->rules;
        $storeRules['credentials.fb_page_access_token'] = 'required_if:type,facebook|nullable|string';
        try {
            $validatedData = $this->validate($request, $storeRules);

            // Xử lý config dựa trên type
            $configData = $validatedData['credentials'] ?? [];

            $dataToCreate = [
                'name'   => $validatedData['name'],
                'type'   => $validatedData['type'],
                'status' => $request->input('status', 0) == 1, // Chuyển sang boolean
                'config' => $configData,
            ];


            // Xác thực Facebook nếu là type 'facebook'
            if ($dataToCreate['type'] === 'facebook') {
                if (! $this->verifyFacebookCredentials($configData['fb_page_id'] ?? null, $configData['fb_page_access_token'] ?? null)) {
                     throw ValidationException::withMessages([
                         // Dùng key chung hoặc key cụ thể hơn nếu cần
                         'credentials.fb_page_access_token' => trans('live_chat::app.channels.validation.invalid_fb_credentials')
                     ]);
                }
            }
            Event::dispatch('live_chat.channel.create.before');

            $channel = $this->channelRepository->create($dataToCreate);

            Event::dispatch('live_chat.channel.create.after', $channel);

            session()->flash('success', trans('live_chat::app.channels.create-success'));

            // Sử dụng tên route đã định nghĩa
            return redirect()->route('admin.live_chat.channel_manager.index');

        } catch (ValidationException $e) {
            // Laravel tự redirect về với lỗi và input cũ khi dùng $this->validate()
            // Chỉ log nếu cần debug thêm
            Log::warning('Channel Store Validation Failed:', $e->errors());
            // Ném lại lỗi để Laravel xử lý hoặc return redirect()->back()
             return redirect()->back()->withErrors($e->errors())->withInput();
            // throw $e; // Hoặc ném lại
        } catch (\Exception $e) {
            Log::error('Channel Store Error: ' . $e->getMessage(), ['exception' => $e]);
            session()->flash('error', trans('admin::app.error.something-went-wrong'));
            return redirect()->back()->withInput();
        }
    }

    /**
     * Hiển thị form chỉnh sửa kênh.
     *
     * @param \Webkul\LiveChat\Models\Channel $channel (Sử dụng Route Model Binding)
     * @return \Illuminate\View\View
     */
     public function edit(Channel $channel): View // <<=== SỬ DỤNG ROUTE MODEL BINDING
     {
         if (!bouncer()->hasPermission('live_chat.channel_manager.edit')) {
             abort(403, 'This action is unauthorized.');
         }
         // Model $channel đã được tự động tìm thấy
         return view('live_chat::channel_manager.edit', compact('channel'));
     }

    /**
     * Cập nhật thông tin kênh trong CSDL.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Webkul\LiveChat\Models\Channel $channel (Sử dụng Route Model Binding)
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Channel $channel): RedirectResponse // <<=== SỬ DỤNG ROUTE MODEL BINDING
    {
        if (!bouncer()->hasPermission('live_chat.channel_manager.edit')) {
            abort(403, 'This action is unauthorized.');
        }

        // Bỏ 'type' khỏi rules khi update
        $updateRules = $this->rules;
        unset($updateRules['type']);
        // Token không bắt buộc khi update
        $updateRules['credentials.fb_page_access_token'] = 'nullable|string';

        try {
             $validatedData = $this->validate($request, $updateRules);

             // Lấy config cũ làm nền
             $currentConfig = $channel->config ?? [];
             $newConfigInput = $validatedData['credentials'][$channel->type] ?? [];

             // Cập nhật config, xử lý token/secret
             $updatedConfig = $currentConfig;
             $newAccessToken = $newConfigInput['fb_page_access_token'] ?? null;

             // Chỉ cập nhật các trường config khác page_access_token trước
             foreach ($newConfigInput as $key => $value) {
                 if ($key !== 'fb_page_access_token') {
                     $updatedConfig[$key] = $value;
                 }
             }

             // Xác thực Facebook nếu có token mới hoặc page ID thay đổi
             $shouldVerifyFacebook = false;
             if ($channel->type === 'facebook') {
                 $currentPageId = $currentConfig['fb_page_id'] ?? null;
                 $newPageId = $newConfigInput['fb_page_id'] ?? null;
                 if ($newAccessToken) { // Nếu người dùng nhập token mới
                     $shouldVerifyFacebook = true;
                     $updatedConfig['fb_page_access_token'] = $newAccessToken; // Cập nhật token mới vào config
                 } elseif ($newPageId !== $currentPageId && $currentPageId !== null) {
                     // Nếu Page ID thay đổi nhưng không nhập token mới -> Bắt buộc nhập lại token
                      throw ValidationException::withMessages([
                         'credentials.fb_page_access_token' => 'Please re-enter the Access Token when changing the Page ID.'
                     ]);
                 } elseif (!$newAccessToken && $newPageId === $currentPageId) {
                     // Không nhập token mới, Page ID không đổi -> Giữ token cũ (đã có trong $updatedConfig)
                     $newAccessToken = $currentConfig['fb_page_access_token'] ?? null; // Dùng token cũ để verify nếu cần
                     if ($newPageId) $shouldVerifyFacebook = true; // Verify lại với token cũ nếu có Page ID
                 }

                 if ($shouldVerifyFacebook) {
                      if (! $this->verifyFacebookCredentials($updatedConfig['fb_page_id'] ?? null, $newAccessToken)) {
                         throw ValidationException::withMessages([
                             'credentials.fb_page_access_token' => trans('live_chat::app.channels.validation.invalid_fb_credentials')
                         ]);
                     }
                 }
             }

            $dataToUpdate = [
                'name'   => $validatedData['name'],
                'status' => $request->input('status', 0) == 1,
                'config' => $updatedConfig,
                 // Không cập nhật 'type'
            ];

            Event::dispatch('live_chat.channel.update.before', $channel->id);

            $updatedChannel = $this->channelRepository->update($dataToUpdate, $channel->id);

            Event::dispatch('live_chat.channel.update.after', $updatedChannel);

            session()->flash('success', trans('live_chat::app.channels.update-success'));

            return redirect()->route('admin.live_chat.channel_manager.index');

        } catch (ValidationException $e) {
             Log::warning('Channel Update Validation Failed:', $e->errors());
             return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Channel Update Error: ' . $e->getMessage(), ['id' => $channel->id, 'exception' => $e]);
            session()->flash('error', trans('admin::app.error.something-went-wrong'));
            return redirect()->back()->withInput();
        }
    }

    /**
     * Xóa kênh khỏi CSDL.
     *
     * @param \Webkul\LiveChat\Models\Channel $channel (Sử dụng Route Model Binding)
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Channel $channel): JsonResponse // <<=== SỬ DỤNG ROUTE MODEL BINDING
    {
        if (!bouncer()->hasPermission('live_chat.channel_manager.delete')) {
             return response()->json(['message' => trans('admin::app.error.unauthorized-action')], 403);
        }

        try {
            Event::dispatch('live_chat.channel.delete.before', $channel->id);

            $this->channelRepository->delete($channel->id); // Repository xử lý việc xóa

            Event::dispatch('live_chat.channel.delete.after', $channel->id);

            // Trả về JSON cho DataGrid
            return response()->json([
                'message' => trans('live_chat::app.channels.delete-success'),
            ], 200);

        } catch (\Exception $e) {
            Log::error('Channel Delete Error: ' . $e->getMessage(), ['id' => $channel->id, 'exception' => $e]);
            return response()->json([
                'message' => trans('live_chat::app.channels.delete-failed') . ' ' . trans('admin::app.error.something-went-wrong'),
            ], 500);
        }
    }

    /**
     * Helper method để kiểm tra thông tin Facebook Page ID và Access Token.
     * (Giữ nguyên logic nhưng nên cải thiện logging và xử lý lỗi)
     *
     * @param string|null $pageId
     * @param string|null $accessToken
     * @return bool True nếu hợp lệ, False nếu không.
     */
    protected function verifyFacebookCredentials(?string $pageId, ?string $accessToken): bool
    {
        if (empty($pageId) || empty($accessToken)) {
            Log::warning('Attempted to verify Facebook credentials with empty Page ID or Access Token.');
            return false; // Không thể xác thực nếu thiếu thông tin
        }

        // Luôn sử dụng phiên bản Graph API mới nhất hoặc phiên bản ổn định
        $graphVersion = 'v19.0'; // Cập nhật phiên bản nếu cần
        $graphUrl = "https://graph.facebook.com/{$graphVersion}";
        $endpoint = '/me'; // Endpoint /me xác thực token và trả về ID của đối tượng sở hữu token (Page hoặc User)

        try {
            Log::info("Verifying Facebook credentials via {$endpoint} endpoint for expected Page ID: {$pageId}");

            $response = Http::timeout(10)->get("{$graphUrl}{$endpoint}", [
                'fields'       => 'id,name',
                'access_token' => $accessToken,
            ]);

            Log::debug('Facebook API Verification Status:', [$response->status()]);
            if (!$response->successful() || config('app.debug')) {
                 Log::debug('Facebook API Verification Response Body:', [$response->json() ?? $response->body()]);
            }

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['error'])) {
                     Log::error('Facebook Graph API Error during verification:', $data['error']);
                     return false;
                }
                // QUAN TRỌNG: Endpoint /me trả về ID của PAGE nếu token là Page Access Token hợp lệ
                if (isset($data['id']) && $data['id'] === $pageId) {
                    $pageName = $data['name'] ?? 'N/A';
                    // Log thông tin với biến đã được xử lý
                    Log::info("Facebook token verified successfully for Page ID: {$data['id']} (Name: {$pageName})");
                    return true;
                } else {
                    Log::warning('Facebook token validation successful, but returned ID does not match the provided Page ID.', [
                        'expected_page_id' => $pageId,
                        'returned_id' => $data['id'] ?? 'Not Found',
                    ]);
                    return false; // ID không khớp
                }
            } else {
                Log::error('Facebook API request failed during verification.', ['status' => $response->status(), 'body' => $response->json() ?? $response->body()]);
                return false; // Lỗi HTTP
            }

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Facebook API Connection Error during verification: ' . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            Log::error('Unexpected Error during Facebook verification: ' . $e->getMessage());
            return false;
        }
    }

    public function massDestroy(Request $request): JsonResponse
    {
        // 1. Kiểm tra quyền xóa
        if (!bouncer()->hasPermission('live_chat.channel_manager.delete')) {
            return response()->json([
                'message' => trans('admin::app.acl.error.unauthorized-action'),
            ], 403); // Forbidden
        }

        // 2. Lấy danh sách ID từ request (Thường là 'indices')
        $indices = $request->input('indices'); // Đây là mảng các ID kênh cần xóa

        // 3. Validate dữ liệu đầu vào
        if (empty($indices) || !is_array($indices)) {
            return response()->json([
                'message' => trans('admin::app.datagrid.invalid-mass-action-request'), // Hoặc thông báo cụ thể hơn
            ], 422); // Unprocessable Entity
        }

        try {
            // 4. Bắn event trước khi xóa (truyền mảng ID)
            Event::dispatch('live_chat.channel.mass_delete.before', $indices);

            // 5. Gọi Repository để xóa hàng loạt
            // Repository nên có phương thức hỗ trợ xóa theo mảng ID
            $count = $this->channelRepository->destroy($indices); // Giả sử repo có phương thức destroy nhận mảng ID

            // Hoặc dùng trực tiếp Eloquent nếu Repo không có sẵn:
            // $count = Channel::destroy($indices); // Trả về số lượng bản ghi đã xóa

            // 6. Bắn event sau khi xóa (truyền mảng ID đã xóa)
            Event::dispatch('live_chat.channel.mass_delete.after', $indices);

            // 7. Trả về response thành công
            return response()->json([
                // Sử dụng key ngôn ngữ đúng
                'message' => trans('admin::app.datagrid.mass-delete-success', ['resource' => trans('live_chat::app.channels.channels')]), // Thay 'channels' bằng key phù hợp nếu có
            ]);

        } catch (\Exception $e) {
            Log::error('Channel Mass Delete Error: ' . $e->getMessage(), ['indices' => $indices, 'exception' => $e]);

            // 8. Trả về response lỗi
            return response()->json([
                 // Sử dụng key ngôn ngữ đúng
                'message' => trans('admin::app.error.something-went-wrong'),
            ], 500); // Internal Server Error
        }
    }

    public function massUpdate(Request $request)
    {
        return 0;
        // // 1. Kiểm tra quyền sửa
        // if (!bouncer()->hasPermission('live_chat.channel_manager.edit')) {
        //     return response()->json([
        //         'message' => trans('admin::app.acl.error.unauthorized-action'),
        //     ], 403);
        // }

        // // 2. Lấy danh sách ID và giá trị cập nhật từ request
        // $indices = $request->input('indices'); // Mảng các ID kênh
        // $value = $request->input('value');      // Giá trị muốn cập nhật (0 hoặc 1 cho status)

        // // 3. Validate dữ liệu đầu vào
        // $validator = Validator::make($request->all(), [
        //     'indices'   => 'required|array',
        //     'indices.*' => 'integer', // Đảm bảo các ID là số nguyên
        //     'value'     => 'required|boolean', // Đảm bảo giá trị là 0 hoặc 1 (true/false)
        // ]);

        // if ($validator->fails()) {
        //     return response()->json([
        //         'message' => trans('admin::app.datagrid.invalid-mass-action-request'),
        //         'errors'  => $validator->errors(),
        //     ], 422);
        // }

        // try {
        //     // 4. Bắn event trước khi cập nhật
        //     Event::dispatch('live_chat.channel.mass_update.before', [$indices, $value]);

        //     // 5. Gọi Repository để cập nhật hàng loạt
        //     // Repository nên có phương thức hỗ trợ cập nhật theo mảng ID và dữ liệu
        //     $count = $this->channelRepository->massUpdate($indices, ['status' => $value]);

        //     // Hoặc dùng trực tiếp Eloquent nếu Repo không có sẵn:
        //     // $count = Channel::whereIn('id', $indices)->update(['status' => $value]); // Trả về số lượng bản ghi đã cập nhật

        //     // 6. Bắn event sau khi cập nhật
        //     Event::dispatch('live_chat.channel.mass_update.after', [$indices, $value]);

        //     // 7. Trả về response thành công
        //     return response()->json([
        //         'message' => trans('admin::app.datagrid.mass-update-success', ['resource' => trans('live_chat::app.channels.channels')]),
        //     ]);

        // } catch (\Exception $e) {
        //     Log::error('Channel Mass Update Error: ' . $e->getMessage(), ['indices' => $indices, 'value' => $value, 'exception' => $e]);

        //     // 8. Trả về response lỗi
        //     return response()->json([
        //          'message' => trans('admin::app.error.something-went-wrong'),
        //     ], 500);
        // }
    }
}
