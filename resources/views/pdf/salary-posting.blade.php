<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payslip - {{ $posting->staff_name }} - {{ $posting->payroll_month?->format('F Y') }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; line-height: 1.25; color: #334155; margin: 0; padding: 0; }
        body::before {
            content: "{{ strtoupper($school?->name ?: 'PAYSLIP') }}";
            position: fixed;
            top: 48%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-38deg);
            font-size: 56px;
            font-weight: 700;
            color: rgba(15, 118, 110, 0.05);
            z-index: -1;
            white-space: nowrap;
        }
        .container { padding: 10px 14px 14px; }
        .header { text-align: center; border-bottom: 2px solid #0f766e; padding-bottom: 6px; margin-bottom: 8px; }
        .logo { max-height: 58px; max-width: 58px; margin: 0 auto 6px; display: block; }
        .brand { font-size: 18px; font-weight: 700; color: #0f766e; margin-bottom: 2px; }
        .brand-copy { font-size: 11px; color: #475569; margin: 0; }
        .title { font-size: 15px; font-weight: 700; color: #0f172a; margin: 8px 0 2px; }
        .month { font-size: 11px; font-weight: 700; color: #475569; }
        .staff-box { border: 1px solid #dbe4ea; background: #f8fafc; border-radius: 4px; padding: 5px 6px; margin-bottom: 8px; }
        .staff-box table { width: 100%; border-collapse: collapse; }
        .staff-box td { padding: 2px 4px; vertical-align: top; }
        .label { font-weight: 700; color: #475569; width: 17%; }
        .summary { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .summary td { border: 1px solid #dbe4ea; padding: 6px 8px; width: 25%; }
        .summary-key { display: block; font-size: 8px; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 2px; }
        .summary-value { display: block; font-size: 13px; font-weight: 700; }
        .summary-basic { background: #eff6ff; color: #1d4ed8; }
        .summary-earnings { background: #ecfdf5; color: #047857; }
        .summary-deductions { background: #fef2f2; color: #b91c1c; }
        .summary-net { background: #f0fdf4; color: #166534; }
        .section-title { font-size: 11px; font-weight: 700; color: #0f766e; margin: 8px 0 3px; padding-bottom: 2px; border-bottom: 1px solid #99f6e4; }
        .half { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin-bottom: 6px; }
        .half td { width: 50%; vertical-align: top; }
        .lines { width: 100%; border-collapse: collapse; }
        .lines th { background: #f1f5f9; color: #334155; border: 1px solid #dbe4ea; padding: 5px 6px; font-size: 9px; text-align: left; }
        .lines td { border: 1px solid #e2e8f0; padding: 5px 6px; }
        .right { text-align: right; }
        .footer { margin-top: 10px; text-align: center; font-size: 9px; color: #64748b; }
        .footer-brand { display: block; margin-top: 4px; font-weight: 700; color: #0f766e; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            @if (! empty($logoDataUri))
                <img src="{{ $logoDataUri }}" alt="School logo" class="logo">
            @endif
            <div class="brand">{{ $school?->name }}</div>
            <p class="brand-copy">{{ $school?->address ?: ($school?->city ? $school->city . ', ' . $school->state : 'Nigeria') }}</p>
            <div class="title">Employee Payslip</div>
            <div class="month">{{ $posting->payroll_month?->format('F Y') }}</div>
        </div>

        <div class="staff-box">
            <table>
                <tr>
                    <td class="label">Name</td>
                    <td>{{ $posting->staff_name }}</td>
                    <td class="label">Staff ID</td>
                    <td>{{ $posting->staff_number ?: '' }}</td>
                    <td class="label">Month</td>
                    <td>{{ $posting->payroll_month?->format('F Y') }}</td>
                </tr>
                <tr>
                    <td class="label">Department</td>
                    <td>{{ $posting->staff?->department?->name ?: '' }}</td>
                    <td class="label">Designation</td>
                    <td>{{ $posting->staff?->job_title ?: '' }}</td>
                    <td class="label">Status</td>
                    <td>{{ ucfirst($posting->status ?: 'posted') }}</td>
                </tr>
                <tr>
                    <td class="label">Grade / Step</td>
                    <td>{{ trim(($posting->grade_level ?: '') . ($posting->step ? ' / Step ' . $posting->step : '')) }}</td>
                    <td class="label">Bank</td>
                    <td>{{ $posting->staff?->staffBank?->name ?: $posting->staff?->bank_name ?: '' }}</td>
                    <td class="label">Account Name</td>
                    <td>{{ $posting->staff?->bank_account_name ?: '' }}</td>
                </tr>
                <tr>
                    <td class="label">Account No.</td>
                    <td>{{ $posting->staff?->bank_account_number ?: '' }}</td>
                    <td class="label">Generated</td>
                    <td>{{ $posting->posted_at?->format('d M Y h:i A') ?: now()->format('d M Y h:i A') }}</td>
                    <td class="label"></td>
                    <td></td>
                </tr>
            </table>
        </div>

        <table class="summary">
            <tr>
                <td class="summary-basic"><span class="summary-key">Basic Salary</span><span class="summary-value">NGN {{ number_format((float) $posting->basic_salary, 2) }}</span></td>
                <td class="summary-earnings"><span class="summary-key">Total Earnings</span><span class="summary-value">NGN {{ number_format((float) $posting->allowances_total + (float) $posting->basic_salary, 2) }}</span></td>
                <td class="summary-deductions"><span class="summary-key">Total Deductions</span><span class="summary-value">NGN {{ number_format((float) $posting->deductions_total, 2) }}</span></td>
                <td class="summary-net"><span class="summary-key">Net Pay</span><span class="summary-value">NGN {{ number_format((float) $posting->net_pay, 2) }}</span></td>
            </tr>
        </table>

        <table class="half">
            <tr>
                <td>
                    <div class="section-title">Earnings</div>
                    <table class="lines">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th class="right">Amount (NGN)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Basic Salary</td>
                                <td class="right">{{ number_format((float) $posting->basic_salary, 2) }}</td>
                            </tr>
                            @forelse(($posting->allowance_breakdown ?? []) as $item)
                                <tr>
                                    <td>{{ $item['name'] ?? 'Earning' }}</td>
                                    <td class="right">{{ number_format((float) ($item['amount'] ?? 0), 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2">No additional earnings were generated for this month.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </td>
                <td>
                    <div class="section-title">Deductions</div>
                    <table class="lines">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th class="right">Amount (NGN)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($posting->deduction_breakdown ?? []) as $item)
                                <tr>
                                    <td>{{ $item['name'] ?? 'Deduction' }}</td>
                                    <td class="right">{{ number_format((float) ($item['amount'] ?? 0), 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2">No deductions were generated for this month.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>

        <div class="footer">
            This is a computer-generated payslip and does not require a signature.
            @if (filled($posting->notes))
                <br>{{ $posting->notes }}
            @endif
            <span class="footer-brand">Powered by School Dice</span>
        </div>
    </div>
</body>
</html>
