<?php
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/ApplicationService.php';

$user = Auth::requireRole('student');
$service = new ApplicationService();
$application = $service->getStudentApplication((int) $user['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($application && in_array($application['status'], ['Approved', 'Rejected'], true)) {
        set_flash('error', 'Finalized applications cannot be edited.');
        redirect('student_dashboard.php');
    }

    $data = [
        'program' => trim($_POST['program'] ?? ''),
        'intake' => trim($_POST['intake'] ?? ''),
        'date_of_birth' => trim($_POST['date_of_birth'] ?? ''),
        'gender' => trim($_POST['gender'] ?? ''),
        'passport_number' => trim($_POST['passport_number'] ?? ''),
        'previous_school' => trim($_POST['previous_school'] ?? ''),
        'gpa' => (float) ($_POST['gpa'] ?? 0),
        'english_score' => trim($_POST['english_score'] ?? ''),
        'personal_statement' => trim($_POST['personal_statement'] ?? ''),
    ];
    $submit = ($_POST['action'] ?? '') === 'submit';

    if ($submit && in_array('', [
        $data['program'],
        $data['intake'],
        $data['date_of_birth'],
        $data['gender'],
        $data['passport_number'],
        $data['previous_school'],
        $data['english_score'],
        $data['personal_statement'],
    ], true)) {
        set_flash('error', 'Please complete all fields before submitting.');
    } else {
        $service->saveApplication((int) $user['user_id'], $data, $submit);
        set_flash('success', $submit ? 'Application submitted for review.' : 'Application draft saved.');
        redirect('student_dashboard.php');
    }
}

$pageTitle = 'Application Form';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-title">
    <div>
        <h1>Online Application</h1>
        <p>Complete academic, passport, and program information.</p>
    </div>
    <?php if ($application): ?>
        <span class="<?= e(status_class($application['status'])) ?>"><?= e($application['status']) ?></span>
    <?php endif; ?>
</section>

<section class="panel">
    <form action="application_form.php" method="post">
        <div class="form-grid">
            <div class="field">
                <label for="program">Program</label>
                <select id="program" name="program" required>
                    <option value="">Select program</option>
                    <?php
                    $programs = ['Computer Science', 'Finance', 'Marketing', 'English', 'Psychology', 'Management'];
                    foreach ($programs as $program):
                    ?>
                        <option value="<?= e($program) ?>" <?= selected($application['program'] ?? '', $program) ?>><?= e($program) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="intake">Intake</label>
                <select id="intake" name="intake" required>
                    <option value="">Select intake</option>
                    <?php foreach (['Fall 2026', 'Spring 2027', 'Fall 2027'] as $intake): ?>
                        <option value="<?= e($intake) ?>" <?= selected($application['intake'] ?? '', $intake) ?>><?= e($intake) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="date_of_birth">Date of Birth</label>
                <input id="date_of_birth" name="date_of_birth" type="date" value="<?= e($application['date_of_birth'] ?? '') ?>" required>
            </div>

            <div class="field">
                <label for="gender">Gender</label>
                <select id="gender" name="gender" required>
                    <option value="">Select gender</option>
                    <?php foreach (['Female', 'Male', 'Other'] as $gender): ?>
                        <option value="<?= e($gender) ?>" <?= selected($application['gender'] ?? '', $gender) ?>><?= e($gender) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="passport_number">Passport Number</label>
                <input id="passport_number" name="passport_number" value="<?= e($application['passport_number'] ?? '') ?>" required>
            </div>

            <div class="field">
                <label for="previous_school">Previous School</label>
                <input id="previous_school" name="previous_school" value="<?= e($application['previous_school'] ?? '') ?>" required>
            </div>

            <div class="field">
                <label for="gpa">GPA</label>
                <input id="gpa" name="gpa" type="number" min="0" max="4" step="0.01" value="<?= e($application['gpa'] ?? '') ?>" required>
            </div>

            <div class="field">
                <label for="english_score">English Score</label>
                <input id="english_score" name="english_score" value="<?= e($application['english_score'] ?? '') ?>" placeholder="IELTS 6.5 / TOEFL 85" required>
            </div>

            <div class="field full">
                <label for="personal_statement">Personal Statement</label>
                <textarea id="personal_statement" name="personal_statement" required><?= e($application['personal_statement'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="actions">
            <button class="btn secondary" type="submit" name="action" value="draft">Save Draft</button>
            <button class="btn" type="submit" name="action" value="submit">Submit Application</button>
            <a class="btn ghost" href="student_dashboard.php">Back</a>
        </div>
    </form>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
