<x-admin::layouts>
    {{-- Page Title --}}
    <x-slot:title>
        @lang('live_chat::app.channels.index.title') {{-- <--- Sửa namespace --}}
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('live_chat::app.channels.index.title') {{-- <--- Sửa namespace --}}
        </p>

        <div class="flex items-center gap-x-2.5">
             {{-- Nút quay lại trang Live Chat chính (nếu cần) --}}
            <a href="{{ route('admin.live_chat.index') }}" class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800">
                 @lang('admin::app.layouts.back') {{-- Dùng lang của admin gốc --}}
            </a>

            {{-- Add Channel Button --}}
            @if (bouncer()->hasPermission('live_chat.channel_manager.create')) {{-- Kiểm tra quyền --}}
                <a
                    href="{{ route('admin.live_chat.channel_manager.create') }}"
                    class="primary-button"
                >
                    @lang('live_chat::app.channels.index.add-channel-btn-title') {{-- <--- Sửa namespace --}}
                </a>
            @endif
        </div>
    </div>

     {{-- DataGrid --}}
     <x-admin::datagrid :src="route('admin.live_chat.channel_manager.index')" />
     {{-- Hoặc nếu dùng @include('...table') --}}
     {{-- @php $channels = app(Webkul\LiveChat\Repositories\ChannelRepository::class)->all(); @endphp
     @include('live_chat::channel_manager.table', ['channels' => $channels]) --}}

</x-admin::layouts>
