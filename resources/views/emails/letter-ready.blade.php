@component('mail::message')
# Your letter is ready

Reference **{{ $letter->reference_number }}** —
{{ $letter->letterRequest->letterType->name }} — has been issued and is ready
to download from the portal.

@component('mail::button', ['url' => $url])
Download your letter
@endcomponent

For your security the letter isn't attached to this email. Sign in to the
portal to download it; the download link expires shortly after you request it.

Thanks,<br>
{{ $companyName }}
@endcomponent
