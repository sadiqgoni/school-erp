# School Dice: Finance, Invoice, Parent Payment Testing Guide

This guide explains how to test the finance flow from school setup to parent payment. It is written for manual testing on local, staging, or production.

## 1. Users In This Test

Use two people:

- **School Admin / Finance Officer**
  - Creates fee setup.
  - Creates students and parents.
  - Creates invoices.
  - Generates checkout links.
  - Confirms that payment updates the invoice.

- **Parent / Guardian**
  - Logs into the parent portal.
  - Sees only invoices for linked children.
  - Downloads invoice PDF.
  - Pays the invoice through checkout.

## 2. Required Data Before Testing

Before testing invoices, confirm these records exist in the school portal:

1. **Academic Year**
   - Example: `2025/2026`
   - Mark current where needed.

2. **Term**
   - Example: `First Term`
   - Link it to the academic year.

3. **Class**
   - Example: `Primary 1`

4. **Student**
   - Create at least one student.
   - Place the student in a class for the academic year and term.

5. **Parent / Guardian**
   - Add a parent/guardian with email and phone.
   - Link the parent/guardian to the student.
   - The email is important because parent login uses it.

6. **Fee Type**
   - Example: `Tuition Fee`
   - Keep it active.

7. **Optional Accounts**
   - Income account.
   - Asset account.
   - Bank account.

These account fields help accounting reports, but the basic invoice and simulated payment test can still be checked without Paystack.

## 3. Create Fee Structure

Use this when the school charges a standard fee by class, session, and term.

1. Login as School Admin.
2. Open the school portal for the correct school.
3. Go to **Finance Setup**.
4. Open **Fee Structures**.
5. Click **Create**.
6. Select:
   - Academic year.
   - Term.
   - Class.
7. Add fee items:
   - Fee type: `Tuition Fee`
   - Amount: example `25000`
8. Save.

Expected result:

- A fee structure exists for the selected class.
- Standard invoices can pull the amount automatically when the matching fee type is selected.

## 4. Create Or Confirm Student And Parent

### Student

1. Go to **Students**.
2. Create or open a student.
3. Confirm the student has class placement/enrollment for the academic year and term.

### Parent / Guardian

1. Go to **Parents & Guardians**.
2. Create or open a guardian.
3. Confirm:
   - Name is correct.
   - Email is present.
   - Guardian is active.
   - Guardian is linked to the student.

Expected result:

- The parent is connected to the student through the guardian-student link.
- This link controls what the parent can see in the parent portal.

## 5. Create Parent Login

1. Go to **Parents & Guardians**.
2. Find the parent/guardian record.
3. In the row actions, click **Create login**.
4. If login already exists, the button may show **Sync login**.

Expected result:

- The system creates or syncs a user account for the parent.
- The parent user is linked to the school with role `parent`.
- The guardian record is linked to the user.
- The notification shows:
  - Email: parent email.
  - Temporary password: `password`.

Parent login details for test:

- Email: guardian email.
- Password: `password`.

After testing, change the parent password if this is production data.

## 6. Create A Student Invoice

1. Go to **Billing & Payments**.
2. Open **Student Invoices**.
3. Click **Create**.
4. In **Invoice Details**, select:
   - Student.
   - Academic year.
   - Term.
   - Invoice type.
   - Invoice date.
   - Due date.
   - Status: `Unpaid`.
5. For a normal school bill, choose:
   - Invoice type: `Standard invoice`.
6. In **Invoice Items**, add charges:
   - Fee type: `Tuition Fee`.
   - The amount should populate from the fee structure when class/year/term match.
7. Save.

Expected result:

- An invoice number is generated.
- Total is calculated.
- Amount paid is `0`.
- Balance equals total.
- Status is `Unpaid`.

## 7. Generate A Test Checkout Link

Use this for local/staging/manual testing without a live payment provider.

1. Go to **Student Invoices**.
2. Find the invoice.
3. In row actions, click **Checkout link**.

Expected result:

- The invoice gets:
  - Payment provider: `simulated`.
  - Payment reference.
  - Payment URL.
  - Gateway status: `initialized`.
4. After that, click **Open pay link** to open checkout directly.

The checkout route is:

```text
/payments/checkout?reference=PAYMENT_REFERENCE
```

The older simulated route also exists:

```text
/payments/simulated/checkout?reference=PAYMENT_REFERENCE
```

## 8. Parent Logs In And Pays

1. Logout from the school admin account.
2. Go to the portal login page:

```text
/portal/login
```

3. Login as the parent:
   - Email: guardian email.
   - Password: `password`.
