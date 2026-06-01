<?php
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/ApplicationService.php';

$user = Auth::requireRole('student');
$service = new ApplicationService();
$application = $service->getStudentApplication((int) $user['user_id']);
$documents = $application ? $service->getDocuments((int) $application['application_id']) : [];
$notifications = $service->getNotifications((int) $user['user_id']);
$announcements = array_slice($service->announcements(), 0, 3);

$pageTitle = 'Student Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-title">
    <div>
        <h1>Student Dashboard</h1>
        <p>Track your application, documents, and admission decision.</p>
    </div>
    <div class="actions" style="margin-top: 0;">
        <a class="btn" href="application_form.php"><?= $application ? 'Edit Application' : 'Start Application' ?></a>
        <a class="btn secondary" href="upload_document.php">Upload Documents</a>
    </div>
</section>

<section class="grid three">
    <article class="metric">
        <span>Application Status</span>
        <strong><?= $application ? e($application['status']) : 'Not Started' ?></strong>
    </article>
    <article class="metric">
        <span>Uploaded Documents</span>
        <strong><?= count($documents) ?></strong>
    </article>
    <article class="metric">
        <span>Program</span>
        <strong><?= $application ? e($application['program']) : '-' ?></strong>
    </article>
</section>

<section class="grid two" style="margin-top: 18px;">
    <article class="panel">
        <h2>Application Summary</h2>
        <?php if (!$application): ?>
            <p class="muted">No application has been created yet.</p>
        <?php else: ?>
            <p><strong>Status:</strong> <span class="<?= e(status_class($application['status'])) ?>"><?= e($application['status']) ?></span></p>
            <p><strong>Intake:</strong> <?= e($application['intake']) ?></p>
            <p><strong>Passport:</strong> <?= e($application['passport_number']) ?></p>
            <p><strong>Submitted:</strong> <?= format_datetime($application['submission_date']) ?></p>
        <?php endif; ?>
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

<section class="grid two" style="margin-top: 18px;">
    <article class="table-panel">
        <h2>Documents</h2>
        <table>
            <thead>
            <tr>
                <th>Type</th>
                <th>File</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$documents): ?>
                <tr><td colspan="3" class="muted">No documents uploaded.</td></tr>
            <?php endif; ?>
            <?php foreach ($documents as $document): ?>
                <tr>
                    <td><?= e($document['type']) ?></td>
                    <td><a href="<?= e($document['file_path']) ?>" target="_blank"><?= e($document['file_name']) ?></a></td>
                    <td><span class="<?= e(status_class($document['status'])) ?>"><?= e($document['status']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
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
