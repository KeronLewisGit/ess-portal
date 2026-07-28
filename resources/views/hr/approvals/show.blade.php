<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Review Request') }}
                <span class="font-mono text-base text-gray-500">{{ $request->reference_number }}</span>
            </h2>
            <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium {{ $request->status->badgeClasses() }}">
                {{ $request->status->label() }}
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 rounded-md px-4 py-3 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-gray-500">Employee</dt>
                        <dd class="mt-0.5 text-gray-900">
                            {{ $request->employee->full_name }} ({{ $request->employee->employee_code }})
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Department / title</dt>
                        <dd class="mt-0.5 text-gray-900">
                            {{ $request->employee->department?->name ?? '—' }} · {{ $request->employee->job_title ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Letter type</dt>
                        <dd class="mt-0.5 text-gray-900">{{ $request->letterType->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Addressed to</dt>
                        <dd class="mt-0.5 text-gray-900">{{ $request->addressed_to ?: 'To whom it may concern' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Submitted</dt>
                        <dd class="mt-0.5 text-gray-900">{{ $request->submitted_at?->format('d M Y H:i') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">States salary</dt>
                        <dd class="mt-0.5 text-gray-900">{{ $request->include_salary ? 'Yes' : 'No' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-gray-500">Purpose</dt>
                        <dd class="mt-0.5 text-gray-900 whitespace-pre-line">{{ $request->purpose }}</dd>
                    </div>
                </dl>
            </div>

            {{-- The salary VALUE is never shown here; approving simply authorises
                 it to be rendered into the PDF at generation time. --}}
            @if ($request->include_salary && $request->status->isPending())
                <div class="bg-amber-50 border border-amber-200 text-amber-900 rounded-md px-4 py-3 text-sm">
                    This letter will state the employee's salary.
                    @cannot('approve', $request)
                        Only an HR administrator can approve it — you can still reject it.
                    @endcannot
                </div>
            @endif

            @if ($request->status->isPending())
                <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
                    @can('approve', $request)
                        <form method="POST" action="{{ route('hr.approvals.approve', $request) }}" class="space-y-3">
                            @csrf
                            <x-input-label for="approve_notes" :value="__('Notes (optional)')" />
                            <textarea id="approve_notes" name="decision_notes" rows="2"
                                      class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
                            <x-primary-button>{{ __('Approve request') }}</x-primary-button>
                        </form>
                    @endcan

                    @can('reject', $request)
                        <form method="POST" action="{{ route('hr.approvals.reject', $request) }}" class="space-y-3 border-t border-gray-100 pt-6">
                            @csrf
                            <x-input-label for="reject_notes" :value="__('Reason for rejection (required)')" />
                            <textarea id="reject_notes" name="decision_notes" rows="2" required
                                      class="mt-1 block w-full border-gray-300 focus:border-red-400 focus:ring-red-400 rounded-md shadow-sm">{{ old('decision_notes') }}</textarea>
                            <x-input-error :messages="$errors->get('decision_notes')" />
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md text-sm font-semibold text-white hover:bg-red-700">
                                {{ __('Reject request') }}
                            </button>
                        </form>
                    @endcan
                </div>
            @else
                <div class="bg-white shadow-sm sm:rounded-lg p-6 text-sm text-gray-700">
                    Decided by {{ $request->decidedBy?->name ?? 'system' }}
                    on {{ $request->decided_at?->format('d M Y H:i') ?? '—' }}.
                    @if ($request->decision_notes)
                        <p class="mt-2 whitespace-pre-line">{{ $request->decision_notes }}</p>
                    @endif
                </div>
            @endif

            <a href="{{ route('hr.approvals.index') }}" class="inline-block text-sm text-gray-600 hover:text-gray-900">
                {{ __('Back to the queue') }}
            </a>
        </div>
    </div>
</x-app-layout>
