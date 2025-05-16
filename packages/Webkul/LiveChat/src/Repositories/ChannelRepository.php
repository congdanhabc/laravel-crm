<?php
namespace Webkul\LiveChat\Repositories;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Event; // Thêm nếu muốn dùng Events
use Illuminate\Support\Facades\Log;
use Webkul\Core\Eloquent\Repository;
use Webkul\LiveChat\Contracts\Channel; // Sử dụng Contract

class ChannelRepository extends Repository
{
    /**
     * Khởi tạo Repository.
     * Có thể inject các dependency khác nếu cần.
     *
     * @param \Illuminate\Container\Container $container
     */
    // public function __construct(Container $container) // Bỏ comment nếu cần inject cái khác
    // {
    //     parent::__construct($container);
    // }

    /**
     * Chỉ định lớp Model.
     *
     * @return string
     */
    public function model(): string
    {
        return \Webkul\LiveChat\Models\Channel::class; // Trỏ đến Model Channel
    }

    /**
     * Tạo mới Channel.
     *
     * @param array $data
     * @return \Webkul\LiveChat\Contracts\Channel
     */
    public function create(array $data): Channel // Sử dụng Contract làm kiểu trả về
    {
        // Optional: Dispatch event trước khi tạo
        // Event::dispatch('channel_manager.channel.create.before');

        // Gọi phương thức create gốc từ Repository cha
        $channel = parent::create($data);

        // Optional: Dispatch event sau khi tạo
        // Event::dispatch('channel_manager.channel.create.after', $channel);

        return $channel;
    }

    /**
     * Cập nhật Channel.
     *
     * @param array  $data
     * @param int    $id
     * @param string $attribute
     * @return \Webkul\ChannelManager\Contracts\Channel
     */
    public function update(array $data, $id, $attribute = "id"): Channel // Sử dụng Contract làm kiểu trả về
    {
        // Optional: Dispatch event trước khi cập nhật
        // Event::dispatch('channel_manager.channel.update.before', $id);

        // Gọi phương thức update gốc
        $channel = parent::update($data, $id);

        // Optional: Dispatch event sau khi cập nhật
        // Event::dispatch('channel_manager.channel.update.after', $channel);

        return $channel;
    }

    /**
     * Xóa Channel.
     *
     * @param int $id
     * @return void
     */
    public function delete($id): void
    {
         // Optional: Dispatch event trước khi xóa
        // Event::dispatch('channel_manager.channel.delete.before', $id);

        parent::delete($id);

        // Optional: Dispatch event sau khi xóa
        // Event::dispatch('channel_manager.channel.delete.after', $id);
    }

     /**
      * Helper: Tìm Channel đang active dựa trên Page ID từ credentials.
      * (Đã có trong WebhookController, nhưng để ở đây cũng hợp lý)
      *
      * @param string $pageId
      * @return \Webkul\ChannelManager\Contracts\Channel|null
      */
     public function findActiveByPageId(string $pageId): ?Channel
     {
         try {
             // Truy vấn dùng Eloquent Model thông qua Repository
             return $this->model // Truy cập trực tiếp model từ repository
                 ->where('type', 'messenger')
                 ->where('status', 1) // Hoặc true tùy kiểu dữ liệu cột status
                 ->where('credentials->fb_page_id', $pageId) // Đảm bảo DB hỗ trợ JSON query
                 ->first();
         } catch (\Exception $e) {
             Log::error("Database error finding channel by Page ID {$pageId} in Repository: " . $e->getMessage());
             return null;
         }
     }

    // Thêm các phương thức truy vấn tùy chỉnh khác nếu cần
}
