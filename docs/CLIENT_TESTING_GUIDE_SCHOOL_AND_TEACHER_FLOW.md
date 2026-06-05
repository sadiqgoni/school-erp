# School Dice: School Admin and Teacher Flow Testing Guide

This guide explains how to test the school portal and teacher portal features from scratch. It is written as a practical user flow, not a technical document.

## 1. Main Users In This Test

Use three people in the test:

- **Mr A: School Admin**
  - Creates school setup records.
  - Creates staff/teacher records.
  - Creates teacher login accounts.
  - Assigns form classes and subject teaching loads.
  - Creates exams, assessment components, subjects, and grading setup.

- **Mr B: Subject Teacher**
  - Logs into the teacher portal.
  - Sees only subjects/classes assigned to him.
  - Enters scores for his assigned subjects only.
  - Submits draft scores when done.

- **Mr C: Form/Class Teacher**
  - Logs into the teacher portal.
  - Sees students in his own form class/arm.
  - Sees subjects attached to his class.
  - Reviews class results after subject teachers submit scores.
  - Adds attendance summary, affective/psychomotor ratings, and class teacher remarks.

One teacher can also be both **form teacher** and **subject teacher**. In that case, he will see both responsibilities, but the system still separates them clearly.

## 2. School Admin First-Time Setup

Mr A logs into the school portal.

The school should first complete these setup areas:

1. **Academic Years**
   - Create the current session, for example `2025/2026`.
   - Mark it as current if the system provides that option.

2. **Terms**
   - Create the term, for example `First Term`, `Second Term`, or `Third Term`.
   - Link the term to the academic year.
   - Mark the current term where needed.

3. **Classes**
   - Create classes such as:
     - Nursery 1
     - Primary 1
     - JSS 1
     - JSS 2
     - JSS 3
   - If the school uses arms, create arms such as:
     - JSS 2 A
     - JSS 2 B
     - JSS 3 A

4. **Subjects**
   - Create subjects such as:
     - English Language
     - Mathematics
     - Basic Science
     - Islamic Studies
     - Christian Religious Studies

5. **Grade Scale**
   - Create or load grading scale.
   - Example:
     - 70-100 = A
     - 60-69 = B
     - 50-59 = C
     - 40-49 = D
     - 0-39 = F

6. **Exam**
   - Create an exam, for example `Second Term Exam`.
   - Link it to the correct session and term.

7. **Assessment Components**
   - Create the score components for the exam.
   - Example:
     - `CA 1` with max score `30`
     - `CA 2` with max score `20`
     - `Exam` with max score `50`
   - Use the position field to control the order on result sheet:
     - CA 1 position 1
     - CA 2 position 2
     - Exam position 3

8. **Result Traits**
   - Create affective and psychomotor items.
   - Example affective traits:
     - Punctuality
     - Neatness
     - Politeness
     - Leadership
   - Example psychomotor traits:
     - Handwriting
     - Drawing and Craft
     - Sports and Games

## 3. Creating Students And Class Placement

Mr A creates student records.

For each student:

1. Go to **Students**.
2. Create the student profile.
3. Add admission number, name, gender, photo, and other details.
4. Place the student into the correct class and arm using **Class Placements**.

Example:

- Student: Adam Iguda
- Session: 2025/2026
- Term: Second Term
- Class: JSS 2
- Arm: B
- Status: Active

Important result rule:

The system uses class placement to know where the student belongs. Class position is calculated only within the student’s class and arm for that exam/session/term.

## 4. Creating Teachers And Login Accounts

Mr A creates teacher records under **Staff Directory**.

For each teacher:

1. Go to **Staff Directory**.
2. Click **Create Staff**.
3. Select staff type as **Teaching**.
4. Fill teacher biodata.
5. Add email address.
6. Create or enable login account.

After creating the teacher, the teacher should have a portal login.

Example:

- Teacher: Mr B
- Staff type: Teaching
- Login email: `mrb@school.test`
- Role: Teacher

## 5. Assigning A Form Teacher

A form teacher is responsible for one class or arm.

Example:

- Mr C is form teacher of JSS 3 A.

How to test:

