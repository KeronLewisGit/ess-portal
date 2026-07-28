<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Employees') }}</h2>
            <div class="flex gap-2">
                <a href="{{ route('hr.employees.import.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                    {{ __('Import') }}
                </a>
                <a href="{{ route('hr.employees.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md text-sm font-semibold text-white hover:bg-gray-700">
                    {{ __('New Employee') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-md px-4 py-3 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <form method="GET" class="bg-white shadow-sm sm:rounded-lg p-4 flex flex-wrap gap-3 items-end">
                <div class="flex-1 min-w-48">
                    <x-input-label for="q" :value="__('Search')" />
                    <x-text-input id="q" name="q" class="mt-1 block w-full" :value="$filters['q'] ?? ''"
                        placeholder="Code, name, email, title" />
                </div>
                <div>
                    <x-input-label for="status" :value="__('Status')" />
                    <select id="status" name="status" class="mt-1 border-gray-300 rounded-md shadow-sm">
                        <option value="">All</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="department" :value="__('Department')" />
                    <select id="department" name="department" class="mt-1 border-gray-300 rounded-md shadow-sm">
                        <option value="">All</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected((string)($filters['department'] ?? '') === (string)$department->id)>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <x-primary-button>{{ __('Filter') }}</x-primary-button>
                <a href="{{ route('hr.employees.index') }}" class="text-sm text-gray-500 underline">Reset</a>
            </form>

            <form method="POST" action="{{ route('hr.employees.bulk-deactivate') }}"
                  onsubmit="return confirm('Deactivate the selected employees?');"
                  class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                @csrf
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                    <span class="text-sm text-gray-500">{{ $employees->total() }} employee(s)</span>
                    @can('manage', App\Models\Employee::class)
                        <button type="submit"
                                class="inline-flex items-center px-3 py-1.5 bg-red-50 border border-red-200 rounded-md text-sm font-medium text-red-700 hover:bg-red-100">
                            {{ __('Deactivate selected') }}
                        </button>
                    @endcan
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-3"><input type="checkbox" onclick="document.querySelectorAll('.row-check').forEach(c=>c.checked=this.checked)"></th>
                                <th class="px-4 py-3">Code</th>
                                <th class="px-4 py-3">Name</th>
                                <th class="px-4 py-3">Department</th>
                                <th class="px-4 py-3">Job title</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Account</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($employees as $employee)
                                <tr>
                                    <td class="px-4 py-3"><input class="row-check" type="checkbox" name="ids[]" value="{{ $employee->id }}"></td>
                                    <td class="px-4 py-3 font-mono">{{ $employee->employee_code }}</td>
                                    <td class="px-4 py-3">{{ $employee->full_name }}</td>
                                    <td class="px-4 py-3">{{ $employee->department?->name ?? '—' }}</td>
                                    <td class="px-4 py-3">{{ $employee->job_title ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $employee->employment_status->badgeClasses() }}">
                                            {{ $employee->employment_status->label() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">{{ $employee->user ? 'Yes' : '—' }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('hr.employees.show', $employee) }}" class="text-indigo-600 hover:underline">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="px-4 py-6 text-center text-gray-500">No employees found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </form>

            <div>{{ $employees->links() }}</div>
        </div>
    </div>
</x-app-layout>
