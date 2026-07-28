@php
    $request = $request ?? null;
@endphp

<div class="space-y-6">
    <div>
        <x-input-label for="letter_type_id" :value="__('Letter type')" />
        <select id="letter_type_id" name="letter_type_id" required
                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            <option value="">Choose a letter type…</option>
            @foreach ($letterTypes as $type)
                <option value="{{ $type->id }}"
                    @selected(old('letter_type_id', $request?->letter_type_id) == $type->id)>
                    {{ $type->name }}@if ($type->description) — {{ $type->description }}@endif
                </option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('letter_type_id')" />
    </div>

    <div>
        <x-input-label for="addressed_to" :value="__('Addressed to (optional)')" />
        <x-text-input id="addressed_to" name="addressed_to" type="text" class="mt-1 block w-full"
                      :value="old('addressed_to', $request?->addressed_to)"
                      placeholder="e.g. First National Bank — leave blank for 'To whom it may concern'" />
        <x-input-error class="mt-2" :messages="$errors->get('addressed_to')" />
    </div>

    <div>
        <x-input-label for="purpose" :value="__('What do you need this letter for?')" />
        <textarea id="purpose" name="purpose" rows="4" required
                  class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('purpose', $request?->purpose) }}</textarea>
        <x-input-error class="mt-2" :messages="$errors->get('purpose')" />
    </div>

    <div class="bg-gray-50 border border-gray-200 rounded-md p-4">
        <label for="include_salary" class="flex items-start gap-3">
            <input type="checkbox" id="include_salary" name="include_salary" value="1"
                   class="mt-0.5 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                   @checked(old('include_salary', $request?->include_salary)) />
            <span class="text-sm text-gray-700">
                <span class="font-medium">Include my salary in this letter.</span>
                <span class="block text-gray-500 mt-1">
                    Tick this only if the recipient requires it (banks and embassies usually do).
                    Requests that state salary must be approved by an HR administrator, so they
                    can take a little longer.
                </span>
            </span>
        </label>
        <x-input-error class="mt-2" :messages="$errors->get('include_salary')" />
    </div>
</div>
