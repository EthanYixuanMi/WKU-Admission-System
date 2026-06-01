CREATE DATABASE IF NOT EXISTS wku_admission
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE wku_admission;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS announcements;
DROP TABLE IF EXISTS documents;
DROP TABLE IF EXISTS applications;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student', 'officer', 'admin') NOT NULL DEFAULT 'student',
    phone VARCHAR(40) NULL,
    nationality VARCHAR(80) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE applications (
    application_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    program VARCHAR(120) NOT NULL,
    intake VARCHAR(60) NOT NULL,
    date_of_birth DATE NULL,
    gender ENUM('Female', 'Male', 'Other') NULL,
    passport_number VARCHAR(80) NULL,
    previous_school VARCHAR(160) NULL,
    gpa DECIMAL(3, 2) NULL,
    english_score VARCHAR(80) NULL,
    personal_statement TEXT NULL,
    status ENUM('Draft', 'Submitted', 'Under Review', 'Need More Documents', 'Approved', 'Rejected') NOT NULL DEFAULT 'Draft',
    submission_date DATETIME NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_applications_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE
);

CREATE TABLE documents (
    document_id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    type ENUM('Passport', 'Transcript', 'English Test', 'Recommendation', 'Other') NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    status ENUM('Pending', 'Verified', 'Rejected') NOT NULL DEFAULT 'Pending',
    remarks VARCHAR(255) NOT NULL DEFAULT '',
    uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_documents_application
        FOREIGN KEY (application_id) REFERENCES applications(application_id)
        ON DELETE CASCADE
);

CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE
);

CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    status ENUM('Pending', 'Paid', 'Waived') NOT NULL DEFAULT 'Pending',
    paid_at DATETIME NULL,
    CONSTRAINT fk_payments_application
        FOREIGN KEY (application_id) REFERENCES applications(application_id)
        ON DELETE CASCADE
);

CREATE TABLE reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    officer_id INT NOT NULL,
    decision ENUM('Under Review', 'Need More Documents', 'Approved', 'Rejected') NOT NULL,
    remarks TEXT NOT NULL,
    reviewed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reviews_application
        FOREIGN KEY (application_id) REFERENCES applications(application_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_reviews_officer
        FOREIGN KEY (officer_id) REFERENCES users(user_id)
        ON DELETE CASCADE
);

CREATE TABLE announcements (
    announcement_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(160) NOT NULL,
    body TEXT NOT NULL,
    deadline DATE NULL,
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_announcements_admin
        FOREIGN KEY (created_by) REFERENCES users(user_id)
        ON DELETE CASCADE
);

INSERT INTO users (name, email, password_hash, role, phone, nationality) VALUES
('Mary Chen', 'student@wku.edu', '$2y$12$wGc9Ck0CriT.welVEb0INO4rx9x1lZVPz0gQguV957FIMm3ZdBc.2', 'student', '+60 12 555 0198', 'Malaysia'),
('David Miller', 'officer@wku.edu', '$2y$12$xNHVnh1ytbsHysXchVLCt.wdK1t.MXqMSARYsHw2WUqBv1tgRHM4C', 'officer', '+86 577 5587 0001', 'United States'),
('WKU Admin', 'admin@wku.edu', '$2y$12$ettv8jtupc6McmEydLMbAeoG6NfnNOmtKau4rFL0nNieXIigff57K', 'admin', '+86 577 5587 0000', 'China');

INSERT INTO applications
    (user_id, program, intake, date_of_birth, gender, passport_number, previous_school,
     gpa, english_score, personal_statement, status, submission_date)
VALUES
    (1, 'Computer Science', 'Fall 2026', '2006-04-18', 'Female', 'MYS-A1234567',
     'Kuala Lumpur International High School', 3.72, 'IELTS 6.5',
     'I want to study computer science at WKU because I am interested in software systems, international collaboration, and applied research.',
     'Submitted', NOW());

INSERT INTO documents (application_id, type, file_name, file_path, status, remarks) VALUES
(1, 'Passport', 'demo_passport.txt', 'uploads/demo_passport.txt', 'Pending', ''),
(1, 'Transcript', 'demo_transcript.txt', 'uploads/demo_transcript.txt', 'Pending', '');

INSERT INTO payments (application_id, amount, status) VALUES
(1, 500.00, 'Pending');

INSERT INTO notifications (user_id, message) VALUES
(1, 'Your application has been submitted and is waiting for review.'),
(2, 'A new student application is waiting for review.'),
(3, 'System initialized with demo admission data.');

INSERT INTO announcements (title, body, deadline, created_by) VALUES
('Fall 2026 Application Deadline', 'International applicants should submit all required documents before the deadline.', '2026-07-31', 3),
('Document Verification Reminder', 'Passport and academic transcript are required before an application can be approved.', NULL, 3);
