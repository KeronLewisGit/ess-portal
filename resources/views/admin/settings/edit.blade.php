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

                <form method="POST" action="{{ route('admin.settings.update') }}" class="mt-6 space-y-6">
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

                    <div class="flex items-center gap-4">
                        <x-primary-button>{{ __('Save') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
