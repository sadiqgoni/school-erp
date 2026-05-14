<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report Card - {{ $student->admission_number }}</title>
    <style>
        @page { margin: 24px; }
        body { color: #0f172a; font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        table { border-collapse: collapse; width: 100%; }
        .sheet { border: 2px solid #0f766e; padding: 10px; }
        .header td { vertical-align: middle; }
        .logo { height: 72px; object-fit: contain; width: 72px; }
        .student-photo { border: 1px solid #94a3b8; height: 82px; object-fit: cover; width: 72px; }
        .school-name { color: #0f766e; font-size: 22px; font-weight: 800; text-align: center; text-transform: uppercase; }
        .school-meta { color: #334155; font-size: 8.5px; line-height: 1.35; text-align: center; text-transform: uppercase; }
        .title { background: #0f766e; color: #fff; font-size: 13px; font-weight: 800; margin: 9px 0 7px; padding: 6px; text-align: center; text-transform: uppercase; }
        .meta td { border: 1px solid #94a3b8; padding: 4px 6px; }
        .label { color: #475569; font-size: 8px; font-weight: 700; text-transform: uppercase; }
        .value { font-weight: 800; text-transform: uppercase; }
        .summary-top { margin-top: 7px; }
        .summary-top td { background: #ecfdf5; border: 1px solid #0f766e; padding: 7px 6px; text-align: center; }
        .summary-top .label { color: #0f766e; }
        .summary-top .value { color: #0f172a; font-size: 13px; }
        .results th { background: #134e4a; border: 1px solid #134e4a; color: #fff; font-size: 8px; padding: 5px; text-transform: uppercase; }
        .results td { border: 1px solid #94a3b8; padding: 4px 5px; }
        .results tbody tr:nth-child(even) td { background: #f0fdfa; }
        .right { text-align: right; }
        .center { text-align: center; }
        .summary td { border: 1px solid #94a3b8; padding: 6px; }
        .section-title { color: #0f766e; font-size: 11px; font-weight: 800; margin: 10px 0 4px; text-transform: uppercase; }
        .traits-wrap td { vertical-align: top; }
        .traits th { background: #e0f2f1; border: 1px solid #94a3b8; font-size: 8px; padding: 4px; text-align: left; }
        .traits td { border: 1px solid #94a3b8; font-size: 8px; padding: 4px; }
        .domain-title { background: #0f766e; color: #fff; font-size: 9px; font-weight: 800; padding: 5px; text-transform: uppercase; }
        .comments td { border: 1px solid #94a3b8; height: 46px; padding: 7px; vertical-align: top; }
        .signature { padding-top: 26px; text-align: center; }
        .line { border-top: 1px solid #0f172a; display: inline-block; padding-top: 4px; width: 160px; }
        .footer { color: #64748b; font-size: 8px; margin-top: 10px; text-align: center; text-transform: uppercase; }
    </style>
</head>
<body>
<div class="sheet">
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
            <td style="width: 82px; text-align: right;">
                @if ($studentPhotoDataUri)
                    <img class="student-photo" src="{{ $studentPhotoDataUri }}" alt="Student photo">
                @endif
            </td>
        </tr>
    </table>

    <div class="title">Terminal Report Sheet</div>

    <table class="meta">
        <tr>
            <td><div class="label">Name</div><div class="value">{{ $student->full_name }}</div></td>
            <td><div class="label">Admission No</div><div class="value">{{ $student->admission_number }}</div></td>
            <td><div class="label">Class</div><div class="value">{{ collect([$placement?->schoolClass?->name, $placement?->classSection?->name])->filter()->implode(' ') ?: 'Not Set' }}</div></td>
        </tr>
        <tr>
            <td><div class="label">Session</div><div class="value">{{ $reportCard->academicYear?->name }}</div></td>
            <td><div class="label">Term / Exam</div><div class="value">{{ $reportCard->term?->name ?? 'Whole Session' }} - {{ $reportCard->exam?->name }}</div></td>
            <td><div class="label">Attendance</div><div class="value">{{ $reportCard->attendance_present_days }} / {{ $reportCard->attendance_total_days }} present</div></td>
        </tr>
    </table>

    <table class="summary-top">
        <tr>
            <td><div class="label">Total Score</div><div class="value">{{ number_format((float) $reportCard->total_score, 2) }} / {{ number_format((float) $expectedTotalScore, 2) }}</div></td>
            <td><div class="label">Average</div><div class="value">{{ number_format((float) $reportCard->average_score, 2) }}</div></td>
            <td><div class="label">Class Position</div><div class="value">{{ $reportCard->position ?? '-' }}</div></td>
            <td><div class="label">Highest Class Avg.</div><div class="value">{{ number_format((float) $highestClassAverage, 2) }}</div></td>
            <td><div class="label">CGPA</div><div class="value">{{ number_format((float) $cgpa, 2) }} / 5.00</div></td>
        </tr>
    </table>

    <div class="section-title">Academic Performance</div>
    <table class="results">
        <thead>
            <tr>
                <th style="width: 32px;">S/N</th>
                <th>Subject</th>
                @foreach ($components as $component)
                    <th class="center">{{ $component->name }}<br>({{ number_format((float) $component->max_score, (float) $component->max_score == (int) $component->max_score ? 0 : 2) }}%)</th>
                @endforeach
                <th class="right">Total<br>({{ number_format((float) $totalMaxScore, (float) $totalMaxScore == (int) $totalMaxScore ? 0 : 2) }}%)</th>
                <th class="center">Grade</th>
                <th>Remark</th>
                <th class="center">Pos.</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($results as $result)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ $result->subject?->name }}</td>
                    @foreach ($components as $component)
                        <td class="center">
                            {{ $scoreMatrix->get($result->subject_id)?->get($component->id)?->score ?? '-' }}
                        </td>
                    @endforeach
                    <td class="right">{{ number_format((float) $result->total_score, 2) }}</td>
                    <td class="center">{{ $result->grade ?? '-' }}</td>
                    <td>{{ $result->remark ?? '-' }}</td>
                    <td class="center">{{ $result->position ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 6 + $components->count() }}" class="center">No subject results available.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Conduct And Skills</div>
    <table class="traits-wrap">
        <tr>
            <td style="width: 50%; padding-right: 5px;">
                <div class="domain-title">Affective Domain</div>
                <table class="traits">
                    <thead>
                        <tr>
                            <th>Trait</th>
                            <th style="width: 62px;" class="center">Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($affectiveRatings as $rating)
                            <tr>
                                <td>{{ $rating->traitItem?->name }}</td>
                                <td class="center">{{ $rating->rating ? str_repeat('*', (int) $rating->rating).' '.$rating->rating : '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="center">No rating.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
            <td style="width: 50%; padding-left: 5px;">
                <div class="domain-title">Psychomotor Domain</div>
                <table class="traits">
                    <thead>
                        <tr>
                            <th>Trait</th>
                            <th style="width: 62px;" class="center">Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($psychomotorRatings as $rating)
                            <tr>
                                <td>{{ $rating->traitItem?->name }}</td>
                                <td class="center">{{ $rating->rating ? str_repeat('*', (int) $rating->rating).' '.$rating->rating : '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="center">No rating.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <div class="section-title">Remarks</div>
    <table class="comments">
        <tr>
            <td style="width: 50%;">
                <div class="label">Class Teacher Remark</div>
                {{ $reportCard->teacher_comment ?: 'No comment yet.' }}
            </td>
            <td style="width: 50%;">
                <div class="label">Head Teacher / Principal Remark</div>
                {{ $reportCard->principal_comment ?: 'No comment yet.' }}
            </td>
        </tr>
    </table>

    <table>
        <tr>
            <td class="signature"><span class="line">Class Teacher</span></td>
            <td class="signature"><span class="line">Head Teacher / Principal</span></td>
        </tr>
    </table>

    <div class="footer">Printed {{ now()->format('d/m/Y') }} | Powered by School Dice</div>
</div>
</body>
</html>
