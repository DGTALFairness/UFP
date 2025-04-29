<?php

/**
 * Universal Fairness Protocol (UFP)
 * Dual Licensed under GPLv3 and Commercial License
 * Author: DgtalFairness | https://universalfairnessprotocol.com
 */

session_start();
require_once('../db/database.php');
require_once('../system/functions/functions.php');

// Dynamic Meta Tags (Customize for your implementation)
$page_title = "Register";
$page_description = "";  // Add your page description here
$page_keywords = "";     // Add your page keywords here

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    $response = registerUser($username, $password);

    if ($response['status'] === 'success') {
        $_SESSION['user_id'] = $response['user_id'];
        header("Location: /");
        exit();
    } else {
        $message = '<div class="alert alert-danger text-center">' . htmlspecialchars($response['message']) . '</div>';
    }
}

include('header.php');
?>

<div class="container my-5">
    <h2 class="text-center mb-4">📝 Register</h2>
    
    <?= $message ?>

    <form method="POST" class="w-50 mx-auto">
        <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" name="username" id="username" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" name="password" id="password" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary w-100">Register</button>
    </form>
</div>

<?php include('footer.php'); ?>
</body>
</html>
