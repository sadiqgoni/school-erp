# School Dice User Portals and Teacher Flow

This document defines how school users should work in the system so future development stays consistent.

## Core Decision

School Dice should support different user portals, but we should not give every person the same dashboard.

- School admin and office staff use the main school portal.
- Teachers use a restricted teacher workspace.
- Parents use a parent portal focused only on their children.
- Students can be added later if the school really needs student self-service.
- NFC/device tracking should be treated as a separate attendance/safety integration, not mixed directly into ordinary login.

## User Account Strategy

It is okay to have thousands of users in the system. Laravel can handle that easily if we design it properly.

The important rule is:

- Every login account is a `User`.
- A teacher login is linked to one `Staff` profile.
- A parent login is linked to one `Guardian` profile.
- A student login, if enabled, is linked to one `Student` profile.

Do not duplicate staff, parent, or student records just because they need to log in.

## Teacher Registration Flow

Teachers should be registered from `Staff & HR -> Staff Directory`.

Recommended flow:

1. School admin creates the staff profile.
2. If the staff member should log in, the admin turns on `Create login account`.
3. The system creates a `User` account and links it to `staff.user_id`.
4. The teacher receives their email and temporary password.
5. When the teacher logs in, the system finds the linked staff profile and shows only their work.

Teaching staff and non-teaching staff can both have user accounts, but their permissions should differ.

## Form Teacher vs Subject Teacher

These are different jobs and must stay separate.

### Form Teacher

A form teacher is responsible for a class or arm.

Examples:

- Primary 5A form teacher
- JSS 2B form teacher
- Nursery 1 class teacher

Form teachers should see:

- Students in their assigned class/arm
- Attendance for their class/arm
- Basic class records
- Conduct/remarks for report cards
- Class-level notices or follow-up tasks

Form teacher assignment should use `TeachingAssignment` with:

- `assignment_role = form_teacher`
- `assignment_role = assistant_form_teacher`
- class and optional arm
- no subject required

### Subject Teacher

A subject teacher teaches a subject across one or more classes.

Examples:

- Biology teacher for SS 1, SS 2, and SS 3
- Mathematics teacher for JSS 1A and JSS 1B
- English teacher for Primary 4, Primary 5, and Primary 6

Subject teachers should see:

- Only the classes/arms where they teach that subject
- Score entry for their own subject
- Subject attendance or lesson records if enabled later
- Subject-related remarks if the school wants it

Subject teacher assignment should come from `ClassSubject.staff_id`.

This means the subject setup should answer:

- Which class offers Biology?
- Who teaches Biology for that class?
- How many periods per week?
- Is it compulsory?

## Who Enters Scores?

Primary schools often work differently from secondary schools, so we should support both.

### Primary / Nursery

Often one form teacher handles most subjects.

The school can either:

- assign that teacher as the form teacher, and
- also assign them as subject teacher for the relevant class subjects.

This keeps score entry simple because the same teacher will see the subjects they are allowed to score.

### Secondary

Subject teachers should enter scores for their own subjects.

Example:

- Biology teacher enters Biology scores for SS 1, SS 2, SS 3.
- Mathematics teacher enters Mathematics scores for JSS 1A and JSS 1B.
- Form teacher may review class records and add conduct comments, but should not automatically enter every subject score.

### Exam Officer / School Admin

Exam officer and school admin should be able to override or enter scores for any class when needed.

This is important because schools often have practical cases where:

- a teacher is absent
- the exam office imports scores
- corrections are needed after submission

## Teacher Dashboard Rules

When a teacher logs in, the dashboard should show:

- My Classes
- My Subjects
- Score Entry
- Class Attendance, if they are a form teacher
- Student list for assigned classes/arms
- Pending tasks such as unsubmitted scores

The teacher should not see:

- School setup
- Finance setup
- Whole-school student records
- Other teachers' scores
- Admin-only configuration

## Parent Portal

Parents should log in through their own parent portal, not the teacher/admin workspace.

A parent may be linked to more than one student.

When a parent logs in, they should see:

- Children linked to their guardian profile
- Fee invoices and payment status
- Results/report cards released by the school
- Attendance summary
- Notices/messages

Parents should not see:

- Other students
- Full class lists
- Staff records
- Internal exam setup

## Student Portal

Student login should be optional and probably later phase.

For many Nigerian nursery/primary/secondary schools, parent login is more useful than student login.

Student login may be useful for:

- senior secondary students
- assignments/homework
- online exams
- result checking
- timetable

Recommended approach:

- Build teacher portal first.
- Build parent portal second.
- Build student portal only when the school workflow needs it.

## NFC / Watch / Tracking Integration

NFC or smart-watch tracking should be handled as an attendance and safety module.

It should not be mixed with ordinary user login.

Recommended device flow:

1. Each student can have one or more registered devices/cards.
2. Device scan creates a location/attendance event.
3. Events can mark arrival, exit, bus boarding, or checkpoint presence.
4. Parent portal can show safe summaries like arrival and departure.
5. Admin can see detailed logs and device status.

Possible tables later:

- `student_devices`
- `student_location_events`
- `device_gateways`

Important privacy rule:

- Parents should see useful safety information.
- Schools should control detailed tracking visibility.
- Raw location history should not be casually exposed to everyone.

## Recommended Build Order

1. Add login account creation to Staff Directory.
2. Add roles/permissions for teacher, exam officer, finance officer, parent, and student.
3. Build Teacher Workspace inside Filament or a separate teacher panel.
4. Make teacher score entry use assigned `ClassSubject.staff_id`.
5. Build parent login and guardian-linked children view.
6. Add fee and result views for parents.
7. Add student login only if required.
8. Add NFC/device tracking as a separate module after attendance is stable.

## Current Code Alignment Notes

The current code already has a useful foundation:

- `users` table for login accounts.
- `staff.user_id` for linking staff profiles to login accounts.
- `TeachingAssignment` for form teacher and assistant form teacher roles.
- `ClassSubject.staff_id` for subject teacher assignment.
- `GuardianStudent` for linking parents/guardians to one or more students.

Important cleanup to do next:

- Teacher score-entry filtering should use `ClassSubject.staff_id` for subject teachers.
- `TeachingAssignment` should remain focused on form teacher / assistant form teacher responsibility.
- Staff creation should optionally create a linked user account.
- We should introduce proper user roles/permissions before exposing teacher/parent/student dashboards.
