<?php

/**
 * Universal Fairness Protocol (UFP)
 * Dual Licensed under GPLv3 and Commercial License
 * Author: DgtalFairness | https://universalfairnessprotocol.com
 */

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(["success" => false, "message" => "User not logged in"]);
    exit;
}

$stmt = $db->prepare("
    SELECT u.username, u.account_balance, 
           COUNT(t.ticket_id) AS total_tickets,
           (
               SELECT COUNT(*) 
               FROM winning_round_tickets 
               WHERE user_id = ?
           ) AS total_winning_rounds
    FROM users u
    LEFT JOIN current_round_tickets t ON u.id = t.user_id
    WHERE u.id = ?
");

$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();

if ($user_data) {
    echo json_encode([
        "success" => true,
        "username" => $user_data['username'],
        "balance" => number_format($user_data['account_balance'], 2),
        "tickets" => number_format($user_data['total_tickets']),
        "wins" => number_format($user_data['total_winning_rounds'])
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to fetch user data"]);
}

