<?php

/**
 * Universal Fairness Protocol (UFP)
 * Dual Licensed under GPLv3 and Commercial License
 * Author: DgtalFairness | https://universalfairnessprotocol.com
 */

// Prevent direct access
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(403);
    exit('❌ Direct access not allowed.');
}

// Database credentials
$host = 'localhost';
$username = '';
$password = '';
$database = '';

// Create connection
$db = new mysqli($host, $username, $password, $database);

// Check connection
if ($db->connect_error) {
    die("Database connection failed: " . $db->connect_error);
}

// Cstom query helpers
function QueryFetchArray($query) {
    global $db;
    $result = $db->query($query);
    return $result ? $result->fetch_assoc() : null;
}

function QueryGetNumRows($query) {
    global $db;
    $result = $db->query($query);
    return $result ? $result->num_rows : 0;
}
?>

