<?php

/**
 * Universal Fairness Protocol (UFP)
 * Dual Licensed under GPLv3 and Commercial License
 * Author: DgtalFairness | https://universalfairnessprotocol.com
 */

session_start();
require_once('../db/database.php');

// Dynamic Meta Tags (Customize for your implementation)
$page_title = "Login";
$page_description = "";  // Add your page description here
$page_keywords = "";     // Add your page keywords here

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $db->prepare("SELECT id, password, is_admin FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['is_admin'] = $user['is_admin'];

        header('Location: /');

        exit;
    } else {
        $error = "Invalid login details.";
    }
}

include('header.php');
?>

<div class="container my-5">
    <h2 class="text-center mb-4">🔐 Login</h2>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="w-50 mx-auto">
        <div class="mb-3">
            <label for="username">Username</label>
            <input id="username" type="text" name="username" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="password">Password</label>
            <input id="password" type="password" name="password" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary w-100">Login</button>
    </form>
</div>

<?php include('footer.php'); ?>
</body>
</html>
