<?php
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/ApplicationService.php';

$user = Auth::requireRole('student');
$service = new ApplicationService();
$application = $service->getStudentApplication((int) $user['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!$application) {
        set_flash('error', 'Create an application before completing this action.');
    } elseif ($action === 'accept_offer') {
        if ($service->acceptOffer((int) $application['application_id'], (int) $user['user_id'])) {
            set_flash('success', 'Offer accepted. Please complete enrollment confirmation when ready.');
        } else {
            set_flash('error', 'The offer could not be accepted.');
        }
    } elseif ($action === 'confirm_enrollment') {
        if ($service->confirmEnrollment((int) $application['application_id'], (int) $user['user_id'])) {
            set_flash('success', 'Enrollment confirmation submitted.');
        } else {
            set_flash('error', 'Accept the offer before confirming enrollment.');
        }
    }

    redirect('student_dashboard.php');
}

$documents = $application ? $service->getDocuments((int) $application['application_id']) : [];
$payment = $application ? $service->paymentForApplication((int) $application['application_id']) : null;
$offer = $application ? $service->offerForApplication((int) $application['application_id']) : null;
$enrollment = $application ? $service->enrollmentForApplication((int) $application['application_id']) : null;
$notifications = $service->getNotifications((int) $user['user_id']);
$announcements = array_slice($service->announcements(), 0, 3);
$inquiries = array_slice($service->inquiriesForUser((int) $user['user_id']), 0, 3);

$pageTitle = 'Student Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-title">
    <div>
        <h1>Student Dashboard</h1>
        <p>Track your application, payment, offer letter, enrollment, and messages.</p>
    </div>
    <div class="actions" style="margin-top: 0;">
        <a class="btn" href="application_form.php"><?= $application ? 'Edit Application' : 'Start Application' ?></a>
        <a class="btn secondary" href="upload_document.php">Upload Documents</a>
        <a class="btn ghost" href="student_inquiries.php">Ask a Question</a>
    </div>
</section>

<section class="grid four">
    <article class="metric">
        <span>Application Status</span>
        <strong><?= $application ? e($application['status']) : 'Not Started' ?></strong>
    </article>
    <article class="metric">
        <span>Payment</span>
        <strong><?= $payment ? e($payment['status']) : '-' ?></strong>
    </article>
    <article class="metric">
        <span>Offer</span>
        <strong><?= $offer ? e($offer['status']) : '-' ?></strong>
    </article>
    <article class="metric">
        <span>Enrollment</span>
        <strong><?= $enrollment ? e($enrollment['status']) : '-' ?></strong>
    </article>
</section>

<section class="grid two" style="margin-top: 18px;">
    <article class="panel">
        <h2>Application Summary</h2>
        <?php if (!$application): ?>
            <p class="muted">No application has been created yet.</p>
        <?php else: ?>
            <p><strong>Status:</strong> <span class="<?= e(status_class($application['status'])) ?>"><?= e($application['status']) ?></span></p>
            <p><strong>Program:</strong> <?= e($application['program']) ?></p>
            <p><strong>Intake:</strong> <?= e($application['intake']) ?></p>
            <p><strong>Passport:</strong> <?= e($application['passport_number']) ?></p>
            <p><strong>Submitted:</strong> <?= format_datetime($application['submission_date']) ?></p>
        <?php endif; ?>
    </article>

    <article class="panel">
        <h2>Application Fee</h2>
        <?php if (!$payment): ?>
            <p class="muted">No payment record yet.</p>
        <?php else: ?>
            <p><strong>Amount:</strong> $<?= number_format((float) $payment['amount'], 2) ?></p>
            <p><strong>Status:</strong> <span class="<?= e(status_class($payment['status'])) ?>"><?= e($payment['status']) ?></span></p>
            <p><strong>Paid At:</strong> <?= format_datetime($payment['paid_at']) ?></p>
            <p><strong>Remarks:</strong> <?= e($payment['remarks'] ?: '-') ?></p>
        <?php endif; ?>
    </article>
</section>

