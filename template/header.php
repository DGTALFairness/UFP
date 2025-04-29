<?php

/**
 * Universal Fairness Protocol (UFP)
 * Dual Licensed under GPLv3 and Commercial License
 * Author: DgtalFairness | https://universalfairnessprotocol.com
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/../db/database.php');
require_once(__DIR__ . '/../system/functions/functions.php');
require_once(__DIR__ . '/../system/functions/header_helper.php');

// Dynamic Meta Tags (Customize for your implementation)
$page_title = "Home";
$page_description = "";  // Add your page description here
$page_keywords = "";     // Add your page keywords here

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($page_keywords) ?>">

    <!-- Bootstrap 5.3.3 (Latest) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <link rel="stylesheet" href="/assets/css/custom.css">

    <!-- JQuery (still needed for legacy code) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>

<body>

<!-- Top Navigation Bar with User Info -->
<nav class="navbar navbar-expand-lg top-nav-bar-header mb-0">
  <div class="container">
    <a class="navbar-brand" href="/">
      <img src="/assets/img/logo-placeholder.png" alt="Placeholder Logo" style="height: 90px;">
    </a>


    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#ufpNav" aria-controls="ufpNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="ufpNav">
     <ul class="navbar-nav me-auto mb-2 mb-lg-0">
      <li class="nav-item">
          <a class="nav-link text-light" href="/">🏠 Home</a>
        </li>
      <li class="nav-item">
        <a class="nav-link text-light" href="/template/mystats.php">📈 My Stats</a>
      </li>
      <li class="nav-item">
        <a class="nav-link text-light" href="/template/verify_rounds.php">🔍 Verify Round</a>
      </li>
      <?php if ($is_admin == 1): ?>
      <li class="nav-item">
        <a class="nav-link text-warning" href="/admin/settings.php">⚙️ Admin</a>
      </li>
      <?php endif; ?>
    </ul>

      <?php if ($user): ?>
        <span class="navbar-text text-white me-3">
    👤 <b>User:</b> ID:<?= (int)$user['id'] ?>/<?= htmlspecialchars($user['username']) ?> | 💰 

    <?php
    $balanceFormatted = number_format($user_balance, 2);
    if ($prefix_or_suffix == 1) {
        $displayBalance = $reward_prefix . $balanceFormatted;
    } elseif ($prefix_or_suffix == 2) {
        $displayBalance = $balanceFormatted . ' ' . $reward_suffix;
    } else {
        $displayBalance = $balanceFormatted;
    }
    ?>
    
    <span id="userBalance"><?= $displayBalance ?></span>
    | 🎟 <span id="userTickets"><?= number_format($user_tickets) ?></span> Tickets 
    | 🏆 <span id="userWins"><?= number_format($total_winning_rounds) ?></span> Wins
    </span>

        <a href="/template/logout.php" class="btn btn-sm btn-outline-light">Logout</a>
          <?php else: ?>
            <form class="d-flex" method="POST" action="/template/login.php">
              <input class="form-control me-2" type="text" name="username" placeholder="Username" required>
              <input class="form-control me-2" type="password" name="password" placeholder="Password" required>
              <button class="btn btn-primary me-2" type="submit">Login</button>
              <a href="/template/register.php" class="btn btn-secondary">Register</a>
            </form>
          <?php endif; ?>
    </div>
  </div>
</nav>