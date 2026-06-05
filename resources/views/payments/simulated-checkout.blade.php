<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout - {{ $invoice->invoice_number }}</title>
    <style>
        :root {
            color-scheme: light;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #0f172a;
            background: #eef3f7;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            background:
                radial-gradient(circle at 20% 10%, rgba(20, 184, 166, 0.15), transparent 24%),
                linear-gradient(180deg, #f8fbfd 0%, #eaf0f6 100%);
        }

        .checkout {
            width: min(100%, 980px);
            display: grid;
            grid-template-columns: 0.95fr 1.05fr;
            overflow: hidden;
            border-radius: 10px;
            background: #ffffff;
            box-shadow: 0 24px 70px rgba(15, 23, 42, 0.18);
        }

        .summary {
            padding: 34px;
            color: #ffffff;
            background:
                radial-gradient(circle at 20% 20%, rgba(45, 212, 191, 0.25), transparent 28%),
                linear-gradient(160deg, #011b33 0%, #073b4c 55%, #0f766e 100%);
        }

        .merchant {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .merchant-mark {
            width: 44px;
            height: 44px;
            display: grid;
            place-items: center;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.14);
            font-weight: 900;
        }

        .merchant strong {
            display: block;
            font-size: 17px;
        }

        .merchant span {
            display: block;
            margin-top: 2px;
            color: rgba(226, 232, 240, 0.75);
            font-size: 13px;
        }

        .amount {
            margin-top: 58px;
        }

        .amount span {
            color: rgba(226, 232, 240, 0.75);
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .amount strong {
            display: block;
            margin-top: 10px;
            font-size: clamp(32px, 5vw, 48px);
            line-height: 1;
        }

        .details {
            margin-top: 42px;
            display: grid;
            gap: 14px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            padding-bottom: 14px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.14);
            color: rgba(226, 232, 240, 0.82);
            font-size: 14px;
        }

        .detail-row b {
            color: #ffffff;
            text-align: right;
        }

        .powered {
            margin-top: 42px;
            color: rgba(226, 232, 240, 0.68);
            font-size: 13px;
        }

        .panel {
            padding: 28px;
        }

        .panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding-bottom: 18px;
            border-bottom: 1px solid #e5edf3;
        }

        .panel-head h1 {
            margin: 0;
            color: #011b33;
            font-size: 20px;
        }

        .close {
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            border: 0;
            border-radius: 999px;
            color: #64748b;
            background: #f1f5f9;
            font-size: 20px;
        }

        .notice {
            margin-top: 18px;
            padding: 12px 14px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
        }

        .notice.success {
            color: #14532d;
            background: #dcfce7;
        }

        .notice.failed {
            color: #7f1d1d;
            background: #fee2e2;
        }

        .methods {
            margin-top: 22px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .method {
            min-height: 78px;
            padding: 12px;
            border: 1px solid #dbe6ee;
            border-radius: 8px;
            color: #475569;
            background: #ffffff;
            text-align: left;
            cursor: pointer;
        }

        .method.is-active {
            border-color: #0ba4db;
            background: #eef9fe;
            color: #011b33;
            box-shadow: 0 0 0 3px rgba(11, 164, 219, 0.12);
        }

        .method strong {
            display: block;
            margin-bottom: 4px;
            font-size: 14px;
        }

        .method span {
            font-size: 12px;
        }

        form {
            margin-top: 22px;
        }

        .method-panel {
            display: none;
            gap: 14px;
        }

        .method-panel.is-active {
            display: grid;
        }

        label {
            display: grid;
            gap: 7px;
            color: #334155;
            font-size: 13px;
            font-weight: 750;
        }

        input,
        select {
            width: 100%;
            min-height: 46px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            padding: 0 13px;
            color: #0f172a;
            background: #ffffff;
            font: inherit;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: #0ba4db;
            box-shadow: 0 0 0 3px rgba(11, 164, 219, 0.12);
        }

        .split {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .bank-box {
            display: grid;
            gap: 8px;
            padding: 14px;
            border: 1px dashed #94a3b8;
            border-radius: 8px;
            background: #f8fafc;
            color: #334155;
            font-size: 14px;
        }

        .bank-box b {
            color: #011b33;
        }

        .hint {
            color: #64748b;
            font-size: 13px;
            line-height: 1.55;
        }

        .actions {
            margin-top: 22px;
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 12px;
        }

        .pay,
        .fail {
            min-height: 48px;
            border: 0;
            border-radius: 7px;
            font-weight: 850;
            cursor: pointer;
            transition: opacity 0.2s ease, transform 0.2s ease;
        }

        .pay {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            color: #ffffff;
            background: #0ba4db;
        }

        .pay .spinner {
            display: none;
            width: 18px;
            height: 18px;
            border: 3px solid rgba(255, 255, 255, 0.38);
            border-top-color: #ffffff;
            border-radius: 999px;
            animation: spin 0.75s linear infinite;
        }

        .pay.is-loading .spinner {
            display: inline-block;
        }

        .pay.is-loading {
            opacity: 0.92;
            cursor: wait;
        }

        form.is-processing .fail,
        form.is-processing .method,
        form.is-processing input,
        form.is-processing select {
            pointer-events: none;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .fail {
            padding: 0 18px;
            color: #7f1d1d;
            background: #fee2e2;
        }

        .done {
            margin-top: 22px;
            padding: 18px;
            border-radius: 8px;
            background: #f8fafc;
            color: #334155;
            text-align: center;
        }

        @media (max-width: 820px) {
            body {
                padding: 12px;
            }

            .checkout {
                grid-template-columns: 1fr;
            }

            .summary {
                padding: 24px;
            }

            .amount {
                margin-top: 28px;
            }
        }

        @media (max-width: 560px) {
            .panel {
                padding: 20px;
            }

            .methods,
            .split,
            .actions {
                grid-template-columns: 1fr;
            }

            .fail {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    @php
        $freshInvoice = $invoice->fresh();
        $balance = (float) $freshInvoice->balance;
        $transferAccount = '80'.str_pad((string) $invoice->getKey(), 8, '0', STR_PAD_LEFT);
    @endphp

    <main class="checkout">
        <section class="summary">
            <div class="merchant">
                <div class="merchant-mark">SD</div>
                <div>
                    <strong>{{ $invoice->school?->name ?? 'School Dice' }}</strong>
                    <span>Secure checkout</span>
                </div>
            </div>

            <div class="amount">
                <span>Amount to pay</span>
                <strong>NGN {{ number_format($balance, 2) }}</strong>
            </div>

            <div class="details">
                <div class="detail-row">
                    <span>Invoice</span>
                    <b>{{ $invoice->invoice_number }}</b>
                </div>
                <div class="detail-row">
                    <span>Student</span>
                    <b>{{ $invoice->student?->full_name ?? 'Student' }}</b>
                </div>
                <div class="detail-row">
                    <span>Reference</span>
                    <b>{{ $invoice->payment_reference }}</b>
                </div>
            </div>

            <div class="powered">Protected checkout powered by School Dice payments.</div>
        </section>

        <section class="panel">
            <div class="panel-head">
                <h1>Complete payment</h1>
                <button class="close" type="button" onclick="window.close()" aria-label="Close checkout">×</button>
            </div>

            @if (session('status') === 'success')
                <div class="notice success">Payment completed. The invoice has been updated in the school portal.</div>
            @elseif (session('status') === 'failed')
                <div class="notice failed">Payment failed. Please try another payment option.</div>
            @elseif (session('status') === 'unmatched')
                <div class="notice failed">Payment could not be matched to an invoice.</div>
            @endif

            @if ($balance > 0)
                <div class="methods" role="tablist" aria-label="Payment methods">
                    <button class="method is-active" data-payment-method="card" type="button">
                        <strong>Card</strong>
                        <span>Pay with debit or credit card</span>
                    </button>
                    <button class="method" data-payment-method="bank_transfer" type="button">
                        <strong>Bank / Teller</strong>
                        <span>Transfer or enter teller details</span>
                    </button>
                    <button class="method" data-payment-method="online_banking" type="button">
                        <strong>Online Banking</strong>
                        <span>Authorize from your bank</span>
                    </button>
                </div>

                <form method="post" action="{{ route('payments.complete') }}" data-payment-form>
                    @csrf
                    <input type="hidden" name="reference" value="{{ $invoice->payment_reference }}">
                    <input type="hidden" name="payment_method" value="card" data-payment-method-input>

                    <div class="method-panel is-active" data-payment-panel="card">
                        <label>
                            Card number
                            <input name="card_number" inputmode="numeric" autocomplete="cc-number" placeholder="4084 0840 8408 4081">
                        </label>
                        <div class="split">
                            <label>
                                Expiry
                                <input name="expiry" placeholder="MM / YY" autocomplete="cc-exp">
                            </label>
                            <label>
                                CVV
                                <input name="cvv" inputmode="numeric" placeholder="123" autocomplete="cc-csc">
                            </label>
                        </div>
                        <input type="hidden" name="card_last4" value="4081">
                        <p class="hint">Enter your card details to authorize this payment securely.</p>
                    </div>

                    <div class="method-panel" data-payment-panel="bank_transfer">
                        <div class="bank-box">
                            <span>Transfer to</span>
                            <b>School Dice Bank</b>
                            <b>Account number: {{ $transferAccount }}</b>
                            <span>Amount: NGN {{ number_format($balance, 2) }}</span>
                        </div>
                        <label>
                            Teller / transfer reference
                            <input name="teller_number" placeholder="Enter teller or transfer reference">
                        </label>
                        <input type="hidden" name="bank" value="School Dice Bank">
                        <p class="hint">After transfer or teller deposit, enter the payment reference for confirmation.</p>
                    </div>

                    <div class="method-panel" data-payment-panel="online_banking">
                        <label>
                            Select bank
                            <select name="bank">
                                <option value="Access Bank">Access Bank</option>
                                <option value="GTBank">GTBank</option>
                                <option value="Zenith Bank">Zenith Bank</option>
                                <option value="UBA">UBA</option>
                                <option value="First Bank">First Bank</option>
                            </select>
                        </label>
                        <label>
                            Account email or phone
                            <input name="bank_login" placeholder="name@example.com or phone number">
                        </label>
                        <p class="hint">Choose your bank and continue to authorize the payment.</p>
                    </div>

                    <div class="actions">
                        <button class="pay" type="submit" name="outcome" value="success" data-pay-button>
                            <span class="spinner" aria-hidden="true"></span>
                            <span data-pay-label>Pay NGN {{ number_format($balance, 2) }}</span>
                        </button>
                        <button class="fail" type="submit" name="outcome" value="failed">Fail payment</button>
                    </div>
                </form>
            @else
                <div class="done">This invoice has already been paid.</div>
            @endif
        </section>
    </main>
    <script>
        document.querySelectorAll('[data-payment-method]').forEach((button) => {
            button.addEventListener('click', () => {
                const method = button.dataset.paymentMethod;

                document.querySelectorAll('[data-payment-method]').forEach((item) => {
                    item.classList.toggle('is-active', item === button);
                });

                document.querySelectorAll('[data-payment-panel]').forEach((panel) => {
                    panel.classList.toggle('is-active', panel.dataset.paymentPanel === method);
                });

                document.querySelector('[data-payment-method-input]').value = method;
            });
        });

        document.querySelector('[data-payment-form]')?.addEventListener('submit', (event) => {
            const submitter = event.submitter;

            if (! submitter?.matches('[data-pay-button]')) {
                return;
            }

            const form = event.currentTarget;
            const button = submitter;
            const label = button.querySelector('[data-pay-label]');

            form.classList.add('is-processing');
            button.classList.add('is-loading');
            button.disabled = true;
            label.textContent = 'Processing payment...';
        });
    </script>
</body>
</html>
