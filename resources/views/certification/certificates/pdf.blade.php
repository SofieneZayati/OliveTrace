<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Certificate {{ $certificate->certificate_number }}</title>
    <style>
        @page {
            margin: 15mm 15mm 15mm 15mm;
            size: a4 portrait;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #27272a;
            line-height: 1.4;
            font-size: 12px;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }

        .certificate-wrapper {
            border: 3px double #283e29;
            padding: 24px;
            position: relative;
            background-color: #ffffff;
        }

        .header-table {
            width: 100%;
            border-bottom: 2px solid #283e29;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .brand-title {
            font-size: 24px;
            font-weight: bold;
            color: #283e29;
            letter-spacing: 2px;
            margin: 0;
            text-transform: uppercase;
        }

        .brand-subtitle {
            font-size: 11px;
            color: #4d6b53;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-top: 4px;
        }

        .cert-header-right {
            text-align: right;
        }

        .cert-badge {
            display: inline-block;
            padding: 6px 14px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-radius: 4px;
        }

        .badge-valid {
            background-color: #eaf3ea;
            color: #1e6628;
            border: 1px solid #78b881;
        }

        .badge-invalid {
            background-color: #fdf2f2;
            color: #991b1b;
            border: 1px solid #f87171;
        }

        .document-title {
            text-align: center;
            margin: 20px 0 25px 0;
        }

        .document-title h1 {
            font-size: 20px;
            color: #1c2d1e;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin: 0;
            padding: 0;
        }

        .document-title p {
            font-size: 11px;
            color: #71717a;
            margin: 6px 0 0 0;
        }

        .cert-number-box {
            background-color: #f6f8f5;
            border: 1px solid #d5dfd3;
            border-radius: 4px;
            padding: 10px 14px;
            margin-bottom: 22px;
        }

        .cert-number-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #4d6b53;
            font-weight: bold;
        }

        .cert-number-value {
            font-size: 15px;
            font-weight: bold;
            color: #1c2d1e;
            font-family: 'Courier New', Courier, monospace;
            margin-top: 2px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 22px;
        }

        .info-table td {
            vertical-align: top;
            width: 50%;
            padding: 0 10px;
        }

        .info-box {
            background-color: #ffffff;
            border: 1px solid #e4e4e7;
            border-radius: 4px;
            padding: 12px 14px;
        }

        .section-header {
            font-size: 11px;
            font-weight: bold;
            color: #283e29;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 1px solid #e4e4e7;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }

        .info-row {
            margin-bottom: 7px;
            font-size: 11px;
        }

        .info-label {
            color: #71717a;
            font-weight: 500;
            width: 120px;
            display: inline-block;
        }

        .info-val {
            color: #18181b;
            font-weight: bold;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 22px;
        }

        .data-table th {
            background-color: #283e29;
            color: #ffffff;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 8px 10px;
            text-align: left;
            border: 1px solid #283e29;
        }

        .data-table td {
            font-size: 11px;
            padding: 8px 10px;
            border: 1px solid #e4e4e7;
        }

        .data-table tr:nth-child(even) td {
            background-color: #fafafa;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px dashed #d4d4d8;
        }

        .signature-table td {
            vertical-align: top;
            width: 50%;
            padding: 0 10px;
        }

        .seal-box {
            border: 2px solid #283e29;
            padding: 10px;
            text-align: center;
            border-radius: 4px;
            background-color: #fbfcfb;
        }

        .seal-title {
            font-size: 10px;
            font-weight: bold;
            color: #283e29;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .seal-subtitle {
            font-size: 8px;
            color: #52525b;
            margin-top: 3px;
        }

        .footer {
            margin-top: 25px;
            text-align: center;
            font-size: 9px;
            color: #71717a;
            border-top: 1px solid #e4e4e7;
            padding-top: 10px;
        }

        .verification-url {
            color: #283e29;
            font-weight: bold;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="certificate-wrapper">
        <table class="header-table">
            <tr>
                <td>
                    <div class="brand-title">OliveTrace</div>
                    <div class="brand-subtitle">Authenticity & Traceability Guarantee</div>
                </td>
                <td class="cert-header-right">
                    @if($valid)
                        <span class="cert-badge badge-valid">&#10003; VALID &amp; ACTIVE</span>
                    @else
                        <span class="cert-badge badge-invalid">&#10007; NOT VALID</span>
                    @endif
                </td>
            </tr>
        </table>

        <div class="document-title">
            <h1>Official Certificate of Analysis</h1>
            <p>Laboratory quality inspection &amp; origin certification for olive oil lot</p>
        </div>

        <div class="cert-number-box">
            <div class="cert-number-label">Certificate Serial Number</div>
            <div class="cert-number-value">{{ $certificate->certificate_number }}</div>
        </div>

        <table class="info-table">
            <tr>
                <td style="padding-left: 0;">
                    <div class="info-box">
                        <div class="section-header">Product &amp; Origin Details</div>
                        <div class="info-row">
                            <span class="info-label">Producer:</span>
                            <span class="info-val">{{ $certificate->certificateRequest?->producer?->name ?? 'N/A' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Oil Lot Number:</span>
                            <span class="info-val" style="font-family: 'Courier New', Courier, monospace;">{{ $certificate->certificateRequest?->oilLot?->lot_number ?? 'N/A' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Product Type:</span>
                            <span class="info-val">{{ \App\Enums\OilQuality::tryFrom($certificate->type)?->label() ?? $certificate->type }}</span>
                        </div>
                    </div>
                </td>
                <td style="padding-right: 0;">
                    <div class="info-box">
                        <div class="section-header">Certification Authority &amp; Validity</div>
                        <div class="info-row">
                            <span class="info-label">Issue Date:</span>
                            <span class="info-val">{{ $certificate->issue_date?->format('F d, Y') ?? 'N/A' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Valid Until:</span>
                            <span class="info-val">{{ $certificate->expiry_date?->format('F d, Y') ?? 'N/A' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Issuing Lab:</span>
                            <span class="info-val">{{ $certificate->certificateRequest?->labAnalysis?->labUser?->name ?? 'Official Partner Laboratory' }}</span>
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <div style="margin-bottom: 8px;">
            <div class="section-header" style="border-bottom: 2px solid #283e29; padding-bottom: 4px;">Laboratory Chemical Analysis</div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 35%;">Parameter / Test</th>
                    <th style="width: 25%;">Measured Value</th>
                    <th style="width: 25%;">Standard Threshold</th>
                    <th style="width: 15%; text-align: right;">Evaluation</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $analysis = $certificate->certificateRequest?->labAnalysis;
                    $acidity = $analysis?->acidity;
                    $peroxide = $analysis?->peroxide_value;
                @endphp
                <tr>
                    <td><strong>Free Acidity</strong> (% oleic acid)</td>
                    <td><strong>{{ $acidity !== null ? number_format($acidity, 2) . ' %' : 'N/A' }}</strong></td>
                    <td>&le; 0.80 % (Extra Virgin)</td>
                    <td style="text-align: right; font-weight: bold; color: {{ ($acidity !== null && $acidity <= 0.8) ? '#1e6628' : '#b45309' }};">
                        {{ ($acidity !== null && $acidity <= 0.8) ? 'PASS' : 'FLAG' }}
                    </td>
                </tr>
                <tr>
                    <td><strong>Peroxide Value</strong> (meq O<sub>2</sub>/kg)</td>
                    <td><strong>{{ $peroxide !== null ? number_format($peroxide, 2) . ' meq O2/kg' : 'N/A' }}</strong></td>
                    <td>&le; 20.00 meq O<sub>2</sub>/kg</td>
                    <td style="text-align: right; font-weight: bold; color: {{ ($peroxide !== null && $peroxide <= 20) ? '#1e6628' : '#b45309' }};">
                        {{ ($peroxide !== null && $peroxide <= 20) ? 'PASS' : 'FLAG' }}
                    </td>
                </tr>
                <tr>
                    <td><strong>Laboratory Decision</strong></td>
                    <td colspan="2">
                        @if($analysis?->result)
                            Official laboratory approval granted
                        @else
                            {{ $analysis ? 'Quality criteria not satisfied' : 'No analysis recorded' }}
                        @endif
                    </td>
                    <td style="text-align: right; font-weight: bold; color: {{ ($analysis?->result) ? '#1e6628' : '#991b1b' }};">
                        {{ ($analysis?->result) ? 'APPROVED' : 'REJECTED' }}
                    </td>
                </tr>
            </tbody>
        </table>

        <table class="signature-table">
            <tr>
                <td style="padding-left: 0;">
                    <div class="seal-box">
                        <div class="seal-title">OliveTrace Certified Origin</div>
                        <div class="seal-subtitle">Secure Digital Fingerprint</div>
                        <div style="font-family: 'Courier New', monospace; font-size: 8px; color: #71717a; margin-top: 5px;">
                            HASH: {{ substr(hash('sha256', $certificate->certificate_number . ($certificate->issue_date?->timestamp ?? 0)), 0, 32) }}
                        </div>
                    </div>
                </td>
                <td style="padding-right: 0; text-align: right;">
                    <div style="display: inline-block; text-align: left; min-width: 180px;">
                        <p style="font-size: 10px; color: #71717a; margin: 0 0 20px 0;">Authorized Signature / Verification:</p>
                        <div style="border-bottom: 1px solid #1c2d1e; width: 100%; margin-bottom: 4px;"></div>
                        <p style="font-size: 9px; color: #1c2d1e; font-weight: bold; margin: 0;">{{ $certificate->certificateRequest?->labAnalysis?->labUser?->name ?? 'OliveTrace Laboratory Network' }}</p>
                        <p style="font-size: 8px; color: #71717a; margin: 2px 0 0 0;">Certified Laboratory Inspector</p>
                    </div>
                </td>
            </tr>
        </table>

        <div class="footer">
            <p style="margin: 0 0 4px 0;">This document is digitally verifiable on the OliveTrace public registry.</p>
            <p style="margin: 0;">Verification URL: <span class="verification-url">{{ route('certificates.show', $certificate->certificate_number) }}</span></p>
            <p style="margin: 4px 0 0 0; font-size: 8px; color: #a1a1aa;">Generated on {{ now()->format('Y-m-d H:i:s') }} UTC &bull; Document Reference: {{ $certificate->certificate_number }}</p>
        </div>
    </div>
</body>
</html>
