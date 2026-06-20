<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Monthly Salary Report - {{ $school?->name }} - {{ $month->format('F Y') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 10px; }
        .header { border-bottom: 2px solid #0f766e; padding-bottom: 10px; margin-bottom: 12px; text-align: center; }
        .logo { max-height: 56px; max-width: 56px; display: block; margin: 0 auto 6px; }
        h1 { margin: 0; font-size: 20px; color: #0f766e; }
        .muted { color: #64748b; }
        .summary { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin: 0 0 12px; }
        .summary td { width: 20%; border: 1px solid #dbe4ea; padding: 7px 8px; }
        .summary-key { display: block; font-size: 8px; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 2px; }
        .summary-value { display: block; font-size: 11px; font-weight: 700; }
        .summary-basic { background: #eff6ff; color: #1d4ed8; }
        .summary-earnings { background: #ecfdf5; color: #047857; }
        .summary-gross { background: #f8fafc; color: #0f172a; }
        .summary-deductions { background: #fef2f2; color: #b91c1c; }
        .summary-net { background: #f0fdf4; color: #166534; }
        table.report { width: 100%; border-collapse: collapse; }
        table.report th, table.report td { border: 1px solid #e5e7eb; padding: 6px; }
        table.report th { background: #f8fafc; text-align: left; font-size: 9px; }
        .right { text-align: right; }
        .total { background: #ecfdf5; font-weight: 700; }
        .staff-meta { color: #64748b; font-size: 8px; margin-top: 2px; }
    </style>
</head>
<body>
    <div class="header">
        @if (! empty($logoDataUri))
            <img src="{{ $logoDataUri }}" alt="School logo" class="logo">
        @endif
        <h1>Monthly Salary Report</h1>
        <div class="muted">{{ $school?->name }} · {{ $month->format('F Y') }}</div>
    </div>

    <table class="summary">
        <tr>
            <td class="summary-basic"><span class="summary-key">Basic Salary</span><span class="summary-value">NGN {{ number_format((float) $totals['basic_salary'], 2) }}</span></td>
            <td class="summary-earnings"><span class="summary-key">Allowances</span><span class="summary-value">NGN {{ number_format((float) $totals['allowances_total'], 2) }}</span></td>
            <td class="summary-gross"><span class="summary-key">Gross Pay</span><span class="summary-value">NGN {{ number_format((float) $totals['gross_pay'], 2) }}</span></td>
            <td class="summary-deductions"><span class="summary-key">Deductions</span><span class="summary-value">NGN {{ number_format((float) $totals['deductions_total'], 2) }}</span></td>
            <td class="summary-net"><span class="summary-key">Net Pay</span><span class="summary-value">NGN {{ number_format((float) $totals['net_pay'], 2) }}</span></td>
        </tr>
    </table>

    <table class="report">
        <thead>
            <tr>
                <th>Staff</th>
                <th>Grade</th>
                <th>Step</th>
                <th class="right">Basic</th>
                <th class="right">Allowances</th>
                <th class="right">Gross</th>
                <th class="right">Deductions</th>
                <th class="right">Net Pay</th>
            </tr>
        </thead>
        <tbody>
            @forelse($postings as $posting)
                <tr>
                    <td>{{ $posting->staff_name }}<div class="staff-meta">{{ $posting->staff_number }}</div></td>
                    <td>{{ $posting->grade_level }}</td>
                    <td>{{ $posting->step }}</td>
                    <td class="right">{{ number_format((float) $posting->basic_salary, 2) }}</td>
                    <td class="right">{{ number_format((float) $posting->allowances_total, 2) }}</td>
                    <td class="right">{{ number_format((float) $posting->gross_pay, 2) }}</td>
                    <td class="right">{{ number_format((float) $posting->deductions_total, 2) }}</td>
                    <td class="right">{{ number_format((float) $posting->net_pay, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="right">No salary postings found for this month.</td></tr>
            @endforelse
            <tr class="total">
                <td colspan="3" class="right">Totals</td>
                <td class="right">{{ number_format((float) $totals['basic_salary'], 2) }}</td>
                <td class="right">{{ number_format((float) $totals['allowances_total'], 2) }}</td>
                <td class="right">{{ number_format((float) $totals['gross_pay'], 2) }}</td>
                <td class="right">{{ number_format((float) $totals['deductions_total'], 2) }}</td>
                <td class="right">{{ number_format((float) $totals['net_pay'], 2) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
