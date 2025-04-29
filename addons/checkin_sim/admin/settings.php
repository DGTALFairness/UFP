<?php

/**
 * Universal Fairness Protocol (UFP)
 * Dual Licensed under GPLv3 and Commercial License
 * Author: DgtalFairness | https://universalfairnessprotocol.com
 */

// Use absolute paths instead of relative
require_once(__DIR__ . '/../../../db/database.php');
require_once(__DIR__ . '/../../../system/functions/functions.php');

// Admin check (optional but still safe if needed)
$user_id = $_SESSION['user_id'] ?? null;
$user = $db->query("SELECT is_admin FROM users WHERE id = '" . (int)$user_id . "'")->fetch_assoc();
if (!$user_id || !$user || $user['is_admin'] != 1) {
    die("❌ Access Denied. Admins only.");
}

// Fetch Addon Settings
$addon_settings = [];
$result = $db->query("SELECT name, value FROM settings_checkin_addon");
while ($row = $result->fetch_assoc()) {
    $addon_settings[$row['name']] = $row['value'];
}

$checkin_lapse_time = $addon_settings['checkin_lapse_time'] ?? 1440;
$checkin_bonus_amount = $addon_settings['checkin_bonus_amount'] ?? 1.00;
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">


<!-- Addon Settings Section (Injects into core admin page) -->
<div class="card mt-4 mx-auto" style="width: 95%;">

  <div class="card-header top-bar-header text-dark">
    ⏱ Check-In Addon Settings
  </div>
  <div class="card-body">
    <form method="POST" action="/addons/checkin_sim/admin/update_settings_checkin_addon.php">
      <div class="mb-3">
        <label for="checkin_lapse_time" class="form-label">Cooldown Between Check-ins (minutes):</label>
        <input type="number" name="checkin_lapse_time" id="checkin_lapse_time" class="form-control" value="<?= htmlspecialchars($checkin_lapse_time) ?>" required>
      </div>

      <button type="submit" class="btn btn-primary w-100">💾 Save Check-In Settings</button>
    </form>
  </div>
</div>
