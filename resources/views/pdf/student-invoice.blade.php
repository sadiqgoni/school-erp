<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 24px 28px; }
        body {
            color: #0f172a;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10.5px;
            line-height: 1.25;
        }
        table { border-collapse: collapse; width: 100%; }
        .header td { vertical-align: top; }
        .logo-cell { width: 92px; text-align: center; }
        .logo {
            width: 72px;
            height: 72px;
            object-fit: contain;
        }
        .school-name {
            font-size: 23px;
            font-weight: 800;
            letter-spacing: .5px;
            text-align: center;
            text-transform: uppercase;
        }
        .school-meta {
            font-size: 9px;
            line-height: 1.35;
            text-align: center;
            text-transform: uppercase;
        }
        .title {
            border-bottom: 1px solid #0f766e;
            color: #0f766e;
            font-size: 13px;
            font-weight: 800;
            margin: 8px 0 6px;
            padding-bottom: 2px;
            text-align: center;
            text-transform: uppercase;
        }
        .meta td {
            border: 1px solid #94a3b8;
            font-size: 9.5px;
            padding: 3px 5px;
            text-transform: uppercase;
        }
        .meta .label {
            font-weight: 700;
            width: 16%;
        }
        .meta .value {
            font-weight: 800;
            width: 34%;
        }
        .items th {
            background: #d9efef;
            border: 1px solid #0f766e;
            color: #111827;
            font-size: 9.5px;
            padding: 5px 6px;
            font-weight: 800;
            text-transform: uppercase;
        }
        .items td {
            border-left: 1px solid #94a3b8;
            border-right: 1px solid #94a3b8;
            font-size: 10px;
            padding: 4px 6px;
        }
        .items tbody tr:nth-child(even) td {
            background: #f3f4f6;
        }
        .items tbody tr:nth-child(odd) td {
            background: #fafafa;
        }
        .items .sn {
            text-align: center;
            width: 48px;
        }
        .items .amount {
            font-family: DejaVu Sans Mono, monospace;
            font-weight: 700;
            text-align: right;
            width: 160px;
        }
        .bar td {
            background: #c7e8e5 !important;
            border: 1px solid #0f766e;
            color: #111827;
            font-size: 9.5px;
            font-weight: 800;
            padding: 5px 7px;
            text-transform: uppercase;
        }
        .summary td {
            border: 1px solid #94a3b8;
            padding: 7px 8px;
        }
        .summary .label {
            font-weight: 700;
        }
        .summary .amount {
            font-family: DejaVu Sans Mono, monospace;
            font-weight: 800;
            text-align: right;
            width: 180px;
        }
        .words {
            border-top: 1px solid #94a3b8;
            margin-top: 8px;
            padding-top: 5px;
        }
        .words-label {
            font-size: 8.5px;
            font-weight: 800;
            text-decoration: underline;
        }
        .words-value {
            font-size: 10px;
            margin-top: 2px;
        }
        .notes {
            margin-top: 8px;
            text-align: center;
        }
        .notes-label {
            font-size: 8.5px;
            font-weight: 800;
            text-decoration: underline;
        }
        .notes-body {
            font-size: 11px;
            font-weight: 700;
            margin-top: 4px;
        }
        .footer {
            background: #0f766e;
            color: #ffffff;
            font-size: 7px;
            margin-top: 14px;
            padding: 4px 6px;
            text-transform: uppercase;
        }
        .right { text-align: right; }
        .muted { color: #4b5563; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td class="logo-cell">
                @if ($logoDataUri)
                    <img class="logo" src="{{ $logoDataUri }}" alt="School logo">
                @endif
            </td>
            <td>
                <div class="school-name">{{ $school->name }}</div>
                <div class="school-meta">
                    {{ collect([$school->address, $school->city, $school->state, $school->country])->filter()->implode(', ') }}<br>
                    {{ collect([$school->phone ? 'Tel: '.$school->phone : null, $school->email])->filter()->implode(' | ') }}
                </div>
            </td>
            <td class="logo-cell">
                <img class="logo" src="{{ public_path('images/branding/school-dice-logo-icon.png') }}" alt="School Dice logo">
            </td>
        </tr>
    </table>

    <div class="title">School Tuition And Bill Summary</div>

    <table class="meta">
        <tr>
            <td class="label">Name</td>
            <td class="value">{{ $student->full_name }}</td>
            <td class="label">Term</td>
            <td class="value">{{ $invoice->term?->name ?? 'Whole Session' }}</td>
        </tr>
        <tr>
            <td class="label">Class</td>
            <td class="value">
                {{ collect([$placement?->schoolClass?->name, $placement?->classSection?->name])->filter()->implode(' ') ?: 'Not Set' }}
            </td>
            <td class="label">Session</td>
            <td class="value">{{ $invoice->academicYear?->name ?? 'Not Set' }}</td>
        </tr>
        <tr>
            <td class="label">Admission No</td>
            <td class="value">{{ $student->admission_number }}</td>
            <td class="label">Invoice No</td>
            <td class="value">{{ $invoice->invoice_number }}</td>
        </tr>
    </table>

    <br>

    <table class="items">
        <thead>
            <tr>
                <th class="sn">S/No</th>
                <th>Description</th>
                <th class="amount">Amount (NGN)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr>
                    <td class="sn">{{ $loop->iteration }}</td>
                    <td>{{ $item->feeType?->name ?? $item->description }}</td>
                    <td class="amount">{{ number_format((float) $item->amount, 2) }}</td>
                </tr>
            @endforeach

            @if ($invoice->items->isEmpty())
                <tr>
                    <td class="sn">1</td>
                    <td>No charges recorded</td>
                    <td class="amount">0.00</td>
                </tr>
            @endif

            <tr class="bar">
                <td colspan="2">Sub-Total</td>
                <td class="amount">{{ number_format((float) $invoice->subtotal, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="summary">
        <tr>
            <td class="label">Discount</td>
            <td class="amount">{{ number_format((float) $invoice->discount, 2) }}</td>
        </tr>
        <tr>
            <td class="label">Amount Paid</td>
            <td class="amount">{{ number_format((float) $invoice->amount_paid, 2) }}</td>
        </tr>
        <tr>
            <td class="label">Outstanding Balance</td>
            <td class="amount">{{ number_format((float) $invoice->balance, 2) }}</td>
        </tr>
        <tr class="bar">
            <td>Total For Term</td>
            <td class="amount">NGN {{ number_format((float) $invoice->total, 2) }}</td>
        </tr>
    </table>

    <div class="words">
        <div class="words-label">Bill Amount In Words</div>
        <div class="words-value">{{ $amountInWords }}</div>
    </div>

    <div class="notes">
        <div class="notes-label">School Account Information / Other Notes</div>
        <div class="notes-body">
            {{ $invoice->notes ?: 'Please pay all fees before the stated due date. Early payment helps the school serve you better.' }}
        </div>
        @if ($invoice->due_date)
            <div class="muted" style="margin-top: 4px;">Due Date: {{ $invoice->due_date->format('d/m/Y') }}</div>
        @endif
    </div>

    <div class="footer">
        <span>Bill summary print date: {{ now()->format('d/m/Y') }}</span>
        <span style="float: right;">Status: {{ strtoupper($invoice->status) }}</span>
    </div>
</body>
</html>
