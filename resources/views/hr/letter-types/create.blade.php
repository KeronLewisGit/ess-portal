<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('New Letter Template') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('hr.letter-types.store') }}">
                    @csrf

                    @include('hr.letter-types._form')

                    <div class="mt-6 flex items-center gap-4">
                        <x-primary-button>{{ __('Create template') }}</x-primary-button>
                        <a href="{{ route('hr.letter-types.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
