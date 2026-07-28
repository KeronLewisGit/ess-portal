<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Request a Letter') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('letter-requests.store') }}">
                    @csrf

                    @include('letter-requests._form', ['request' => null])

                    <div class="mt-6 flex items-center gap-4">
                        <x-primary-button name="action" value="submit">{{ __('Submit for approval') }}</x-primary-button>
                        <button type="submit" name="action" value="draft"
                                class="text-sm text-gray-600 hover:text-gray-900 underline">
                            {{ __('Save as draft') }}
                        </button>
                        <a href="{{ route('letter-requests.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
