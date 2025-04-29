<?php

/**
 * Universal Fairness Protocol (UFP)
 * Dual Licensed under GPLv3 and Commercial License
 * Author: DgtalFairness | https://universalfairnessprotocol.com
 */

// Load database connection
require_once(__DIR__ . '/../../db/database.php');
global $db; // Ensure database connection

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Secure Redirect Function
function redirect($location) {
    header('Location: '.$location);
    exit();
}

// Secure Password Hashing
function securePassword($pass) {
    return password_hash($pass, PASSWORD_BCRYPT);
}

// Validate Password Complexity
function validatePassword($password) {
    return (strlen($password) >= 8 && preg_match("#[0-9]+#", $password) &&
            preg_match("#[A-Z]+#", $password) && preg_match("#[a-z]+#", $password));
}

// Validate Email Format
function isEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Generate a Secure Random Key
function GenerateKey($length = 10) {
    return bin2hex(random_bytes($length / 2));
}

// Generate Security Tokens
function GenToken() {
    return bin2hex(random_bytes(32));
}

function GenGlobalToken() {
    $_SESSION['token'] = GenToken();
    return $_SESSION['token'];
}

// URL Generator (For Clean Links)
function GenerateURL($page) {
    return 'template/' . urlencode($page);
}

// Get Visitor IP Address
function VisitorIP() {
    return $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
}

// Function to Truncate Long Text
function truncate($str, $length, $trailing = '...') {
    return (mb_strlen($str) > $length) ? mb_substr($str, 0, $length) . $trailing : $str;
}

// Simple Percentage Calculation
function percent($num_amount, $num_total) {
    return ($num_total > 0) ? number_format(($num_amount / $num_total) * 100, 2) : 0;
}

// Calculate Remaining Time (e.g., for active rounds)
function remainingTime($seconds) {
    $timeUnits = ['day' => 86400, 'hour' => 3600, 'minute' => 60, 'second' => 1];
    foreach ($timeUnits as $label => $amount) {
        if ($seconds >= $amount) {
            return floor($seconds / $amount) . " " . $label . ((floor($seconds / $amount) > 1) ? "s" : "");
        }
    }
    return "Now";
}

// Fetch External Data (Using cURL)
function get_data($url, $timeout = 15) {
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => $timeout
    ];
    $ch = curl_init();
    curl_setopt_array($ch, $options);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

// Convert BBCode to HTML
function BBCode($string) {
    $search = [
        '/(\[b\])(.*?)(\[\/b\])/', '/(\[i\])(.*?)(\[\/i\])/', '/(\[u\])(.*?)(\[\/u\])/',
        '/(\[img\])(.*?)(\[\/img\])/', '/(\[url=)(.*?)(\])(.*?)(\[\/url\])/', '/(\[url\])(.*?)(\[\/url\])/'
    ];
    $replace = [
        '<b>$2</b>', '<em>$2</em>', '<u>$2</u>',
        '<img src="$2" alt="" />', '<a href="$2" target="_blank">$4</a>', '<a href="$2" target="_blank">$2</a>'
    ];
    return preg_replace($search, $replace, $string);
}

// Register a New User and Auto-Login
function registerUser($username, $password) {
    global $db;

    // Check if username already exists
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        return ['status' => 'error', 'message' => 'Username is already taken.'];
    }

    $stmt->close();

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Insert new user with 0 account balance initially
    $stmt = $db->prepare("INSERT INTO users (username, password, account_balance) VALUES (?, ?, 0)");
    $stmt->bind_param("ss", $username, $hashed_password);

    if ($stmt->execute()) {
        // Get user_id of the newly inserted user
        $user_id = $stmt->insert_id;

        // Now, add 100 to the new user's account balance
        $update_balance = $db->prepare("UPDATE users SET account_balance = account_balance + 100 WHERE id = ?");
        $update_balance->bind_param('i', $user_id);
        $update_balance->execute();

        // Auto-login the user
        $_SESSION['user_id'] = $user_id; // Store session

        return [
            'status' => 'success',
            'message' => 'Registration successful. Redirecting...',
            'redirect' => '/' // Redirect to home after successful registration
        ];
    } else {
        return ['status' => 'error', 'message' => 'Registration failed. Please try again.'];
    }
}

// Log a User In
function loginUser($username, $password) {
    global $db;

    // Fetch user data
    $stmt = $db->prepare("SELECT id, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        return ['status' => 'error', 'message' => 'Invalid username or password.'];
    }

    $stmt->bind_result($user_id, $hashed_password);
    $stmt->fetch();
    $stmt->close();

    // Verify password
    if (!password_verify($password, $hashed_password)) {
        return ['status' => 'error', 'message' => 'Invalid username or password.'];
    }

    // Store session
    $_SESSION['user_id'] = $user_id;

    return ['status' => 'success', 'message' => 'Login successful.', 'redirect' => '/'];

}

// Log Out User
function logoutUser() {
    session_destroy();
    redirect("/");
}

// Function to Fetch Any Setting from `settings` Table
function getSetting($name, $default = null) {
    global $db;
    $result = $db->query("SELECT value FROM settings WHERE name = '$name' LIMIT 1");
    return ($result->num_rows > 0) ? $result->fetch_assoc()['value'] : $default;
}

// Extract numeric segments from the hash (respects limit)
function extractWinningSegments($hash, $tier, $limit = 20) {
    $numericOnly = preg_replace('/\D/', '', $hash); // Remove all non-numeric characters
    $segments = [];

    // Ensure we extract at most $limit, never exceeding 20 per tier
    $maxExtract = min($limit, 20); 

    for ($i = 0; $i < $maxExtract; $i++) {
        if (strlen($numericOnly) < 10) break;
        $segment = substr($numericOnly, ($i % (strlen($numericOnly) - 10)), 10);
        
        if (!in_array($segment, $segments)) { // Avoid duplicate extractions
            $segments[] = ["tier" => $tier, "segment" => $segment];
        }
    }

    return $segments;
}

function getRewardPrefix() {
    global $db;
    $prefix_row = $db->query("SELECT value FROM settings WHERE name = 'reward_prefix' LIMIT 1")->fetch_assoc();
    return $prefix_row ? $prefix_row['value'] : '';
}

function getRewardSuffix() {
    global $db;
    $suffix_row = $db->query("SELECT value FROM settings WHERE name = 'reward_suffix' LIMIT 1")->fetch_assoc();
    return $suffix_row ? $suffix_row['value'] : '';
}

function getTicketMiddleText() {
    global $db;
    $result = $db->query("SELECT value FROM settings WHERE name = 'ticket_middle_text' LIMIT 1");
    $row = $result->fetch_assoc();
    return $row ? $row['value'] : ':Bought-Ticket-';
}

?>