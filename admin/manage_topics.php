<?php
require_once __DIR__ . '/../includes/config.php';
require_admin_login();

$success = $error = false;

// === ADD ===
if (isset($_POST['add_topic'])) {
    $name = trim($_POST['topic_name'] ?? '');
    if ($name === '') {
        $error = "Please enter a faculty name!";
    } else {
        $new_id = next_id($conn, 'faculty', 'F', 2);
        $stmt = $conn->prepare("INSERT INTO faculty (id, name) VALUES (?, ?)");
        $stmt->bind_param("ss", $new_id, $name);
        if ($stmt->execute()) {
            $success = true;
        } else {
            $error = "Error adding faculty: " . $conn->error;
        }
        $stmt->close();
    }
}

// === EDIT ===
if (isset($_POST['edit_topic'])) {
    $id   = $_POST['topic_id'] ?? '';
    $name = trim($_POST['topic_name'] ?? '');
    if ($name === '' || $id === '') {
        $error = "Faculty name and ID are required.";
    } else {
        $stmt = $conn->prepare("UPDATE faculty SET name = ? WHERE id = ?");
        $stmt->bind_param("ss", $name, $id);
        $stmt->execute();
        $stmt->close();
        $success = true;
    }
}

// === DELETE ===
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    // Block delete if this faculty has any exam history (keeps results table intact)
    $check = $conn->prepare("SELECT COUNT(*) AS c FROM results WHERE exam_topic_id = ?");
    $check->bind_param("s", $id);
    $check->execute();
    $has_results = $check->get_result()->fetch_assoc()['c'] > 0;
    $check->close();

    if ($has_results) {
        set_flash('error', "Cannot delete this faculty — it already has exam results on record.");
    } else {
        // questions.f_id has ON DELETE CASCADE, so deleting faculty removes its questions too
        $stmt = $conn->prepare("DELETE FROM faculty WHERE id = ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $stmt->close();
        set_flash('success', "Faculty deleted successfully, along with its question bank.");
    }
    redirect('manage_topics.php');
}

if ($success) {
    set_flash('success', "Faculty saved successfully.");
    redirect('manage_topics.php');
}

$sql = "SELECT f.*, COUNT(q.id) AS qcount
        FROM faculty f
        LEFT JOIN questions q ON f.id = q.f_id
        GROUP BY f.id
        ORDER BY f.name";
$faculties = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

$page_title = "Manage Topics";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="es-shell">
    <div class="page-head flex-between">
        <div>
            <div class="eyebrow">Faculty / Subjects</div>
            <h1>Manage Topics</h1>
            <p>Add, edit, or remove the faculties/subjects students can be examined on.</p>
        </div>
        <button class="btn btn-primary" onclick="esOpenModal('addTopicModal')">+ Add New Faculty</button>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><span><?= h($error) ?></span></div>
    <?php endif; ?>
    <?php require __DIR__ . '/../includes/flash.php'; ?>

    <div class="es-table-wrap">
        <table class="es-table">
            <thead>
                <tr>
                    <th>Faculty ID</th>
                    <th>Faculty Name</th>
                    <th>Questions</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($faculties as $f): ?>
                    <tr>
                        <td class="mono"><?= h($f['id']) ?></td>
                        <td><strong><?= h($f['name']) ?></strong></td>
                        <td><span class="chip chip-neutral"><?= $f['qcount'] ?></span></td>
                        <td style="white-space:nowrap;">
                            <button class="btn btn-sm btn-outline"
                                    onclick="openEditTopic('<?= h($f['id']) ?>', '<?= h(addslashes($f['name'])) ?>')">
                                Edit
                            </button>
                            <a href="?delete=<?= urlencode($f['id']) ?>"
                               class="btn btn-sm btn-danger"
                               onclick="return esConfirm('Delete faculty <?= h($f['id']) ?> and all its questions?')">
                                Delete
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Modal -->
<div class="es-modal-backdrop" id="addTopicModal">
    <div class="es-modal">
        <form method="post">
            <div class="es-modal-head">
                <h3>Add New Faculty</h3>
                <button type="button" class="close-x" onclick="esCloseModal('addTopicModal')">&times;</button>
            </div>
            <div class="es-modal-body">
                <label class="form-label">Faculty Name</label>
                <input type="text" name="topic_name" class="form-control" placeholder="e.g. SUMAN DAS" required autofocus>
            </div>
            <div class="es-modal-foot">
                <button type="button" class="btn btn-outline" onclick="esCloseModal('addTopicModal')">Cancel</button>
                <button type="submit" name="add_topic" class="btn btn-success">Save Faculty</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="es-modal-backdrop" id="editTopicModal">
    <div class="es-modal">
        <form method="post">
            <div class="es-modal-head">
                <h3>Edit Faculty</h3>
                <button type="button" class="close-x" onclick="esCloseModal('editTopicModal')">&times;</button>
            </div>
            <div class="es-modal-body">
                <input type="hidden" name="topic_id" id="editTopicId">
                <label class="form-label">Faculty Name</label>
                <input type="text" name="topic_name" id="editTopicName" class="form-control" required>
            </div>
            <div class="es-modal-foot">
                <button type="button" class="btn btn-outline" onclick="esCloseModal('editTopicModal')">Cancel</button>
                <button type="submit" name="edit_topic" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script src="../assets/js/app.js"></script>
<script>
function openEditTopic(id, name) {
    document.getElementById('editTopicId').value = id;
    document.getElementById('editTopicName').value = name;
    esOpenModal('editTopicModal');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
