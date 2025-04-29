<?php

/**
 * Universal Fairness Protocol (UFP)
 * Dual Licensed under GPLv3 and Commercial License
 * Author: DgtalFairness | https://universalfairnessprotocol.com
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(0, "/");
    session_start();
}

// Ensure JSON output even for errors
function safe_json_response($data) {
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    echo json_encode($data);
    exit;
}

// Include database connection & functions
require_once(__DIR__ . '/../db/database.php');
require_once(__DIR__ . '/../system/functions/functions.php');

// Retrieve user session data
$user_id = $_SESSION['user_id'] ?? null;

// Ensure request method is POST (to prevent direct access)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    safe_json_response(['status' => 400, 'message' => 'Invalid request method']);
}

// Validate request action
$action = isset($_POST['action']) ? trim($_POST['action']) : null;

if (!$action) {
    ob_clean();
    safe_json_response(['status' => 400, 'message' => 'No action specified']);
}

// Main switch for handling AJAX actions
switch ($action) {
   case 'register':
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($username) || empty($password) || empty($confirm_password)) {
        safe_json_response(['status' => 'error', 'message' => 'All fields are required.']);
    }

    if ($password !== $confirm_password) {
        safe_json_response(['status' => 'error', 'message' => 'Passwords do not match.']);
    }

    // Call the function to register the user
    $result = registerUser($username, $password);

    // Add initial balance to the new user
    if ($result['status'] === 'success') {
        // Get user_id from the registration response
        $user_id = $result['user_id'];
        // Set an initial balance of 100
        $add_balance_query = $db->prepare("UPDATE users SET account_balance = account_balance + 100 WHERE id = ?");
        $add_balance_query->bind_param('i', $user_id);
        $add_balance_query->execute();
    }

    safe_json_response($result);
    break;

    case 'login':
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);

        if (empty($username) || empty($password)) {
            safe_json_response(['status' => 'error', 'message' => 'All fields are required.']);
        }

        // Call the function to log the user in
        $result = loginUser($username, $password);
        safe_json_response($result);
        break;

   // Case: Fetch the main ticket list and any associated rehash chain
    case 'fetch_ticket_list':
        $round_id = $_POST['lottery_id'] ?? null;
        if (!$round_id || !is_numeric($round_id)) {
            safe_json_response(['status' => 400, 'message' => 'Invalid round ID']);
        }
    
        // Query the database to get round data
        $roundData = $db->query("SELECT ticket_list, hash, rehash_chain, segment_chain FROM `fairness_ticket_logs` WHERE `round_id`='" . (int)$round_id . "' LIMIT 1")->fetch_assoc();
    
        // Check if data is found, if not return a 404 error
        if (!$roundData) {
            safe_json_response(['status' => 404, 'message' => 'No data found for this round']);
        }
    
        // Ensure segment_chain is properly formatted (use a fallback in case of invalid JSON)
        $segment_chain = !empty($roundData['segment_chain']) ? json_decode($roundData['segment_chain'], true) : [];
    
        // Check if decoding the segment chain was successful
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Set $segment_chain to an empty array if decoding fails
            $segment_chain = [];
        }
    
        // Similarly decode rehash_chain (using a fallback if decoding fails)
        $rehash_chain = !empty($roundData['rehash_chain']) ? json_decode($roundData['rehash_chain'], true) : [];
    
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Set $rehash_chain to an empty array if decoding fails
            $rehash_chain = [];
        }
    
        // Return the response with ticket data, hash, rehash_chain, and segment_chain
        safe_json_response([
            'status' => 200,
            'ticket_list' => $roundData['ticket_list'],
            'ticket_hash' => $roundData['hash'],
            'rehash_chain' => $rehash_chain,
            'segment_chain' => $segment_chain
        ]);
    break;

    // Case: Fetch rehashed ticket list by `hash`
       case 'fetch_rehash_data':
        $rehash_key = $_POST['rehash_key'] ?? null;
        if (!$rehash_key) {
            safe_json_response(['status' => 400, 'message' => 'Rehash key not provided']);
        }
    
        // Find the round_id that contains this rehash key inside `rehash_chain`
        $roundQuery = $db->query("
            SELECT round_id, ticket_list, rehash_chain, segment_chain
            FROM fairness_ticket_logs
            WHERE JSON_CONTAINS(rehash_chain, '\"$rehash_key\"')
            LIMIT 1
        ");
    
        if (!$roundQuery || $roundQuery->num_rows === 0) {
            safe_json_response(['status' => 404, 'message' => "❌ No ticket data found for rehash key: $rehash_key"]);
        }
    
        $roundData = $roundQuery->fetch_assoc();
        $original_ticket_list = $roundData['ticket_list'];
    
        // Ensure segment_chain is properly formatted
        $segment_chain = !empty($roundData['segment_chain']) ? json_decode($roundData['segment_chain'], true) : [];
    
        // Return the ticket list and the rehash key as the new hash
        safe_json_response([
            'status' => 200,
            'ticket_list' => $original_ticket_list,
            'ticket_hash' => $rehash_key,
            'segment_chain' => $segment_chain
        ]);
        break;

        case 'fetch_highlight_segments':
        $round_id = $_POST['lottery_id'] ?? null;
        $selected_tier = $_POST['selected_tier'] ?? null;
    
        if (!$round_id || !is_numeric($round_id) || !$selected_tier || !is_numeric($selected_tier)) {
            safe_json_response(['status' => 400, 'message' => 'Invalid round ID or tier']);
        }
    
        $roundData = $db->query("
            SELECT segment_chain 
            FROM `fairness_ticket_logs` 
            WHERE `round_id`='" . (int)$round_id . "' 
            LIMIT 1
        ")->fetch_assoc();
    
        if (!$roundData) {
            safe_json_response(['status' => 404, 'message' => 'No segment data found for this round']);
        }
    
        // Decode the stored segment chain
        $segment_chain = !empty($roundData['segment_chain']) ? json_decode($roundData['segment_chain'], true) : [];
    
        // Filter only the segments that belong to the selected tier
        $filtered_segments = array_filter($segment_chain, function ($segment) use ($selected_tier) {
            return $segment['tier'] == $selected_tier;
        });
    
        safe_json_response([
            'status' => 200,
            'highlight_segments' => array_values($filtered_segments)
        ]);
        break;

    case 'fetch_ticket_stats':
    $active_round_query = $db->query("SELECT round_id FROM rounds WHERE closed = 0 ORDER BY round_id DESC LIMIT 1");
    if (!$active_round_query || $active_round_query->num_rows == 0) {
        safe_json_response(['status' => 404, 'message' => 'No active round found']);
    }

    $round_id = (int) $active_round_query->fetch_assoc()['round_id'];

    $stats_query = $db->query("
        SELECT u.id AS user_id, u.username, COUNT(t.ticket_id) AS total_tickets
        FROM current_round_tickets t
        INNER JOIN users u ON u.id = t.user_id
        WHERE t.round_id = '$round_id'
        GROUP BY u.id
        ORDER BY total_tickets DESC
    ");

    $stats = [];
    while ($row = $stats_query->fetch_assoc()) {
        $stats[] = $row;
    }

    safe_json_response([
        'status' => 200,
        'round_id' => $round_id,
        'stats' => $stats
    ]);
    break;

    case 'fetch_round_status':
    $row = $db->query("SELECT MAX(round_id) AS round_id, MAX(expected_end_date) AS expected_end_date, MIN(date) AS date FROM rounds WHERE closed = 0")->fetch_assoc();

    if ($row) {
        safe_json_response([
            "status" => 200,
            "round_id" => (int)$row['round_id'],
            "expected_end_date" => (int)$row['expected_end_date'],
            "date" => (int)$row['date']
        ]);
    } else {
        safe_json_response(["status" => 404]);
    }
    break;

   // Case: Fetch live ticket list (before round ends)
    case 'fetch_live_ticket_list':
    // Fetch the active round ID
    $active_round_query = $db->query("SELECT round_id FROM `rounds` WHERE `closed`='0' ORDER BY `round_id` DESC LIMIT 1");

    if ($active_round_query->num_rows == 0) {
        safe_json_response(['status' => 404, 'message' => 'No active round found']);
    }

    $active_round = $active_round_query->fetch_assoc();
    $round_id = (int) $active_round['round_id'];

    // SUM the `prize` across all positions for the active round
    $prizeQuery = $db->query("SELECT SUM(prize) AS total_prize FROM `rounds` WHERE `round_id` = '$round_id'");
    $prizeData = $prizeQuery->fetch_assoc();
    $totalPrize = number_format((float) $prizeData['total_prize'], 2);

    // Count total tickets from current_round_tickets
    $ticketCountQuery = $db->query("SELECT COUNT(*) AS ticket_count FROM `current_round_tickets` WHERE `round_id` = '$round_id'");
    $ticketCountData = $ticketCountQuery->fetch_assoc();
    $totalTickets = (int) $ticketCountData['ticket_count'];

    // Fetch all tickets for this round
    $ticketList = "";
    $ticketQuery = $db->query("SELECT ticket_id, user_id FROM `current_round_tickets` WHERE `round_id`='$round_id' ORDER BY ticket_id ASC");

    while ($ticket = $ticketQuery->fetch_assoc()) {
        $ticketList .= "User-ID-" . $ticket['user_id'] . ":" . getTicketMiddleText() . "-" . $ticket['ticket_id'] . ";";
    }

    safe_json_response([
        'status' => 200,
        'prize' => $totalPrize,
        'tickets_sold' => $totalTickets,
        'round_id' => $round_id,
        'ticket_list' => !empty($ticketList) ? $ticketList : "No tickets purchased yet."
    ]);


    break;
    
   case 'fetch_prize_breakdown':
    $active_round_query = $db->query("SELECT round_id FROM `rounds` WHERE `closed` = '0' ORDER BY `round_id` DESC LIMIT 1");

    if ($active_round_query->num_rows == 0) {
        safe_json_response(['status' => 404, 'message' => 'No active round found']);
    }

    $active_round = $active_round_query->fetch_assoc();
    $round_id = (int) $active_round['round_id'];

    // Fetch all positions & their prize amounts for this round
    $positions_query = $db->query("
        SELECT position, prize 
        FROM rounds 
        WHERE round_id = '{$round_id}' 
        ORDER BY position ASC
    ");

    $prizes = [];
    $total_prize = 0;

    while ($position = $positions_query->fetch_assoc()) {
        $prizes[] = [
            'position' => (int)$position['position'],
            'prize' => (float)$position['prize']
        ];
        $total_prize += (float) ($position['prize'] ?? 0);
    }

    // Fetch round-specific reward mode
    $reward_meta_query = $db->query("SELECT use_custom_rewards FROM rounds WHERE round_id = '{$round_id}' LIMIT 1");
    $use_custom_rewards = 0;

    if ($reward_meta_query->num_rows > 0) {
        $use_custom_rewards = (int) $reward_meta_query->fetch_assoc()['use_custom_rewards'];
    }

    $reward_labels = json_decode(getSetting('reward_labels') ?? '[]', true);

    // Final response
    safe_json_response([
        'status' => 200,
        'prizes' => $prizes,
        'total_prize' => number_format($total_prize, 2),
        'round_id' => $round_id,
        'reward_config' => [
            'prefix_or_suffix'   => (int) getSetting('prefix_or_suffix'),
            'reward_prefix'      => getSetting('reward_prefix'),
            'reward_suffix'      => getSetting('reward_suffix'),
            'use_custom_rewards' => $use_custom_rewards,
            'reward_labels'      => $reward_labels
        ]
    ]);
    break;

   case 'fetch_sync_snapshot':
    // Fetch all required sync data at once
    $round = $db->query("SELECT MAX(round_id) AS round_id, MAX(expected_end_date) AS expected_end_date, MIN(date) AS date FROM rounds WHERE closed = 0")->fetch_assoc();

    if (!$round || !$round['round_id']) {
        safe_json_response(['status' => 404, 'message' => 'No active round found']);
    }

    $round_id = (int) $round['round_id'];
    $expected_end_date = (int) $round['expected_end_date'];
    $start_date = (int) $round['date'];

    // Ticket stats (no cost info)
    $stats_query = $db->query("
        SELECT u.id AS user_id, u.username, COUNT(t.ticket_id) AS total_tickets
        FROM current_round_tickets t
        INNER JOIN users u ON u.id = t.user_id
        WHERE t.round_id = '$round_id'
        GROUP BY u.id
        ORDER BY total_tickets DESC
    ");
    $ticket_stats = [];
    while ($row = $stats_query->fetch_assoc()) {
        $ticket_stats[] = $row;
    }

    // Prize Breakdown (RAW values!)
    $prizes_query = $db->query("SELECT position, prize FROM rounds WHERE round_id = '$round_id' ORDER BY position ASC");
    $prizes = [];
    $total_prize = 0;
    while ($prize = $prizes_query->fetch_assoc()) {
        $total_prize += (float) $prize['prize'];
        $prizes[] = [
            'position' => (int) $prize['position'],
            'prize' => (float) $prize['prize']
        ];
    }

    // Total tickets sold
    $ticket_count_query = $db->query("SELECT COUNT(*) AS ticket_count FROM current_round_tickets WHERE round_id = '$round_id'");
    $ticket_count = $ticket_count_query->fetch_assoc()['ticket_count'] ?? 0;

    // Ticket list (User-ID format)
    $ticket_list = "";
    $ticket_query = $db->query("SELECT ticket_id, user_id FROM current_round_tickets WHERE round_id='$round_id' ORDER BY ticket_id ASC");
    while ($ticket = $ticket_query->fetch_assoc()) {
        $ticket_list .= "User-ID-" . $ticket['user_id'] . ":" . getTicketMiddleText() . "-" . $ticket['ticket_id'] . ";";
    }

    // Reward formatting settings
    $prefix_or_suffix = (int) getSetting('prefix_or_suffix');
    $reward_prefix = getSetting('reward_prefix');
    $reward_suffix = getSetting('reward_suffix');
    
    $use_custom_rewards = 0;
    $reward_labels = [];
    
    $round_meta_query = $db->query("SELECT use_custom_rewards FROM rounds WHERE round_id = '$round_id' LIMIT 1");
    if ($round_meta_query->num_rows > 0) {
        $use_custom_rewards = (int)$round_meta_query->fetch_assoc()['use_custom_rewards'];
    }
    
    $reward_labels = json_decode(getSetting('reward_labels') ?? '[]', true);


    // Final JSON Response
    safe_json_response([
        'status' => 200,
        'round' => [
            'round_id' => $round_id,
            'expected_end_date' => $expected_end_date,
            'date' => $start_date
        ],
        'ticket_stats' => $ticket_stats,
        'prizes' => $prizes,
        'total_prize' => (float) $total_prize,
        'tickets_sold' => $ticket_count,
        'ticket_list' => !empty($ticket_list) ? $ticket_list : "No tickets purchased yet.",
        'user_balance' => (float) ($user_balance ?? 0),
        'reward_config' => [
        'prefix_or_suffix' => $prefix_or_suffix,
        'reward_prefix' => $reward_prefix,
        'reward_suffix' => $reward_suffix,
        'use_custom_rewards' => $use_custom_rewards,
        'reward_labels' => $reward_labels
        ]

    ]);
    break;
    
    // Case: Return next expected_end_date if available
    case 'fetch_round_end_time':
    $active = $db->query("SELECT MIN(date) AS date, MAX(expected_end_date) AS expected_end_date FROM rounds WHERE closed = 0");

    if ($active && $active->num_rows > 0) {
        $row = $active->fetch_assoc();
        safe_json_response([
            "status" => 200,
            "date" => (int)$row['date'], // 👈 Useful for 5s buy delay check
            "expected_end_date" => (int)$row['expected_end_date']
        ]);
    } else {
        safe_json_response(["status" => 404]);
    }
    break;
    
   case 'fetch_recent_winners':
    $result = $db->query("
        SELECT 
            r.round_id, 
            r.position, 
            r.winner_id, 
            u.username, 
            r.winning_ticket, 
            r.winner_tickets, 
            r.reward_received, 
            r.end_date
        FROM rounds r
        LEFT JOIN users u ON r.winner_id = u.id
        WHERE r.closed = 1 AND r.winning_ticket > 0
        ORDER BY r.round_id DESC, r.position ASC
        LIMIT 50
    ");

    $winners = [];
    while ($row = $result->fetch_assoc()) {
        // Manually ensure user_id exists
        $row['user_id'] = (int) $row['winner_id'];
        $winners[] = $row;
    }

    $reward_labels = json_decode(getSetting('reward_labels') ?? '[]', true);
    $use_custom_rewards = (int) getSetting('use_custom_rewards');
    $prefix_or_suffix = (int) getSetting('prefix_or_suffix');
    $reward_prefix = getSetting('reward_prefix');
    $reward_suffix = getSetting('reward_suffix');

    safe_json_response([
        'status' => 200,
        'winners' => $winners,
        'reward_config' => [
            'use_custom_rewards' => $use_custom_rewards,
            'reward_labels' => $reward_labels,
            'prefix_or_suffix' => $prefix_or_suffix,
            'reward_prefix' => $reward_prefix,
            'reward_suffix' => $reward_suffix
        ]
    ]);
    break;
    
    case 'get_user_winning_tickets':
    if (!$user_id) {
        safe_json_response([
            'status' => 401,
            'message' => 'User not logged in'
        ]);
    }

    $round_id = $_POST['round_id'] ?? null;

    if (!$round_id || !is_numeric($round_id)) {
        safe_json_response([
            'status' => 400,
            'message' => 'Invalid or missing round ID'
        ]);
    }

    // Fetch user’s winning tickets for this round
    $query = $db->query("
        SELECT ticket_id 
        FROM winning_round_tickets 
        WHERE user_id = '" . (int)$user_id . "' 
        AND round_id = '" . (int)$round_id . "'
    ");

    $tickets = [];
    while ($row = $query->fetch_assoc()) {
        $tickets[] = (int)$row['ticket_id'];
    }

    safe_json_response([
        'status' => 200,
        'tickets' => $tickets
    ]);
    break;
    
    case 'fetch_live_ticket_list':
        fetchLiveTicketList();
        break;
        
    case 'fetch_user_stats':
        fetchUserStats();
        break;
        
    default:
    $handled = false;

    // Scan all addon folders and include ajax_cases.php if found
    $addonPath = __DIR__ . '/../addons/';
    $addonDirs = scandir($addonPath);

    foreach ($addonDirs as $addonName) {
        if ($addonName === '.' || $addonName === '..') continue;

        $caseFile = $addonPath . $addonName . '/system/ajax_cases.php';
        if (file_exists($caseFile)) {
            include $caseFile;
            if ($handled) break;
        }
    }

    if (!$handled) {
        safe_json_response(['status' => 'error', 'message' => 'Unknown action']);
    }
    break;

}

function fetchUserStats() {
    global $db, $user_id, $action;

    if (!$user_id && $action === 'buy_ticket') { 
        safe_json_response(['status' => 400, 'message' => 'User not logged in']);
    }

    // Get user balance
    $user_query = $db->query("SELECT account_balance FROM users WHERE id = '" . (int)$user_id . "'");
    $user_balance = ($user_query->num_rows > 0) ? (float)$user_query->fetch_assoc()['account_balance'] : 0.00;

    // Get live ticket count
    $ticket_query = $db->query("SELECT COUNT(*) as ticket_count FROM current_round_tickets WHERE user_id = '" . (int)$user_id . "'");
    $user_tickets = ($ticket_query->num_rows > 0) ? (int)$ticket_query->fetch_assoc()['ticket_count'] : 0;

    // Get total wins
    $wins_query = $db->query("SELECT COUNT(DISTINCT round_id) as total_winning_rounds FROM winning_round_tickets WHERE user_id = '" . (int)$user_id . "'");
    $user_wins = ($wins_query->num_rows > 0) ? (int)$wins_query->fetch_assoc()['total_winning_rounds'] : 0;

    // Return updated stats
    safe_json_response([
        'status' => 200,
        'balance' => number_format($user_balance, 2),
        'tickets' => $user_tickets,
        'user_wins' => $user_wins
    ]);
}
