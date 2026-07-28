<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Department') }} — {{ $department->code }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <form method="POST" action="{{ route('hr.departments.update', $department) }}" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
                @csrf
                @method('PUT')
                @include('hr.departments._form', ['department' => $department])

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('hr.departments.index') }}" class="text-sm text-gray-500 underline">Cancel</a>
                    <x-primary-button>{{ __('Save changes') }}</x-primary-button>
                </div>
            </form>

            @can('delete', $department)
                <form method="POST" action="{{ route('hr.departments.destroy', $department) }}"
                      onsubmit="return confirm('Delete this department?');">
                    @csrf
                    @method('DELETE')
                    <button class="text-sm text-red-600 hover:underline">Delete department</button>
                </form>
            @endcan
        </div>
    </div>
</x-app-layout>
