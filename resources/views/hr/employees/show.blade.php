<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $employee->full_name }}
                <span class="text-gray-400 font-mono text-sm">{{ $employee->employee_code }}</span>
            </h2>
            <div class="flex gap-2">
                @can('update', $employee)
                    <a href="{{ route('hr.employees.edit', $employee) }}"
                       class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">Edit</a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-md px-4 py-3 text-sm">
                    @switch(session('status'))
                        @case('employee-created') Employee created. @break
                        @case('employee-updated') Employee updated. @break
                        @case('invitation-sent') Invitation email queued. @break
                        @default {{ session('status') }}
                    @endswitch
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Employment details</h3>
                {{-- Salary and national ID are intentionally not displayed. --}}
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div><dt class="text-gray-500">Work email</dt><dd>{{ $employee->work_email }}</dd></div>
                    <div><dt class="text-gray-500">Personal email</dt><dd>{{ $employee->personal_email ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Phone</dt><dd>{{ $employee->phone ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Job title</dt><dd>{{ $employee->job_title ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Department</dt><dd>{{ $employee->department?->name ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Manager</dt><dd>{{ $employee->manager?->full_name ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Employment type</dt><dd>{{ $employee->employment_type->label() }}</dd></div>
                    <div>
                        <dt class="text-gray-500">Status</dt>
                        <dd>
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $employee->employment_status->badgeClasses() }}">
                                {{ $employee->employment_status->label() }}
                            </span>
                        </dd>
                    </div>
                    <div><dt class="text-gray-500">Date hired</dt><dd>{{ $employee->date_hired?->toFormattedDateString() ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Date separated</dt><dd>{{ $employee->date_separated?->toFormattedDateString() ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Pay frequency</dt><dd>{{ $employee->pay_frequency->label() }}</dd></div>
                    <div><dt class="text-gray-500">Salary currency</dt><dd>{{ $employee->salary_currency ?? '—' }}</dd></div>
                </dl>
            </div>

            @can('provisionUser', $employee)
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800 mb-2">Portal account</h3>
                    @if ($employee->user)
                        <p class="text-sm text-gray-600 mb-4">
                            Account exists: <span class="font-medium">{{ $employee->user->email }}</span>
                            (role: {{ $employee->user->role->label() }}){{ $employee->user->must_change_password ? ' — password change pending' : '' }}.
                        </p>
                    @else
                        <p class="text-sm text-gray-600 mb-4">No login account yet.</p>
                    @endif

                    <form method="POST" action="{{ route('hr.employees.provision-user', $employee) }}" class="flex flex-wrap items-end gap-3">
                        @csrf
                        <div>
                            <x-input-label for="role" :value="__('Role')" />
                            <select id="role" name="role" class="mt-1 border-gray-300 rounded-md shadow-sm">
                                {{-- Only the roles the current user may grant (a super admin can only be created by a super admin). --}}
                                @foreach (auth()->user()->role->assignableRoles() as $role)
                                    <option value="{{ $role->value }}" @selected($employee->user?->role?->value === $role->value || (!$employee->user && $role === App\Enums\Role::Employee))>{{ $role->label() }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-1" :messages="$errors->get('role')" />
                        </div>
                        <x-primary-button>{{ $employee->user ? __('Re-send invitation') : __('Create account & invite') }}</x-primary-button>
                    </form>
                </div>
            @endcan

            @can('delete', $employee)
                <form method="POST" action="{{ route('hr.employees.destroy', $employee) }}"
                      onsubmit="return confirm('Soft-delete this employee?');">
                    @csrf
                    @method('DELETE')
                    <button class="text-sm text-red-600 hover:underline">Delete employee</button>
                </form>
            @endcan
        </div>
    </div>
</x-app-layout>
