<?php

namespace Webkul\ChannelManager\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\ChannelManager\Contracts\Channel as ChannelContract; // Tham chiếu đến Contract

class Channel extends Model implements ChannelContract // Implement Contract
{
    // Tên bảng (nếu không phải 'channels' thì đặt ở đây)
    // protected $table = 'your_custom_channel_table_name';

    /**
     * Các thuộc tính có thể gán hàng loạt.
     * QUAN TRỌNG: Thêm tất cả các cột bạn muốn có thể tạo/cập nhật qua repository.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'type',
        'status',
        'credentials', // Ví dụ: Lưu trữ API key, token dạng JSON hoặc mã hóa
        'config',      // Ví dụ: Các cài đặt khác dạng JSON
        // Thêm các cột khác của bạn ở đây
    ];

    /**
     * Các thuộc tính nên được chuyển đổi kiểu dữ liệu.
     * Hữu ích cho cột JSON hoặc boolean.
     *
     * @var array
     */
    protected $casts = [
        'credentials' => 'array', // Tự động decode/encode JSON
        'config'      => 'array',
        'status'      => 'boolean', // Ví dụ nếu status là 1/0
    ];

    // Thêm các relationships (ví dụ: belongsTo User, hasMany Messages) nếu cần
    // public function owner() {
    //     return $this->belongsTo(UserProxy::modelClass());
    // }
}
