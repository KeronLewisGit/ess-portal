<div class="space-y-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" required
                          :value="old('name', $letterType->name)" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="code" :value="__('Code')" />
            <x-text-input id="code" name="code" type="text" class="mt-1 block w-full font-mono" required
                          :value="old('code', $letterType->code)" placeholder="EMPLOYMENT_CONFIRMATION" />
            <p class="mt-1 text-xs text-gray-500">Unique, uppercased automatically.</p>
            <x-input-error class="mt-2" :messages="$errors->get('code')" />
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div>
            <x-input-label for="reference_prefix" :value="__('Reference prefix')" />
            <x-text-input id="reference_prefix" name="reference_prefix" type="text" class="mt-1 block w-full font-mono"
                          required maxlength="10" :value="old('reference_prefix', $letterType->reference_prefix)" />
            <p class="mt-1 text-xs text-gray-500">
                Letters get numbers like <span class="font-mono">{{ $letterType->reference_prefix ?: 'LTR' }}-{{ now()->year }}-00001</span>.
            </p>
            <x-input-error class="mt-2" :messages="$errors->get('reference_prefix')" />
        </div>

        <div>
            <x-input-label for="description" :value="__('Description (optional)')" />
            <x-text-input id="description" name="description" type="text" class="mt-1 block w-full"
                          :value="old('description', $letterType->description)" />
            <p class="mt-1 text-xs text-gray-500">Shown to employees when they choose a letter type.</p>
            <x-input-error class="mt-2" :messages="$errors->get('description')" />
        </div>
    </div>

    <div>
        <x-input-label for="body_template" :value="__('Body template')" />
        <textarea id="body_template" name="body_template" rows="14" required
                  class="mt-1 block w-full font-mono text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('body_template', $letterType->body_template) }}</textarea>
        <x-input-error class="mt-2" :messages="$errors->get('body_template')" />

        <div class="mt-3 bg-gray-50 border border-gray-200 rounded-md p-4">
            <p class="text-xs font-medium text-gray-700">Available placeholders</p>
            <dl class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-1 text-xs text-gray-600">
                @foreach (App\Models\LetterType::PLACEHOLDERS as $token => $meaning)
                    <div class="flex gap-2">
                        <dt class="font-mono text-gray-800 whitespace-nowrap">{{ $token }}</dt>
                        <dd class="text-gray-500">{{ $meaning }}</dd>
                    </div>
                @endforeach
            </dl>
            <p class="mt-3 text-xs text-gray-500">
                <span class="font-mono">@{{salary}}</span> only renders when the employee has
                opted to include their salary and an HR administrator has approved the request.
            </p>
        </div>
    </div>

    <label for="is_active" class="flex items-center gap-2">
        <input type="checkbox" id="is_active" name="is_active" value="1"
               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
               @checked(old('is_active', $letterType->is_active)) />
        <span class="text-sm text-gray-700">Active — employees can request this letter</span>
    </label>
</div>
