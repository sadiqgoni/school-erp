<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset your School Dice password</title>
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
                            <div style="font-size:13px;color:#64748b;margin-top:2px">Password reset request</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px">
                            <h1 style="margin:0 0 14px;font-size:22px;line-height:1.3;color:#111827">Reset your password</h1>

                            <p style="margin:0 0 16px">Hello {{ $name }},</p>

                            <p style="margin:0 0 18px">
                                We received a request to reset the password for your School Dice account. Click the button below to choose a new password.
                            </p>

                            <p style="margin:0 0 26px">
                                <a href="{{ $url }}" style="display:inline-block;background:#0f766e;color:#ffffff;text-decoration:none;font-weight:700;padding:12px 18px;border-radius:7px">Reset password</a>
                            </p>

                            <p style="margin:0 0 16px;color:#475569">
                                This link expires in {{ $expiresInMinutes }} minutes.
                            </p>

                            <p style="margin:0 0 18px;color:#475569">
                                If the button does not work, copy and paste this link into your browser:
                            </p>

                            <p style="margin:0 0 22px;word-break:break-all;font-size:13px">
                                <a href="{{ $url }}" style="color:#0f766e">{{ $url }}</a>
                            </p>

                            <p style="margin:0;color:#64748b;font-size:13px">
                                If you did not request a password reset, you can safely ignore this email.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
