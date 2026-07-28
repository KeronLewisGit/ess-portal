<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Departments') }}</h2>
            @can('create', App\Models\Department::class)
                <a href="{{ route('hr.departments.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md text-sm font-semibold text-white hover:bg-gray-700">
                    {{ __('New Department') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-md px-4 py-3 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Code</th>
                                <th class="px-4 py-3">Name</th>
                                <th class="px-4 py-3">Head</th>
                                <th class="px-4 py-3">Employees</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($departments as $department)
                                <tr>
                                    <td class="px-4 py-3 font-mono">{{ $department->code }}</td>
                                    <td class="px-4 py-3">{{ $department->name }}</td>
                                    <td class="px-4 py-3">{{ $department->head?->full_name ?? '—' }}</td>
                                    <td class="px-4 py-3">{{ $department->employees_count }}</td>
                                    <td class="px-4 py-3 text-right">
                                        @can('update', $department)
                                            <a href="{{ route('hr.departments.edit', $department) }}" class="text-indigo-600 hover:underline">Edit</a>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">No departments.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div>{{ $departments->links() }}</div>
        </div>
    </div>
</x-app-layout>
