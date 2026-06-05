# Payment and Communication Integration Plan

## Client Request

The client asked for an integrated version of the payment and communication system. This means fee billing, payment collection, parent reminders, receipts, and message tracking should work together instead of existing as separate admin tasks.

## Market Direction

Comparable school ERP systems usually connect payments and communication into one parent-facing workflow:

- Fedena supports SMS/messaging for fee dues, events, emergency alerts, and parent updates.
- Schoolites combines WhatsApp, SMS, email, push notifications, payment reminders, and payment confirmations.
- Schooli positions payment chasing and parent communication as one operational workflow.
- Spark includes billing, online payments, guardian portal communication, and student updates.
- Shikshaware advertises online payments with receipts sent through WhatsApp and email.
- Paystack and Flutterwave both support checkout/payment links and webhooks for automatic payment confirmation.

## Current Project Readiness

The ERP already has the foundation:

- Student invoices
- Student invoice items
- Fee payments
- PDF invoice/receipt flow
- Ledger accounts and finance posting
- Guardians and guardian-student contact settings
- Notices
- Placeholder communication logs
- Placeholder reminders

The main work is to connect these parts and add gateway/channel automation.

## Implementation Scenarios

### Scenario 1: Basic Integrated Version

Admin creates an invoice, the system records a message/reminder for the parent, and admin can manually record the payment. Once payment is recorded, a receipt/confirmation communication is logged.

Best for quick MVP and low-risk client demo.

### Scenario 2: Payment Gateway Integrated Version

The system creates a Paystack or Flutterwave checkout link for each invoice. Parent pays online, the gateway sends a webhook, the ERP verifies the transaction, updates invoice/payment records, and queues a receipt notification.

Best for production fee collection.

### Scenario 3: Full Communication Suite

The system supports event-triggered communication across in-app, email, SMS, WhatsApp, and later push notifications.

Useful triggers:

- Invoice created
- Fee due soon
- Fee overdue
- Payment received
- Partial payment received
- Attendance absence
- Result/report card published
- PTA/event notice

### Scenario 4: Parent Portal Version

Parents can log in to view invoices, pay online, download receipts, see payment history, and read school messages.

Best long-term product version.

### Scenario 5: WhatsApp-First Version

Parents receive WhatsApp reminders with payment links and automatic receipt confirmations. This is practical for many Nigerian private schools, but official WhatsApp Business API setup requires templates, opt-in handling, delivery webhooks, and provider configuration.

## Recommended Phasing

1. Add payment gateway fields, communication logs, and reminder structure.
2. Add fee reminder and receipt logging from invoice/payment events.
3. Integrate Paystack or Flutterwave checkout initialization.
4. Add webhook verification and automatic payment posting.
5. Add parent portal payment screen.
6. Add WhatsApp/SMS provider delivery and delivery-status webhooks.

## Recommended Client Message

We can integrate the payment and communication system so invoices, reminders, online payment links, receipts, and parent notifications all work together. Parents receive fee alerts, pay online, and automatically get confirmations, while the school dashboard updates instantly.

## References Checked

- Fedena SMS integration: https://fedena.com/feature-tour/sms-integration
- Fedena messaging system: https://fedena.com/feature-tour/messaging-system
- Schoolites communication suite: https://schoolites.com/services/communication-suite
- Schooli school management: https://www.myschooli.com/
- Paystack documentation: https://paystack.com/docs/
- Flutterwave webhooks: https://developer.flutterwave.com/docs/webhooks
- Flutterwave payment links: https://flutterwave.com/us/support/my-account/receiving-payments
