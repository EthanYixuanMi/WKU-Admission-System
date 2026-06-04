<?php
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/ApplicationService.php';

$user = Auth::requireRole('admin');
$service = new ApplicationService();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'announcement';

    if ($action === 'announcement') {
        $title = trim($_POST['title'] ?? '');
        $body = trim($_POST['body'] ?? '');
        $deadline = trim($_POST['deadline'] ?? '');

        if ($title === '' || $body === '') {
            set_flash('error', 'Announcement title and message are required.');
        } else {
            $service->addAnnouncement((int) $user['user_id'], $title, $body, $deadline ?: null);
            $service->notifyRole('student', 'New admission announcement: ' . $title);
            set_flash('success', 'Announcement published.');
        }
    } elseif ($action === 'profile') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $nationality = trim($_POST['nationality'] ?? '');

        if ($name === '') {
            set_flash('error', 'Profile name is required.');
        } else {
            $service->updateUserProfile((int) $user['user_id'], $name, $phone, $nationality);
            set_flash('success', 'Admin profile updated.');
        }
    } elseif ($action === 'user_role') {
        $targetUserId = (int) ($_POST['user_id'] ?? 0);
        $role = $_POST['role'] ?? '';

        if ($targetUserId === (int) $user['user_id'] && $role !== 'admin') {
            set_flash('error', 'You cannot remove your own admin access.');
        } else {
            $service->updateUserRole($targetUserId, $role);
            set_flash('success', 'User role updated.');
        }
    } elseif ($action === 'payment') {
        $applicationId = (int) ($_POST['application_id'] ?? 0);
        $amount = (float) ($_POST['amount'] ?? 0);
        $status = $_POST['status'] ?? 'Pending';
        $remarks = trim($_POST['remarks'] ?? '');

        if ($applicationId <= 0 || $amount < 0) {
            set_flash('error', 'Valid payment amount is required.');
        } else {
            $service->updatePayment($applicationId, $amount, $status, $remarks);
            set_flash('success', 'Payment record updated.');
        }
    }

    redirect('admin_dashboard.php');
}

$stats = $service->adminStats();
$counts = $service->applicationCounts();
$users = $service->users();
$announcements = $service->announcements();
$applications = $service->applicationsForReview();
$payments = $service->paymentsForReport();
$enrollments = $service->enrollmentsForReport();
$emailLogs = $service->emailLogs(10);
$openInquiries = array_slice($service->inquiriesForStaff('Open'), 0, 5);

$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-title">
    <div>
        <h1>Admin Dashboard</h1>
        <p>Monitor users, applications, reports, payments, enrollment, deadlines, and email logs.</p>
    </div>
    <a class="btn secondary" href="manage_inquiries.php">Manage Inquiries</a>
</section>

<section class="grid four">
    <article class="metric"><span>Total Users</span><strong><?= $stats['users'] ?></strong></article>
    <article class="metric"><span>Students</span><strong><?= $stats['students'] ?></strong></article>
    <article class="metric"><span>Applications</span><strong><?= $stats['applications'] ?></strong></article>
    <article class="metric"><span>Documents</span><strong><?= $stats['documents'] ?></strong></article>
    <article class="metric"><span>Open Inquiries</span><strong><?= $stats['open_inquiries'] ?></strong></article>
    <article class="metric"><span>Enrolled</span><strong><?= $stats['enrolled'] ?></strong></article>
</section>

