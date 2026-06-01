# WKU International Online Admission Management System

PHP/MySQL MVP for the CPS3962 final project. It runs directly in WampServer.

## Run with WampServer

1. Start WampServer and make sure the icon is green.
2. Open phpMyAdmin or use MySQL CLI.
3. Import `database/schema.sql`.
4. Visit `http://localhost/wku_admission/`.

## Demo Accounts

| Role | Email | Password |
|---|---|---|
| Student | student@wku.edu | student123 |
| Admission Officer | officer@wku.edu | officer123 |
| Admin | admin@wku.edu | admin123 |

## Main Modules

- Authentication and role-based dashboards
- Student online application form
- Document upload and verification
- Application status tracking
- Notifications
- Officer review workflow
- Admin statistics, reports, users, deadlines, and announcements

## Project Structure

```text
wku_admission/
  assets/styles.css
  config/database.php
  database/schema.sql
  docs/OOAD_Documentation.md
  includes/
    ApplicationService.php
    Auth.php
    helpers.php
    header.php
    footer.php
  uploads/
  *.php
```

## Notes

- Database name: `wku_admission`
- MySQL user: `root`
- MySQL password: empty, matching the default WampServer setup
- Uploaded documents are stored in `uploads/app_{application_id}/`
