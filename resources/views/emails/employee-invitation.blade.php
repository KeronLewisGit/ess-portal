<x-mail::message>
# Welcome to the {{ $companyName }} ESS Portal

Hello {{ $userName }},

An Employee Self-Service Portal account has been created for you. Use the button
below to set your password and sign in. This link will expire for security.

<x-mail::button :url="$setupUrl">
Set your password
</x-mail::button>

Once signed in you can request job letters and access your payslips.

If you did not expect this email, you can safely ignore it.

Thanks,<br>
{{ $companyName }} HR
</x-mail::message>