4. Select the correct school/tenant if asked.
5. Open **Parent Portal**.
6. Go to **My Invoices**.
7. Confirm the parent only sees invoices for linked children.
8. Click **PDF** to test invoice PDF access.
9. Click **Pay**.

Expected result:

- If the invoice already has a payment URL, it opens that URL.
- If it does not have a payment URL, the system creates a simulated checkout link automatically.
- Parent is sent to the checkout page.

## 9. Complete Simulated Payment

On the checkout page:

1. Confirm:
   - School name.
   - Student name.
   - Invoice number.
   - Amount to pay.
   - Payment reference.
2. Choose a payment method:
   - Card.
   - Bank transfer.
   - Online banking.
3. Click **Pay NGN ...**.

Expected result:

- The payment completes.
- The invoice is updated.
- A fee payment/receipt is created.
- Invoice amount paid increases.
- Invoice balance reduces.
- If fully paid, invoice status becomes `Paid`.
- Payment confirmation communication logs may be created.

To test failed payment:

1. Open the checkout again.
2. Click **Fail payment**.

Expected result:

- Payment status is marked failed.
- Invoice should not be settled as paid.

## 10. School Admin Confirms Payment

Login again as School Admin.

Check these places:

1. **Student Invoices**
   - Invoice status.
   - Amount paid.
   - Balance.
   - Gateway status.

2. **Fee Payments**
   - New receipt number.
   - Student.
   - Invoice.
   - Amount.
   - Payment method.
   - Status.

3. **PDF**
   - Open invoice PDF.
   - Confirm payment rows and balance reflect the transaction.

4. **Communication Logs / Reminders** if available in the UI.
   - Invoice reminder logs.
   - Payment confirmation logs.

## 11. Manual Payment Test

Use this when a parent paid cash, POS, or bank transfer outside the checkout.

1. Go to **Billing & Payments**.
2. Open **Fee Payments**.
3. Click **Create**.
4. Select the invoice.
5. Enter:
   - Payer.
   - Payment date.
   - Amount.
   - Payment method.
   - Bank account or asset account if needed.
   - Status: `Confirmed`.
6. Save.

Expected result:

- Receipt number is generated.
- Invoice amount paid updates.
- Invoice balance updates.
- Invoice status becomes `Partial` or `Paid`.

## 12. Reminder Test

1. Go to **Student Invoices**.
2. Find an unpaid invoice.
3. Click **Queue reminder**.

Expected result:

- Communication log entries are created for active guardians.
- Reminder records are scheduled.
- Reminder text includes invoice number, balance, due date, and payment URL if available.

## 13. Security Checks

Test these carefully:

1. Parent A should not see Parent B's child invoices.
2. Parent PDF access should only work for linked children.
3. School Admin should see invoices in their own school.
4. A parent should not see admin finance setup pages.
5. A teacher should not see parent-only invoices unless also assigned parent access.

## 14. Common Problems And Fixes

### Parent cannot login

Check:

- Guardian has email.
- **Create login** or **Sync login** was clicked.
- User is active.
- User is attached to the school in `school_user` with role `parent`.
- Password is `password` unless changed.

### Parent logs in but sees no invoices

Check:

- Guardian is linked to the student.
- Guardian record has `user_id`.
- Student invoice belongs to the same student.
- Invoice belongs to the same school/tenant.

### Pay button opens no checkout

Check:

- Invoice balance is greater than zero.
- Invoice is not cancelled.
- Try row action **Checkout link** from school admin first.

### Standard invoice amount is empty

Check:

- Student has class enrollment for selected academic year and term.
- Fee structure exists for that class, academic year, term, and fee type.
- Fee type is active.

### Payment completes but invoice does not update

Check:

- Payment reference matches invoice `payment_reference`.
- Invoice provider is `simulated` for test checkout.
- Fee payment record was created.
- Run `php artisan optimize:clear` after deployment changes.

## 15. Full End-To-End Checklist

Use this checklist for sign-off:

- [ ] Academic year exists.
- [ ] Term exists.
- [ ] Class exists.
- [ ] Student exists and is enrolled.
- [ ] Guardian exists and is linked to student.
- [ ] Parent login created.
- [ ] Fee type exists.
- [ ] Fee structure exists.
- [ ] Student invoice created.
- [ ] Checkout link generated.
- [ ] Parent can login.
- [ ] Parent sees **My Invoices**.
- [ ] Parent can download invoice PDF.
- [ ] Parent can open checkout.
- [ ] Simulated payment succeeds.
- [ ] Invoice status updates.
- [ ] Fee payment receipt exists.
- [ ] Parent cannot see another student's invoice.

