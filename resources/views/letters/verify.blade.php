<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- A public page: keep it out of search results. --}}
    <meta name="robots" content="noindex, nofollow">
    <title>Letter verification — {{ \App\Models\Setting::get('company_name', config('app.name')) }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="h-full font-sans antialiased text-gray-900">
    <div class="min-h-full flex flex-col items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <h1 class="text-center text-lg font-semibold text-gray-700 mb-6">
                {{ \App\Models\Setting::get('company_name', config('app.name')) }} — letter verification
            </h1>

            @if ($letter === null)
                <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                    <div class="bg-gray-700 px-6 py-4 text-white">
                        <p class="font-semibold">No matching letter</p>
                    </div>
                    <div class="px-6 py-5 text-sm text-gray-600">
                        We can't find a letter for that verification link. Check the link
                        was copied in full from the document, or contact the issuer.
                    </div>
                </div>
            @elseif ($letter->isRevoked())
                <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                    <div class="bg-red-600 px-6 py-4 text-white">
                        <p class="font-semibold">Revoked — do not rely on this letter</p>
                    </div>
                    <div class="px-6 py-5 space-y-3 text-sm">
                        <p class="text-gray-600">
                            This letter was issued but has since been revoked by the issuer.
                            Please contact them before acting on it.
                        </p>
                        <dl class="divide-y divide-gray-100">
                            <div class="flex justify-between py-2">
                                <dt class="text-gray-500">Reference</dt>
                                <dd class="font-mono">{{ $letter->reference_number }}</dd>
                            </div>
                            <div class="flex justify-between py-2">
                                <dt class="text-gray-500">Revoked on</dt>
                                <dd>{{ $letter->revoked_at->format('d M Y') }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            @else
                <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                    <div class="bg-green-600 px-6 py-4 text-white">
                        <p class="font-semibold">Valid letter</p>
                    </div>
                    <div class="px-6 py-5 text-sm">
                        <dl class="divide-y divide-gray-100">
                            <div class="flex justify-between py-2">
                                <dt class="text-gray-500">Reference</dt>
                                <dd class="font-mono">{{ $letter->reference_number }}</dd>
                            </div>
                            <div class="flex justify-between py-2">
                                <dt class="text-gray-500">Issued to</dt>
                                <dd>{{ $initials }}</dd>
                            </div>
                            <div class="flex justify-between py-2">
                                <dt class="text-gray-500">Type</dt>
                                <dd>{{ $letter->letterRequest->letterType->name }}</dd>
                            </div>
                            <div class="flex justify-between py-2">
                                <dt class="text-gray-500">Issued on</dt>
                                <dd>{{ $letter->issued_at->format('d M Y') }}</dd>
                            </div>
                        </dl>
                        <p class="mt-4 text-xs text-gray-500">
                            This page confirms a letter with this reference was issued by us on
                            the date shown. For privacy, the holder's full name and the contents
                            of the letter are not disclosed here — compare the reference against
                            the document you were given.
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</body>
</html>
