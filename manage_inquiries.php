<?php
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/ApplicationService.php';

$user = Auth::requireRole(['officer', 'admin']);
$service = new ApplicationService();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inquiryId = (int) ($_POST['inquiry_id'] ?? 0);
    $response = trim($_POST['response'] ?? '');
    $status = $_POST['status'] ?? 'Answered';

    if ($inquiryId <= 0 || $response === '') {
        set_flash('error', 'Reply text is required.');
    } else {
        $service->respondInquiry($inquiryId, (int) $user['user_id'], $response, $status);
        set_flash('success', 'Inquiry response saved.');
    }

    redirect('manage_inquiries.php');
}

$filter = trim($_GET['status'] ?? '');
$validStatuses = ['Open', 'Answered', 'Closed'];
$inquiries = $service->inquiriesForStaff(in_array($filter, $validStatuses, true) ? $filter : null);

$pageTitle = 'Inquiry Management';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-title">
    <div>
        <h1>Inquiry Management</h1>
        <p>Respond to student questions and keep communication status clear.</p>
    </div>
    <form class="inline-form toolbar-form" action="manage_inquiries.php" method="get">
        <select name="status">
            <option value="">All statuses</option>
            <?php foreach ($validStatuses as $status): ?>
                <option value="<?= e($status) ?>" <?= selected($filter, $status) ?>><?= e($status) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn secondary" type="submit">Filter</button>
    </form>
</section>

<section class="stack">
    <?php if (!$inquiries): ?>
        <article class="panel">
            <p class="muted">No inquiries found.</p>
        </article>
    <?php endif; ?>

    <?php foreach ($inquiries as $inquiry): ?>
        <article class="panel">
            <div class="record-header">
                <div>
                    <h2><?= e($inquiry['subject']) ?></h2>
                    <p class="muted">
                        <?= e($inquiry['name']) ?> | <?= e($inquiry['email']) ?> |
                        <?= e($inquiry['program'] ?: 'General question') ?> |
                        Created <?= format_datetime($inquiry['created_at']) ?>
                    </p>
                </div>
                <span class="<?= e(status_class($inquiry['status'])) ?>"><?= e($inquiry['status']) ?></span>
            </div>

            <p><?= nl2br(e($inquiry['message'])) ?></p>

            <?php if ($inquiry['response']): ?>
                <div class="review-box">
                    <strong>Current Reply</strong>
                    <p><?= nl2br(e($inquiry['response'])) ?></p>
                    <span class="muted">
                        <?= $inquiry['responder_name'] ? 'By ' . e($inquiry['responder_name']) . ' | ' : '' ?>
                        <?= format_datetime($inquiry['responded_at']) ?>
                    </span>
                </div>
            <?php endif; ?>

            <form action="manage_inquiries.php" method="post" class="response-form">
                <input type="hidden" name="inquiry_id" value="<?= (int) $inquiry['inquiry_id'] ?>">
                <div class="field">
                    <label for="response-<?= (int) $inquiry['inquiry_id'] ?>">Reply</label>
                    <textarea id="response-<?= (int) $inquiry['inquiry_id'] ?>" name="response" required><?= e($inquiry['response'] ?? '') ?></textarea>
                </div>
                <div class="actions">
                    <select name="status">
                        <?php foreach (['Answered', 'Closed'] as $status): ?>
                            <option value="<?= e($status) ?>" <?= selected($inquiry['status'], $status) ?>><?= e($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn" type="submit">Save Reply</button>
                </div>
            </form>
        </article>
    <?php endforeach; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
