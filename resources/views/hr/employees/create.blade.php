<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('New Employee') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('hr.employees.store') }}" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
                @csrf
                @include('hr.employees._form', ['employee' => null])

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('hr.employees.index') }}" class="text-sm text-gray-500 underline">Cancel</a>
                    <x-primary-button>{{ __('Create employee') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
