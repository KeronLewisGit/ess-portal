<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Approval Queue') }}</h2>
            <span class="text-sm text-gray-600">{{ $pendingCount }} awaiting approval</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-md px-4 py-3 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="flex flex-wrap gap-2 text-sm">
                @foreach (App\Enums\LetterRequestStatus::cases() as $case)
                    <a href="{{ route('hr.approvals.index', ['status' => $case->value]) }}"
                       class="px-3 py-1 rounded-full border {{ $status === $case->value ? 'border-indigo-400 bg-indigo-50 text-indigo-700' : 'border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                        {{ $case->label() }}
                    </a>
                @endforeach
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Reference</th>
                                <th class="px-4 py-3">Employee</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Salary</th>
                                <th class="px-4 py-3">Submitted</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($requests as $request)
                                <tr>
                                    <td class="px-4 py-3 font-mono">{{ $request->reference_number ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        {{ $request->employee->full_name }}
                                        <span class="block text-xs text-gray-500">
                                            {{ $request->employee->employee_code }} · {{ $request->employee->department?->name ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">{{ $request->letterType->name }}</td>
                                    <td class="px-4 py-3">
                                        @if ($request->include_salary)
                                            <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                                HR admin
                                            </span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">{{ $request->submitted_at?->format('d M Y') ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('hr.approvals.show', $request) }}" class="text-indigo-600 hover:underline">Review</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">Nothing here.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div>{{ $requests->links() }}</div>
        </div>
    </div>
</x-app-layout>
