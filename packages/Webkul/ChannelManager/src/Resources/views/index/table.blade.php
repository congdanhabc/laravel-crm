<x-admin::datagrid :src="route('admin.channelmanager.index')"> {{-- <<<=== THAY ROUTE Ở ĐÂY --}}
    {{-- DataGrid Shimmer (Hiệu ứng loading) --}}
    <x-admin::shimmer.datagrid />

    {{-- Optional: Thêm slot nếu bạn cần tùy chỉnh toolbar --}}
    {{--
    <x-slot:toolbar-right-after>
         Nội dung tùy chỉnh cho toolbar bên phải (ví dụ: nút lọc riêng,...)
    </x-slot>
    --}}

    {{-- Component <x-admin::datagrid> sẽ tự xử lý việc gọi AJAX, hiển thị bảng, phân trang,... --}}
</x-admin::datagrid>

{!! view_render_event('channel_manager.index.table.after') !!}
