# Client Scope Decisions (July 2026 Final Push)

These decisions came directly from the client review and override earlier roadmap ideas.

## Build

1. **Assignments / Homework**
   - Teachers create assignments for their class (form teacher) or subject/class (subject teacher).
   - Parents of students in that class see the assignment in the parent portal.
   - Parents can mark "my child has done this" so the teacher sees who confirmed.

2. **Timetable**
   - Form teacher creates a clean weekly timetable for their class. No over-engineering.
   - Parents see their ward's timetable in the parent portal.

3. **Notices**
   - School admin can type a notice or upload an existing printed newsletter (file upload).
   - Notice audience is selectable: whole school, a division (Nursery / Primary / Junior Secondary / Senior Secondary), or a specific class.
   - Form teachers can send announcements to the parents of their own class only
     (example: "JSS 3 creative session tomorrow — ensure your child comes with kit").
   - Parents see notices targeted at them in the parent portal.

4. **Promotion / Repeat / Graduate**
   - Form teacher (or admin) selects some or all students in a class and promotes the group to a
     target class/arm. Different groups can go to different arms (JSS2A → JSS3A and JSS3B).
   - Same flow supports repeat (stay in class next session) and graduate (leaves school).

5. **WhatsApp (wa.me links only)**
   - Share invoice/receipt/fee reminders through wa.me deep links with prefilled messages.
   - NO SMS provider and NO WhatsApp Business API for now.

6. **Debtors dashboard**
   - Finance/admin view of all outstanding balances by class with per-parent WhatsApp reminder links.

7. **Safety check-in (NFC/watch) + School bus**
   - Students can have registered NFC cards/watches. Scans create movement events.
   - Parents see whereabouts: in school / left school / boarded bus.
   - Admin manages bus routes and can record boarding; parents see it.

8. **Grading**
   - Ensure grade scales work per division (primary grading vs secondary A1–F9).

## Explicitly excluded (client said NO — do not re-propose)

- Teacher digital attendance (internet is unreliable; attendance stays on paper).
- Exam officer role.
- SMS providers / WhatsApp Business API.
- Public result-checker with PIN (parents log in instead).
- Spreadsheet score import/export.
- CBT / online exams.
- Library and hostel modules.

## Quality bar

Everything must be polished and user-appealing — the client demo decides the deal.
