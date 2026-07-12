<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your School Dice password was changed</title>
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
                            <div style="font-size:13px;color:#64748b;margin-top:2px">Account security alert</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px">
                            <h1 style="margin:0 0 14px;font-size:22px;line-height:1.3;color:#111827">Password changed</h1>

                            <p style="margin:0 0 16px">Hello {{ $name }},</p>

                            <p style="margin:0 0 18px">
                                The password for your School Dice account was changed.
                            </p>

                            <p style="margin:0 0 18px;color:#475569">
                                If this was you, no action is needed.
                            </p>

                            <p style="margin:0;color:#64748b;font-size:13px">
                                If this was not you, contact the school immediately so they can help secure the account.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
