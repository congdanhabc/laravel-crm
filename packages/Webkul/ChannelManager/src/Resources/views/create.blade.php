{{-- packages/Webkul/ChannelManager/src/Resources/views/create.blade.php --}}

<x-admin::layouts>
    {{-- Page Title Slot --}}
    <x-slot:title>
        @lang('channelmanager::app.create.title')
    </x-slot>
    <div class="flex flex-col gap-4">
        {{-- Page Header --}}
        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            {{-- Left Side --}}
            <div class="flex flex-col gap-1">
                <h1 class="text-xl font-bold dark:text-white">
                    @lang('channelmanager::app.create.title')
                </h1>
                <div class="mt-1 flex items-center gap-x-2.5">
                    <a href="{{ route('admin.dashboard.index') }}" class="text-gray-600 dark:text-gray-300">
                        @lang('admin::app.layouts.dashboard')
                    </a>
                    <i class="icon-arrow-right text-xl text-gray-600 dark:text-gray-300 rtl:icon-arrow-left"></i>
                    <a href="{{ route('admin.channelmanager.index') }}" class="text-gray-600 dark:text-gray-300">
                        @lang('channelmanager::app.title')
                    </a>
                    <i class="icon-arrow-right text-xl text-gray-600 dark:text-gray-300 rtl:icon-arrow-left"></i>
                    <span class="font-semibold text-gray-800 dark:text-gray-200">
                        @lang('channelmanager::app.create.breadcrumb')
                    </span>
                </div>
            </div>
        </div>

        {{-- Form Content Area --}}
        <x-admin::form :action="route('admin.channelmanager.store')" method="POST">
            <div class="flex gap-2.5 max-xl:flex-wrap">
                {{-- Left Section --}}
                <div class="flex flex-1 flex-col gap-2 max-xl:flex-auto">
                    <x-admin::accordion :is-active="true">
                        <x-slot:header>
                            <p class="text-gray-600 dark:text-gray-300 text-base font-semibold">
                                @lang('channelmanager::app.create.general')
                            </p>
                        </x-slot:header>
                        <x-slot:content>
                            {{-- Name --}}
                            <x-admin::form.control-group class="mb-2.5">
                                <x-admin::form.control-group.label class="required">
                                    @lang('channelmanager::app.create.name')
                                </x-admin::form.control-group.label>
                                <x-admin::form.control-group.control
                                    type="text" name="name" id="name" rules="required" :value="old('name')"
                                    :label="trans('channelmanager::app.create.name')"
                                    :placeholder="trans('channelmanager::app.create.name_placeholder')"
                                />
                                <x-admin::form.control-group.error control-name="name" />
                            </x-admin::form.control-group>

                            {{-- Type --}}
                            <x-admin::form.control-group class="mb-2.5">
                                <x-admin::form.control-group.label class="required">
                                    @lang('channelmanager::app.create.type')
                                </x-admin::form.control-group.label>
                                <x-admin::form.control-group.control
                                    type="select" name="type" id="channel_type"
                                    rules="required" :value="old('type')"
                                    :label="trans('channelmanager::app.create.type')"
                                >
                                    <option value="" disabled>@lang('channelmanager::app.create.default')</option>
                                    <option value="facebook" {{ old('type') == 'facebook' ? 'selected' : '' }}>
                                        @lang('channelmanager::app.create.type_facebook')
                                    </option>
                                    <option value="channex" {{ old('type') == 'channex' ? 'selected' : '' }}>
                                        @lang('channelmanager::app.create.type_channex')
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
                                @lang('channelmanager::app.create.credentials')
                            </p>
                        </x-slot:header>
                        <x-slot:content>
                            {{-- === START: Facebook Integration Section === --}}
                            <div id="facebook-integration-section"> {{-- Mặc định ẩn --}}
                                <x-admin::form.control-group class="mb-2.5">
                                    <x-admin::form.control-group.label>
                                        @lang('channelmanager::app.create.facebook.page_id')
                                    </x-admin::form.control-group.label>
                                    <x-admin::form.control-group.control
                                        type="text" name="credentials[fb_page_id]" id="fb_page_id"
                                    />
                                    <x-admin::form.control-group.error control-name="fb_page_id" />
                                </x-admin::form.control-group>

                                <x-admin::form.control-group class="mb-2.5">
                                    <x-admin::form.control-group.label>
                                        @lang('channelmanager::app.create.facebook.page_access_token')
                                    </x-admin::form.control-group.label>
                                    <x-admin::form.control-group.control
                                        type="password" name="credentials[fb_page_access_token]" id="fb_page_access_token"
                                    />
                                    <x-admin::form.control-group.error control-name="fb_page_access_token" />
                                </x-admin::form.control-group>

                                <x-admin::form.control-group class="mb-2.5">
                                    <x-admin::form.control-group.label>
                                        @lang('channelmanager::app.create.facebook.app_secret')
                                    </x-admin::form.control-group.label>
                                    <x-admin::form.control-group.control
                                        type="password" name="credentials[fb_app_secret]" id="fb_app_secret"
                                    />
                                    <x-admin::form.control-group.error control-name="fb_app_secret" />
                                </x-admin::form.control-group>

                                {{-- Connect Button --}}
                                <div class="mb-2.5">
                                    <button type="submit" id="facebook-connect_btn" class="secondary-button">
                                        @lang('channelmanager::app.create.facebook.connect_btn')
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
