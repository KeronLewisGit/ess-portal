<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Letter Request') }}
                <span class="font-mono text-base text-gray-500">{{ $request->reference_number ?? '(draft)' }}</span>
            </h2>
            <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium {{ $request->status->badgeClasses() }}">
                {{ $request->status->label() }}
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-md px-4 py-3 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            @if ($request->status === App\Enums\LetterRequestStatus::Rejected && $request->decision_notes)
                <div class="bg-red-50 border border-red-200 text-red-800 rounded-md px-4 py-3 text-sm">
                    <span class="font-medium">Reason for rejection:</span> {{ $request->decision_notes }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-gray-500">Letter type</dt>
                        <dd class="mt-0.5 text-gray-900">{{ $request->letterType->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Addressed to</dt>
                        <dd class="mt-0.5 text-gray-900">{{ $request->addressed_to ?: 'To whom it may concern' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Includes salary</dt>
                        <dd class="mt-0.5 text-gray-900">{{ $request->include_salary ? 'Yes' : 'No' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Submitted</dt>
                        <dd class="mt-0.5 text-gray-900">{{ $request->submitted_at?->format('d M Y H:i') ?? '—' }}</dd>
                    </div>
                    @if ($request->decided_at)
                        <div>
                            <dt class="text-gray-500">Decision</dt>
                            <dd class="mt-0.5 text-gray-900">
                                {{ $request->status->label() }} on {{ $request->decided_at->format('d M Y') }}
                            </dd>
                        </div>
                    @endif
                    <div class="sm:col-span-2">
                        <dt class="text-gray-500">Purpose</dt>
                        <dd class="mt-0.5 text-gray-900 whitespace-pre-line">{{ $request->purpose }}</dd>
                    </div>
                </dl>
            </div>

            @if ($request->status === App\Enums\LetterRequestStatus::Approved)
                <div class="bg-blue-50 border border-blue-200 text-blue-800 rounded-md px-4 py-3 text-sm">
                    This request has been approved and your letter is being prepared.
                    Refresh in a moment — you'll also get an email when it's ready.
                </div>
            @endif

            @if ($request->issuedLetter && ! $request->issuedLetter->isRevoked())
                <div class="bg-green-50 border border-green-200 rounded-md px-4 py-4 text-sm">
                    <p class="text-green-900 font-medium">Your letter is ready.</p>
                    <p class="mt-1 text-green-800">
                        Issued {{ $request->issuedLetter->issued_at->format('d M Y') }} ·
                        reference <span class="font-mono">{{ $request->issuedLetter->reference_number }}</span>
                    </p>
                    <a href="{{ route('letters.prepare', $request->issuedLetter) }}"
                       class="mt-3 inline-flex items-center px-4 py-2 bg-green-700 border border-transparent rounded-md text-sm font-semibold text-white hover:bg-green-800">
                        {{ __('Download PDF') }}
                    </a>
                </div>
            @elseif ($request->issuedLetter?->isRevoked())
                <div class="bg-red-50 border border-red-200 text-red-800 rounded-md px-4 py-3 text-sm">
                    This letter was revoked on {{ $request->issuedLetter->revoked_at->format('d M Y') }}
                    and can no longer be downloaded. Please contact HR.
                </div>
            @endif

            <div class="flex items-center gap-4">
                @can('update', $request)
                    <a href="{{ route('letter-requests.edit', $request) }}"
                       class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md text-sm font-semibold text-white hover:bg-gray-700">
                        {{ __('Edit draft') }}
                    </a>
                @endcan

                @can('submit', $request)
                    <form method="POST" action="{{ route('letter-requests.submit', $request) }}">
                        @csrf
                        <x-primary-button>{{ __('Submit for approval') }}</x-primary-button>
                    </form>
                @endcan

                @can('cancel', $request)
                    <form method="POST" action="{{ route('letter-requests.cancel', $request) }}"
                          onsubmit="return confirm('Withdraw this request?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm text-red-600 hover:text-red-800 underline">
                            {{ __('Withdraw request') }}
                        </button>
                    </form>
                @endcan

                <a href="{{ route('letter-requests.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                    {{ __('Back to my requests') }}
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
