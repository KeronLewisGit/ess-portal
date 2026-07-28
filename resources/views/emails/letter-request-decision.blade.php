@component('mail::message')
# Your letter request was {{ $request->status->label() }}

Reference **{{ $request->reference_number }}** — {{ $request->letterType->name }}.

@if ($request->status === App\Enums\LetterRequestStatus::Approved)
Your request has been approved. You can view it in the portal; the signed
letter will be available to download once it has been generated.
@else
Your request was not approved.

@if ($request->decision_notes)
@component('mail::panel')
{{ $request->decision_notes }}
@endcomponent
@endif

You can submit a new request from the portal if you still need this letter.
@endif

@component('mail::button', ['url' => $url])
View request
@endcomponent

Thanks,<br>
{{ $companyName }}
@endcomponent
