# Smart School ERP Roadmap

## Source Review

- Existing folder: Django REST API plus React frontend. It has useful domain ideas, but the structure and copy feel generated and inconsistent, so it should be treated as a reference only.
- Client screenshot: old tile dashboard with modules for Admin, SMS/Email, Notice, Reminder, Visitor, Staff, Salary, Staff Attendance, Leave, Student, Exam, Attendance, Homework, Activity, Calendar, Transport, Fee, Expense, Accounts, Library, LMS, Timetable, Online Exam, and E-News.
- Client feature list: student, staff, library, payroll, notifications, hostel, attendance, examinations, reports, and general school automation.
- PDF note: the supplied PDF appears to be image-based Samsung output, so it needs OCR/manual reading before we treat it as final requirements.

## Product Direction

Build a multi-tenant Laravel Filament school ERP where each school has isolated records, branding, users, settings, enabled modules, and subscription limits. Use Filament for fast, professional admin screens, then add parent/student/teacher portals only where Filament is not the right user experience.

## Tenancy Decision

Start with single-database row-level tenancy:

- Faster to build and deploy for many small/medium schools.
- Easier shared reporting and support access.
- Each tenant is a `School`; every operational record belongs to one school.
- Super admin can manage all schools, while school admins/staff only see their own school.
- If a future enterprise client needs database-per-school isolation, keep the data model clean enough to migrate.

## Core Roles

- Platform Admin: manages schools, subscriptions, global settings, and support.
- School Admin: manages one school and its modules.
- Finance Officer: fees, invoices, payments, expenses, accounts.
- HR/Admin Officer: staff, payroll, leave, attendance.
- Exam Officer: exams, grading, report sheets.
- Teacher: classes, attendance, homework, results, remarks.
- Librarian: books, issues, returns, barcode workflow.
- Parent/Guardian: children, fees, attendance, results, messages.
- Student: timetable, homework, attendance, fees status, results.

## Build Phases

### Phase 1: Foundation

- Laravel and Filament installation.
- School tenant model and user-school membership.
- Authentication, panel access, and platform admin account.
- Module navigation structure.
- Base audit fields, statuses, and import/export approach.

### Phase 2: Academic Setup

- Academic years, terms, arms/sections, classes, subjects.
- Class-subject assignment and teacher assignment.
- Student admission numbers and class enrollment.
- Promotion and transfer flow.

### Phase 3: People

- Student profiles, parent/guardian links, emergency contacts.
- Staff profiles, roles, departments, teaching assignments.
- Visitor log.
- Basic document/photo uploads.

### Phase 4: Daily Operations

- Student attendance.
- Staff attendance.
- Timetable/calendar.
- Homework and activities.
- Notices, SMS/email queue, reminders, and e-news.

### Phase 5: Finance

- Fee types and fee structures by class/term.
- Invoices/bills, receipts, discounts, scholarships, arrears.
- Expenses and accounts ledger.
- Payroll and salary runs.
- Payment gateway integration after the client confirms provider.

### Phase 6: Exams and Reports

- Exam setup, continuous assessment, score entry.
- Grade scales and result compilation.
- Report cards/mark sheets.
- Attendance, finance, student, staff, payroll, and academic reports.

### Phase 7: Extended Modules

- Library with book copies, barcode IDs, issue/return/fines.
- Hostel rooms, allocations, hostel fees, incidents.
- Transport routes, vehicles, drivers, student route assignment.
- LMS and online exam only after core school operations are stable.

## Immediate Checklist

- [x] Create Laravel project on Desktop.
- [x] Install Filament.
- [x] Add first project roadmap.
- [x] Add initial School tenancy foundation.
- [ ] Create platform admin user.
- [ ] Enable Filament tenant switcher after first school/user seed is ready.
- [ ] Add academic setup migrations and resources.
- [ ] Add student and guardian resources.
- [ ] Add staff and role resources.
- [ ] Review/OCR the PDF for hidden requirements.
