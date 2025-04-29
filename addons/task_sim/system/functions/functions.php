<?php

/**
 * Universal Fairness Protocol (UFP)
 * Dual Licensed under GPLv3 and Commercial License
 * Author: DgtalFairness | https://universalfairnessprotocol.com
 */

// 🔒 Safe JSON response helper for the Task Addon
function task_addon_safe_json_response($array) {
    header('Content-Type: application/json');
    echo json_encode($array);
    exit;
}
