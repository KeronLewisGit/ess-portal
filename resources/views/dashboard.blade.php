<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="text-lg font-medium">
                        Welcome back, {{ auth()->user()->name }}.
                    </p>
                    <p class="mt-1 text-sm text-gray-600">
                        You are signed in to the {{ \App\Models\Setting::get('company_name', config('app.name')) }}
                        Employee Self-Service Portal as
                        <span class="font-medium">{{ auth()->user()->role->label() }}</span>.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <a href="{{ route('letter-requests.index') }}"
                   class="block bg-white shadow-sm sm:rounded-lg p-6 hover:shadow-md transition">
                    <h3 class="font-semibold text-gray-800">My Letter Requests</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Request job letters and track their status. Available in Phase 3.
                    </p>
                </a>

                <a href="{{ route('payslips.index') }}"
                   class="block bg-white shadow-sm sm:rounded-lg p-6 hover:shadow-md transition">
                    <h3 class="font-semibold text-gray-800">My Payslips</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        View and download payslips for published pay periods. Available in Phase 5.
                    </p>
                </a>

                @can('access-hr-area')
                    <a href="{{ route('hr.approvals.index') }}"
                       class="block bg-white shadow-sm sm:rounded-lg p-6 hover:shadow-md transition">
                        <h3 class="font-semibold text-gray-800">HR Area</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Approvals, employee management, letter templates, payslips and reports.
                        </p>
                    </a>
                @endcan

                @can('manage-settings')
                    <a href="{{ route('admin.settings.edit') }}"
                       class="block bg-white shadow-sm sm:rounded-lg p-6 hover:shadow-md transition">
                        <h3 class="font-semibold text-gray-800">Portal Settings</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Company details used across the portal and on issued letters.
                        </p>
                    </a>
                @endcan
            </div>
        </div>
    </div>
</x-app-layout>
