<?php
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/ApplicationService.php';

$user = Auth::requireRole('officer');
$service = new ApplicationService();
$filter = trim($_GET['status'] ?? '');
$validStatuses = ['Submitted', 'Under Review', 'Need More Documents', 'Approved', 'Rejected'];
$applications = $service->applicationsForReview(in_array($filter, $validStatuses, true) ? $filter : null);
$counts = $service->applicationCounts();
$notifications = $service->getNotifications((int) $user['user_id'], 4);

$pageTitle = 'Officer Review';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-title">
    <div>
        <h1>Admission Officer Review</h1>
        <p>Verify documents, review applications, and send admission decisions.</p>
    </div>
    <form class="inline-form" action="officer_dashboard.php" method="get">
        <select name="status">
            <option value="">All statuses</option>
            <?php foreach ($validStatuses as $status): ?>
                <option value="<?= e($status) ?>" <?= selected($filter, $status) ?>><?= e($status) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn secondary" type="submit">Filter</button>
    </form>
</section>

<section class="grid four">
    <article class="metric"><span>Submitted</span><strong><?= $counts['Submitted'] ?></strong></article>
    <article class="metric"><span>Under Review</span><strong><?= $counts['Under Review'] ?></strong></article>
    <article class="metric"><span>Need Docs</span><strong><?= $counts['Need More Documents'] ?></strong></article>
    <article class="metric"><span>Approved</span><strong><?= $counts['Approved'] ?></strong></article>
</section>

<section class="grid two" style="margin-top: 18px;">
    <article class="table-panel">
        <h2>Applications</h2>
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Student</th>
                <th>Program</th>
                <th>Status</th>
                <th>Submitted</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$applications): ?>
                <tr><td colspan="6" class="muted">No applications found.</td></tr>
            <?php endif; ?>
            <?php foreach ($applications as $application): ?>
                <tr>
                    <td>#<?= (int) $application['application_id'] ?></td>
                    <td><?= e($application['name']) ?><br><span class="muted"><?= e($application['email']) ?></span></td>
                    <td><?= e($application['program']) ?></td>
                    <td><span class="<?= e(status_class($application['status'])) ?>"><?= e($application['status']) ?></span></td>
                    <td><?= format_datetime($application['submission_date']) ?></td>
                    <td><a class="btn secondary" href="review_application.php?id=<?= (int) $application['application_id'] ?>">Review</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </article>

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
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
