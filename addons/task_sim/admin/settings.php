<?php

/**
 * Universal Fairness Protocol (UFP)
 * Dual Licensed under GPLv3 and Commercial License
 * Author: DgtalFairness | https://universalfairnessprotocol.com
 */

// /addons/task_sim/admin/settings.php
require_once(__DIR__ . '/../../../db/database.php');
require_once(__DIR__ . '/../../../system/functions/functions.php');

// Safe session start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? null;
$user = $db->query("SELECT is_admin FROM users WHERE id = '" . (int)$user_id . "'")->fetch_assoc();
if (!$user_id || !$user || $user['is_admin'] != 1) {
    die("❌ Access Denied. Admins only.");
}

// Fetch task addon settings
$settings = [];
$result = $db->query("SELECT name, value FROM settings_task_addon");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['name']] = $row['value'];
    }
}

// Defaults
$max_pending = $settings['max_pending_per_user'] ?? 3;
$auto_approve = $settings['auto_approve_tasks'] ?? 0;
$reset_time = $settings['reset_approved_jobs'] ?? 1440;
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

<div class="card mt-4 mx-auto" style="width: 95%;">
  <div class="card-header top-bar-header text-dark">
    📅 Task Addon Settings
  </div>
  <div class="card-body">
    <form method="POST" action="/addons/task_sim/admin/update_settings_task_addon.php">
      <div class="mb-3">
        <label for="max_pending_per_user" class="form-label">Max Pending Submissions Per User:</label>
        <input type="number" name="max_pending_per_user" id="max_pending_per_user" class="form-control" value="<?= htmlspecialchars($max_pending) ?>" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Auto Approve Tasks?</label>
        <select name="auto_approve_tasks" class="form-control">
          <option value="0" <?= $auto_approve == 0 ? 'selected' : '' ?>>No (manual approval)</option>
          <option value="1" <?= $auto_approve == 1 ? 'selected' : '' ?>>Yes (approve automatically)</option>
        </select>
      </div>

      <div class="mb-3">
        <label for="reset_approved_jobs" class="form-label">Reset Approved Jobs After (minutes):</label>
        <input type="number" name="reset_approved_jobs" id="reset_approved_jobs" class="form-control" value="<?= htmlspecialchars($reset_time) ?>" required>
        <small class="form-text text-muted">After this time, completed jobs will be moved to the archive and users can repeat them.</small>
      </div>

      <button type="submit" class="btn btn-primary w-100">📅 Save Task Addon Settings</button>
    </form>
  </div>
</div>

