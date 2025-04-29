<?php

/**
 * Universal Fairness Protocol (UFP)
 * Dual Licensed under GPLv3 and Commercial License
 * Author: DgtalFairness | https://universalfairnessprotocol.com
 */

// Load database connection
require_once(__DIR__ . '/../db/database.php');
require_once(__DIR__ . '/../system/functions/functions.php');

echo "🔄 Running Cron Job for UFP...\n";

// Function to check if a section should run
function shouldRunTask($task_name, $interval_seconds) {
    global $db;
    $query = $db->query("SELECT last_run FROM cron_timestamps WHERE task_name = '$task_name' LIMIT 1");
    $task = $query->fetch_assoc();

    if (!$task) {
        // If task is missing, insert it and return false (so it doesn't run immediately)
        $db->query("INSERT INTO cron_timestamps (task_name, last_run) VALUES ('$task_name', UNIX_TIMESTAMP())");
        return false;
    }

    // Compare current time with last run time
    return (time() - $task['last_run']) >= $interval_seconds;
    }
    
    // Function to update timestamp after execution
    function updateTaskTimestamp($task_name) {
        global $db;
        $db->query("UPDATE cron_timestamps SET last_run = UNIX_TIMESTAMP() WHERE task_name = '$task_name'");
    }
    
    // 1-Minute Section
    
    // Fetch the current active round that should be closed
    $round = $db->query("SELECT * FROM rounds WHERE closed = '0' ORDER BY round_id DESC LIMIT 1")->fetch_assoc();
    
        if (!empty($round) && $round['expected_end_date'] <= time() && $round['closed'] == 0) {        echo "⏳ Closing Round #" . $round['round_id'] . " as expected end time has passed...\n";
    
        // Fetch all tickets for this round
        $tickets = $db->query("SELECT user_id, ticket_id FROM current_round_tickets WHERE round_id='" . $round['round_id'] . "'");
        $ticketArray = $tickets->fetch_all(MYSQLI_ASSOC);

    
        if (count($ticketArray) === 0) {
            echo "❌ ERROR: No tickets were sold for this round! Closing round without winners.\n";
            $db->query("UPDATE rounds SET closed='1', end_date=UNIX_TIMESTAMP() WHERE round_id='" . $round['round_id'] . "'");
        } else {
            // Fetch positions directly from the rounds table
            $positionsQuery = $db->query("SELECT position FROM rounds WHERE round_id='" . $round['round_id'] . "' ORDER BY position ASC");
            $availablePositions = [];
            while ($row = $positionsQuery->fetch_assoc()) {
                $availablePositions[] = $row['position'];
            }
    
            // Ensure ticket list is sorted for consistency
            usort($ticketArray, function ($a, $b) {
                return $a['ticket_id'] <=> $b['ticket_id'];
            });

    
            // Convert ticket data into SHA-256 format (optimized array mapping)
            $ticketListSHA = implode(";", array_map(function ($ticket) {
                return "User-ID-{$ticket['user_id']}:" . getTicketMiddleText() . "-{$ticket['ticket_id']}";
            }, $ticketArray));

    
            // Generate SHA-256 hash of the ticket list
            $ticketHash = hash('sha256', $ticketListSHA);
    
            // Store the hash in the round table and close it
            $db->query("UPDATE rounds SET ticket_hash='$ticketHash', closed='1', end_date=UNIX_TIMESTAMP() WHERE round_id='" . $round['round_id'] . "'");
    
            // Store ticket list and hash for verification
            $db->query("INSERT INTO fairness_ticket_logs (round_id, ticket_list, hash, created_at)
                        VALUES ('" . $round['round_id'] . "', '" . $db->real_escape_string($ticketListSHA) . "', '$ticketHash', NOW())");
    
            // Find the first and total ticket numbers
            $firstTicket = !empty($ticketArray) ? $ticketArray[0]['ticket_id'] : 0;
            $totalTickets = count($ticketArray);

    
            if ($totalTickets > 0) {
                // Fetch admin-defined payout percentages
                $settings_query = $db->query("SELECT name, value FROM settings WHERE name IN ('payouts')");
                $settings = [];
                while ($row = $settings_query->fetch_assoc()) {
                    $settings[$row['name']] = $row['value'];
                }
    
                // Fetch payouts from settings, or use fallback default structure
                $payouts = isset($settings['payouts']) ? json_decode($settings['payouts'], true) : array_fill(0, count($availablePositions), 100 / count($availablePositions));
    
                if (!is_array($payouts) || count($payouts) !== count($availablePositions)) {
                    echo "❌ ERROR: Invalid payout distribution! Falling back to equal distribution.\n";
                    $payouts = array_fill(0, count($availablePositions), 100 / count($availablePositions));
                }
    
                // Precalculate how many segments can be extracted before processing
                preg_match_all('/\d/', $ticketHash, $hashNumbers);
                $numbersOnly = implode("", $hashNumbers[0]);
    
                $availableSegments = max(0, strlen($numbersOnly) - 9); // 10-digit segments
    
                // Calculate how many rehashes are needed
                $requiredSegments = count($availablePositions);
                $rehashNeeded = max(0, $requiredSegments - $availableSegments);
                

                // Step 1: Determine the actual number of unique users (participants) for this round
                $totalUsersQuery = $db->query("
                    SELECT COUNT(DISTINCT user_id) AS total_users 
                    FROM current_round_tickets 
                    WHERE round_id = '" . $round['round_id'] . "'
                ");
                $totalUsersData = $totalUsersQuery->fetch_assoc();
                $totalUsers = (int) $totalUsersData['total_users']; // The real number of unique participants
                
                // Step 2: Determine correct number of winners based on reward mode
                $useRewardMode = (int) ($round['use_custom_rewards'] ?? 0);
                
                $winnerSettingName = $useRewardMode === 1 ? 'reward_number_of_winners' : 'number_of_winners';
                $maxWinnersQuery = $db->query("SELECT value FROM settings WHERE name = '$winnerSettingName' LIMIT 1");
                $maxWinnersData = $maxWinnersQuery->fetch_assoc();
                $maxWinners = (int) $maxWinnersData['value'] ?? 3; // Fallback to 3 if missing

                
                // Step 3: Set total_winners as the **minimum between actual participants & max winners**
                $totalWinners = min($totalUsers, $maxWinners);
                
                // Step 4: Fetch only the number of positions needed (aligns with `total_winners`)
                $positionsQuery = $db->query("
                    SELECT position 
                    FROM rounds 
                    WHERE round_id = '" . $round['round_id'] . "' 
                    ORDER BY position ASC 
                    LIMIT $totalWinners
                ");
                
                $availablePositions = [];
                while ($row = $positionsQuery->fetch_assoc()) {
                    $availablePositions[] = $row['position'];
                }
                
                // Ensure Available Positions Match Actual Winner Count
                if (count($availablePositions) !== $totalWinners) {

                }
                
                // Step 5: Select Winners Based on Actual Positions
                $winners = [];
                $previousHash = $ticketHash; // Start with Tier 1 hash
                $hashCounter = 0;
                $segmentLimit = 20; // Max 20 segments per hash before rehashing
                $rehashChain = []; // Store only rehashed keys, excluding the original
                
                for ($i = 0; $i < $totalWinners; $i++) {
                    if ($i > 0 && $i % $segmentLimit === 0) {
                        // Rehash only when necessary
                        $previousHash = hash('sha256', $previousHash);
                        $rehashChain[] = $previousHash;
                    }
                
                    // Extract numeric segments from the current hash
                    preg_match_all('/\d/', $previousHash, $hashNumbers);
                    $numbersOnly = implode("", $hashNumbers[0]);
                
                    if (strlen($numbersOnly) < 10) {

                        break;
                    }
                
                    // Extract 10-digit sliding segment
                    $startIndex = $i % (strlen($numbersOnly) - 10);
                    $numericSegment = substr($numbersOnly, $startIndex, 10);
                    $hashNumber = intval($numericSegment);
                    $winningTicket = ($hashNumber % $totalTickets) + $firstTicket;
                
                    // Find the user associated with this ticket
                    $winner = $db->query("SELECT * FROM `current_round_tickets` WHERE ticket_id='$winningTicket'")->fetch_assoc();

                
                    if (!empty($winner['user_id'])) {
                        $winnerData = [
                            "round_id" => $round['round_id'],  // Include Round ID in logs
                            "position" => $availablePositions[$i], 
                            "ticket_id" => $winningTicket,
                            "user_id" => $winner['user_id'],
                            "prize_percent" => $payouts[$i],
                            "winning_segment" => $numericSegment 
                        ];
                        
                        $winners[] = $winnerData;
                
                       // Determine the correct hash used for the winning segment
                        $hashUsedForSegment = $previousHash; // Default to current hash before rehashing
                        
                        // If the segment was extracted AFTER a rehash, find the correct hash
                        if (!empty($rehashChain)) {
                            foreach ($rehashChain as $rehash) {
                                if (strpos($rehash, $numericSegment) !== false) {
                                    $hashUsedForSegment = $rehash;
                                    break;
                                }
                            }
                        }
                        
                        // Update rounds table with BOTH `winning_segment` and `hash_used_for_segment`
                        $db->query("UPDATE rounds 
                                    SET winning_segment = '$numericSegment', 
                                        hash_used_for_segment = '{$db->real_escape_string($hashUsedForSegment)}' 
                                    WHERE round_id='" . $round['round_id'] . "' 
                                    AND position='" . $availablePositions[$i] . "'");
                                    
                                    
                        // Determine which hash generated the winning segment
                        $hash_tier = 1; // Default to Tier 1
                        $hash = $ticketHash; // Default to base ticket hash
                        
                        foreach ($rehashChain as $index => $rehash) {
                            if ($rehash === $hashUsedForSegment) {
                                $hash_tier = $index + 2; // If found in rehash_chain, its tier is index + 2
                                break;
                            }
                        }
                        
                        // Store the correct tier in `rounds`
                        $db->query("
                            UPDATE rounds 
                            SET hash_tier_for_segment = '{$hash_tier}'
                            WHERE round_id = '{$round['round_id']}'
                            AND position = '{$availablePositions[$i]}'
                        ");

                    }
                }
                
               // Count how many rehashes were performed
                $hashCounter = count($rehashChain);
                
                // Debugging: Log the value of $hashCounter before storing

                // Convert rehash chain to JSON for storage (only contains new rehashes)
                $rehashChainJson = empty($rehashChain) ? "NULL" : "'" . json_encode($rehashChain) . "'";
                
                // Store the final rehash count & chain in rounds and fairness_ticket_logs
                $db->query("UPDATE rounds 
                            SET rehash_count = '$hashCounter', rehash_chain = $rehashChainJson 
                            WHERE round_id = '" . $round['round_id'] . "'");
                
                if ($db->affected_rows > 0) {

                } else {

                }
                
                // Store the rehash chain in fairness_ticket_logs
                $db->query("UPDATE fairness_ticket_logs 
                            SET rehash_chain = $rehashChainJson 
                            WHERE round_id = '" . $round['round_id'] . "'");
                
                // Final Check: Recheck rounds table to verify if rehash_count updated
                $rehashCountCheck = $db->query("SELECT rehash_count FROM rounds WHERE round_id = '" . $round['round_id'] . "'")->fetch_assoc();

                // Count unique users who bought at least 1 ticket in this round
                $uniqueUserCountQuery = $db->query("
                    SELECT COUNT(DISTINCT user_id) AS total_users 
                    FROM current_round_tickets 
                    WHERE round_id = '" . $round['round_id'] . "'
                ");
                $totalUsers = ($uniqueUserCountQuery->num_rows > 0) ? $uniqueUserCountQuery->fetch_assoc()['total_users'] : 0;
                
                // Update total_users column in the rounds table
                $db->query("UPDATE rounds 
                            SET total_users = '$totalUsers' 
                            WHERE round_id = '" . $round['round_id'] . "'");
                            
                // Distribute rewards to winners
                foreach ($winners as $winnerData) {
                    // Fetch prize, use_custom_rewards, and reward_label
                    $prizeAmountQuery = $db->query("SELECT prize, use_custom_rewards, reward_label FROM rounds 
                                                    WHERE round_id='" . $round['round_id'] . "' 
                                                    AND position='" . $winnerData['position'] . "'");
                    $prizeAmountRow = $prizeAmountQuery->fetch_assoc();
                    $rawPrize = $prizeAmountRow['prize'] ?? '0';
                    $prizeAmount = (is_numeric($rawPrize)) ? round((float)$rawPrize, 2) : 0;

                    $isRewardMode = isset($prizeAmountRow['use_custom_rewards']) && (int)$prizeAmountRow['use_custom_rewards'] === 1;
                    $rewardLabel  = $prizeAmountRow['reward_label'] ?? '';
                
                    if (!$isRewardMode && $prizeAmount <= 0) {

                        continue;
                    }
                
                    // Update winner's balance (only in payout mode)
                    if (!$isRewardMode && $prizeAmount > 0) {
                        $db->query("UPDATE users SET 
                            account_balance = account_balance + $prizeAmount, 
                            today_revenue = today_revenue + $prizeAmount, 
                            total_revenue = total_revenue + $prizeAmount 
                            WHERE id = '" . $winnerData['user_id'] . "'");
                    }
                
                    // Fetch total tickets bought by this winner
                    $winnerTotalTicketsQuery = $db->query("SELECT COUNT(*) AS total FROM current_round_tickets 
                        WHERE round_id='" . $round['round_id'] . "' 
                        AND user_id='" . $winnerData['user_id'] . "'");
                    $winnerTotalTickets = ($winnerTotalTicketsQuery->num_rows > 0) ? $winnerTotalTicketsQuery->fetch_assoc()['total'] : 0;
                
                    // Determine what to store in `reward_received`
                    $rewardReceived = $isRewardMode ? $db->real_escape_string($rewardLabel) : number_format($prizeAmount, 2, '.', '');
                
                    // Update `rounds` with the winner’s info
                    $db->query("UPDATE rounds 
                                SET winner_id='" . $winnerData['user_id'] . "', 
                                    winner_tickets='" . $winnerTotalTickets . "',
                                    winning_ticket='" . $winnerData['ticket_id'] . "', 
                                    reward_received='" . $rewardReceived . "'  
                                WHERE round_id='" . $round['round_id'] . "' 
                                AND position='" . $winnerData['position'] . "'");
                
                    // Log payout distribution (only in payout mode)
                    if (!$isRewardMode) {

                    } else {

                    }
                }
                
                // Count unique users who bought at least 1 ticket in this round
                $uniqueUserCountQuery = $db->query("SELECT COUNT(DISTINCT user_id) AS total_users FROM current_round_tickets WHERE round_id = '" . $round['round_id'] . "'");
                $totalUsers = ($uniqueUserCountQuery->num_rows > 0) ? $uniqueUserCountQuery->fetch_assoc()['total_users'] : 0;

                // Update the rounds table with the total number of users who participated
                $db->query("UPDATE rounds 
                    SET total_users = '$totalUsers' 
                    WHERE round_id = '" . $round['round_id'] . "'");

                
                $totalWinners = count($winners); // Count total winners, including duplicates
                
                $db->query("UPDATE rounds 
                SET total_winners = '$totalWinners' 
                WHERE round_id = '" . $round['round_id'] . "'");

               // Move tickets
                if ($db->query("
                    INSERT INTO completed_round_tickets (ticket_id, round_id, user_id, date)
                    SELECT ticket_id, round_id, user_id, date FROM current_round_tickets
                    WHERE round_id='" . $round['round_id'] . "'
                ")) {

                } else {

                }
                
                // Delete moved tickets
                if ($db->query("DELETE FROM current_round_tickets WHERE round_id='" . $round['round_id'] . "'")) {

                } else {

                }
                                echo "✅ Round #" . $round['round_id'] . " ended. " . count($winners) . " tickets moved to completed_round_tickets.\n";
                
                    
                                echo "✅ Round #" . $round['round_id'] . " ended. " . count($winners) . " winners selected.\n";
                            }
                        }
                    }
                
                  // If no active round exists, create a new round
                    if (empty($round)) {
                        echo "🔄 No active rounds found. Creating a new round...\n";
                    
                        // Fetch round duration settings
                        $round_duration_query = $db->query("SELECT value FROM settings WHERE name = 'round_duration' LIMIT 1");
                        $round_duration_type_query = $db->query("SELECT value FROM settings WHERE name = 'round_duration_type' LIMIT 1");
                    
                        $round_duration = ($round_duration_query->num_rows > 0) ? (int)$round_duration_query->fetch_assoc()['value'] : 60;
                        $round_duration_type = ($round_duration_type_query->num_rows > 0) ? $round_duration_type_query->fetch_assoc()['value'] : 'minutes';
                    
                        $currentMinute = strtotime(date('Y-m-d H:i:00'));
                        $currentTime = $currentMinute + 5;
                    
                        switch ($round_duration_type) {
                            case 'minutes': $expected_end_date = $currentMinute + ($round_duration * 60) - 5; break;
                            case 'hours':   $expected_end_date = $currentMinute + ($round_duration * 3600) - 5; break;
                            case 'days':    $expected_end_date = $currentMinute + ($round_duration * 86400) - 5; break;
                            case 'weeks':   $expected_end_date = $currentMinute + ($round_duration * 604800) - 5; break;
                            case 'months':  $expected_end_date = strtotime('first day of next month', $currentMinute) - 5; break;
                            default:        $expected_end_date = $currentMinute + ($round_duration * 60) - 5;
                        }
                    
                        // Fetch other settings
                        $starting_prize_raw = (float) $db->query("SELECT value FROM settings WHERE name = 'starting_prize'")->fetch_assoc()['value'] ?? 10.00;
                        $starting_prize = number_format($starting_prize_raw, 2, '.', '');

                    
                        // Fix: Get ticket_price from settings_lottery_addon
                        $entry_price_query = $db->query("SELECT value FROM settings_lottery_addon WHERE name = 'ticket_price' LIMIT 1");
                        $entry_price = ($entry_price_query->num_rows > 0) ? (float)$entry_price_query->fetch_assoc()['value'] : 0.00;
                    
                        $use_custom_rewards = (int) $db->query("SELECT value FROM settings WHERE name = 'use_custom_rewards'")->fetch_assoc()['value'] ?? 0;
                        $next_round_id = (int) $db->query("SELECT MAX(round_id) AS last_round FROM rounds")->fetch_assoc()['last_round'] + 1 ?? 1;
                    
                        // Depending on mode, fetch winners and labels if needed
                        if ($use_custom_rewards === 1) {
                            $num_winners = (int) $db->query("SELECT value FROM settings WHERE name = 'reward_number_of_winners'")->fetch_assoc()['value'] ?? 10;
                            $labels_json = $db->query("SELECT value FROM settings WHERE name = 'reward_labels'")->fetch_assoc()['value'] ?? '[]';
                            $reward_labels = json_decode($labels_json, true);
                    
                            $values = [];
                            for ($position = 1; $position <= $num_winners; $position++) {
                                $label = isset($reward_labels[$position - 1]) ? $db->real_escape_string($reward_labels[$position - 1]) : '';
                                $values[] = "('$next_round_id', '$position', '$label', '$currentTime', '$expected_end_date', '$entry_price', 1, '$label')";
                            }
                    
                            $query = "INSERT INTO rounds 
                                (round_id, position, prize, date, expected_end_date, entry_price, use_custom_rewards, reward_label) 
                                VALUES " . implode(',', $values);
                    
                        } else {
                            $num_winners = (int) $db->query("SELECT value FROM settings WHERE name = 'number_of_winners'")->fetch_assoc()['value'] ?? 3;
                    
                            $values = [];
                            for ($position = 1; $position <= $num_winners; $position++) {
                                $values[] = "('$next_round_id', '$position', '$starting_prize', '$currentTime', '$expected_end_date', '$entry_price')";
                            }
                    
                            $query = "INSERT INTO rounds 
                                (round_id, position, prize, date, expected_end_date, entry_price) 
                                VALUES " . implode(',', $values);
                        }
                    
                        if ($db->query($query)) {

                        } else {

                        }
                    }

    
   // Standalone Block for Extracting Segments (Place AFTER the entire round processing)

// Fetch rounds from fairness_ticket_logs that still need segment extraction
$pendingRounds = $db->query("
    SELECT round_id, hash, rehash_chain 
    FROM fairness_ticket_logs 
    WHERE segment_chain_updated = 0 
    ORDER BY round_id DESC
");

if (!$pendingRounds) {

    return;
}

while ($row = $pendingRounds->fetch_assoc()) {
    $round_id = $row['round_id'];
    $tier1_hash = $row['hash'];  // Get hash from fairness_ticket_logs
    $rehash_chain = json_decode($row['rehash_chain'], true) ?? [];



    // Fetch the actual number of winners from rounds
    $winnerQuery = $db->query("
        SELECT total_winners 
        FROM rounds 
        WHERE round_id = '$round_id'
    ");
    $winnerData = $winnerQuery->fetch_assoc();
    $total_winners = (int) $winnerData['total_winners']; // The **real** number of winners

    if ($total_winners === 0) {

        continue;
    }

    // Initialize segment storage
    $allSegments = [];
    $remaining_segments = $total_winners; // Start with the total number of winners needed
    $segmentLimit = 20; // Max 20 segments per hash before rehashing
    $current_position = 1; // Global position tracker

    // Extract from Tier 1 first
    if ($remaining_segments > 0) {
        $segmentsTier1 = extractWinningSegments($tier1_hash, 1, min($remaining_segments, $segmentLimit));

        // Assign position numbers correctly
        foreach ($segmentsTier1 as &$segment) {
            $segment["position"] = $current_position++; // Increment position globally
        }

        $allSegments = array_merge($allSegments, $segmentsTier1);
        $remaining_segments -= count($segmentsTier1);
    }

    // Process additional tiers from rehash_chain **only if necessary**
    $tier_level = 2;
    foreach ($rehash_chain as $rehash_hash) {
        if ($remaining_segments <= 0) {
            break; // Stop extracting if we already have enough segments
        }


        // Extract only the **remaining** number of winners, but max 20 per tier
        $segments = extractWinningSegments($rehash_hash, $tier_level, min($remaining_segments, $segmentLimit));

        // Assign position numbers correctly
        foreach ($segments as &$segment) {
            $segment["position"] = $current_position++; // Continue position numbering
        }

        $allSegments = array_merge($allSegments, $segments);
        $remaining_segments -= count($segments);

        $tier_level++;
    }

    // Final segment count
    $segmentCount = count($allSegments);

    // Convert merged segments to JSON and store them
    $updatedSegmentsJson = json_encode($allSegments);

    if (json_last_error() === JSON_ERROR_NONE) {
        $updateQuery = "UPDATE fairness_ticket_logs 
                        SET segment_chain = '$updatedSegmentsJson', 
                            segment_count = '$segmentCount', 
                            segment_chain_updated = 1 
                        WHERE round_id = '$round_id'";
        $db->query($updateQuery);

        if ($db->affected_rows > 0) {

        } else {

        }
    } else {

    }
}

// Step 1: Find closed rounds that are not yet saved
$winningTicketsQuery = $db->query("
    SELECT 
        r.id,              -- ✅ Include 'id' from rounds
        r.round_id, 
        r.position, 
        r.winner_id AS user_id, 
        r.winning_ticket AS ticket_id, 
        r.winning_segment, 
        r.hash_tier_for_segment, 
        r.hash_used_for_segment, 
        r.prize, 
        r.entry_price,
        r.reward_label,    -- ✅ NEW: Fetch reward label if present
        ct.date
    FROM rounds r
    LEFT JOIN completed_round_tickets ct 
        ON ct.ticket_id = r.winning_ticket 
        AND ct.round_id = r.round_id
    WHERE r.closed = 1 AND r.saved = 0 AND r.winning_ticket > 0
    ORDER BY r.round_id ASC, r.position ASC
");

if ($winningTicketsQuery->num_rows > 0) {
    echo "✅ Found " . $winningTicketsQuery->num_rows . " winning tickets to insert.\n";
    
    $winningTickets = [];

    while ($winner = $winningTicketsQuery->fetch_assoc()) {
        $id            = $winner['id'];
        $round_id      = $winner['round_id'];
        $ticket_id     = $winner['ticket_id'];
        $user_id       = $winner['user_id'];
        $position      = $winner['position'];
        $segment       = $winner['winning_segment'];
        $hash_tier     = $winner['hash_tier_for_segment'];
        $hash          = $winner['hash_used_for_segment'];
        $reward_amount = $winner['prize'];
        $entry_price   = $winner['entry_price'];
        $reward_label  = $db->real_escape_string($winner['reward_label'] ?? '');
        $date          = $winner['date'] ?? time();

        $winningTickets[] = "(
            '{$id}', 
            '{$ticket_id}', 
            '{$round_id}', 
            '{$user_id}', 
            '{$position}', 
            '{$segment}', 
            '{$hash_tier}', 
            '{$hash}', 
            '{$reward_amount}', 
            '{$entry_price}', 
            '{$date}',
            '{$reward_label}'  -- ✅ NEW: Insert reward label
        )";
    }

    if (!empty($winningTickets)) {
        $sql = "
            INSERT INTO winning_round_tickets 
            (id, ticket_id, round_id, user_id, position, segment, hash_tier, hash, reward_amount, entry_price, date, reward_label)
            VALUES " . implode(',', $winningTickets);

        if ($db->query($sql)) {
            echo "✅ Successfully inserted winners into winning_round_tickets.\n";
        } else {
            echo "❌ ERROR inserting winners: " . $db->error . "\n";
        }
    } else {
        echo "⚠️ No valid winners to insert.\n";
    }

    // Mark processed rounds as saved
    $db->query("UPDATE rounds SET saved = 1 WHERE closed = 1 AND saved = 0");
    echo "✅ Processed rounds marked as saved.\n";

} else {
    echo "❌ No closed rounds found that need saving.\n";
}

    // Ensure the timestamp updates EVERY TIME the 1-minute cron runs
    updateTaskTimestamp('1_minute_cron');
    
    // 5-Minute Section
        if (shouldRunTask('5_min', 300)) { // 300 seconds = 5 minutes
            echo "✅ Running 5-minute tasks...\n";
            // (Put your 5 minutes logic here)
            updateTaskTimestamp('5_min');
        }

    // 15-Minute Section
    if (shouldRunTask('15_min', 900)) {
        echo "✅ Running 15-minute tasks...\n";
        // (Put your 15 minutes logic here)
        updateTaskTimestamp('15_min');
    }
    
    // Hourly Section
    if (shouldRunTask('hourly', 3600)) {
        echo "✅ Running hourly tasks...\n";
        // (Put your hourly logic here)
        updateTaskTimestamp('hourly');
    }
    
    // Daily Section
    if (shouldRunTask('daily', 86400)) {
        echo "✅ Running daily tasks...\n";
       
        echo "🔄 Archiving completed rounds older than 1 month...\n";
    
        // Move rounds older than 1 month to `rounds_archive`
        $moveRounds = $db->query("
            INSERT INTO rounds_archive
            SELECT * FROM rounds 
            WHERE closed = 1 
            AND end_date <= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 MONTH))
        ");
        
        if ($moveRounds) {
            // Delete rounds that were moved
            $deleteRounds = $db->query("
                DELETE FROM rounds 
                WHERE closed = 1 
                AND end_date <= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 MONTH))
            ");
        
            if ($deleteRounds) {
                echo "✅ Successfully archived old completed rounds.\n";
            } else {
                echo "❌ ERROR: Failed to delete old rounds from `rounds`.\n";
            }
        } else {
            echo "❌ ERROR: Failed to move rounds to `rounds_archive`.\n";
        }
        
        // Log the operation
        $db->query("
            INSERT INTO system_logs (event_type, event_details, event_time)
            VALUES ('rounds_archive', 'Archived completed rounds older than 1 month', NOW())
        ");
        
        echo "✅ Archiving process completed.\n";
       
        updateTaskTimestamp('daily');
    }
    
    // Weekly Section
    if (shouldRunTask('weekly', 604800)) { // 604800 seconds = 7 days
        echo "✅ Running weekly tasks...\n";
        // (Put your weekly logic here)
        updateTaskTimestamp('weekly');
    }
    
    echo "✅ Cron Job Completed.\n";
?>