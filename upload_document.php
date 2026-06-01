<?php
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/ApplicationService.php';

$user = Auth::requireRole('student');
$service = new ApplicationService();
$application = $service->getStudentApplication((int) $user['user_id']);

if (!$application) {
    set_flash('error', 'Create an application before uploading documents.');
    redirect('application_form.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = trim($_POST['type'] ?? '');
    $file = $_FILES['document'] ?? null;
    $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];

    if ($type === '' || !$file || $file['error'] !== UPLOAD_ERR_OK) {
        set_flash('error', 'Please choose a document type and file.');
    } else {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowed, true)) {
            set_flash('error', 'Only PDF, Word, JPG, and PNG files are allowed.');
        } elseif ($file['size'] > 8 * 1024 * 1024) {
            set_flash('error', 'File size must be 8MB or smaller.');
        } else {
            $folder = __DIR__ . '/uploads/app_' . (int) $application['application_id'];
            if (!is_dir($folder)) {
                mkdir($folder, 0775, true);
            }

            $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name']));
            $storedName = date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '_' . $safeName;
            $target = $folder . '/' . $storedName;
            $relativePath = 'uploads/app_' . (int) $application['application_id'] . '/' . $storedName;

            if (move_uploaded_file($file['tmp_name'], $target)) {
                $service->addDocument((int) $application['application_id'], $type, $safeName, $relativePath);
                set_flash('success', 'Document uploaded for verification.');
                redirect('upload_document.php');
            }

            set_flash('error', 'Upload failed. Check the uploads folder permission.');
        }
    }
}

$documents = $service->getDocuments((int) $application['application_id']);

$pageTitle = 'Upload Documents';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-title">
    <div>
        <h1>Document Upload</h1>
        <p>Submit passport, transcript, English test score, and supporting files.</p>
    </div>
</section>

<section class="grid two">
    <article class="panel">
        <h2>Upload New Document</h2>
        <form action="upload_document.php" method="post" enctype="multipart/form-data">
            <div class="field">
                <label for="type">Document Type</label>
                <select id="type" name="type" required>
                    <option value="">Select document type</option>
                    <?php foreach (['Passport', 'Transcript', 'English Test', 'Recommendation', 'Other'] as $type): ?>
                        <option value="<?= e($type) ?>"><?= e($type) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field" style="margin-top: 16px;">
                <label for="document">File</label>
                <input id="document" name="document" type="file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
            </div>
            <div class="actions">
                <button class="btn" type="submit">Upload</button>
                <a class="btn ghost" href="student_dashboard.php">Back</a>
            </div>
        </form>
    </article>

    <article class="table-panel">
        <h2>Uploaded Documents</h2>
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
    </article>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
