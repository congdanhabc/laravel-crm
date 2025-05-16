<?php

namespace Webkul\LiveChat\Models;

class ChannelProxy extends Channel // Quan trọng: Kế thừa từ Model gốc của bạn
{
    /**
     * Chỉ định bảng trong CSDL (không bắt buộc nếu tên model và bảng khớp quy ước Laravel)
     * Nhưng thêm vào để rõ ràng và phòng trường hợp ghi đè phức tạp.
     */
    protected $table = 'live_chat_channels';
}