<section class="grid two" style="margin-top: 18px;">
    <article class="panel">
        <h2>Admin Profile</h2>
        <form action="admin_dashboard.php" method="post">
            <input type="hidden" name="action" value="profile">
            <div class="form-grid compact">
                <div class="field">
                    <label for="profile-name">Name</label>
                    <input id="profile-name" name="name" value="<?= e($user['name']) ?>" required>
                </div>
                <div class="field">
                    <label for="profile-phone">Phone</label>
                    <input id="profile-phone" name="phone" value="<?= e($user['phone'] ?? '') ?>">
                </div>
                <div class="field">
                    <label for="profile-nationality">Nationality</label>
                    <input id="profile-nationality" name="nationality" value="<?= e($user['nationality'] ?? '') ?>">
                </div>
            </div>
            <div class="actions">
                <button class="btn" type="submit">Save Profile</button>
            </div>
        </form>
    </article>

    <article class="panel">
        <h2>Publish Announcement</h2>
        <form action="admin_dashboard.php" method="post">
            <input type="hidden" name="action" value="announcement">
            <div class="field">
                <label for="title">Title</label>
                <input id="title" name="title" required>
            </div>
            <div class="field" style="margin-top: 14px;">
                <label for="deadline">Deadline</label>
                <input id="deadline" name="deadline" type="date">
            </div>
            <div class="field" style="margin-top: 14px;">
                <label for="body">Message</label>
                <textarea id="body" name="body" required></textarea>
            </div>
            <div class="actions">
                <button class="btn" type="submit">Publish</button>
            </div>
        </form>
    </article>
</section>

