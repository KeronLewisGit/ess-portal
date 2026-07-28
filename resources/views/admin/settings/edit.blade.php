<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Portal Settings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <p class="text-sm text-gray-600">
                    These values are used across the portal and on issued letters. Changes take effect immediately.
                </p>

                @if (session('status') === 'settings-updated')
                    <p class="mt-3 text-sm font-medium text-green-600">
                        {{ __('Settings saved.') }}
                    </p>
                @endif

                <form method="POST" action="{{ route('admin.settings.update') }}" class="mt-6 space-y-6"
                      enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    @foreach ($fields as $key => $field)
                        <div>
                            <x-input-label :for="'settings_'.$key" :value="$field['label']" />

                            @if ($field['type'] === 'textarea')
                                <textarea
                                    id="settings_{{ $key }}"
                                    name="settings[{{ $key }}]"
                                    rows="3"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                >{{ old('settings.'.$key, $values[$key]) }}</textarea>
                            @else
                                <x-text-input
                                    :id="'settings_'.$key"
                                    :name="'settings['.$key.']'"
                                    :type="$field['type']"
                                    class="mt-1 block w-full"
                                    :value="old('settings.'.$key, $values[$key])"
                                />
                            @endif

                            @isset($field['hint'])
                                <p class="mt-1 text-xs text-gray-500">{{ $field['hint'] }}</p>
                            @endisset

                            <x-input-error class="mt-2" :messages="$errors->get('settings.'.$key)" />
                        </div>
                    @endforeach

                    <div class="border-t border-gray-100 pt-6 space-y-6">
                        <p class="text-sm font-medium text-gray-700">Letterhead</p>
                        <p class="-mt-4 text-xs text-gray-500">
                            These images are stored privately and embedded into issued letter
                            PDFs. They are never served over the web.
                        </p>

                        @foreach ($uploads as $key => $upload)
                            <div>
                                <x-input-label :for="$key" :value="$upload['label']" />

                                @if ($uploadValues[$key])
                                    <p class="mt-1 text-xs text-green-700">
                                        Currently set. Upload a new file to replace it.
                                    </p>
                                    <label class="mt-1 flex items-center gap-2">
                                        <input type="checkbox" name="remove_{{ $key }}" value="1"
                                               class="rounded border-gray-300 text-red-600 shadow-sm focus:ring-red-500" />
                                        <span class="text-xs text-gray-600">Remove the current image</span>
                                    </label>
                                @else
                                    <p class="mt-1 text-xs text-gray-500">Not set.</p>
                                @endif

                                <input type="file" id="{{ $key }}" name="{{ $key }}" accept="image/png,image/jpeg"
                                       class="mt-2 block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200" />

                                <p class="mt-1 text-xs text-gray-500">{{ $upload['hint'] }}</p>
                                <x-input-error class="mt-2" :messages="$errors->get($key)" />
                            </div>
                        @endforeach
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>{{ __('Save') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
