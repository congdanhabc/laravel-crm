<x-admin::layouts>
    {{-- Page Title Slot --}}
    <x-slot:title>
        @lang('channelmanager::app.title')
    </x-slot>

    {{-- Header --}}
    {!! view_render_event('channel_manager.index.header.before') !!}

    <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
        {!! view_render_event('channel_manager.index.header.left.before') !!}

        {{-- Left Side: Title & Breadcrumbs --}}
        <div class="flex flex-col gap-1">
            <div class="mt-1 flex items-center gap-x-2.5">
                <a href="{{ route('admin.dashboard.index') }}" class="text-brandColor dark:text-brandColor">
                    @lang('admin::app.layouts.dashboard')/
                </a>
                <span class="font-semibold text-gray-600 dark:text-gray-300">
                    @lang('channelmanager::app.title')
                </span>
            </div>
            <h1 class="text-xl font-bold dark:text-white">
                @lang('channelmanager::app.title')
            </h1>
        </div>

        {!! view_render_event('channel_manager.index.header.left.after') !!}

        {!! view_render_event('channel_manager.index.header.right.before') !!}

        {{-- Right Side: Actions --}}
        <div class="flex items-center gap-x-2.5">
            {{-- Optional: Export Button (Nếu cần và DataGrid hỗ trợ) --}}
            {{-- <x-admin::datagrid.export :src="route('admin.channelmanager.index')" /> --}}

            {{-- Create button for Channels --}}
            @if (bouncer()->hasPermission('channel_manager.channels.create')) {{-- Thay key quyền nếu cần --}}
                <a
                    href="{{ route('admin.channelmanager.create') }}" {{-- Đảm bảo route này đúng --}}
                    class="primary-button"
                >
                    @lang('channelmanager::app.index.add-channel-btn-title')
                </a>
            @endif
        </div>

        {!! view_render_event('channel_manager.index.header.right.after') !!}
    </div>

    {!! view_render_event('channel_manager.index.header.after') !!}

    {!! view_render_event('channel_manager.index.content.before') !!}

    {{-- Content - Include the Table Partial --}}
    {{-- CSS class từ Lead để điều chỉnh layout toolbar, có thể cần hoặc không --}}
    <div class="mt-3.5 [&>*>*:nth-child(1)]:max-lg:!flex-wrap [&>*>*>*.toolbarRight]:max-lg:w-full [&>*>*>*.toolbarRight]:max-lg:justify-between [&>*>*>*.toolbarRight]:max-md:flex-wrap [&>*>*>*.toolbarRight]:max-md:gap-y-2">
        {{-- Include file partial chứa DataGrid/Table --}}
        @include('channelmanager::index.table')
    </div>

    {!! view_render_event('channel_manager.index.content.after') !!}

</x-admin::layouts>
