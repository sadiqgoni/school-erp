<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment status</title>
    <style>
        :root {
            color-scheme: light;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            --ink: #0f172a;
            --muted: #64748b;
            --line: #dbe4e9;
            --success: #0f766e;
            --danger: #b91c1c;
            --bg: #f6f8fb;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: var(--bg);
            color: var(--ink);
            padding: 24px;
        }

        main {
            width: min(560px, 100%);
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 8px;
            box-shadow: 0 18px 55px rgb(15 23 42 / 10%);
            padding: 28px;
        }

        .mark {
            width: 52px;
            height: 52px;
            border-radius: 999px;
            display: grid;
            place-items: center;
            color: #fff;
            background: var(--success);
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 18px;
        }

        .mark.failed {
            background: var(--danger);
        }

        h1 {
            margin: 0;
            font-size: 26px;
            line-height: 1.2;
        }

        p {
            color: var(--muted);
            line-height: 1.6;
            margin: 10px 0 0;
        }

        dl {
            display: grid;
            gap: 12px;
            margin: 24px 0;
            padding: 18px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fbfdff;
        }

        .row {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            border-bottom: 1px solid #edf2f7;
            padding-bottom: 12px;
        }

        .row:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        dt {
            color: var(--muted);
        }

        dd {
            margin: 0;
            text-align: right;
            font-weight: 700;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        a, button {
            border: 0;
            border-radius: 8px;
            padding: 11px 16px;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
        }

        a {
            background: var(--success);
            color: #fff;
        }

        button {
            background: #e2e8f0;
            color: var(--ink);
        }
    </style>
</head>
<body>
    @php
        $isSuccess = in_array($status, ['success', 'paid'], true) || $invoice->status === 'paid';
    @endphp

    <main>
        <div class="mark {{ $isSuccess ? '' : 'failed' }}">{{ $isSuccess ? 'OK' : '!' }}</div>

        <h1>{{ $isSuccess ? 'Payment received' : 'Payment not completed' }}</h1>
        <p>
            @if ($isSuccess)
                Thank you. The invoice has been updated and marked as {{ ucfirst($invoice->status) }}.
            @else
                We could not complete this payment. Please try again or contact the school with the reference below.
            @endif
        </p>

        <dl>
            <div class="row">
                <dt>Invoice</dt>
                <dd>{{ $invoice->invoice_number }}</dd>
            </div>
            <div class="row">
                <dt>Student</dt>
                <dd>{{ $invoice->student?->full_name ?? 'Student' }}</dd>
            </div>
            <div class="row">
                <dt>Amount paid</dt>
                <dd>NGN {{ number_format((float) ($payment?->amount ?? $invoice->amount_paid), 2) }}</dd>
            </div>
            <div class="row">
                <dt>Balance</dt>
                <dd>NGN {{ number_format((float) $invoice->balance, 2) }}</dd>
            </div>
            <div class="row">
                <dt>Receipt</dt>
                <dd>{{ $payment?->receipt_number ?? 'Not issued' }}</dd>
            </div>
            <div class="row">
                <dt>Reference</dt>
                <dd>{{ $invoice->payment_reference }}</dd>
            </div>
        </dl>

        <div class="actions">
            @if ($portalUrl)
                <a href="{{ $portalUrl }}">Back to invoices</a>
            @endif
            <button type="button" onclick="window.close()">Close</button>
        </div>
    </main>
</body>
</html>
