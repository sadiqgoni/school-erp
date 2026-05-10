<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report Card - {{ $student->admission_number }}</title>
    <style>
        @page { margin: 28px; }
        body { color: #111827; font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        table { border-collapse: collapse; width: 100%; }
        .header td { vertical-align: top; }
        .logo { height: 70px; object-fit: contain; width: 70px; }
        .school-name { font-size: 22px; font-weight: 800; text-align: center; text-transform: uppercase; }
        .school-meta { font-size: 9px; line-height: 1.35; text-align: center; text-transform: uppercase; }
        .title { background: #111827; color: #fff; font-size: 14px; font-weight: 800; margin: 12px 0 8px; padding: 6px; text-align: center; text-transform: uppercase; }
        .meta td { border: 1px solid #9ca3af; padding: 5px 6px; }
        .label { color: #374151; font-size: 9px; font-weight: 700; text-transform: uppercase; }
        .value { font-weight: 800; text-transform: uppercase; }
        .results th { background: #111827; border: 1px solid #111827; color: #fff; font-size: 9px; padding: 6px; text-transform: uppercase; }
        .results td { border: 1px solid #9ca3af; padding: 5px 6px; }
        .results tbody tr:nth-child(even) td { background: #f3f4f6; }
        .right { text-align: right; }
        .center { text-align: center; }
        .summary td { border: 1px solid #9ca3af; padding: 6px; }
        .comments td { border: 1px solid #9ca3af; height: 52px; padding: 7px; vertical-align: top; }
        .signature { padding-top: 32px; text-align: center; }
        .line { border-top: 1px solid #111827; display: inline-block; padding-top: 4px; width: 180px; }
        .footer { color: #6b7280; font-size: 8px; margin-top: 14px; text-align: center; text-transform: uppercase; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td style="width: 90px;">
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
            <td style="width: 90px; text-align: right;">
                @if ($logoDataUri)
                    <img class="logo" src="{{ $logoDataUri }}" alt="School logo">
                @endif
            </td>
        </tr>
    </table>

    <div class="title">Student Report Card</div>

    <table class="meta">
        <tr>
            <td><div class="label">Name</div><div class="value">{{ $student->full_name }}</div></td>
            <td><div class="label">Admission No</div><div class="value">{{ $student->admission_number }}</div></td>
            <td><div class="label">Class</div><div class="value">{{ collect([$placement?->schoolClass?->name, $placement?->classSection?->name])->filter()->implode(' ') ?: 'Not Set' }}</div></td>
        </tr>
        <tr>
            <td><div class="label">Session</div><div class="value">{{ $reportCard->academicYear?->name }}</div></td>
            <td><div class="label">Term</div><div class="value">{{ $reportCard->term?->name ?? 'Whole Session' }}</div></td>
            <td><div class="label">Exam</div><div class="value">{{ $reportCard->exam?->name }}</div></td>
        </tr>
    </table>

    <br>

    <table class="results">
        <thead>
            <tr>
                <th style="width: 44px;">S/No</th>
                <th>Subject</th>
                <th class="right">Total</th>
                <th class="center">Grade</th>
                <th>Remark</th>
                <th class="center">Position</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($results as $result)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ $result->subject?->name }}</td>
                    <td class="right">{{ number_format((float) $result->total_score, 2) }}</td>
                    <td class="center">{{ $result->grade ?? '-' }}</td>
                    <td>{{ $result->remark ?? '-' }}</td>
                    <td class="center">{{ $result->position ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="center">No compiled subject results available.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <br>

    <table class="summary">
        <tr>
            <td><div class="label">Total Score</div><div class="value">{{ number_format((float) $reportCard->total_score, 2) }}</div></td>
            <td><div class="label">Average</div><div class="value">{{ number_format((float) $reportCard->average_score, 2) }}</div></td>
            <td><div class="label">Class Position</div><div class="value">{{ $reportCard->position ?? '-' }}</div></td>
            <td><div class="label">Status</div><div class="value">{{ strtoupper($reportCard->status) }}</div></td>
        </tr>
    </table>

    <br>

    <table class="comments">
        <tr>
            <td style="width: 50%;">
                <div class="label">Class Teacher Comment</div>
                {{ $reportCard->teacher_comment ?: 'No comment yet.' }}
            </td>
            <td style="width: 50%;">
                <div class="label">Principal Comment</div>
                {{ $reportCard->principal_comment ?: 'No comment yet.' }}
            </td>
        </tr>
    </table>

    <table>
        <tr>
            <td class="signature"><span class="line">Class Teacher</span></td>
            <td class="signature"><span class="line">Principal</span></td>
            <td class="signature"><span class="line">Parent / Guardian</span></td>
        </tr>
    </table>

    <div class="footer">Printed {{ now()->format('d/m/Y') }}</div>
</body>
</html>
