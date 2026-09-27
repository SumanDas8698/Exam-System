<?php
require_once __DIR__ . '/../includes/config.php';
require_admin_login();
require_once __DIR__ . '/../includes/SimpleXLSX.php';

use Shuchkin\SimpleXLSX;

const Q_ID_PREFIX = 'Q';
const Q_ID_DIGITS  = 2;

// Find next question number
$result = $conn->query("
    SELECT MAX(CAST(SUBSTRING(id, 2) AS UNSIGNED)) as max_num
    FROM questions
    WHERE id LIKE '" . Q_ID_PREFIX . "%'
");
$row = $result->fetch_assoc();
$maxNum = (int)($row['max_num'] ?? 0);
$nextNumber = $maxNum + 1;
$nextSuggested = Q_ID_PREFIX . str_pad((string)$nextNumber, Q_ID_DIGITS, '0', STR_PAD_LEFT);

$faculty_names = $conn->query("SELECT name FROM faculty ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

$importResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    $fileTmp = $_FILES['excel_file']['tmp_name'];
    $ext = strtolower(pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION));

    if ($ext !== 'xlsx') {
        $importResult = ['status' => 'error', 'message' => 'Only .xlsx files are allowed.'];
    } else {
        try {
            if (!$xlsx = SimpleXLSX::parse($fileTmp)) {
                throw new Exception(SimpleXLSX::parseError());
            }

            $rows = $xlsx->rows(0);
            array_shift($rows); // remove header row

            $success = 0;
            $skipped = 0;
            $messages = [];
            $currentNext = $nextNumber;

            foreach ($rows as $index => $row) {
                $line = $index + 2;
                $cells = array_map(fn($v) => trim((string)$v), $row);

                if (count(array_filter($cells)) === 0) {
                    continue; // skip fully empty rows
                }

                $question_text = $cells[0] ?? '';
                $opt1          = $cells[1] ?? '';
                $opt2          = $cells[2] ?? '';
                $opt3          = $cells[3] ?? '';
                $opt4          = $cells[4] ?? '';
                $correct_opt   = strtoupper(trim($cells[5] ?? ''));
                $faculty_name  = trim($cells[6] ?? '');

                $validationErrors = [];
                if ($question_text === '') $validationErrors[] = "question missing";
                if ($opt1 === '')           $validationErrors[] = "opt1 missing";
                if ($opt2 === '')           $validationErrors[] = "opt2 missing";
                if ($opt3 === '')           $validationErrors[] = "opt3 missing";
                if ($opt4 === '')           $validationErrors[] = "opt4 missing";
                if (!in_array($correct_opt, ['A','B','C','D'], true)) {
                    $validationErrors[] = "correct_opt must be A/B/C/D";
                }
                if ($faculty_name === '') $validationErrors[] = "faculty name missing";

                if ($validationErrors) {
                    $skipped++;
                    $messages[] = "Line $line skipped: " . implode(", ", $validationErrors);
                    continue;
                }

                $stmt = $conn->prepare("SELECT id FROM faculty WHERE name = ? LIMIT 1");
                $stmt->bind_param("s", $faculty_name);
                $stmt->execute();
                $f_result = $stmt->get_result();

                if ($f_result->num_rows === 0) {
                    $skipped++;
                    $messages[] = "Line $line: Faculty name '$faculty_name' not found in database";
                    $stmt->close();
                    continue;
                }

                $f_id = $f_result->fetch_assoc()['id'];
                $stmt->close();

                $finalId = Q_ID_PREFIX . str_pad((string)$currentNext, Q_ID_DIGITS, '0', STR_PAD_LEFT);
                $currentNext++;

                $query = "INSERT INTO questions
                          (id, question_text, opt1, opt2, opt3, opt4, correct_opt, f_id)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                          ON DUPLICATE KEY UPDATE
                          question_text = VALUES(question_text), opt1 = VALUES(opt1),
                          opt2 = VALUES(opt2), opt3 = VALUES(opt3), opt4 = VALUES(opt4),
                          correct_opt = VALUES(correct_opt), f_id = VALUES(f_id)";
                $ins = $conn->prepare($query);
                $ins->bind_param("ssssssss", $finalId, $question_text, $opt1, $opt2, $opt3, $opt4, $correct_opt, $f_id);

                if ($ins->execute()) {
                    $success++;
                } else {
                    $skipped++;
                    $messages[] = "Line $line (ID: $finalId) database error: " . $ins->error;
                }
                $ins->close();
            }

            $importResult = ['status' => 'success', 'success' => $success, 'skipped' => $skipped, 'messages' => $messages];

        } catch (Exception $e) {
            $importResult = ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}

$page_title = "Bulk Upload Questions";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="es-shell">
    <div class="page-head">
        <div class="eyebrow">Question Bank</div>
        <h1>Bulk Upload Questions from Excel</h1>
        <p>Upload a 7-column .xlsx file to add or update many questions at once.</p>
    </div>

    <div class="es-card" style="max-width: 760px;">
        <p>Next automatic question ID: <code class="mono" style="font-size:1.1em;"><?= h($nextSuggested) ?></code></p>
        <p class="text-muted" style="font-size:0.9rem;">
            Last column must contain a <strong>faculty name</strong> that exactly matches one in the database:<br>
            <?= implode(', ', array_map(fn($f) => h($f['name']), $faculty_names)) ?>
        </p>

        <?php if ($importResult): ?>
            <?php if ($importResult['status'] === 'success'): ?>
                <div class="alert alert-success">
                    <span>
                        Import completed — added/updated <strong><?= $importResult['success'] ?></strong>,
                        skipped <strong><?= $importResult['skipped'] ?></strong>.
                    </span>
                </div>
                <?php if (!empty($importResult['messages'])): ?>
                    <details style="margin-bottom:18px;">
                        <summary>Show details (<?= count($importResult['messages']) ?> messages)</summary>
                        <pre style="background:var(--paper); padding:12px; border-radius:6px; font-size:0.85rem; white-space:pre-wrap;"><?= h(implode("\n", $importResult['messages'])) ?></pre>
                    </details>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-danger"><span><?= h($importResult['message']) ?></span></div>
            <?php endif; ?>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <div class="mb-3" style="margin-bottom:18px;">
                <label class="form-label">Select Excel File (.xlsx)</label>
                <input type="file" name="excel_file" accept=".xlsx" required class="form-control">
            </div>
            <button type="submit" class="btn btn-primary btn-lg">Upload &amp; Import</button>
        </form>
    </div>

    <div class="es-card" style="max-width: 760px;">
        <h3 style="margin-top:0;">Required Excel Format (7 columns — no ID column)</h3>
        <div class="es-table-wrap">
            <table class="es-table">
                <thead>
                    <tr>
                        <th>question_text</th><th>opt1</th><th>opt2</th><th>opt3</th>
                        <th>opt4</th><th>correct_opt (A/B/C/D)</th><th>faculty name</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Which shortcut key saves a document in MS Word?</td>
                        <td>Ctrl+P</td><td>Ctrl+D</td><td>Ctrl+S</td><td>Ctrl+M</td>
                        <td>C</td><td>SUMAN DAS</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
