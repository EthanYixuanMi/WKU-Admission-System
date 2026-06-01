<?php
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/ApplicationService.php';

$user = Auth::requireRole('admin');
$service = new ApplicationService();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $body = trim($_POST['body'] ?? '');
    $deadline = trim($_POST['deadline'] ?? '');

    if ($title === '' || $body === '') {
        set_flash('error', 'Announcement title and message are required.');
    } else {
        $service->addAnnouncement((int) $user['user_id'], $title, $body, $deadline ?: null);
        $service->notifyRole('student', 'New admission announcement: ' . $title);
        set_flash('success', 'Announcement published.');
        redirect('admin_dashboard.php');
    }
}

$stats = $service->adminStats();
$counts = $service->applicationCounts();
$users = $service->users();
$announcements = $service->announcements();
$applications = $service->applicationsForReview();

$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-title">
    <div>
        <h1>Admin Dashboard</h1>
        <p>Monitor users, application statistics, reports, deadlines, and announcements.</p>
    </div>
</section>

<section class="grid four">
    <article class="metric"><span>Total Users</span><strong><?= $stats['users'] ?></strong></article>
    <article class="metric"><span>Students</span><strong><?= $stats['students'] ?></strong></article>
    <article class="metric"><span>Applications</span><strong><?= $stats['applications'] ?></strong></article>
    <article class="metric"><span>Documents</span><strong><?= $stats['documents'] ?></strong></article>
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
        <h2>Publish Announcement</h2>
        <form action="admin_dashboard.php" method="post">
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

<section class="table-panel" style="margin-top: 18px;">
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

<section class="grid two" style="margin-top: 18px;">
    <article class="table-panel">
        <h2>Users</h2>
        <table>
            <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Nationality</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $record): ?>
                <tr>
                    <td><?= e($record['name']) ?></td>
                    <td><?= e($record['email']) ?></td>
                    <td><?= e($record['role']) ?></td>
                    <td><?= e($record['nationality'] ?: '-') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </article>

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
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
