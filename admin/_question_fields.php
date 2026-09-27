<?php
// _question_fields.php — shared form fields for add/edit question modals.
// Expects $faculties to be populated by the including page.
?>
<div class="mb-3" style="margin-bottom:14px;">
    <label class="form-label">Faculty</label>
    <select name="f_id" class="form-select" required>
        <option value="">-- Select Faculty --</option>
        <?php foreach ($faculties as $f): ?>
            <option value="<?= h($f['id']) ?>"><?= h($f['name']) ?></option>
        <?php endforeach; ?>
    </select>
</div>
<div class="mb-3" style="margin-bottom:14px;">
    <label class="form-label">Question Text</label>
    <textarea name="question_text" class="form-control" rows="2" required></textarea>
</div>
<div class="mb-3" style="margin-bottom:14px;">
    <label class="form-label">Option A</label>
    <input type="text" name="opt1" class="form-control" required>
</div>
<div class="mb-3" style="margin-bottom:14px;">
    <label class="form-label">Option B</label>
    <input type="text" name="opt2" class="form-control" required>
</div>
<div class="mb-3" style="margin-bottom:14px;">
    <label class="form-label">Option C</label>
    <input type="text" name="opt3" class="form-control" required>
</div>
<div class="mb-3" style="margin-bottom:14px;">
    <label class="form-label">Option D</label>
    <input type="text" name="opt4" class="form-control" required>
</div>
<div class="mb-3">
    <label class="form-label">Correct Option</label>
    <select name="correct_opt" class="form-select" required>
        <option value="">-- Select --</option>
        <option value="A">A</option>
        <option value="B">B</option>
        <option value="C">C</option>
        <option value="D">D</option>
    </select>
</div>
