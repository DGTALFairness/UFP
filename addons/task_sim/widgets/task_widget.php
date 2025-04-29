<?php

/**
 * Universal Fairness Protocol (UFP)
 * Dual Licensed under GPLv3 and Commercial License
 * Author: DgtalFairness | https://universalfairnessprotocol.com
 */

require_once(__DIR__ . '/../../../db/database.php');
require_once(__DIR__ . '/../../../system/functions/functions.php');
require_once(__DIR__ . '/../system/functions/functions.php');


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo '<div class="alert alert-danger">🚫 Please log in to access tasks.</div>';
    return;
}

// Get settings
$settings = [];
$res = $db->query("SELECT name, value FROM settings_task_addon");
if (!$res) {
    echo '<div class="alert alert-danger">❌ Failed to load settings: ' . $db->error . '</div>';
    return;
}
while ($row = $res->fetch_assoc()) {
    $settings[$row['name']] = $row['value'];
}

$max_pending = (int)($settings['max_pending_per_user'] ?? 3);
$reset_minutes = (int)($settings['reset_approved_jobs'] ?? 1440);
$reset_seconds = $reset_minutes * 60;

// Completed tasks
$completed_tasks = [];
$done_query = $db->query("SELECT task_id, approved_at FROM task_addon_done WHERE user_id = '$user_id'");
if (!$done_query) {
    echo '<div class="alert alert-danger">❌ Failed to load completed tasks: ' . $db->error . '</div>';
    return;
}
while ($row = $done_query->fetch_assoc()) {
    $completed_tasks[(int)$row['task_id']] = (int)$row['approved_at'];
}


// Pending count
$pending_result = $db->query("SELECT COUNT(*) as cnt FROM task_addon_pending WHERE user_id = '$user_id'");
if (!$pending_result) {
    echo '<div class="alert alert-danger">❌ Failed to load pending tasks: ' . $db->error . '</div>';
    return;
}
$pending_count = $pending_result->fetch_assoc()['cnt'] ?? 0;

// Fetch tasks
$tasks = $db->query("SELECT * FROM task_addon_jobs WHERE is_active = 1 ORDER BY task_id DESC");

if (!$tasks) {
    echo '<div class="alert alert-danger">❌ Failed to load tasks: ' . $db->error . '</div>';
    return;
}
?>

