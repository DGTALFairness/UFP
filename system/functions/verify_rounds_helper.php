<?php

/**
 * Universal Fairness Protocol (UFP)
 * Dual Licensed under GPLv3 and Commercial License
 * Author: DgtalFairness | https://universalfairnessprotocol.com
 */

$pastRounds = [];
$query = $db->query("
    SELECT DISTINCT ftl.round_id AS id, r.end_date 
    FROM `fairness_ticket_logs` ftl
    LEFT JOIN `rounds` r ON r.round_id = ftl.round_id
    ORDER BY ftl.round_id DESC LIMIT 250
");

if ($query) {
    while ($row = $query->fetch_assoc()) {
        $pastRounds[] = $row;
    }
}
