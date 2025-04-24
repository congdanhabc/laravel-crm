<form method="POST" action="{{ route('admin.channel_manager.update', $channel->id) }}" @submit.prevent="onSubmit">
    @method('PUT') {{-- Hoặc @method('PATCH') --}}
    @csrf
    {{-- ... các trường input ... --}}
    <input type="text" v-validate="'required'" class="control" id="name" name="name" value="{{ old('name') ?: $channel->name }}" data-vv-as=""{{ __('channelmanager::app.name') }}>
</form>
