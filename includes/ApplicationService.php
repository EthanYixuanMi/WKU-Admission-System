<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/EmailService.php';

final class ApplicationService
{
    public function getStudentApplication(int $userId): ?array
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM applications WHERE user_id = ? ORDER BY application_id DESC LIMIT 1');
        $stmt->bind_param('i', $userId);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function saveApplication(int $userId, array $data, bool $submit): int
    {
        $db = Database::connection();
        $existing = $this->getStudentApplication($userId);
        $status = $submit ? 'Submitted' : ($existing['status'] ?? 'Draft');
        $submissionDate = $submit ? date('Y-m-d H:i:s') : ($existing['submission_date'] ?? null);

        if ($existing) {
            $applicationId = (int) $existing['application_id'];
            $stmt = $db->prepare(
                'UPDATE applications
                 SET program = ?, intake = ?, date_of_birth = ?, gender = ?, passport_number = ?,
                     previous_school = ?, gpa = ?, english_score = ?, personal_statement = ?,
                     status = ?, submission_date = ?, updated_at = NOW()
                 WHERE application_id = ? AND user_id = ?'
            );
            $stmt->bind_param(
                'ssssssdssssii',
                $data['program'],
                $data['intake'],
                $data['date_of_birth'],
                $data['gender'],
                $data['passport_number'],
                $data['previous_school'],
                $data['gpa'],
                $data['english_score'],
                $data['personal_statement'],
                $status,
                $submissionDate,
                $applicationId,
                $userId
            );
            $stmt->execute();
        } else {
            $stmt = $db->prepare(
                'INSERT INTO applications
                    (user_id, program, intake, date_of_birth, gender, passport_number,
                     previous_school, gpa, english_score, personal_statement, status, submission_date)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->bind_param(
                'issssssdssss',
                $userId,
                $data['program'],
                $data['intake'],
                $data['date_of_birth'],
                $data['gender'],
                $data['passport_number'],
                $data['previous_school'],
                $data['gpa'],
                $data['english_score'],
                $data['personal_statement'],
                $status,
                $submissionDate
            );
            $stmt->execute();
            $applicationId = (int) $db->insert_id;
        }

        if ($submit) {
            $this->createNotification($userId, 'Your application has been submitted and is ready for review.');
            $this->notifyRole('officer', 'A new student application is waiting for review.');
        }

        $this->ensurePayment($applicationId);

        return $applicationId;
    }

    public function getDocuments(int $applicationId): array
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM documents WHERE application_id = ? ORDER BY uploaded_at DESC');
        $stmt->bind_param('i', $applicationId);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function addDocument(int $applicationId, string $type, string $fileName, string $filePath): void
    {
        $db = Database::connection();
        $status = 'Pending';
        $stmt = $db->prepare(
            'INSERT INTO documents (application_id, type, file_name, file_path, status)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('issss', $applicationId, $type, $fileName, $filePath, $status);
        $stmt->execute();
    }

    public function getNotifications(int $userId, int $limit = 6): array
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?'
        );
        $stmt->bind_param('ii', $userId, $limit);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function createNotification(int $userId, string $message): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('INSERT INTO notifications (user_id, message) VALUES (?, ?)');
        $stmt->bind_param('is', $userId, $message);
        $stmt->execute();

        (new EmailService())->queueToUser($userId, 'WKU Admission Portal Update', $message);
    }