1. Mr A goes to **Arms** or the class/arm resource.
2. Selects `JSS 3 A`.
3. Uses the form teacher assignment action.
4. Assigns Mr C as form teacher.

Expected result:

- When Mr C logs in, he should see:
  - **My Class Students**
  - **My Class Subjects**
  - **Class Results**
  - His JSS 3 A form class information

Important:

A form teacher does not automatically enter scores for all subjects in his class. He can only enter scores for subjects assigned to him as a subject teacher.

## 6. Assigning A Subject Teacher

A subject teacher teaches one subject in one or more classes/arms.

Example:

- Mr B teaches English Language in JSS 2 B.

How to test:

1. Mr A goes to **Staff Directory**.
2. Finds Mr B.
3. Clicks **Assign Subjects**.
4. Selects:
   - Session: 2025/2026
   - Term: Second Term
   - Subject: English Language
   - Class: JSS 2
   - Arm: B
   - Weekly periods: 3
5. Saves.

Expected result:

- Mr B should see English Language for JSS 2 B in his teacher portal.
- Mr B should not see unrelated subjects/classes.
- In score entry, Mr B should see only English Language - JSS 2 B.

If Mr C is form teacher of JSS 3 A and also teaches Mathematics in JSS 3 A:

1. Assign Mr C as form teacher of JSS 3 A.
2. Also use **Assign Subjects** to assign Mathematics - JSS 3 A to Mr C.

Expected result:

- Mr C sees JSS 3 A under form teacher duties.
- Mr C sees Mathematics - JSS 3 A under subject teaching.
- Mr C can enter scores only for Mathematics - JSS 3 A, not every subject in JSS 3 A.

## 7. Teacher Dashboard Test

Mr B logs into the school portal.

Expected teacher dashboard:

- Welcome panel with teacher name.
- Summary cards:
  - Form Classes
  - Class Students
  - Teaching Load
  - Draft Scores
  - Pending Reviews
- Quick action buttons:
  - Enter Scores
  - Review Results
  - Class Subjects

For a subject teacher with no form class:

- Form Classes may show 0.
- Class Students may show 0.
- Teaching Load should show assigned subject classes.

For a form teacher:

- Form Classes should show the assigned class/arm.
- Class Students should show active students in that class/arm.

## 8. My Classes And Subjects Test

Teacher opens **My Classes & Subjects**.

Expected:

- The top section shows the teacher profile.
- Form class duties are shown separately.
- Subject teaching is grouped by subject.
- Class/arm chips show where the teacher teaches each subject.

Example:

Mr B teaches English Language in JSS 2 B.

He should see:

- Subject: English Language
- Class/arm: JSS 2 B

He should not see JSS 3 A unless he is assigned there.

## 9. My Class Subjects Test For Form Teacher

Mr C is form teacher of JSS 3 A.

Mr C opens **My Class Subjects**.

Expected:

- It shows subjects attached to JSS 3 A.
- It shows the subject teacher name where available.
- It does not show subjects Mr C teaches in another class unless that subject is part of JSS 3 A.

Example:

If Mr C is form teacher of JSS 3 A but teaches English in JSS 2 B:

- **My Class Subjects** should show JSS 3 A subjects.
- It should not show English - JSS 2 B.
- English - JSS 2 B should appear under **My Classes & Subjects** and **My Score Entry** because that is his teaching load.

## 10. Entering Scores As Subject Teacher

Mr B logs in.

He goes to **My Score Entry**.

Steps:

1. Click **Enter Scores**.
2. Select exam, for example `Second Term Exam`.
3. Select component, for example `CA 1`.
4. Select subject/class, for example `English Language - JSS 2 B`.
5. The system loads active students in JSS 2 B.
6. Mr B enters scores beside each student.
7. He can save as draft or submit scores.

Expected:

- Mr B should only see students in the selected class/arm.
- Mr B should only see subjects/classes assigned to him.
- If a student does not take the subject, Mr B can remove the student from the score list before saving.

Repeat for each component:

- CA 1
- CA 2
- Exam

## 11. Draft Scores And Submitting Scores

If Mr B saves scores as draft:

- Scores are saved but not ready for result compilation.
- Mr B can return and continue editing.

When Mr B is done:

