<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit Letter Template') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('hr.letter-types.update', $letterType) }}">
                    @csrf
                    @method('PUT')

                    @include('hr.letter-types._form')

                    <div class="mt-6 flex items-center gap-4">
                        <x-primary-button>{{ __('Save template') }}</x-primary-button>
                        <a href="{{ route('hr.letter-types.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>

            @can('delete', $letterType)
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-600">
                        This template hasn't been used by any request yet, so it can still be deleted.
                    </p>
                    <form method="POST" action="{{ route('hr.letter-types.destroy', $letterType) }}" class="mt-3"
                          onsubmit="return confirm('Delete this template?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm text-red-600 hover:text-red-800 underline">
                            {{ __('Delete template') }}
                        </button>
                    </form>
                </div>
            @endcan
        </div>
    </div>
</x-app-layout>
