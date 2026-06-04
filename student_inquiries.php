<?php
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/ApplicationService.php';

$user = Auth::requireRole('student');
$service = new ApplicationService();
$application = $service->getStudentApplication((int) $user['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $applicationId = $application ? (int) $application['application_id'] : null;

    if ($subject === '' || $message === '') {
        set_flash('error', 'Subject and message are required.');
    } else {
        $service->createInquiry((int) $user['user_id'], $applicationId, $subject, $message);
        set_flash('success', 'Your inquiry has been sent.');
        redirect('student_inquiries.php');
    }
}

$inquiries = $service->inquiriesForUser((int) $user['user_id']);

$pageTitle = 'Student Inquiries';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-title">
    <div>
        <h1>Student Inquiries</h1>
        <p>Ask admission questions and track staff replies.</p>
    </div>
    <a class="btn ghost" href="student_dashboard.php">Back to Dashboard</a>
</section>

<section class="grid two">
    <article class="panel">
        <h2>New Inquiry</h2>
        <form action="student_inquiries.php" method="post">
            <div class="field">
                <label for="subject">Subject</label>
                <input id="subject" name="subject" maxlength="160" required>
            </div>
            <div class="field" style="margin-top: 14px;">
                <label for="message">Message</label>
                <textarea id="message" name="message" required></textarea>
            </div>
            <div class="actions">
                <button class="btn" type="submit">Send Inquiry</button>
            </div>
        </form>
    </article>

    <article class="panel">
        <h2>Inquiry History</h2>
        <?php if (!$inquiries): ?>
            <p class="muted">No inquiries submitted yet.</p>
        <?php else: ?>
            <ul class="list">
                <?php foreach ($inquiries as $inquiry): ?>
                    <li>
                        <strong><?= e($inquiry['subject']) ?></strong>
                        <span class="<?= e(status_class($inquiry['status'])) ?>"><?= e($inquiry['status']) ?></span>
                        <p><?= nl2br(e($inquiry['message'])) ?></p>
                        <?php if ($inquiry['response']): ?>
                            <p><strong>Reply:</strong> <?= nl2br(e($inquiry['response'])) ?></p>
                        <?php endif; ?>
                        <span class="muted">
                            <?= e($inquiry['program'] ?: 'General question') ?> |
                            Created <?= format_datetime($inquiry['created_at']) ?>
                            <?= $inquiry['responded_at'] ? ' | Replied ' . format_datetime($inquiry['responded_at']) : '' ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </article>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
