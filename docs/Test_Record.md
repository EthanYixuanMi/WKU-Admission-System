# Test Record

## Test Environment

| Item | Value |
|---|---|
| Project | WKU International Online Admission Management System |
| Local Server | WampServer 64-bit |
| Web URL | `http://localhost/WKU-Admission-System/` |
| PHP Version | PHP 8.x through WampServer |
| Database | MySQL `wku_admission` |
| Browser | Google Chrome |
| Test Date | June 4, 2026 |

## Demo Accounts

| Role | Account | Password |
|---|---|---|
| Student | `1306031@wku.edu.cn` | `student123` |
| Student | `1307943@wku.edu.cn` | `student123` |
| Admission Officer | `officer@wku.edu` | `officer123` |
| Admin | `admin@wku.edu` | `admin123` |

## Test Case Record

| Test Case | Steps | Expected Result | Actual Result | Evidence | Status |
|---|---|---|---|---|---|
| TC-01 Student Login | Open login page and log in with `1306031@wku.edu.cn / student123`. | Student dashboard opens. | Student dashboard opened successfully for Steve. | `docs/screenshots/02-student-dashboard-steve.png` | Pass |
| TC-02 Officer Login | Log out and log in with `officer@wku.edu / officer123`. | Officer dashboard opens. | Officer review dashboard opened successfully. | `docs/screenshots/06-officer-dashboard.png` | Pass |
| TC-03 Admin Login | Log out and log in with `admin@wku.edu / admin123`. | Admin dashboard opens. | Admin dashboard opened successfully with reports and statistics. | `docs/screenshots/09-admin-dashboard-full.png` | Pass |
| TC-04 Application Form | Student opens the online application form. | Existing application information appears and can be edited. | Application form displayed program, intake, personal, passport, academic, and English score fields. | `docs/screenshots/03-student-application-form.png` | Pass |
| TC-05 Document Upload View | Student opens document upload page. | Upload form and uploaded document list appear. | Document upload page displayed document type, file upload control, and uploaded document records. | `docs/screenshots/04-student-document-upload.png` | Pass |
| TC-06 Officer Document Verification | Officer opens application review page and checks uploaded documents. | Documents can be marked Pending, Verified, or Rejected with remarks. | Application review page displayed document verification controls and verified document records. | `docs/screenshots/07-officer-review-application.png` | Pass |
| TC-07 Application Decision | Officer updates application decision to Approved. | Application status changes and review history is recorded. | Application was marked Approved and review history was displayed. | `docs/screenshots/07-officer-review-application.png`, `docs/screenshots/14-email-application-approved.jpg` | Pass |
| TC-08 Offer Letter | Student dashboard displays offer letter after approval. | Offer letter appears and student can accept it. | Offer letter was issued and accepted. | `docs/screenshots/02-student-dashboard-steve.png`, `docs/screenshots/13-email-offer-letter-issued.jpg` | Pass |
| TC-09 Enrollment Tracking | Student accepts offer and confirms enrollment. | Enrollment status updates to Enrolled. | Admin enrollment report showed the enrolled student record. | `docs/screenshots/10-admin-enrollment-report.png` | Pass |
| TC-10 Student Inquiry | Student opens inquiry page and views inquiry history. | Student can submit inquiries and view replies. | Inquiry history displayed an answered enrollment question. | `docs/screenshots/05-student-inquiries.png` | Pass |
| TC-11 Staff Inquiry Response | Officer/Admin opens inquiry management page. | Staff can view and reply to student inquiries. | Inquiry management page showed question, reply, responder, and status. | `docs/screenshots/08-staff-inquiry-management.png`, `docs/screenshots/12-admin-inquiry-management.png` | Pass |
| TC-12 Payment Management | Admin opens payment management section and updates payment status. | Payment records can be viewed and updated. | Payment management table appeared in admin dashboard. | `docs/screenshots/09-admin-dashboard-full.png` | Pass |
| TC-13 Email Notification Log | Admin checks Email Log after workflow actions. | Email delivery attempts are recorded as Sent or Failed. | Email Log displayed sent notification records. | `docs/screenshots/11-admin-email-log.png` | Pass |
| TC-14 Real Email Notification | Check the real student email inbox after workflow actions. | Student receives admission notification emails. | Student mailbox received application submitted, document status, approved, and offer letter emails. | `docs/screenshots/13-email-offer-letter-issued.jpg`, `docs/screenshots/14-email-application-approved.jpg`, `docs/screenshots/15-email-document-verified.jpg`, `docs/screenshots/17-email-application-submitted.jpg` | Pass |
| TC-15 SQL Schema Import | Import `database/schema.sql` into MySQL. | All database tables and demo rows are created. | `wku_admission` database contained all required tables and four demo users. | Database verification command output | Pass |
| TC-16 PHP Syntax Check | Run `php -l` on all PHP files. | No PHP syntax errors. | All PHP files passed syntax check. | CLI verification output | Pass |

## Screenshot Evidence List

| File | Purpose |
|---|---|
| `01-login-page.png` | Login page |
| `02-student-dashboard-steve.png` | Student dashboard, application status, payment, offer, enrollment |
| `03-student-application-form.png` | Online application form |
| `04-student-document-upload.png` | Document upload and uploaded document list |
| `05-student-inquiries.png` | Student inquiry history |
| `06-officer-dashboard.png` | Officer application review dashboard |
| `07-officer-review-application.png` | Officer application review and document verification |
| `08-staff-inquiry-management.png` | Staff inquiry reply page |
| `09-admin-dashboard-full.png` | Admin dashboard and reports |
| `10-admin-enrollment-report.png` | Enrollment report |
| `11-admin-email-log.png` | Email notification log |
| `12-admin-inquiry-management.png` | Admin inquiry management |
| `13-email-offer-letter-issued.jpg` | Real email evidence for offer letter |
| `14-email-application-approved.jpg` | Real email evidence for approved application |
| `15-email-document-verified.jpg` | Real email evidence for document verification |
| `16-email-document-pending.jpg` | Real email evidence for pending document status |
| `17-email-application-submitted.jpg` | Real email evidence for submitted application |

## User Acceptance Summary

The tested system supports the required admission workflow from student login and application submission to document verification, admission decision, offer letter issue, enrollment tracking, student inquiry handling, admin reporting, payment management, and email notification evidence. All tested cases passed in the local WampServer environment.
