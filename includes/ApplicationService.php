<?php

require_once __DIR__ . '/../config/database.php';

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
        ];
    }

    public function users(): array
    {
        $db = Database::connection();
        return $db->query(
            'SELECT user_id, name, email, role, nationality, created_at FROM users ORDER BY created_at DESC'
        )->fetch_all(MYSQLI_ASSOC);
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
}
