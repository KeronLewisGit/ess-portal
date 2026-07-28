<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Import Employees') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-md px-4 py-3 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-2">Expected columns</h3>
                <p class="text-sm text-gray-600 mb-3">
                    Upload a CSV or XLSX with a header row. Required: <code>employee_code</code>,
                    <code>first_name</code>, <code>last_name</code>, <code>work_email</code>,
                    <code>employment_type</code>, <code>employment_status</code>, <code>pay_frequency</code>.
                    <code>department_code</code> and <code>manager_employee_code</code> must match existing records.
                </p>
                <div class="overflow-x-auto">
                    <code class="text-xs text-gray-500">{{ implode(', ', $columns) }}</code>
                </div>
                <a href="{{ route('hr.employees.import.template') }}" class="inline-block mt-3 text-sm text-indigo-600 underline">
                    Download sample template (CSV)
                </a>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Step 1 — Validate (dry run)</h3>
                <form method="POST" action="{{ route('hr.employees.import.preview') }}" enctype="multipart/form-data" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <div>
                        <x-input-label for="file" :value="__('CSV / XLSX file')" />
                        <input id="file" name="file" type="file" accept=".csv,.txt,.xlsx,.xls" required
                               class="mt-1 block text-sm text-gray-700" />
                        <x-input-error class="mt-1" :messages="$errors->get('file')" />
                    </div>
                    <x-primary-button>{{ __('Validate') }}</x-primary-button>
                </form>
            </div>

            @isset($result)
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">Dry-run report</h3>
                    <p class="text-sm text-gray-600">
                        {{ $result['rows'] }} data row(s) read — {{ $result['valid'] }} valid,
                        {{ count($result['errors']) }} with errors.
                    </p>

                    @if ($result['errors'] !== [])
                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full text-sm divide-y divide-gray-200">
                                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                                    <tr><th class="px-3 py-2">Row</th><th class="px-3 py-2">Problems</th></tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($result['errors'] as $error)
                                        <tr>
                                            <td class="px-3 py-2 font-mono">{{ $error['row'] }}</td>
                                            <td class="px-3 py-2">
                                                <ul class="list-disc ms-4 text-red-700">
                                                    @foreach ($error['messages'] as $message)
                                                        <li>{{ $message }}</li>
                                                    @endforeach
                                                </ul>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="mt-4 text-sm text-red-700">Fix the rows above and re-validate. Nothing has been imported.</p>
                    @else
                        <div class="mt-4 bg-green-50 border border-green-200 text-green-800 rounded-md px-4 py-3 text-sm">
                            All rows are valid. Nothing has been written yet.
                        </div>

                        @if ($token)
                            <form method="POST" action="{{ route('hr.employees.import.store') }}" class="mt-4">
                                @csrf
                                <input type="hidden" name="token" value="{{ $token }}">
                                <input type="hidden" name="extension" value="{{ $stagedExtension ?? 'csv' }}">
                                <x-primary-button>{{ __('Confirm import of') }} {{ $result['valid'] }} {{ __('employees') }}</x-primary-button>
                            </form>
                        @endif
                    @endif
                </div>
            @endisset
        </div>
    </div>
</x-app-layout>