<section class="grid two" style="margin-top: 18px;">
    <article class="panel">
        <h2>Application Statistics</h2>
        <div class="grid two">
            <?php foreach ($counts as $status => $total): ?>
                <div class="review-box">
                    <span class="<?= e(status_class($status)) ?>"><?= e($status) ?></span>
                    <strong style="display: block; font-size: 26px; margin-top: 8px;"><?= $total ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </article>

    <article class="panel">
        <h2>Open Inquiries</h2>
        <?php if (!$openInquiries): ?>
            <p class="muted">No open inquiries.</p>
        <?php else: ?>
            <ul class="list">
                <?php foreach ($openInquiries as $inquiry): ?>
                    <li>
                        <strong><?= e($inquiry['subject']) ?></strong>
                        <br><span class="muted"><?= e($inquiry['name']) ?> | <?= format_datetime($inquiry['created_at']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <div class="actions">
            <a class="btn secondary" href="manage_inquiries.php">Reply to Inquiries</a>
        </div>
    </article>
</section>

<section class="table-panel wide" style="margin-top: 18px;">
    <h2>Admission Report</h2>
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>Student</th>
            <th>Program</th>
            <th>Status</th>
            <th>Submitted</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$applications): ?>
            <tr><td colspan="5" class="muted">No application records.</td></tr>
        <?php endif; ?>
        <?php foreach ($applications as $application): ?>
            <tr>
                <td>#<?= (int) $application['application_id'] ?></td>
                <td><?= e($application['name']) ?><br><span class="muted"><?= e($application['email']) ?></span></td>
                <td><?= e($application['program']) ?></td>
                <td><span class="<?= e(status_class($application['status'])) ?>"><?= e($application['status']) ?></span></td>
                <td><?= format_datetime($application['submission_date']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="table-panel wide" style="margin-top: 18px;">
    <h2>Payment Management</h2>
    <table>
        <thead>
        <tr>
            <th>Student</th>
            <th>Program</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Update</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$payments): ?>
            <tr><td colspan="5" class="muted">No payment records.</td></tr>
        <?php endif; ?>
        <?php foreach ($payments as $payment): ?>
            <tr>
                <td><?= e($payment['name']) ?><br><span class="muted"><?= e($payment['email']) ?></span></td>
                <td><?= e($payment['program']) ?><br><span class="muted"><?= e($payment['application_status']) ?></span></td>
                <td>$<?= number_format((float) $payment['amount'], 2) ?></td>
                <td><span class="<?= e(status_class($payment['status'])) ?>"><?= e($payment['status']) ?></span></td>
                <td>
                    <form class="inline-form" action="admin_dashboard.php" method="post">
                        <input type="hidden" name="action" value="payment">
                        <input type="hidden" name="application_id" value="<?= (int) $payment['application_id'] ?>">
                        <input name="amount" type="number" min="0" step="0.01" value="<?= e($payment['amount']) ?>" aria-label="Amount">
                        <select name="status" aria-label="Payment status">
                            <?php foreach (['Pending', 'Paid', 'Waived'] as $status): ?>
                                <option value="<?= e($status) ?>" <?= selected($payment['status'], $status) ?>><?= e($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input name="remarks" value="<?= e($payment['remarks']) ?>" placeholder="Remarks">
                        <button class="btn secondary" type="submit">Save</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="table-panel wide" style="margin-top: 18px;">
    <h2>Enrollment Report</h2>
    <table>
        <thead>
        <tr>
            <th>Student</th>
            <th>Program</th>
            <th>Offer Code</th>
            <th>Application</th>
            <th>Enrollment</th>
            <th>Updated</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$enrollments): ?>
            <tr><td colspan="6" class="muted">No enrollment records yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($enrollments as $enrollment): ?>
            <tr>
                <td><?= e($enrollment['name']) ?><br><span class="muted"><?= e($enrollment['email']) ?></span></td>
                <td><?= e($enrollment['program']) ?><br><span class="muted"><?= e($enrollment['intake']) ?></span></td>
                <td><?= e($enrollment['offer_code'] ?: '-') ?></td>
                <td><span class="<?= e(status_class($enrollment['application_status'])) ?>"><?= e($enrollment['application_status']) ?></span></td>
                <td><span class="<?= e(status_class($enrollment['status'])) ?>"><?= e($enrollment['status']) ?></span></td>
                <td><?= format_datetime($enrollment['updated_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="table-panel wide" style="margin-top: 18px;">
    <h2>User Management</h2>
    <table>
        <thead>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Phone</th>
            <th>Nationality</th>
            <th>Update Role</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $record): ?>
            <tr>
                <td><?= e($record['name']) ?></td>
                <td><?= e($record['email']) ?></td>
                <td><?= e($record['role']) ?></td>
                <td><?= e($record['phone'] ?: '-') ?></td>
                <td><?= e($record['nationality'] ?: '-') ?></td>
                <td>
                    <form class="inline-form compact" action="admin_dashboard.php" method="post">
                        <input type="hidden" name="action" value="user_role">
                        <input type="hidden" name="user_id" value="<?= (int) $record['user_id'] ?>">
                        <select name="role">
                            <?php foreach (['student', 'officer', 'admin'] as $role): ?>
                                <option value="<?= e($role) ?>" <?= selected($record['role'], $role) ?>><?= e($role) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn secondary" type="submit">Save</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="grid two" style="margin-top: 18px;">
    <article class="panel">
        <h2>Announcements</h2>
        <?php if (!$announcements): ?>
            <p class="muted">No announcements published.</p>
        <?php else: ?>
            <ul class="list">
                <?php foreach ($announcements as $announcement): ?>
                    <li>
                        <strong><?= e($announcement['title']) ?></strong>
                        <p><?= e($announcement['body']) ?></p>
                        <span class="muted">
                            <?= $announcement['deadline'] ? 'Deadline: ' . e($announcement['deadline']) . ' | ' : '' ?>
                            Posted by <?= e($announcement['author_name']) ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </article>

    <article class="panel">
        <h2>Email Log</h2>
        <p class="muted">Automatic notification delivery attempts are recorded here for demo evidence.</p>
        <?php if (!$emailLogs): ?>
            <p class="muted">No email records yet.</p>
        <?php else: ?>
            <ul class="list">
                <?php foreach ($emailLogs as $email): ?>
                    <li>
                        <strong><?= e($email['subject']) ?></strong>
                        <br><?= e($email['recipient_email']) ?>
                        <br><span class="<?= e(status_class($email['status'])) ?>"><?= e($email['status']) ?></span>
                        <span class="muted"><?= format_datetime($email['created_at']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </article>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
