<?php
require_once __DIR__ . '/../includes/config.php';
require_admin_login();

$faculties = $conn->query("SELECT id, name FROM faculty ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

// === ADD ===
if (isset($_POST['add_question'])) {
    $f_id = $_POST['f_id'] ?? '';
    $qt   = trim($_POST['question_text'] ?? '');
    $opt1 = trim($_POST['opt1'] ?? '');
    $opt2 = trim($_POST['opt2'] ?? '');
    $opt3 = trim($_POST['opt3'] ?? '');
    $opt4 = trim($_POST['opt4'] ?? '');
    $correct = $_POST['correct_opt'] ?? '';

    if (empty($f_id) || $qt === '' || $opt1 === '' || $opt2 === '' || $opt3 === '' || $opt4 === '' || !in_array($correct, ['A','B','C','D'], true)) {
        set_flash('error', "All fields are required and correct answer must be A, B, C, or D.");
    } else {
        $new_id = next_id($conn, 'questions', 'Q', 2);
        $stmt = $conn->prepare("INSERT INTO questions (id, f_id, question_text, opt1, opt2, opt3, opt4, correct_opt) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $new_id, $f_id, $qt, $opt1, $opt2, $opt3, $opt4, $correct);
        $stmt->execute();
        $stmt->close();
        set_flash('success', "Question " . h($new_id) . " added successfully.");
    }
    redirect('manage_questions.php');
}

// === EDIT ===
if (isset($_POST['edit_question'])) {
    $id   = $_POST['question_id'] ?? '';
    $f_id = $_POST['f_id'] ?? '';
    $qt   = trim($_POST['question_text'] ?? '');
    $opt1 = trim($_POST['opt1'] ?? '');
    $opt2 = trim($_POST['opt2'] ?? '');
    $opt3 = trim($_POST['opt3'] ?? '');
    $opt4 = trim($_POST['opt4'] ?? '');
    $correct = $_POST['correct_opt'] ?? '';

    if (empty($id) || empty($f_id) || $qt === '' || !in_array($correct, ['A','B','C','D'], true)) {
        set_flash('error', "All fields are required.");
    } else {
        $stmt = $conn->prepare("UPDATE questions SET f_id=?, question_text=?, opt1=?, opt2=?, opt3=?, opt4=?, correct_opt=? WHERE id=?");
        $stmt->bind_param("ssssssss", $f_id, $qt, $opt1, $opt2, $opt3, $opt4, $correct, $id);
        $stmt->execute();
        $stmt->close();
        set_flash('success', "Question " . h($id) . " updated successfully.");
    }
    redirect('manage_questions.php');
}

// === DELETE ===
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM questions WHERE id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $stmt->close();
    set_flash('success', "Question " . h($id) . " deleted.");
    redirect('manage_questions.php');
}

// Filter by faculty
$filter_f = $_GET['f_id'] ?? '';
if ($filter_f !== '') {
    $stmt = $conn->prepare("SELECT q.*, f.name AS faculty_name FROM questions q JOIN faculty f ON q.f_id = f.id WHERE q.f_id = ? ORDER BY q.id");
    $stmt->bind_param("s", $filter_f);
    $stmt->execute();
    $questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $questions = $conn->query("SELECT q.*, f.name AS faculty_name FROM questions q JOIN faculty f ON q.f_id = f.id ORDER BY q.id")->fetch_all(MYSQLI_ASSOC);
}

$page_title = "Manage Questions";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="es-shell">
    <div class="page-head flex-between">
        <div>
            <div class="eyebrow">Question Bank</div>
            <h1>Manage Questions</h1>
            <p>Add, edit, or delete individual exam questions.</p>
        </div>
        <button class="btn btn-primary" onclick="esOpenModal('addQModal')">+ Add Question</button>
    </div>

    <?php require __DIR__ . '/../includes/flash.php'; ?>

    <form method="get" style="margin-bottom: 18px; max-width:340px;">
        <select name="f_id" class="form-select" onchange="this.form.submit()">
            <option value="">-- All Faculties --</option>
            <?php foreach ($faculties as $f): ?>
                <option value="<?= h($f['id']) ?>" <?= $filter_f === $f['id'] ? 'selected' : '' ?>><?= h($f['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </form>

    <div class="es-table-wrap">
        <table class="es-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th width="30%">Question</th>
                    <th>Faculty</th>
                    <th>Correct</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($questions)): ?>
                    <tr><td colspan="5" class="text-muted text-center" style="padding:30px;">No questions found.</td></tr>
                <?php endif; ?>
                <?php foreach ($questions as $q): ?>
                    <tr>
                        <td class="mono"><?= h($q['id']) ?></td>
                        <td><?= h($q['question_text']) ?></td>
                        <td><?= h($q['faculty_name']) ?></td>
                        <td><span class="chip chip-good"><?= h($q['correct_opt']) ?></span></td>
                        <td style="white-space:nowrap;">
                            <button class="btn btn-sm btn-outline" onclick='openEditQ(<?= json_encode($q) ?>)'>Edit</button>
                            <a href="?delete=<?= urlencode($q['id']) ?>" class="btn btn-sm btn-danger"
                               onclick="return esConfirm('Delete question <?= h($q['id']) ?>?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Modal -->
<div class="es-modal-backdrop" id="addQModal">
    <div class="es-modal">
        <form method="post">
            <div class="es-modal-head">
                <h3>Add Question</h3>
                <button type="button" class="close-x" onclick="esCloseModal('addQModal')">&times;</button>
            </div>
            <div class="es-modal-body">
                <?php include __DIR__ . '/_question_fields.php'; ?>
            </div>
            <div class="es-modal-foot">
                <button type="button" class="btn btn-outline" onclick="esCloseModal('addQModal')">Cancel</button>
                <button type="submit" name="add_question" class="btn btn-success">Save Question</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="es-modal-backdrop" id="editQModal">
    <div class="es-modal">
        <form method="post" id="editQForm">
            <div class="es-modal-head">
                <h3>Edit Question</h3>
                <button type="button" class="close-x" onclick="esCloseModal('editQModal')">&times;</button>
            </div>
            <div class="es-modal-body">
                <input type="hidden" name="question_id" id="eq_id">
                <?php include __DIR__ . '/_question_fields.php'; ?>
            </div>
            <div class="es-modal-foot">
                <button type="button" class="btn btn-outline" onclick="esCloseModal('editQModal')">Cancel</button>
                <button type="submit" name="edit_question" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script src="../assets/js/app.js"></script>
<script>
function openEditQ(q) {
    const form = document.getElementById('editQForm');
    form.querySelector('#eq_id').value = q.id;
    form.querySelector('[name="f_id"]').value = q.f_id;
    form.querySelector('[name="question_text"]').value = q.question_text;
    form.querySelector('[name="opt1"]').value = q.opt1;
    form.querySelector('[name="opt2"]').value = q.opt2;
    form.querySelector('[name="opt3"]').value = q.opt3;
    form.querySelector('[name="opt4"]').value = q.opt4;
    form.querySelector('[name="correct_opt"]').value = q.correct_opt;
    esOpenModal('editQModal');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
