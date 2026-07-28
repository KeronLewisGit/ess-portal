@php($department = $department ?? null)

<div class="space-y-4">
    <div>
        <x-input-label for="name" :value="__('Name')" />
        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $department?->name)" required />
        <x-input-error class="mt-1" :messages="$errors->get('name')" />
    </div>

    <div>
        <x-input-label for="code" :value="__('Code')" />
        <x-text-input id="code" name="code" class="mt-1 block w-full" :value="old('code', $department?->code)" required />
        <x-input-error class="mt-1" :messages="$errors->get('code')" />
    </div>

    <div>
        <x-input-label for="head_employee_id" :value="__('Department head')" />
        <select id="head_employee_id" name="head_employee_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            <option value="">—</option>
            @foreach ($employees as $employee)
                <option value="{{ $employee->id }}" @selected((string)old('head_employee_id', $department?->head_employee_id) === (string)$employee->id)>{{ $employee->employee_code }} — {{ $employee->first_name }} {{ $employee->last_name }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-1" :messages="$errors->get('head_employee_id')" />
    </div>
</div>
