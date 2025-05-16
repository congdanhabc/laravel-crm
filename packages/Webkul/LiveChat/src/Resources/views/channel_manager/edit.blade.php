<x-admin::layouts>
    {{-- Page Title --}}
    <x-slot:title>
        @lang('live_chat::app.channels.edit.title') {{-- <--- Sửa namespace --}}
    </x-slot>

    {{-- Edit Form --}}
    {{-- Truyền biến $channel từ Controller vào đây --}}
    <x-admin::form :action="route('admin.live_chat.channel_manager.update', $channel->id)" method="PUT">
        <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                 @lang('live_chat::app.channels.edit.title') {{-- <--- Sửa namespace --}}
            </p>

            <div class="flex items-center gap-x-2.5">
                {{-- Cancel Button --}}
                <a
                    href="{{ route('admin.live_chat.channel_manager.index') }}"
                    class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
                >
                    @lang('admin::app.layouts.cancel')
                </a>

                {{-- Update Button --}}
                <button
                    type="submit"
                    class="primary-button"
                >
                     @lang('admin::app.settings.users.edit.save-btn-title') {{-- Lấy từ admin gốc --}}
                </button>
            </div>
        </div>

        {{-- Form Fields --}}
        <div class="mt-3.5 flex gap-2.5 max-xl:flex-wrap">
            {{-- Left Section --}}
            <div class="flex flex-1 flex-col gap-2 max-xl:flex-auto">
                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('live_chat::app.channels.create.general') {{-- <--- Sửa namespace --}}
                    </p>

                    {{-- Channel Name --}}
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('live_chat::app.channels.create.name') {{-- <--- Sửa namespace --}}
                        </x-admin::form.control-group.label>
                        <x-admin::form.control
                            type="text"
                            name="name"
                            :value="old('name', $channel->name)" {{-- Lấy giá trị từ $channel --}}
                            rules="required"
                            :label="__('live_chat::app.channels.create.name')" {{-- <--- Sửa namespace --}}
                            :placeholder="__('live_chat::app.channels.create.name_placeholder')" {{-- <--- Sửa namespace --}}
                        />
                        <x-admin::form.control-group.error control-name="name" />
                    </x-admin::form.control-group>

                     {{-- Channel Type (Không cho sửa type) --}}
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('live_chat::app.channels.create.type') {{-- <--- Sửa namespace --}}
                        </x-admin::form.control-group.label>
                        <x-admin::form.control
                            type="text"
                            name="type_display" {{-- Tên khác để không submit --}}
                            :value="$channel->type" {{-- Hiển thị type hiện tại --}}
                            :label="__('live_chat::app.channels.create.type')" {{-- <--- Sửa namespace --}}
                            ::disabled="true" {{-- Không cho sửa --}}
                        />
                         {{-- Input ẩn để gửi type nếu cần --}}
                         <input type="hidden" name="type" value="{{ $channel->type }}">
                    </x-admin::form.control-group>

                     {{-- Status --}}
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                             @lang('admin::app.settings.users.index.status')
                        </x-admin::form.control-group.label>
                        <x-admin::form.control
                            type="switch"
                            name="status"
                            :value="1"
                            :label="__('admin::app.settings.users.index.status')"
                            :checked="(bool) old('status', $channel->status)" {{-- Lấy giá trị từ $channel --}}
                        />
                         <x-admin::form.control-group.error control-name="status" />
                    </x-admin::form.control-group>

                </div>

                 {{-- Phần Credentials & Config theo Type --}}
                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                     <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('live_chat::app.channels.create.config') {{-- <--- Sửa namespace --}}
                    </p>

                    {{-- Ví dụ cho Facebook --}}
                    <div id="facebook-config" {{ $channel->type !== 'facebook' ? 'style=display:none;' : '' }}>
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('live_chat::app.channels.create.facebook.page_id') {{-- <--- Sửa namespace --}}
                            </x-admin::form.control-group.label>
                            <x-admin::form.control type="text" name="config[facebook][page_id]" :value="old('config.facebook.page_id', $channel->config['page_id'] ?? '')" rules="required" :label="__('live_chat::app.channels.create.facebook.page_id')" />
                            <x-admin::form.control-group.error control-name="config.facebook.page_id" />
                        </x-admin::form.control-group>

                         <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('live_chat::app.channels.create.facebook.page_access_token') {{-- <--- Sửa namespace --}}
                            </x-admin::form.control-group.label>
                            <x-admin::form.control type="password" name="config[facebook][page_access_token]" :value="old('config.facebook.page_access_token')" :placeholder="__('admin::app.settings.users.edit.password_placeholder')" :label="__('live_chat::app.channels.create.facebook.page_access_token')" />
                             <x-admin::form.control-group.info>@lang('admin::app.settings.users.edit.password_help')</x-admin::form.control-group.info> {{-- Để trống nếu không muốn thay đổi --}}
                            <x-admin::form.control-group.error control-name="config.facebook.page_access_token" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('live_chat::app.channels.create.facebook.webhook_verify_token') {{-- Thêm key này vào lang --}}
                            </x-admin::form.control-group.label>
                            <x-admin::form.control type="text" name="config[facebook][webhook_verify_token]" :value="old('config.facebook.webhook_verify_token', $channel->config['webhook_verify_token'] ?? '')" rules="required" :label="__('live_chat::app.channels.create.facebook.webhook_verify_token')"/>
                            <x-admin::form.control-group.error control-name="config.facebook.webhook_verify_token" />
                        </x-admin::form.control-group>

                         {{-- Hiển thị Webhook URL (chỉ đọc) --}}
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('live_chat::app.channels.edit.webhook_url') {{-- Thêm key này vào lang --}}
                            </x-admin::form.control-group.label>
                            <input type="text" class="control" value="{{ route('live_chat.webhook.messenger.handle') }}" readonly style="background:#eee; cursor:text;">
                             <x-admin::form.control-group.info>@lang('live_chat::app.channels.edit.webhook_info')</x-admin::form.control-group.info> {{-- Thêm key này vào lang --}}
                        </x-admin::form.control-group>
                    </div>
                    {{-- Thêm các khối div config cho các loại kênh khác --}}
                </div>
            </div>
        </div>
    </x-admin::form>

     {{-- Không cần JS ở đây vì type không đổi --}}

</x-admin::layouts>
