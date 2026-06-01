<?php
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/ApplicationService.php';

$user = Auth::requireRole('officer');
$service = new ApplicationService();
$applicationId = (int) ($_GET['id'] ?? $_POST['application_id'] ?? 0);
$application = $service->applicationWithStudent($applicationId);

if (!$application) {
    set_flash('error', 'Application not found.');
    redirect('officer_dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = $_POST['form'] ?? '';

    if ($form === 'document') {
        $documentId = (int) ($_POST['document_id'] ?? 0);
        $status = $_POST['status'] ?? 'Pending';
        $remarks = trim($_POST['remarks'] ?? '');

        if (!in_array($status, ['Pending', 'Verified', 'Rejected'], true)) {
            set_flash('error', 'Invalid document status.');
        } else {
            $service->setDocumentStatus($documentId, $status, $remarks);
            $service->createNotification(
                (int) $application['user_id'],
                'Document verification updated: ' . $status . '. ' . $remarks
            );
            set_flash('success', 'Document status updated.');
        }
        redirect('review_application.php?id=' . $applicationId);
    }

    if ($form === 'decision') {
        $decision = $_POST['decision'] ?? '';
        $remarks = trim($_POST['remarks'] ?? '');

        if (!in_array($decision, ['Under Review', 'Need More Documents', 'Approved', 'Rejected'], true)) {
            set_flash('error', 'Invalid application decision.');
        } elseif ($remarks === '') {
            set_flash('error', 'Remarks are required for an application decision.');
        } else {
            $service->reviewApplication($applicationId, (int) $user['user_id'], $decision, $remarks);
            set_flash('success', 'Application decision saved.');
        }
        redirect('review_application.php?id=' . $applicationId);
    }
}

$documents = $service->getDocuments($applicationId);
$reviews = $service->reviewsForApplication($applicationId);

$pageTitle = 'Review Application';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-title">
    <div>
        <h1>Review Application #<?= $applicationId ?></h1>
        <p><?= e($application['name']) ?>, <?= e($application['nationality'] ?: 'International applicant') ?></p>
    </div>
    <a class="btn ghost" href="officer_dashboard.php">Back to List</a>
</section>

<section class="grid two">
    <article class="panel">
        <h2>Applicant Information</h2>
        <p><strong>Name:</strong> <?= e($application['name']) ?></p>
        <p><strong>Email:</strong> <?= e($application['email']) ?></p>
        <p><strong>Program:</strong> <?= e($application['program']) ?></p>
        <p><strong>Intake:</strong> <?= e($application['intake']) ?></p>
        <p><strong>Passport:</strong> <?= e($application['passport_number']) ?></p>
        <p><strong>GPA:</strong> <?= e($application['gpa']) ?></p>
        <p><strong>English Score:</strong> <?= e($application['english_score']) ?></p>
        <p><strong>Status:</strong> <span class="<?= e(status_class($application['status'])) ?>"><?= e($application['status']) ?></span></p>
    </article>

    <article class="panel">
        <h2>Personal Statement</h2>
        <p><?= nl2br(e($application['personal_statement'])) ?></p>
    </article>
</section>

<section class="table-panel" style="margin-top: 18px;">
    <h2>Document Verification</h2>
    <table>
        <thead>
        <tr>
            <th>Type</th>
            <th>File</th>
            <th>Status</th>
            <th>Review</th>
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
                <td>
                    <form class="inline-form" action="review_application.php" method="post">
                        <input type="hidden" name="form" value="document">
                        <input type="hidden" name="application_id" value="<?= $applicationId ?>">
                        <input type="hidden" name="document_id" value="<?= (int) $document['document_id'] ?>">
                        <select name="status">
                            <?php foreach (['Pending', 'Verified', 'Rejected'] as $status): ?>
                                <option value="<?= e($status) ?>" <?= selected($document['status'], $status) ?>><?= e($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input name="remarks" value="<?= e($document['remarks']) ?>" placeholder="Remarks">
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
        <h2>Application Decision</h2>
        <form action="review_application.php" method="post">
            <input type="hidden" name="form" value="decision">
            <input type="hidden" name="application_id" value="<?= $applicationId ?>">
            <div class="field">
                <label for="decision">Decision</label>
                <select id="decision" name="decision" required>
                    <?php foreach (['Under Review', 'Need More Documents', 'Approved', 'Rejected'] as $decision): ?>
                        <option value="<?= e($decision) ?>" <?= selected($application['status'], $decision) ?>><?= e($decision) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field" style="margin-top: 16px;">
                <label for="remarks">Feedback</label>
                <textarea id="remarks" name="remarks" required></textarea>
            </div>
            <div class="actions">
                <button class="btn" type="submit">Save Decision</button>
            </div>
        </form>
    </article>

    <article class="panel">
        <h2>Review History</h2>
        <?php if (!$reviews): ?>
            <p class="muted">No review history yet.</p>
        <?php else: ?>
            <ul class="list">
                <?php foreach ($reviews as $review): ?>
                    <li>
                        <strong><?= e($review['decision']) ?></strong> by <?= e($review['officer_name']) ?>
                        <p><?= e($review['remarks']) ?></p>
                        <span class="muted"><?= format_datetime($review['reviewed_at']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </article>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
