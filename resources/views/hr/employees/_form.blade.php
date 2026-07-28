{{-- Shared create/edit fields. $employee is null on create.
     Salary and national_id are NEVER pre-filled from stored values (they are
     encrypted and $hidden); on edit, leaving them blank keeps the current
     value. --}}
@php($employee = $employee ?? null)

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <x-input-label for="employee_code" :value="__('Employee code')" />
        <x-text-input id="employee_code" name="employee_code" class="mt-1 block w-full"
            :value="old('employee_code', $employee?->employee_code)" required />
        <x-input-error class="mt-1" :messages="$errors->get('employee_code')" />
    </div>

    <div>
        <x-input-label for="work_email" :value="__('Work email')" />
        <x-text-input id="work_email" name="work_email" type="email" class="mt-1 block w-full"
            :value="old('work_email', $employee?->work_email)" required />
        <x-input-error class="mt-1" :messages="$errors->get('work_email')" />
    </div>

    <div>
        <x-input-label for="first_name" :value="__('First name')" />
        <x-text-input id="first_name" name="first_name" class="mt-1 block w-full"
            :value="old('first_name', $employee?->first_name)" required />
        <x-input-error class="mt-1" :messages="$errors->get('first_name')" />
    </div>

    <div>
        <x-input-label for="last_name" :value="__('Last name')" />
        <x-text-input id="last_name" name="last_name" class="mt-1 block w-full"
            :value="old('last_name', $employee?->last_name)" required />
        <x-input-error class="mt-1" :messages="$errors->get('last_name')" />
    </div>

    <div>
        <x-input-label for="middle_name" :value="__('Middle name')" />
        <x-text-input id="middle_name" name="middle_name" class="mt-1 block w-full"
            :value="old('middle_name', $employee?->middle_name)" />
        <x-input-error class="mt-1" :messages="$errors->get('middle_name')" />
    </div>

    <div>
        <x-input-label for="national_id" :value="__('National ID')" />
        <x-text-input id="national_id" name="national_id" class="mt-1 block w-full" :value="old('national_id')"
            autocomplete="off" />
        <p class="mt-1 text-xs text-gray-500">
            Encrypted at rest.@if($employee) Leave blank to keep the current value.@endif
        </p>
        <x-input-error class="mt-1" :messages="$errors->get('national_id')" />
    </div>

    <div>
        <x-input-label for="personal_email" :value="__('Personal email')" />
        <x-text-input id="personal_email" name="personal_email" type="email" class="mt-1 block w-full"
            :value="old('personal_email', $employee?->personal_email)" />
        <x-input-error class="mt-1" :messages="$errors->get('personal_email')" />
    </div>

    <div>
        <x-input-label for="phone" :value="__('Phone')" />
        <x-text-input id="phone" name="phone" class="mt-1 block w-full"
            :value="old('phone', $employee?->phone)" />
        <x-input-error class="mt-1" :messages="$errors->get('phone')" />
    </div>

    <div>
        <x-input-label for="job_title" :value="__('Job title')" />
        <x-text-input id="job_title" name="job_title" class="mt-1 block w-full"
            :value="old('job_title', $employee?->job_title)" />
        <x-input-error class="mt-1" :messages="$errors->get('job_title')" />
    </div>

    <div>
        <x-input-label for="department_id" :value="__('Department')" />
        <select id="department_id" name="department_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            <option value="">—</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}" @selected((string)old('department_id', $employee?->department_id) === (string)$department->id)>{{ $department->name }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-1" :messages="$errors->get('department_id')" />
    </div>

    <div>
        <x-input-label for="manager_id" :value="__('Manager')" />
        <select id="manager_id" name="manager_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            <option value="">—</option>
            @foreach ($managers as $manager)
                @continue($employee && $manager->id === $employee->id)
                <option value="{{ $manager->id }}" @selected((string)old('manager_id', $employee?->manager_id) === (string)$manager->id)>{{ $manager->employee_code }} — {{ $manager->first_name }} {{ $manager->last_name }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-1" :messages="$errors->get('manager_id')" />
    </div>

    <div>
        <x-input-label for="employment_type" :value="__('Employment type')" />
        <select id="employment_type" name="employment_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            @foreach ($employmentTypes as $type)
                <option value="{{ $type->value }}" @selected(old('employment_type', $employee?->employment_type?->value) === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-1" :messages="$errors->get('employment_type')" />
    </div>

    <div>
        <x-input-label for="employment_status" :value="__('Employment status')" />
        <select id="employment_status" name="employment_status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            @foreach ($employmentStatuses as $status)
                <option value="{{ $status->value }}" @selected(old('employment_status', $employee?->employment_status?->value) === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-1" :messages="$errors->get('employment_status')" />
    </div>

    <div>
        <x-input-label for="date_hired" :value="__('Date hired')" />
        <x-text-input id="date_hired" name="date_hired" type="date" class="mt-1 block w-full"
            :value="old('date_hired', $employee?->date_hired?->toDateString())" />
        <x-input-error class="mt-1" :messages="$errors->get('date_hired')" />
    </div>

    <div>
        <x-input-label for="date_separated" :value="__('Date separated')" />
        <x-text-input id="date_separated" name="date_separated" type="date" class="mt-1 block w-full"
            :value="old('date_separated', $employee?->date_separated?->toDateString())" />
        <x-input-error class="mt-1" :messages="$errors->get('date_separated')" />
    </div>

    <div>
        <x-input-label for="annual_salary" :value="__('Annual salary')" />
        <x-text-input id="annual_salary" name="annual_salary" type="number" step="0.01" min="0"
            class="mt-1 block w-full" :value="old('annual_salary')" autocomplete="off" />
        <p class="mt-1 text-xs text-gray-500">
            Encrypted at rest.@if($employee) Leave blank to keep the current value.@endif
        </p>
        <x-input-error class="mt-1" :messages="$errors->get('annual_salary')" />
    </div>

    <div>
        <x-input-label for="salary_currency" :value="__('Salary currency')" />
        <x-text-input id="salary_currency" name="salary_currency" class="mt-1 block w-full"
            :value="old('salary_currency', $employee?->salary_currency ?? config('ess.defaults.salary_currency'))" maxlength="3" />
        <x-input-error class="mt-1" :messages="$errors->get('salary_currency')" />
    </div>

    <div>
        <x-input-label for="pay_frequency" :value="__('Pay frequency')" />
        <select id="pay_frequency" name="pay_frequency" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            @foreach ($payFrequencies as $frequency)
                <option value="{{ $frequency->value }}" @selected(old('pay_frequency', $employee?->pay_frequency?->value) === $frequency->value)>{{ $frequency->label() }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-1" :messages="$errors->get('pay_frequency')" />
    </div>
</div>