<!-- Task Addon Tabs Section -->
<div class="card mt-4 mx-auto" style="width: 95%;">
  <div class="card-header top-bar-header text-dark">
    🧩 Manage Task Addon
  </div>
  <div class="card-body">
    <!-- Nav Tabs -->
    <ul class="nav nav-tabs" id="taskAddonTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="create-tab" data-bs-toggle="tab" data-bs-target="#create" type="button" role="tab">➕ Create Tasks</button>
      </li>
      <li class="nav-item" role="presentation">
          <button class="nav-link" id="manage-tab" data-bs-toggle="tab" data-bs-target="#manage" type="button" role="tab">🛠 Manage Tasks</button>
        </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab">⏳ Pending Submissions</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="completed-tab" data-bs-toggle="tab" data-bs-target="#completed" type="button" role="tab">✅ Completed Tasks</button>
      </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content mt-3" id="taskAddonTabsContent">
      <div class="tab-pane fade show active" id="create" role="tabpanel">
        <!-- 🔧 Enhanced Task Creation Form -->
            <form method="POST" action="/addons/task_sim/admin/create_task.php">
              <div class="mb-3">
                <label for="title" class="form-label">Task Title:</label>
                <input type="text" name="title" id="title" class="form-control" required>
              </div>
            
              <div class="mb-3">
                <label for="description" class="form-label">Task Instructions (HTML Allowed):</label>
                <textarea name="description" id="description" rows="4" class="form-control" required></textarea>
              </div>
            
              <div class="mb-3">
                <label for="input_type" class="form-label">Submission Field Type:</label>
                <select name="input_type" id="input_type" class="form-control">
                  <option value="text" selected>Text</option>
                  <option value="url">URL</option>
                  <option value="email">Email</option>
                </select>
                <small class="form-text text-muted">Used to validate user input.</small>
              </div>
            
              <div class="mb-3">
                <label for="input_name" class="form-label">Submission Input Name (Internal):</label>
                <input type="text" name="input_name" id="input_name" class="form-control" required>
                <small class="form-text text-muted">This is used in the database and forms (e.g., "twitter_url").</small>
              </div>
            
              <div class="mb-3">
                <label for="instructions" class="form-label">User-Facing Instructions:</label>
                <input type="text" name="instructions" id="instructions" class="form-control" required>
                <small class="form-text text-muted">e.g., "Paste your tweet link" or "Enter your Reddit username".</small>
              </div>
            
              <div class="mb-3">
                <label for="example_format" class="form-label">Example / Screenshot URL (Optional):</label>
                <input type="url" name="example_format" id="example_format" class="form-control">
              </div>
            
              <div class="mb-3">
                <label for="is_active" class="form-label">Task Status:</label>
                <select name="is_active" id="is_active" class="form-control">
                  <option value="1" selected>✅ Active</option>
                  <option value="0">🚫 Inactive</option>
                </select>
              </div>
              <button type="submit" class="btn btn-success w-100">➕ Create Task</button>
            </form>

      </div>

      <div class="tab-pane fade" id="pending" role="tabpanel">
        <?php
        $pending = $db->query("
            SELECT p.id, p.task_id, p.user_id, p.submission_data, p.submitted_at, j.title AS job_title
            FROM task_addon_pending p
            JOIN task_addon_jobs j ON p.task_id = j.task_id
            ORDER BY p.submitted_at DESC

        ");
        ?>
        <?php if ($pending && $pending->num_rows > 0): ?>
          <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
              <thead class="table-dark">
                <tr>
                  <th>ID</th>
                  <th>Task</th>
                  <th>User ID</th>
                  <th>Submission</th>
                  <th>Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($row = $pending->fetch_assoc()): ?>
                  <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['job_title']) ?></td>
                    <td><?= $row['user_id'] ?></td>
                    <td><?= htmlspecialchars($row['submission_data']) ?></td>
                    <td><?= date('Y-m-d H:i:s', (int)$row['submitted_at']) ?></td>

                    <td>
                      <div class="d-flex gap-2">
                          <button class="btn btn-success btn-sm task-approve-btn"
                            data-id="<?= $row['id'] ?>"
                            data-task="<?= $row['task_id'] ?>"
                            data-user="<?= $row['user_id'] ?>">✅ Approve</button>
                        
                          <button class="btn btn-danger btn-sm task-reject-btn"
                            data-id="<?= $row['id'] ?>"
                            data-task="<?= $row['task_id'] ?>"
                            data-user="<?= $row['user_id'] ?>">❌ Reject</button>
                        </div>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="alert alert-info">There are no pending task submissions at the moment.</div>
        <?php endif; ?>
      </div>

      <div class="tab-pane fade" id="completed" role="tabpanel">
        <?php
        $completed = $db->query("
            SELECT d.id, d.task_id, d.user_id, d.submission_text, d.submission_date, j.job_title
            FROM task_addon_done d
            JOIN task_addon_jobs j ON d.task_id = j.task_id
            ORDER BY d.submission_date DESC
        ");
        ?>
        <?php if ($completed && $completed->num_rows > 0): ?>
          <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
              <thead class="table-dark">
                <tr>
                  <th>ID</th>
                  <th>Task</th>
                  <th>User ID</th>
                  <th>Submission</th>
                  <th>Completed At</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($row = $completed->fetch_assoc()): ?>
                  <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['job_title']) ?></td>
                    <td><?= $row['user_id'] ?></td>
                    <td><?= htmlspecialchars($row['submission_text']) ?></td>
                    <td><?= date('Y-m-d H:i:s', (int)$row['submission_date']) ?></td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="alert alert-info">✅ No completed tasks yet. As users finish jobs, they will show up here.</div>
        <?php endif; ?>
      </div>
      
      <div class="tab-pane fade" id="manage" role="tabpanel">
          <?php
          $jobs = $db->query("SELECT * FROM task_addon_jobs ORDER BY task_id DESC");
          ?>
          
          <?php if ($jobs && $jobs->num_rows > 0): ?>
            <div class="table-responsive">
              <table class="table table-striped table-bordered align-middle">
                <thead class="table-dark">
                  <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Group</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($row = $jobs->fetch_assoc()): ?>
                    <tr>
                      <td><?= $row['task_id'] ?></td>
                      <td><?= htmlspecialchars($row['title']) ?></td>
                      <td><?= htmlspecialchars($row['task_group'] ?? '-') ?></td>
                      <td><?= $row['is_active'] ? '✅ Active' : '🚫 Inactive' ?></td>
                      <td><?= date('Y-m-d', $row['created_at']) ?></td>
                      <td>
                        <form method="POST" action="/addons/task_sim/admin/manage_task.php" class="d-flex gap-2">
                          <input type="hidden" name="task_id" value="<?= $row['task_id'] ?>">
                          
                          <button type="submit" name="action" value="toggle" class="btn btn-warning btn-sm">
                            <?= $row['is_active'] ? '🔒 Deactivate' : '✅ Activate' ?>
                          </button>
                          
                          <button type="submit" name="action" value="delete" class="btn btn-danger btn-sm" onclick="return confirm('Delete this task?');">🗑 Delete</button>
                        </form>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="alert alert-info">📭 No tasks have been created yet.</div>
          <?php endif; ?>
        </div>
    </div>
  </div>
</div>

<!-- Enable Bootstrap tab behavior -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
  function sendTaskAction(type, id, taskId, userId, button) {
    if (!confirm(`Are you sure you want to ${type} this submission?`)) return;

    const formData = new FormData();
    formData.append("action", type);
    formData.append("submission_id", id);
    formData.append("task_id", taskId);
    formData.append("user_id", userId);

    fetch("/addons/task_sim/admin/manage_task.php", {
      method: "POST",
      body: formData,
      credentials: "same-origin"
    })
    .then(res => res.text())
    .then(html => {
      const match = html.match(/postMessage\((\{.*?\})\s*,\s*['"][^'"]*['"]\)/s);
      if (match) {
        try {
          const data = JSON.parse(match[1]);
          window.parent.postMessage(data, "*");
        } catch (e) {
          // Silently ignore parse errors
        }
      }

      const row = button.closest("tr");
      if (row) row.remove();
    })
    .catch(() => {
      alert("❌ Failed to process task.");
    });
  }

  document.querySelectorAll(".task-approve-btn").forEach(btn => {
    btn.addEventListener("click", () => {
      sendTaskAction("approve", btn.dataset.id, btn.dataset.task, btn.dataset.user, btn);
    });
  });

  document.querySelectorAll(".task-reject-btn").forEach(btn => {
    btn.addEventListener("click", () => {
      sendTaskAction("reject", btn.dataset.id, btn.dataset.task, btn.dataset.user, btn);
    });
  });
});
</script>