    public function notifyRole(string $role, string $message): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT user_id FROM users WHERE role = ?');
        $stmt->bind_param('s', $role);
        $stmt->execute();
        $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($users as $user) {
            $this->createNotification((int) $user['user_id'], $message);
        }
    }

    public function applicationCounts(): array
    {
        $db = Database::connection();
        $result = $db->query(
            'SELECT status, COUNT(*) AS total FROM applications GROUP BY status ORDER BY status'
        );

        $counts = [
            'Draft' => 0,
            'Submitted' => 0,
            'Under Review' => 0,
            'Need More Documents' => 0,
            'Approved' => 0,
            'Rejected' => 0,
        ];

        while ($row = $result->fetch_assoc()) {
            $counts[$row['status']] = (int) $row['total'];
        }

        return $counts;
    }

    public function applicationsForReview(?string $status = null): array
    {
        $db = Database::connection();

        if ($status) {
            $stmt = $db->prepare(
                'SELECT a.*, u.name, u.email, u.nationality
                 FROM applications a
                 JOIN users u ON u.user_id = a.user_id
                 WHERE a.status = ?
                 ORDER BY a.updated_at DESC'
            );
            $stmt->bind_param('s', $status);
        } else {
            $stmt = $db->prepare(
                'SELECT a.*, u.name, u.email, u.nationality
                 FROM applications a
                 JOIN users u ON u.user_id = a.user_id
                 ORDER BY a.updated_at DESC'
            );
        }

        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function applicationWithStudent(int $applicationId): ?array
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT a.*, u.name, u.email, u.nationality, u.phone
             FROM applications a
             JOIN users u ON u.user_id = a.user_id
             WHERE a.application_id = ?'
        );
        $stmt->bind_param('i', $applicationId);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function setDocumentStatus(int $documentId, string $status, string $remarks): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('UPDATE documents SET status = ?, remarks = ? WHERE document_id = ?');
        $stmt->bind_param('ssi', $status, $remarks, $documentId);
        $stmt->execute();
    }

    public function reviewApplication(int $applicationId, int $officerId, string $decision, string $remarks): void
    {
        $db = Database::connection();
        $application = $this->applicationWithStudent($applicationId);
        if (!$application) {
            return;
        }

        $stmt = $db->prepare('UPDATE applications SET status = ?, updated_at = NOW() WHERE application_id = ?');
        $stmt->bind_param('si', $decision, $applicationId);
        $stmt->execute();

        $stmt = $db->prepare(
            'INSERT INTO reviews (application_id, officer_id, decision, remarks) VALUES (?, ?, ?, ?)'
        );
        $stmt->bind_param('iiss', $applicationId, $officerId, $decision, $remarks);
        $stmt->execute();

        $this->createNotification(
            (int) $application['user_id'],
            'Application status updated to "' . $decision . '". ' . $remarks
        );

        if ($decision === 'Approved') {
            $this->issueOfferLetter($applicationId);
        }
    }

    public function reviewsForApplication(int $applicationId): array
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT r.*, u.name AS officer_name
             FROM reviews r
             JOIN users u ON u.user_id = r.officer_id
             WHERE r.application_id = ?
             ORDER BY r.reviewed_at DESC'
        );
        $stmt->bind_param('i', $applicationId);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function adminStats(): array
    {
        $db = Database::connection();

        return [
            'users' => (int) $db->query('SELECT COUNT(*) AS total FROM users')->fetch_assoc()['total'],
            'students' => (int) $db->query("SELECT COUNT(*) AS total FROM users WHERE role = 'student'")->fetch_assoc()['total'],
            'applications' => (int) $db->query('SELECT COUNT(*) AS total FROM applications')->fetch_assoc()['total'],
            'documents' => (int) $db->query('SELECT COUNT(*) AS total FROM documents')->fetch_assoc()['total'],
            'open_inquiries' => (int) $db->query("SELECT COUNT(*) AS total FROM inquiries WHERE status = 'Open'")->fetch_assoc()['total'],
            'enrolled' => (int) $db->query("SELECT COUNT(*) AS total FROM enrollments WHERE status = 'Enrolled'")->fetch_assoc()['total'],
        ];
    }

    public function users(): array
    {
        $db = Database::connection();
        return $db->query(
            'SELECT user_id, name, email, role, phone, nationality, created_at FROM users ORDER BY created_at DESC'
        )->fetch_all(MYSQLI_ASSOC);
    }

    public function updateUserRole(int $userId, string $role): void
    {
        if (!in_array($role, ['student', 'officer', 'admin'], true)) {
            return;
        }

        $db = Database::connection();
        $stmt = $db->prepare('UPDATE users SET role = ? WHERE user_id = ?');
        $stmt->bind_param('si', $role, $userId);
        $stmt->execute();
    }

    public function updateUserProfile(int $userId, string $name, string $phone, string $nationality): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('UPDATE users SET name = ?, phone = ?, nationality = ? WHERE user_id = ?');
        $stmt->bind_param('sssi', $name, $phone, $nationality, $userId);
        $stmt->execute();
    }

    public function announcements(): array
    {
        $db = Database::connection();
        return $db->query(
            'SELECT a.*, u.name AS author_name
             FROM announcements a
             JOIN users u ON u.user_id = a.created_by
             ORDER BY a.created_at DESC'
        )->fetch_all(MYSQLI_ASSOC);
    }

    public function addAnnouncement(int $adminId, string $title, string $body, ?string $deadline): void
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'INSERT INTO announcements (title, body, deadline, created_by) VALUES (?, ?, ?, ?)'
        );
        $stmt->bind_param('sssi', $title, $body, $deadline, $adminId);
        $stmt->execute();
    }

    public function ensurePayment(int $applicationId): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT payment_id FROM payments WHERE application_id = ? LIMIT 1');
        $stmt->bind_param('i', $applicationId);
        $stmt->execute();

        if ($stmt->get_result()->fetch_assoc()) {
            return;
        }

        $amount = 500.00;
        $status = 'Pending';
        $stmt = $db->prepare('INSERT INTO payments (application_id, amount, status) VALUES (?, ?, ?)');
        $stmt->bind_param('ids', $applicationId, $amount, $status);
        $stmt->execute();
    }

    public function paymentForApplication(int $applicationId): ?array
    {
        $this->ensurePayment($applicationId);

        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM payments WHERE application_id = ? LIMIT 1');
        $stmt->bind_param('i', $applicationId);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function updatePayment(int $applicationId, float $amount, string $status, string $remarks): void
    {
        if (!in_array($status, ['Pending', 'Paid', 'Waived'], true)) {
            return;
        }

        $this->ensurePayment($applicationId);
        $db = Database::connection();
        $paidAtSql = $status === 'Paid' ? 'NOW()' : 'NULL';
        $stmt = $db->prepare(
            "UPDATE payments
             SET amount = ?, status = ?, paid_at = $paidAtSql, remarks = ?
             WHERE application_id = ?"
        );
        $stmt->bind_param('dssi', $amount, $status, $remarks, $applicationId);
        $stmt->execute();

        $application = $this->applicationWithStudent($applicationId);
        if ($application) {
            $this->createNotification(
                (int) $application['user_id'],
                'Application fee status updated to "' . $status . '". ' . $remarks
            );
        }
    }

    public function paymentsForReport(): array
    {
        $db = Database::connection();

        return $db->query(
            'SELECT p.*, a.program, a.status AS application_status, u.name, u.email
             FROM payments p
             JOIN applications a ON a.application_id = p.application_id
             JOIN users u ON u.user_id = a.user_id
             ORDER BY p.updated_at DESC'
        )->fetch_all(MYSQLI_ASSOC);
    }

    public function offerForApplication(int $applicationId): ?array
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM offer_letters WHERE application_id = ? LIMIT 1');
        $stmt->bind_param('i', $applicationId);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function issueOfferLetter(int $applicationId): void
    {
        $application = $this->applicationWithStudent($applicationId);
        if (!$application || $application['status'] !== 'Approved' || $this->offerForApplication($applicationId)) {
            return;
        }

        $db = Database::connection();
        $offerCode = 'WKU-' . date('Y') . '-' . str_pad((string) $applicationId, 5, '0', STR_PAD_LEFT);
        $title = 'Conditional Admission Offer';
        $message = 'Congratulations ' . $application['name'] . '. You have been approved for '
            . $application['program'] . ' in ' . $application['intake']
            . '. Please accept the offer and complete enrollment confirmation in the admission portal.';
        $status = 'Issued';

        $stmt = $db->prepare(
            'INSERT INTO offer_letters (application_id, offer_code, title, message, status)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('issss', $applicationId, $offerCode, $title, $message, $status);
        $stmt->execute();

        $this->ensureEnrollment($applicationId);
        $this->createNotification(
            (int) $application['user_id'],
            'Your offer letter has been issued. Please review it in your student dashboard.'
        );
    }

    public function enrollmentForApplication(int $applicationId): ?array
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM enrollments WHERE application_id = ? LIMIT 1');
        $stmt->bind_param('i', $applicationId);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function ensureEnrollment(int $applicationId): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT enrollment_id FROM enrollments WHERE application_id = ? LIMIT 1');
        $stmt->bind_param('i', $applicationId);
        $stmt->execute();

        if ($stmt->get_result()->fetch_assoc()) {
            return;
        }

        $status = 'Not Started';
        $stmt = $db->prepare('INSERT INTO enrollments (application_id, status) VALUES (?, ?)');
        $stmt->bind_param('is', $applicationId, $status);
        $stmt->execute();
    }

    public function acceptOffer(int $applicationId, int $userId): bool
    {
        $application = $this->getStudentApplication($userId);
        $offer = $this->offerForApplication($applicationId);

        if (!$application || (int) $application['application_id'] !== $applicationId || !$offer || $offer['status'] === 'Withdrawn') {
            return false;
        }

        $db = Database::connection();
        $offerStatus = 'Accepted';
        $stmt = $db->prepare('UPDATE offer_letters SET status = ? WHERE application_id = ?');
        $stmt->bind_param('si', $offerStatus, $applicationId);
        $stmt->execute();

        $enrollmentStatus = 'Offer Accepted';
        $remarks = 'Student accepted the offer.';
        $stmt = $db->prepare(
            'INSERT INTO enrollments (application_id, status, remarks, student_response_at)
             VALUES (?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE status = VALUES(status),
                                     remarks = VALUES(remarks),
                                     student_response_at = NOW(),
                                     enrolled_at = NULL'
        );
        $stmt->bind_param('iss', $applicationId, $enrollmentStatus, $remarks);
        $stmt->execute();

        $this->createNotification($userId, 'You accepted your WKU admission offer.');
        $this->notifyRole('admin', 'A student accepted an admission offer.');
        $this->notifyRole('officer', 'A student accepted an admission offer.');

        return true;
    }

    public function confirmEnrollment(int $applicationId, int $userId): bool
    {
        $application = $this->getStudentApplication($userId);
        $enrollment = $this->enrollmentForApplication($applicationId);

        if (!$application || (int) $application['application_id'] !== $applicationId || !$enrollment) {
            return false;
        }

        if (!in_array($enrollment['status'], ['Offer Accepted', 'Enrolled'], true)) {
            return false;
        }

        $db = Database::connection();
        $status = 'Enrolled';
        $remarks = 'Student confirmed enrollment.';
        $stmt = $db->prepare(
            'UPDATE enrollments
             SET status = ?, remarks = ?, enrolled_at = NOW()
             WHERE application_id = ?'
        );
        $stmt->bind_param('ssi', $status, $remarks, $applicationId);
        $stmt->execute();

        $this->createNotification($userId, 'Your enrollment confirmation has been recorded.');
        $this->notifyRole('admin', 'A student completed enrollment confirmation.');
        $this->notifyRole('officer', 'A student completed enrollment confirmation.');

        return true;
    }

    public function enrollmentsForReport(): array
    {
        $db = Database::connection();

        return $db->query(
            'SELECT e.*, o.offer_code, a.program, a.intake, a.status AS application_status, u.name, u.email
             FROM enrollments e
             JOIN applications a ON a.application_id = e.application_id
             JOIN users u ON u.user_id = a.user_id
             LEFT JOIN offer_letters o ON o.application_id = e.application_id
             ORDER BY e.updated_at DESC'
        )->fetch_all(MYSQLI_ASSOC);
    }

    public function createInquiry(int $userId, ?int $applicationId, string $subject, string $message): int
    {
        $application = $this->getStudentApplication($userId);
        if (!$application || (int) $application['application_id'] !== (int) $applicationId) {
            $applicationId = null;
        }

        $db = Database::connection();
        $stmt = $db->prepare(
            'INSERT INTO inquiries (user_id, application_id, subject, message)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->bind_param('iiss', $userId, $applicationId, $subject, $message);
        $stmt->execute();
        $inquiryId = (int) $db->insert_id;

        $this->notifyRole('officer', 'New student inquiry: ' . $subject);
        $this->notifyRole('admin', 'New student inquiry: ' . $subject);

        return $inquiryId;
    }

    public function inquiriesForUser(int $userId): array
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT i.*, a.program
             FROM inquiries i
             LEFT JOIN applications a ON a.application_id = i.application_id
             WHERE i.user_id = ?
             ORDER BY i.updated_at DESC'
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function inquiriesForStaff(?string $status = null): array
    {
        $db = Database::connection();

        if ($status && in_array($status, ['Open', 'Answered', 'Closed'], true)) {
            $stmt = $db->prepare(
                'SELECT i.*, u.name, u.email, a.program, r.name AS responder_name
                 FROM inquiries i
                 JOIN users u ON u.user_id = i.user_id
                 LEFT JOIN applications a ON a.application_id = i.application_id
                 LEFT JOIN users r ON r.user_id = i.responded_by
                 WHERE i.status = ?
                 ORDER BY i.updated_at DESC'
            );
            $stmt->bind_param('s', $status);
            $stmt->execute();

            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        return $db->query(
            'SELECT i.*, u.name, u.email, a.program, r.name AS responder_name
             FROM inquiries i
             JOIN users u ON u.user_id = i.user_id
             LEFT JOIN applications a ON a.application_id = i.application_id
             LEFT JOIN users r ON r.user_id = i.responded_by
             ORDER BY i.updated_at DESC'
        )->fetch_all(MYSQLI_ASSOC);
    }

    public function respondInquiry(int $inquiryId, int $responderId, string $response, string $status): void
    {
        if (!in_array($status, ['Answered', 'Closed'], true)) {
            return;
        }

        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM inquiries WHERE inquiry_id = ? LIMIT 1');
        $stmt->bind_param('i', $inquiryId);
        $stmt->execute();
        $inquiry = $stmt->get_result()->fetch_assoc();

        if (!$inquiry) {
            return;
        }

        $stmt = $db->prepare(
            'UPDATE inquiries
             SET response = ?, status = ?, responded_by = ?, responded_at = NOW()
             WHERE inquiry_id = ?'
        );
        $stmt->bind_param('ssii', $response, $status, $responderId, $inquiryId);
        $stmt->execute();

        $this->createNotification(
            (int) $inquiry['user_id'],
            'Your inquiry "' . $inquiry['subject'] . '" has been updated.'
        );
    }

    public function emailLogs(int $limit = 20): array
    {
        return (new EmailService())->recentLogs($limit);
    }
}
