<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Letter Templates') }}</h2>
            @can('create', App\Models\LetterType::class)
                <a href="{{ route('hr.letter-types.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md text-sm font-semibold text-white hover:bg-gray-700">
                    {{ __('New Template') }}
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
                                <th class="px-4 py-3">Name</th>
                                <th class="px-4 py-3">Code</th>
                                <th class="px-4 py-3">Prefix</th>
                                <th class="px-4 py-3">Requests</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($letterTypes as $type)
                                <tr>
                                    <td class="px-4 py-3">
                                        {{ $type->name }}
                                        @if ($type->description)
                                            <span class="block text-xs text-gray-500">{{ $type->description }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs">{{ $type->code }}</td>
                                    <td class="px-4 py-3 font-mono text-xs">{{ $type->reference_prefix }}</td>
                                    <td class="px-4 py-3">{{ $type->letter_requests_count }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium {{ $type->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-600' }}">
                                            {{ $type->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        @can('update', $type)
                                            <a href="{{ route('hr.letter-types.edit', $type) }}" class="text-indigo-600 hover:underline">Edit</a>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">No letter templates yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div>{{ $letterTypes->links() }}</div>
        </div>
    </div>
</x-app-layout>