<!-- Task Widget -->
<div class="card shadow-sm mb-4">
    <div class="card-header top-bar-header text-white text-center">
        🧩 Complete tasks to Earn Tickets
    </div>
    <div class="card-body bg-light-gray">
        <?php if (!$tasks || $tasks->num_rows === 0): ?>
            <div class="alert alert-warning">📭 No tasks are available at the moment. Check back later.</div>
        <?php else: ?>
            <?php while ($task = $tasks->fetch_assoc()): 
                $task_id     = $task['task_id'];
                $title       = htmlspecialchars($task['title']);
                $desc        = $task['description'];
                $instructions = htmlspecialchars($task['instructions']);
                $group       = htmlspecialchars($task['input_name'] ?? '');
                $example     = htmlspecialchars($task['example_format']);

                $disabled = false;
                $reset_time_remaining = 0;

                if (isset($completed_tasks[$task_id])) {
                    $completed_at = $completed_tasks[$task_id];
                    if (time() < ($completed_at + $reset_seconds)) {
                        $disabled = true;
                        $reset_time_remaining = ($completed_at + $reset_seconds) - time();
                    }
                }
            ?>
                <div class="card mb-4">
                    <div class="card-header top-bar-header text-white d-flex justify-content-between">
                        <b>📌 <?= $title ?></b>
                        <?php if (!empty($group)): ?>
                            <span class="badge bg-info"><?= $group ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="mb-3"><?= $desc ?></div>
                        <?php if (!empty($example)): ?>
                            <div class="mb-3"><b>🖼 Example Screenshot:</b> <a href="<?= $example ?>" target="_blank"><?= $example ?></a></div>
                        <?php endif; ?>

                        <?php
                        // Step 1: Get current round
                        $current_round_id = 0;
                        $round_check = $db->query("SELECT round_id FROM rounds WHERE closed = 0 ORDER BY round_id DESC LIMIT 1");
                        if ($round_check && $round_check->num_rows > 0) {
                            $current_round_id = (int)$round_check->fetch_assoc()['round_id'];
                        }
                        
                        // Step 2: Count all pending tasks for this round
                        $pending_query = $db->query("
                            SELECT submitted_at 
                            FROM task_addon_pending 
                            WHERE user_id = '$user_id' 
                              AND round_id = '$current_round_id'
                            ORDER BY submitted_at DESC
                        ");
                        
                        $pending_for_round = $pending_query->num_rows;
                        $latest_submission_time = $pending_for_round > 0 ? (int)$pending_query->fetch_assoc()['submitted_at'] : 0;
                        $cooldown_passed = (time() - $latest_submission_time) >= $reset_seconds;
                        
                        // Step 3: Evaluate flags
                        $can_submit_task = $pending_for_round < $max_pending && $cooldown_passed;
                        $pending_limit_hit = $pending_for_round >= $max_pending;
                        $on_cooldown = !$cooldown_passed && !$pending_limit_hit;

                        ?>
                            
                            <form method="POST" action="/system/ajax.php" class="task-submit-form" data-task-id="<?= $task_id ?>">
                                <input type="hidden" name="action" value="submit_task_entry">
                                <input type="hidden" name="task_id" value="<?= $task_id ?>">
                                <div class="mb-2">
                                    <label><b><?= $instructions ?></b></label>
                                    <input type="text" name="submission_text" class="form-control submission-input" required>
                                </div>
                            
                                <!-- Message box for user -->
                                <div class="task-submit-message mt-2">
                                    <?php if ($disabled): ?>
                                    <div class="alert alert-warning">✅ You have already completed this task. Please wait for reset if allowed.</div>
                                <?php elseif ($pending_limit_hit): ?>
                                    <div class="alert alert-danger">🚫 Max pending submissions for this round reached.</div>
                                <?php elseif ($on_cooldown): ?>
                                    <div class="alert alert-info">⏳ Please wait before submitting again. Cooldown in effect.</div>
                                <?php endif; ?>
                                </div>
                            
                                <?php if ($disabled): 
                                    $mins = ceil($reset_time_remaining / 60); ?>
                                    <button type="submit" class="btn btn-secondary w-100" disabled>
                                        ⏳ Task Completed — Available again in <?= $mins ?> min
                                    </button>
                                        <?php elseif ($pending_limit_hit): ?>
                                    <button type="submit" class="btn btn-danger w-100" disabled>
                                        🚫 Max pending tasks for this round reached
                                    </button>
                                <?php elseif ($on_cooldown): ?>
                                    <button type="submit" class="btn btn-secondary w-100" disabled>
                                        ⏳ Cooldown active — please wait
                                    </button>
                                <?php else: ?>
                                    <button type="submit" class="btn btn-warning w-100">
                                        🚀 Submit Task
                                    </button>
                                <?php endif; ?>
                            </form>

                    </div>
                </div>
                
                <hr class="my-3">
                    <div class="your-submissions-box mt-3">
                      <h6 class="text-muted text-center">📄 Your Submissions</h6>
                      <?php
                        $history = $db->query("
                            SELECT 'pending' AS status, p.submission_data, p.submitted_at, NULL AS approved_at,
                                   j.task_id, j.title, j.description, p.round_id
                            FROM task_addon_pending p
                            JOIN task_addon_jobs j ON p.task_id = j.task_id
                            WHERE p.user_id = '$user_id' AND p.task_id = '$task_id'
                            
                            UNION
                        
                            SELECT 'approved' AS status, d.submission_data, d.submitted_at, d.approved_at,
                                   j.task_id, j.title, j.description, d.round_id
                            FROM task_addon_done d
                            JOIN task_addon_jobs j ON d.task_id = j.task_id
                            WHERE d.user_id = '$user_id' AND d.task_id = '$task_id'
                            
                            UNION
                        
                            SELECT 'missed' AS status, m.submission_data, m.submitted_at, NULL AS approved_at,
                                   j.task_id, j.title, j.description, m.round_id
                            FROM task_addon_missed m
                            JOIN task_addon_jobs j ON m.task_id = j.task_id
                            WHERE m.user_id = '$user_id' AND m.task_id = '$task_id'
                        
                            ORDER BY submitted_at DESC
                            LIMIT 5
                        ");


                        if ($history && $history->num_rows > 0):
                      ?>
                        <ul class="list-group">
                          <?php while ($entry = $history->fetch_assoc()): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                              <div class="d-flex flex-column">
                                <div><b>Round ID #<?= (int)$entry['round_id'] ?> - Task ID #<?= (int)$entry['task_id'] ?>:</b> <?= htmlspecialchars($entry['title']) ?></div>
                                <div class="mb-1"><?= htmlspecialchars($entry['description']) ?></div>
                                <small class="text-muted">
                                  <b>Submitted:</b> (<?= htmlspecialchars($entry['submission_data']) ?>) at <?= date('Y-m-d H:i', $entry['submitted_at']) ?>
                                  <?php if ($entry['approved_at']): ?>
                                    | <b>Approved:</b> at <?= date('Y-m-d H:i', $entry['approved_at']) ?>
                                  <?php endif; ?>
                                </small>
                              </div>
                              <span class="badge bg-<?= 
                              $entry['status'] === 'approved' ? 'success' : 
                              ($entry['status'] === 'pending' ? 'warning' : 'secondary') ?> ms-3 align-self-center">
                              <?= $entry['status'] === 'missed' ? 'Missed ⏱' : ucfirst($entry['status']) ?>
                            </span>

                            </li>

                          <?php endwhile; ?>
                        </ul>
                      <?php else: ?>
                        <div class="text-muted text-center">No submissions yet.</div>
                      <?php endif; ?>
                    </div>


                
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</div>

<script src="/addons/task_sim/assets/js/task.js"></script>