<section class="grid two" style="margin-top: 18px;">
    <article class="panel">
        <h2>Offer Letter</h2>
        <?php if (!$offer): ?>
            <p class="muted">No offer letter has been issued yet.</p>
        <?php else: ?>
            <p><strong>Offer Code:</strong> <?= e($offer['offer_code']) ?></p>
            <p><strong>Status:</strong> <span class="<?= e(status_class($offer['status'])) ?>"><?= e($offer['status']) ?></span></p>
            <p><strong>Issued:</strong> <?= format_datetime($offer['issued_at']) ?></p>
            <p><?= e($offer['message']) ?></p>
            <?php if ($offer['status'] === 'Issued'): ?>
                <form action="student_dashboard.php" method="post" class="actions">
                    <input type="hidden" name="action" value="accept_offer">
                    <button class="btn" type="submit">Accept Offer</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </article>

    <article class="panel">
        <h2>Enrollment Tracking</h2>
        <?php if (!$enrollment): ?>
            <p class="muted">Enrollment opens after an offer letter is issued.</p>
        <?php else: ?>
            <p><strong>Status:</strong> <span class="<?= e(status_class($enrollment['status'])) ?>"><?= e($enrollment['status']) ?></span></p>
            <p><strong>Student Response:</strong> <?= format_datetime($enrollment['student_response_at']) ?></p>
            <p><strong>Enrolled At:</strong> <?= format_datetime($enrollment['enrolled_at']) ?></p>
            <p><strong>Remarks:</strong> <?= e($enrollment['remarks'] ?: '-') ?></p>
            <?php if ($enrollment['status'] === 'Offer Accepted'): ?>
                <form action="student_dashboard.php" method="post" class="actions">
                    <input type="hidden" name="action" value="confirm_enrollment">
                    <button class="btn" type="submit">Confirm Enrollment</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </article>
</section>

<section class="table-panel wide" style="margin-top: 18px;">
    <h2>Documents</h2>
    <table>
        <thead>
        <tr>
            <th>Type</th>
            <th>File</th>
            <th>Status</th>
            <th>Remarks</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$documents): ?>
            <tr><td colspan="4" class="muted">No documents uploaded.</td></tr>
        <?php endif; ?>
        <?php foreach ($documents as $document): ?>
            <tr>
                <td><?= e($document['type']) ?></td>
                <td><a href="<?= e($document['file_path']) ?>" target="_blank"><?= e($document['file_name']) ?></a></td>
                <td><span class="<?= e(status_class($document['status'])) ?>"><?= e($document['status']) ?></span></td>
                <td><?= e($document['remarks'] ?: '-') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="grid three" style="margin-top: 18px;">
    <article class="panel">
        <h2>Notifications</h2>
        <?php if (!$notifications): ?>
            <p class="muted">No notifications yet.</p>
        <?php else: ?>
            <ul class="list">
                <?php foreach ($notifications as $notification): ?>
                    <li>
                        <?= e($notification['message']) ?>
                        <br><span class="muted"><?= format_datetime($notification['created_at']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </article>

    <article class="panel">
        <h2>Inquiries</h2>
        <?php if (!$inquiries): ?>
            <p class="muted">No inquiries yet.</p>
        <?php else: ?>
            <ul class="list">
                <?php foreach ($inquiries as $inquiry): ?>
                    <li>
                        <strong><?= e($inquiry['subject']) ?></strong>
                        <br><span class="<?= e(status_class($inquiry['status'])) ?>"><?= e($inquiry['status']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <div class="actions">
            <a class="btn secondary" href="student_inquiries.php">View Inquiries</a>
        </div>
    </article>

    <article class="panel">
        <h2>Announcements</h2>
        <?php if (!$announcements): ?>
            <p class="muted">No current announcements.</p>
        <?php else: ?>
            <ul class="list">
                <?php foreach ($announcements as $announcement): ?>
                    <li>
                        <strong><?= e($announcement['title']) ?></strong>
                        <p><?= e($announcement['body']) ?></p>
                        <?php if ($announcement['deadline']): ?>
                            <span class="muted">Deadline: <?= e($announcement['deadline']) ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </article>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
