<?php

/**
 * Universal Fairness Protocol (UFP)
 * Dual Licensed under GPLv3 and Commercial License
 * Author: DgtalFairness | https://universalfairnessprotocol.com
 */

require_once(__DIR__ . '/../../../db/database.php');
require_once(__DIR__ . '/../../../system/functions/functions.php');

$user_id = $_SESSION['user_id'] ?? null;
$user = $db->query("SELECT is_admin FROM users WHERE id = '" . (int)$user_id . "'")->fetch_assoc();
if (!$user_id || !$user || $user['is_admin'] != 1) {
    die("❌ Access Denied. Admins only.");
}

$ticket_price = isset($_POST['ticket_price']) ? floatval($_POST['ticket_price']) : null;
$ticket_fee   = isset($_POST['ticket_fee']) ? floatval($_POST['ticket_fee']) : null;

if ($ticket_price === null || $ticket_fee === null) {
    die("❌ Missing fields.");
}

// Save or update in `settings_lottery_addon`
$db->query("REPLACE INTO settings_lottery_addon (name, value) VALUES 
    ('ticket_price', '{$ticket_price}'),
    ('ticket_fee', '{$ticket_fee}')
");

// Post message back to parent (iframe) and redirect within iframe
echo "<script>
    parent.postMessage({ type: 'addon_save_success', message: '✅ Lottery settings updated successfully!' }, '*');
    window.location.href = 'settings.php';
</script>";
exit;
