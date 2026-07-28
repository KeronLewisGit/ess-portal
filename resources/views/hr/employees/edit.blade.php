<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Employee') }} — {{ $employee->employee_code }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('hr.employees.update', $employee) }}" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
                @csrf
                @method('PUT')
                @include('hr.employees._form', ['employee' => $employee])

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('hr.employees.show', $employee) }}" class="text-sm text-gray-500 underline">Cancel</a>
                    <x-primary-button>{{ __('Save changes') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