1. He clicks **Submit Draft Scores**.
2. Selects the exam/component/scope.
3. Submits.

Expected:

- Draft scores become submitted.
- Submitted scores are prepared for result review.
- The form teacher can now review class results.

## 12. Form Teacher Reviewing Results

Mr C logs in as form teacher.

He goes to **Class Results**.

Expected:

- He sees report cards for students in his form class/arm.
- Results appear after subject scores have been submitted and compiled/prepared.
- He should not edit subject scores entered by other teachers.

Mr C opens a student result and clicks **Review Result**.

He enters:

1. Total school days.
2. Days present.
3. Days absent is calculated automatically.
4. Affective ratings.
5. Psychomotor ratings.
6. Class teacher remark.

Expected:

- After saving, the result status moves forward for principal/head teacher review.
- Results with missing class teacher remark remain easy to identify.

## 13. Principal Or School Admin Reviewing Results

Mr A or the principal logs in.

He goes to **Report Cards**.

Expected:

- He sees results after the class teacher has reviewed them.
- Results can be filtered by session, term, exam, class, arm, and status.

Steps:

1. Open a student result.
2. Add head teacher/principal remark.
3. Approve result.
4. Publish result.

Expected:

- Published result is ready for PDF download.

## 14. PDF Result Test

Open any student report card and click **Download Result**.

Expected PDF content:

- School logo.
- Student photo.
- Student name and admission number.
- Session, term, exam.
- Class and arm.
- Attendance summary.
- Subject result table.
- Each component header shows max score, for example:
  - CA 1 (30%)
  - CA 2 (20%)
  - Exam (50%)
  - Total (100%)
- Total score over expected total, for example:
  - 298.00 / 300.00
- Average.
- Class position.
- Highest class average.
- CGPA on 5.00 scale.
- Affective domain ratings.
- Psychomotor domain ratings.
- Class teacher remark.
- Head teacher/principal remark.
- Class teacher and head teacher/principal signature lines.

## 15. Important Scenarios To Test

### Scenario A: Teacher Is Only A Subject Teacher

Mr B teaches English Language in JSS 2 B.

Expected:

- He sees teaching load.
- He can enter English scores for JSS 2 B.
- He does not see JSS 2 A unless assigned.
- He does not review class results unless he is also a form teacher.

### Scenario B: Teacher Is Only A Form Teacher

Mr C is form teacher of JSS 3 A.

Expected:

- He sees JSS 3 A students.
- He sees JSS 3 A class subjects.
- He sees subject teachers attached to those subjects.
- He reviews result cards for JSS 3 A.
- He does not enter scores unless he is assigned a subject.

### Scenario C: Teacher Is Both Form Teacher And Subject Teacher

Mr C is form teacher of JSS 3 A and teaches Mathematics in JSS 3 A.

Expected:

- He sees JSS 3 A as form class.
- He sees Mathematics - JSS 3 A as teaching load.
- He can enter Mathematics scores.
- He can review all JSS 3 A results as form teacher.

### Scenario D: Teacher Teaches A Subject Outside His Form Class

Mr C is form teacher of JSS 3 A but teaches English in JSS 2 B.

Expected:

- **My Class Subjects** shows only JSS 3 A subjects.
- **My Score Entry** shows English - JSS 2 B.
- **Class Results** shows only JSS 3 A result cards.

### Scenario E: Class Position

Two students are in different arms/classes.

Expected:

- Class position is calculated only within the student’s class and arm.
- If a student is the only student in JSS 2 B, the position should be 1.
- The system should not compare JSS 2 B with JSS 1 A or JSS 3 A.

## 16. Final Acceptance Checklist

The feature is working correctly if:

- School admin can create teachers and login accounts.
- School admin can assign form classes.
- School admin can assign subject teaching loads by class and arm.
- Teacher login opens a teacher-focused dashboard.
- Teacher sees only his correct classes and subjects.
- Subject teacher can enter scores only for assigned subjects.
- Form teacher can see class students and class subjects.
- Form teacher can review result cards.
- Principal/admin can add final remark, approve, and publish.
- PDF report card contains academic results, ratings, comments, position, CGPA, and school branding.
- Audit logs do not fill up from simple page visits.
