<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>School Dice</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

        <style>
            :root {
                --bg: #071719;
                --panel: #0d2427;
                --panel-soft: #102d31;
                --line: rgba(217, 239, 239, 0.12);
                --text: #f3fbfb;
                --muted: #adc6c8;
                --muted-strong: #d5e7e7;
                --accent: #41c7bd;
                --accent-dark: #0f5f63;
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                min-height: 100vh;
                background:
                    linear-gradient(135deg, rgba(65, 199, 189, 0.08), transparent 34%),
                    linear-gradient(180deg, #071719 0%, #0a1d20 100%);
                color: var(--text);
                font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
            }

            a {
                color: inherit;
                text-decoration: none;
            }

            .page {
                min-height: 100vh;
                display: grid;
                place-items: center;
                padding: 28px;
            }

            .shell {
                width: min(1080px, 100%);
                display: grid;
                grid-template-columns: minmax(0, 1fr) 390px;
                gap: 28px;
                align-items: stretch;
            }

            .intro,
            .access {
                border: 1px solid var(--line);
                background: rgba(13, 36, 39, 0.86);
                box-shadow: 0 26px 80px -50px rgba(0, 0, 0, 0.95);
            }

            .intro {
                min-height: 560px;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                padding: 34px;
                border-radius: 28px;
                background:
                    linear-gradient(145deg, rgba(13, 36, 39, 0.9), rgba(7, 23, 25, 0.96)),
                    repeating-linear-gradient(90deg, transparent 0 34px, rgba(255, 255, 255, 0.018) 34px 35px);
            }

            .brand {
                display: flex;
                align-items: center;
                gap: 14px;
            }

            .brand-logo {
                width: 100px;
                height: auto;
                display: block;
            }

            .brand-name {
                margin: 0;
                font-size: 28px;
                font-weight: 700;
            }

            .brand-subtitle {
                margin: 4px 0 0;
                font-size: 13px;
                color: var(--muted);
            }

            .intro-main {
                max-width: 660px;
                padding: 52px 0;
            }

            .intro-main h1 {
                margin: 0;
                max-width: 620px;
                font-size: clamp(38px, 5vw, 58px);
                line-height: 1.05;
                font-weight: 700;
                letter-spacing: 0;
            }

            .intro-main p {
                margin: 22px 0 0;
                max-width: 560px;
                font-size: 17px;
                line-height: 1.85;
                color: var(--muted);
            }

            .intro-footer {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 12px;
            }

            .mini {
                padding: 16px;
                border: 1px solid var(--line);
                border-radius: 16px;
                background: rgba(255, 255, 255, 0.035);
            }

            .mini strong {
                display: block;
                font-size: 14px;
                font-weight: 700;
                color: var(--muted-strong);
            }

            .mini span {
                display: block;
                margin-top: 7px;
                font-size: 13px;
                line-height: 1.55;
                color: var(--muted);
            }

            .access {
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                padding: 28px;
                border-radius: 28px;
                background: linear-gradient(180deg, rgba(16, 45, 49, 0.96), rgba(10, 29, 32, 0.98));
            }

            .access-label {
                margin: 0;
                font-size: 12px;
                font-weight: 700;
                letter-spacing: 0;
                color: var(--accent);
            }

            .access h2 {
                margin: 16px 0 0;
                font-size: 30px;
                line-height: 1.2;
                font-weight: 700;
                letter-spacing: 0;
            }

            .access p {
                margin: 14px 0 0;
                font-size: 15px;
                line-height: 1.8;
                color: var(--muted);
            }

            .portal-button {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 100%;
                min-height: 54px;
                margin-top: 26px;
                border-radius: 14px;
                background: var(--accent);
                color: #062123;
                font-size: 15px;
                font-weight: 700;
            }

            .portal-button:hover {
                background: #60ddd4;
            }

            .access-note {
                margin-top: 18px;
                padding: 14px;
                border: 1px solid var(--line);
                border-radius: 12px;
                background: rgba(255, 255, 255, 0.03);
            }

            .access-note strong {
                display: block;
                font-size: 12px;
                letter-spacing: 0.02em;
                color: var(--muted-strong);
            }

            .access-note ol {
                margin: 8px 0 0;
                padding-left: 16px;
            }

            .access-note li {
                margin-top: 6px;
                font-size: 12px;
                line-height: 1.6;
                color: var(--muted);
            }

            .access-footer {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-top: 42px;
                border-top: 1px solid var(--line);
                padding-top: 20px;
                font-size: 12px;
                line-height: 1.7;
                color: #8eaaad;
            }

            .footer-icon {
                width: 24px;
                height: 24px;
                border-radius: 6px;
                flex-shrink: 0;
            }

            @media (max-width: 940px) {
                .shell {
                    grid-template-columns: 1fr;
                }

                .intro {
                    min-height: auto;
                }
            }

            @media (max-width: 680px) {
                .page {
                    padding: 14px;
                }

                .intro,
                .access {
                    border-radius: 22px;
                    padding: 22px;
                }

                .intro-main {
                    padding: 42px 0;
                }

                .intro-main h1 {
                    font-size: 34px;
                }

                .intro-main p {
                    font-size: 15px;
                }

                .intro-footer {
                    grid-template-columns: 1fr;
                }
            }
        </style>
    </head>
    <body>
        <main class="page">
            <section class="shell">
                <div class="intro">
                    <header class="brand">
                        <div class="brand">
                            <img src="{{ asset('images/branding/school-dice-logo-icon.png') }}" alt="School Dice logo" class="brand-logo">
                            <h1 class="brand-name">School Dice</h1>
                        </div>
                    </header>

                    <div class="intro-main">
                        <h1>Welcome to your school workspace.</h1>
                        <p>
                            Continue with the records, classes, fees, attendance, and reports your school works with every day.
                        </p>
                    </div>

                    <div class="intro-footer">
                        <div class="mini">
                            <strong>Academics</strong>
                            <span>Sessions, classes, arms, and subjects.</span>
                        </div>
                        <div class="mini">
                            <strong>Records</strong>
                            <span>Students, guardians, staff, and placement.</span>
                        </div>
                        <div class="mini">
                            <strong>Office</strong>
                            <span>Fees, attendance, exams, and reports.</span>
                        </div>
                    </div>
                </div>

                <aside class="access">
                    <div>
                        <p class="access-label">Sign in</p>
                        <h2>Open your portal</h2>
                        <p>Use the account details provided for your school.</p>

                        <a href="{{ url('/portal/login') }}" class="portal-button">Continue</a>

                        <div class="access-note">
                            <strong>Before you continue</strong>
                            <ol>
                                <li>Enter your Email address* and password exactly as provided.</li>
                                <li>Use the school code and password shared by your admin.</li>
                                <li>Contact your school office if you cannot access your portal.</li>
                            </ol>
                        </div>
                    </div>

                    <div class="access-footer">
                        <img src="{{ asset('images/branding/school-dice-logo-icon.png') }}" alt="School Dice icon" class="footer-icon">
                        <span>School Dice keeps school operations in one controlled workspace.</span>
                    </div>
                </aside>
            </section>
        </main>
    </body>
</html>
