USE wku_admission;

ALTER TABLE payments
    ADD COLUMN remarks VARCHAR(255) NOT NULL DEFAULT '' AFTER paid_at,
    ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER remarks;

CREATE TABLE IF NOT EXISTS offer_letters (
    offer_id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL UNIQUE,
    offer_code VARCHAR(40) NOT NULL UNIQUE,
    title VARCHAR(160) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('Issued', 'Accepted', 'Withdrawn') NOT NULL DEFAULT 'Issued',
    issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_offer_letters_application
        FOREIGN KEY (application_id) REFERENCES applications(application_id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS enrollments (
    enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL UNIQUE,
    status ENUM('Not Started', 'Offer Accepted', 'Enrolled', 'Withdrawn') NOT NULL DEFAULT 'Not Started',
    remarks VARCHAR(255) NOT NULL DEFAULT '',
    student_response_at DATETIME NULL,
    enrolled_at DATETIME NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_enrollments_application
        FOREIGN KEY (application_id) REFERENCES applications(application_id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS inquiries (
    inquiry_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    application_id INT NULL,
    subject VARCHAR(160) NOT NULL,
    message TEXT NOT NULL,
    response TEXT NULL,
    status ENUM('Open', 'Answered', 'Closed') NOT NULL DEFAULT 'Open',
    responded_by INT NULL,
    responded_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_inquiries_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_inquiries_application
        FOREIGN KEY (application_id) REFERENCES applications(application_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_inquiries_responder
        FOREIGN KEY (responded_by) REFERENCES users(user_id)
        ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS email_logs (
    email_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    recipient_email VARCHAR(160) NOT NULL,
    subject VARCHAR(180) NOT NULL,
    body TEXT NOT NULL,
    status ENUM('Queued', 'Sent', 'Failed') NOT NULL DEFAULT 'Queued',
    error_message TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_email_logs_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE SET NULL
);

UPDATE users
SET name = 'Steve',
    email = '1306031@wku.edu.cn'
WHERE email = 'student@wku.edu';

UPDATE users
SET name = 'Steve'
WHERE email = '1306031@wku.edu.cn';

INSERT INTO users (name, email, password_hash, role, phone, nationality)
SELECT 'Ethan',
       '1307943@wku.edu.cn',
       '$2y$12$wGc9Ck0CriT.welVEb0INO4rx9x1lZVPz0gQguV957FIMm3ZdBc.2',
       'student',
       '+86 130 7943 0000',
       'China'
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE email = '1307943@wku.edu.cn'
);

UPDATE users
SET name = 'Ethan'
WHERE email = '1307943@wku.edu.cn';
