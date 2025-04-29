<?php

/**
 * Universal Fairness Protocol (UFP)
 * Dual Licensed under GPLv3 and Commercial License
 * Author: DgtalFairness | https://universalfairnessprotocol.com
 */

if ($action === 'checkin_status') {
    $handled = true;

    // Your check-in status logic here
    $active_round_query = $db->query("SELECT round_id FROM rounds WHERE closed = 0 ORDER BY round_id DESC LIMIT 1");
    if (!$active_round_query || $active_round_query->num_rows === 0) {
        safe_json_response([
            'status' => 'error',
            'message' => 'No active round found. Please wait for the next round to begin.',
            'noActiveRound' => true
        ]);
    }

    if (!isset($_SESSION['user_id'])) {
        safe_json_response(['status' => 'error', 'message' => 'You must be logged in to check in.']);
    }

    $user_id = $_SESSION['user_id'];
    $checkin_lapse_time = getSetting('checkin_lapse_time', 1);
    $current_time = time();

    $stmt = $db->prepare("SELECT last_activity FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($last_activity);
    $stmt->fetch();
    $stmt->close();

    if ($last_activity) {
        $time_since_last_checkin = $current_time - $last_activity;
        if ($time_since_last_checkin < $checkin_lapse_time * 60) {
            $next_checkin_time = $checkin_lapse_time * 60 - $time_since_last_checkin;
            safe_json_response([
                'status' => 'success',
                'canCheckIn' => false,
                'message' => 'You need to wait before checking in again.',
                'lastCheckin' => date('Y-m-d H:i:s', $last_activity),
                'lastCheckin_unix' => $last_activity,
                'nextCheckinInSeconds' => $next_checkin_time
            ]);
        }
    }

    safe_json_response([
        'status' => 'success',
        'canCheckIn' => true,
        'lastCheckin' => $last_activity ? date('Y-m-d H:i:s', $last_activity) : 'Never',
        'lastCheckin_unix' => $last_activity ?? 0,
        'nextCheckinInSeconds' => 0
    ]);
}

if ($action === 'checkin') {
    $handled = true;

    if (!isset($_SESSION['user_id'])) {
        safe_json_response(['status' => 'error', 'message' => 'You must be logged in to check in.']);
    }

    $user_id = $_SESSION['user_id'];
    $checkin_lapse_time = getSetting('checkin_lapse_time', 1);
    $current_time = time();

    $stmt = $db->prepare("SELECT last_activity FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($last_activity);
    $stmt->fetch();
    $stmt->close();

    if ($last_activity) {
        $time_since_last_checkin = $current_time - $last_activity;
        if ($time_since_last_checkin < $checkin_lapse_time * 60) {
            $next_checkin_time = $checkin_lapse_time * 60 - $time_since_last_checkin;
            safe_json_response([
                'status' => 'error',
                'message' => 'You need to wait before checking in again.',
                'nextCheckinInSeconds' => $next_checkin_time
            ]);
        }
    }

    $active_round_query = $db->query("SELECT round_id FROM rounds WHERE closed = 0 ORDER BY round_id DESC LIMIT 1");
    if (!$active_round_query || $active_round_query->num_rows === 0) {
        safe_json_response([
            'status' => 'error',
            'message' => 'No active round found. Please wait for the next round to begin.',
            'noActiveRound' => true
        ]);
    }

    $round_id = $active_round_query->fetch_assoc()['round_id'];

    $stmt = $db->prepare("UPDATE users SET last_activity = ? WHERE id = ?");
    $stmt->bind_param("ii", $current_time, $user_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $db->prepare("INSERT INTO current_round_tickets (round_id, user_id, date) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $round_id, $user_id, $current_time);
    $stmt->execute();
    $stmt->close();
    
    // Increment total_checkins in rounds
    $db->query("UPDATE rounds SET total_checkins = total_checkins + 1 WHERE round_id = '$round_id'");

    safe_json_response([
        'status' => 'success',
        'message' => 'Check-in successful!',
        'nextCheckinInSeconds' => $checkin_lapse_time * 60,
        'lastCheckin' => date('Y-m-d H:i:s', $current_time)
    ]);
}
