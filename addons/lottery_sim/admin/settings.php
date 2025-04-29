<?php

/**
 * Universal Fairness Protocol (UFP)
 * Dual Licensed under GPLv3 and Commercial License
 * Author: DgtalFairness | https://universalfairnessprotocol.com
 */

require_once(__DIR__ . '/../../../db/database.php');
require_once(__DIR__ . '/../../../system/functions/functions.php');

// Admin check
$user_id = $_SESSION['user_id'] ?? null;
$user = $db->query("SELECT is_admin FROM users WHERE id = '" . (int)$user_id . "'")->fetch_assoc();
if (!$user_id || !$user || $user['is_admin'] != 1) {
    die("❌ Access Denied. Admins only.");
}

// Fetch lottery addon settings
$lottery_settings = [];
$result = $db->query("SELECT name, value FROM settings_lottery_addon");
while ($row = $result->fetch_assoc()) {
    $lottery_settings[$row['name']] = $row['value'];
}

// Defaults (used if values are missing)
$ticket_price = $lottery_settings['ticket_price'] ?? 11.00;
$ticket_fee   = $lottery_settings['ticket_fee'] ?? 22.00;
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">


<!-- Lottery Addon Settings Section (Injects into core admin page) -->
<div class="card mt-4 mx-auto" style="width: 95%;">
  <div class="card-header top-bar-header text-dark">
    🎟️ Lottery Addon Settings
  </div>
  <div class="card-body">
    <form method="POST" action="/addons/lottery_sim/admin/update_settings_lottery_addon.php">

      <div class="mb-3">
        <label for="ticket_price" class="form-label">Ticket Price:</label>
        <input type="number" name="ticket_price" step="0.01" class="form-control" value="<?= htmlspecialchars($ticket_price) ?>" required>
      </div>

      <div class="mb-3">
        <label for="ticket_fee" class="form-label">Ticket Fee:</label>
        <input type="number" name="ticket_fee" step="0.01" class="form-control" value="<?= htmlspecialchars($ticket_fee) ?>" required>
      </div>

      <button type="submit" class="btn btn-primary w-100">💾 Save Lottery Settings</button>
    </form>
  </div>
</div>
