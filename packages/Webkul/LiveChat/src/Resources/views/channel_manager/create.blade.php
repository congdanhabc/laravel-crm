
<x-admin::layouts>
    {{-- Page Title Slot --}}
    <x-slot:title>
        @lang('live_chat::app.channels.create.title')
    </x-slot>
    <div class="flex flex-col gap-4">
        {{-- Page Header --}}
        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            {{-- Left Side --}}
            <div class="flex flex-col gap-1">
                <h1 class="text-xl font-bold dark:text-white">
                    @lang('live_chat::app.channels.create.title')
                </h1>
                <div class="mt-1 flex items-center gap-x-2.5">
                    <a href="{{ route('admin.dashboard.index') }}" class="text-gray-600 dark:text-gray-300">
                        @lang('admin::app.layouts.dashboard')
                    </a>
                    <i class="icon-arrow-right text-xl text-gray-600 dark:text-gray-300 rtl:icon-arrow-left"></i>
                    <a href="{{ route('admin.live_chat.channel_manager.index') }}" class="text-gray-600 dark:text-gray-300">
                        @lang('live_chat::app.channels.title')
                    </a>
                    <i class="icon-arrow-right text-xl text-gray-600 dark:text-gray-300 rtl:icon-arrow-left"></i>
                    <span class="font-semibold text-gray-800 dark:text-gray-200">
                        @lang('live_chat::app.channels.create.breadcrumb')
                    </span>
                </div>
            </div>
        </div>

        {{-- Form Content Area --}}
        <x-admin::form :action="route('admin.live_chat.channel_manager.store')" method="POST">
            <div class="flex gap-2.5 max-xl:flex-wrap">
                {{-- Left Section --}}
                <div class="flex flex-1 flex-col gap-2 max-xl:flex-auto">
                    <x-admin::accordion :is-active="true">
                        <x-slot:header>
                            <p class="text-gray-600 dark:text-gray-300 text-base font-semibold">
                                @lang('live_chat::app.channels.create.general')
                            </p>
                        </x-slot:header>
                        <x-slot:content>
                            {{-- Name --}}
                            <x-admin::form.control-group class="mb-2.5">
                                <x-admin::form.control-group.label class="required">
                                    @lang('live_chat::app.channels.create.name')
                                </x-admin::form.control-group.label>
                                <x-admin::form.control-group.control
                                    type="text" name="name" id="name" rules="required" :value="old('name')"
                                    :label="trans('live_chat::app.channels.create.name')"
                                    :placeholder="trans('live_chat::app.channels.create.name_placeholder')"
                                />
                                <x-admin::form.control-group.error control-name="name" />
                            </x-admin::form.control-group>

                            {{-- Type --}}
                            <x-admin::form.control-group class="mb-2.5">
                                <x-admin::form.control-group.label class="required">
                                    @lang('live_chat::app.channels.create.type')
                                </x-admin::form.control-group.label>
                                <x-admin::form.control-group.control
                                    type="select" name="type" id="channel_type"
                                    rules="required" :value="old('type')"
                                    :label="trans('live_chat::app.channels.create.type')"
                                >
                                    <option value="" disabled>@lang('live_chat::app.channels.create.default')</option>
                                    <option value="facebook" {{ old('type') == 'facebook' ? 'selected' : '' }}>
                                        @lang('live_chat::app.channels.create.type_facebook')
                                    </option>
                                    <option value="channex" {{ old('type') == 'channex' ? 'selected' : '' }}>
                                        @lang('live_chat::app.channels.create.type_channex')
                                    </option>
                                </x-admin::form.control-group.control>
                                <x-admin::form.control-group.error control-name="type" />
                            </x-admin::form.control-group>
                        </x-slot:content>
                    </x-admin::accordion>

                    {{-- Credentials Accordion --}}
                    <x-admin::accordion :is-active="true">
                        <x-slot:header>
                            <p class="text-gray-600 dark:text-gray-300 text-base font-semibold">
                                @lang('live_chat::app.channels.create.credentials')
                            </p>
                        </x-slot:header>
                        <x-slot:content>
                            {{-- === START: Facebook Integration Section === --}}
                            <div id="facebook-integration-section"> {{-- Mặc định ẩn --}}
                                <x-admin::form.control-group class="mb-2.5">
                                    <x-admin::form.control-group.label>
                                        @lang('live_chat::app.channels.create.facebook.page_id')
                                    </x-admin::form.control-group.label>
                                    <x-admin::form.control-group.control
                                        type="text" name="credentials[fb_page_id]" id="fb_page_id"
                                    />
                                    <x-admin::form.control-group.error control-name="fb_page_id" />
                                </x-admin::form.control-group>

                                <x-admin::form.control-group class="mb-2.5">
                                    <x-admin::form.control-group.label>
                                        @lang('live_chat::app.channels.create.facebook.page_access_token')
                                    </x-admin::form.control-group.label>
                                    <x-admin::form.control-group.control
                                        type="password" name="credentials[fb_page_access_token]" id="fb_page_access_token"
                                    />
                                    <x-admin::form.control-group.error control-name="fb_page_access_token" />
                                </x-admin::form.control-group>

                                <x-admin::form.control-group class="mb-2.5">
                                    <x-admin::form.control-group.label>
                                        @lang('live_chat::app.channels.create.facebook.app_secret')
                                    </x-admin::form.control-group.label>
                                    <x-admin::form.control-group.control
                                        type="password" name="credentials[fb_app_secret]" id="fb_app_secret"
                                    />
                                    <x-admin::form.control-group.error control-name="fb_app_secret" />
                                </x-admin::form.control-group>

                                {{-- Connect Button --}}
                                <div class="mb-2.5">
                                    <button type="submit" id="facebook-connect_btn" class="secondary-button">
                                        @lang('live_chat::app.channels.create.facebook.connect_btn')
                                    </button>
                                </div>
                            </div>
                            {{-- === END: Facebook Integration Section === --}}
                        </x-slot:content>
                    </x-admin::accordion>
                </div>
            </div>
        </x-admin::form>
    </div>
</x-admin::layouts>
