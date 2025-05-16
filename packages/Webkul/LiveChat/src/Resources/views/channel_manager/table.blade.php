{{-- Lưu ý: Cách này không được khuyến nghị bằng <x-admin::datagrid> --}}
    <div class="relative mt-5 overflow-x-auto border dark:border-gray-800 box-shadow rounded">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-900 dark:text-gray-400">
                <tr>
                    {{-- Thêm checkbox cho mass action nếu cần --}}
                    {{-- <th scope="col" class="p-4">
                        <div class="flex items-center">
                            <input id="checkbox-all-search" type="checkbox" class="...">
                            <label for="checkbox-all-search" class="sr-only">checkbox</label>
                        </div>
                    </th> --}}
                    <th scope="col" class="px-6 py-3">
                        @lang('admin::app.datagrid.id')
                    </th>
                    <th scope="col" class="px-6 py-3">
                        @lang('live_chat::app.channels.datagrid.name') {{-- Namespace đúng --}}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        @lang('live_chat::app.channels.datagrid.type') {{-- Namespace đúng --}}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        @lang('live_chat::app.channels.datagrid.status') {{-- Namespace đúng --}}
                    </th>
                     <th scope="col" class="px-6 py-3">
                        @lang('admin::app.datagrid.created_at')
                    </th>
                    <th scope="col" class="px-6 py-3">
                        @lang('admin::app.datagrid.actions')
                    </th>
                </tr>
            </thead>
            <tbody>
                {{-- Giả sử biến $channels được truyền vào từ index.blade.php --}}
                @forelse ($channels ?? [] as $channel)
                    <tr class="bg-white border-b dark:bg-gray-900 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800">
                        {{-- Checkbox --}}
                        {{-- <td class="w-4 p-4">...</td> --}}
                        <td class="px-6 py-4">
                            {{ $channel->id }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $channel->name }}
                        </td>
                        <td class="px-6 py-4">
                             @switch($channel->type)
                                 @case('facebook')
                                     @lang('live_chat::app.channels.create.type_facebook')
                                     @break
                                 @default
                                     {{ $channel->type }}
                             @endswitch
                        </td>
                        <td class="px-6 py-4">
                            {{-- Sử dụng class Tailwind hoặc class Krayin gốc --}}
                            @if ($channel->status)
                                <span class="label-active">@lang('live_chat::app.channels.datagrid.active')</span>
                            @else
                                <span class="label-inactive">@lang('live_chat::app.channels.datagrid.inactive')</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            {{ $channel->created_at?->format('Y-m-d H:i:s') }} {{-- Format ngày giờ --}}
                        </td>
                        <td class="px-6 py-4">
                            {{-- Actions --}}
                            <div class="flex gap-2.5">
                                 @if (bouncer()->hasPermission('live_chat.channel_manager.edit'))
                                     <a href="{{ route('admin.live_chat.channel_manager.edit', $channel->id) }}" class="cursor-pointer text-blue-600 transition-all hover:underline">
                                         <span class="icon-edit text-2xl"></span> {{-- Icon Edit --}}
                                     </a>
                                 @endif
                                 @if (bouncer()->hasPermission('live_chat.channel_manager.delete'))
                                      {{-- Component Confirm Modal của Krayin --}}
                                     <x-admin::modal.confirm
                                         :title="__('admin::app.datagrid.delete_title')"
                                         :content="__('admin::app.datagrid.delete_content', ['resource' => __('live_chat::app.channels.channel_resource')])"
                                         :url="route('admin.live_chat.channel_manager.delete', $channel->id)" {{-- Route đúng --}}
                                     >
                                         {{-- Phần tử kích hoạt modal --}}
                                         <button type="button" class="cursor-pointer text-red-600 transition-all hover:underline">
                                             <span class="icon-delete text-2xl"></span> {{-- Icon Delete --}}
                                         </button>
                                     </x-admin::modal.confirm>
                                 @endif
                            </div>
                        </td>
                    </tr>
                @empty
                     <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400"> {{-- Colspan đúng --}}
                            @lang('admin::app.datagrid.no-records-available')
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination Links (nếu không dùng DataGrid component) --}}
    {{-- @if ($channels instanceof \Illuminate\Pagination\LengthAwarePaginator && $channels->hasPages())
        <div class="mt-4">
            {{ $channels->links('admin::partials.pagination.simple-tailwind') }}
        </div>
    @endif --}}
