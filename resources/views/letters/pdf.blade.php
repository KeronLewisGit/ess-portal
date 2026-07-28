{{-- Rendered by dompdf. Inline styles only: no external stylesheets, fonts or
     images are fetched — the letterhead assets arrive as base64 data URIs. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $request->reference_number }}</title>
    <style>
        @page { margin: 130px 60px 120px 60px; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #1a1a1a;
        }

        header {
            position: fixed;
            top: -100px; left: 0; right: 0;
            height: 90px;
        }

        header .logo { max-height: 60px; max-width: 220px; }

        header .company {
            font-size: 9pt;
            color: #555;
            text-align: right;
        }

        footer {
            position: fixed;
            bottom: -90px; left: 0; right: 0;
            height: 80px;
            font-size: 8pt;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 8px;
        }

        .ref { font-size: 9pt; color: #555; margin-bottom: 24px; }
        .body-text { white-space: pre-line; text-align: justify; }
        .signature { margin-top: 48px; }
        .signature img { max-height: 60px; }
        .signature .rule { border-top: 1px solid #333; width: 220px; margin-top: 4px; padding-top: 4px; }
        .verify { font-family: DejaVu Sans Mono, monospace; word-break: break-all; }
    </style>
</head>
<body>
    <header>
        <table width="100%">
            <tr>
                <td>
                    @if ($logo)
                        <img class="logo" src="{{ $logo }}" alt="">
                    @else
                        <strong>{{ $companyName }}</strong>
                    @endif
                </td>
                <td class="company">
                    {{ $companyName }}<br>
                    {!! nl2br(e($companyAddress)) !!}
                </td>
            </tr>
        </table>
    </header>

    <footer>
        @if ($footerText)
            <div>{{ $footerText }}</div>
        @endif
        <div style="margin-top:4px;">
            Verify this letter at <span class="verify">{{ $verificationUrl }}</span>
        </div>
    </footer>

    <main>
        <div class="ref">
            <strong>Ref:</strong> {{ $values['reference_number'] }}<br>
            <strong>Date:</strong> {{ $values['issue_date'] }}
        </div>

        <div class="body-text">{{ $body }}</div>

        <div class="signature">
            @if ($signature)
                <img src="{{ $signature }}" alt="">
            @endif
            <div class="rule">
                For and on behalf of<br>
                <strong>{{ $companyName }}</strong>
            </div>
        </div>
    </main>
</body>
</html>
