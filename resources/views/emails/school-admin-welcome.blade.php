<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your School Dice portal login</title>
</head>
<body style="margin:0;background:#f6f8fb;color:#1f2937;font-family:Arial,Helvetica,sans-serif;line-height:1.6">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f6f8fb;padding:28px 12px">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden">
                    <tr>
                        <td style="padding:26px 30px;border-bottom:1px solid #eef2f7">
                            <div style="margin-bottom:14px">
                                <img src="{{ asset('images/branding/school-dice-logo-ful.png') }}" alt="School Dice logo" style="display:block;max-width:170px;height:auto">
                            </div>
                            <div style="font-size:18px;font-weight:700;color:#0f766e">School Dice</div>
                            <div style="font-size:13px;color:#64748b;margin-top:2px">{{ $school->name }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px">
                            <h1 style="margin:0 0 14px;font-size:22px;line-height:1.3;color:#111827">Your school portal is ready</h1>

                            <p style="margin:0 0 16px">Hello {{ $adminName }},</p>

                            <p style="margin:0 0 18px">
                                An administrator account has been created for {{ $school->name }}. Use the details below to sign in and complete the school setup.
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;margin:22px 0">
                                <tr>
                                    <td style="padding:16px 18px;border-bottom:1px solid #e2e8f0;color:#475569;width:36%">Portal</td>
                                    <td style="padding:16px 18px;border-bottom:1px solid #e2e8f0">
                                        <a href="{{ $portalUrl }}" style="color:#0f766e;font-weight:700;text-decoration:none">{{ $portalUrl }}</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:16px 18px;border-bottom:1px solid #e2e8f0;color:#475569">Email</td>
                                    <td style="padding:16px 18px;border-bottom:1px solid #e2e8f0;font-weight:700">{{ $email }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:16px 18px;color:#475569">Password</td>
                                    <td style="padding:16px 18px;font-weight:700">{{ $temporaryPassword }}</td>
                                </tr>
                            </table>

                            <p style="margin:0 0 26px">
                                <a href="{{ $portalUrl }}" style="display:inline-block;background:#0f766e;color:#ffffff;text-decoration:none;font-weight:700;padding:12px 18px;border-radius:7px">Open school portal</a>
                            </p>

                            <p style="margin:0;color:#64748b;font-size:13px">
                                If you were not expecting this email, please contact the person who created the school account.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
