<?php

/**
 * Universal Fairness Protocol (UFP)
 * Dual Licensed under GPLv3 and Commercial License
 * Author: DgtalFairness | https://universalfairnessprotocol.com
 */

require_once('../db/database.php');
require_once('../system/functions/functions.php');

// Safe fallback values for statistics
$total_tickets = 0;
$total_winnings = 0;
$total_rounds = 0;
$total_winning_rounds = 0;

// Dynamic Meta Tags (Customize for your implementation)
$page_title = "My Stats";
$page_description = "";  // Add your page description here
$page_keywords = "";     // Add your page keywords here

include('header.php');

$json_raw = getSetting('reward_labels');
$reward_labels = [];

if (!empty($json_raw)) {
    $decoded = json_decode($json_raw, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $reward_labels = $decoded;
    }
}

?>

<div class="container my-5">
    <h3 class="text-center mb-4">📊 My Stats</h3>

    <!-- Intro -->
    <div class="card shadow-sm mb-4">
        <div class="card-header top-bar-header text-white text-center">
            🧹 Personal Fairness Overview
        </div>
        <div class="card-body text-center">
            <p class="mb-2">Track your <strong>total tickets</strong>, <strong>winnings</strong>, and <strong>participation history</strong> across all completed rounds.</p>
            <p class="mb-2">Review your current round performance and past round contributions in a transparent and detailed format.</p>
            <p class="mb-0">This page reflects your activity within the <strong>Universal Fairness Protocol</strong> in real time.</p>
        </div>
    </div>

    <!-- Global Statistics -->
    <div class="card shadow-sm mb-4">
        <div class="card-header top-bar-header text-white text-center">
            📊 Global Statistics
        </div>
        <div class="card-body text-center bg-light-gray">
            <p><b>Total Tickets:</b> <?= number_format($total_tickets) ?></p>
            <p><b>Total Winnings:</b> <?= number_format($total_winnings, 2) ?> Points</p>
            <p><b>Rounds Participated:</b> <?= number_format($total_rounds) ?></p>
            <p><b>Winning Rounds:</b> <?= number_format($total_winning_rounds) ?></p>
        </div>
    </div>

    <!-- Current Round Participation -->
    <div class="card shadow-sm mb-4">
        <div class="card-header top-bar-header text-white text-center">
            🎟 ️Current Round
        </div>
        <div class="card-body text-center bg-light-gray">
            <p><b>Round ID:</b> <?= $current_round['round_id'] ?? 'No Active Round' ?></p>
            <p><b>Total Check-ins:</b> <?= number_format($user_current_tickets) ?></p>
            <p><b>Expected End:</b> <?= $current_round['expected_end_date'] ? date('Y-m-d H:i:s', $current_round['expected_end_date']) : 'N/A' ?></p>
        </div>
    </div>

    <!-- Past Rounds -->
    <div class="card shadow-sm mb-4">
        <div class="card-header top-bar-header text-white text-center">
            🗓 ️Recent Tickets
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 410px; overflow-y: auto;">
                <table class="table table-hover table-striped table-light text-center mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Round ID</th>
                            <th>Total Check-ins</th>
                            <th>Ticket IDs</th>
                            <th>Verify</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ended_rounds as $round): ?>
                            <tr>
                                <td><span class="badge bg-dark text-white"><?= $round['round_id'] ?></span></td>
                                <td><span class="badge bg-dark text-white"><?= $round['tickets_bought'] ?></span></td>
                                <td class="text-wrap text-primary small"><?= htmlspecialchars($round['ticket_ids']) ?></td>
                                <td>
                                    <a href="/template/verify_rounds.php?round=<?= $round['round_id'] ?>" class="btn btn-sm btn-muted">🔍 Verify</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Winning Rounds -->
    <div class="card shadow-sm mb-4">
        <div class="card-header top-bar-header text-white text-center">
            🏆 Past Winning Rounds
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 410px; overflow-y: auto;">
                <table class="table table-hover table-striped table-light text-center mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Round ID</th>
                            <th>Total Winning Tickets</th>
                            <th>Winning Ticket IDs</th>
                            <th>Total Prize</th>
                            <th>Verify</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($winning_rounds as $round): ?>
                            <?php
                            $ticket_ids = array_map('trim', explode(',', $round['ticket_ids']));
                            $formattedRewards = [];
                            $payoutTotal = 0;

                            foreach ($ticket_ids as $ticket_id) {
                                $ticket_id = (int)$ticket_id;
                                $query = $db->query("SELECT reward_label, reward_amount, position FROM winning_round_tickets WHERE ticket_id = '{$ticket_id}' LIMIT 1");

                                if ($query && $query->num_rows > 0) {
                                    $row = $query->fetch_assoc();

                                    if (!empty($round['use_custom_rewards']) && $round['use_custom_rewards'] == 1) {
                                        $formattedRewards[] = $row['reward_label'] ?? '🎁';
                                    } else {
                                        $amountRaw = (float)$row['reward_amount'];
                                        $payoutTotal += $amountRaw;

                                        $amountFormatted = number_format($amountRaw, 2);
                                        if ($prefix_or_suffix == 1) {
                                            $formattedRewards[] = $reward_prefix . $amountFormatted;
                                        } elseif ($prefix_or_suffix == 2) {
                                            $formattedRewards[] = $amountFormatted . ' ' . $reward_suffix;
                                        } else {
                                            $formattedRewards[] = $amountFormatted;
                                        }
                                    }
                                }
                            }

                            if (!empty($formattedRewards)) {
                                if (!empty($round['use_custom_rewards']) && $round['use_custom_rewards'] == 1) {
                                    $finalRewardDisplay = implode(', ', $formattedRewards);
                                } else {
                                    $totalFormatted = number_format($payoutTotal, 2);
                                    if ($prefix_or_suffix == 1) {
                                        $totalFormatted = $reward_prefix . $totalFormatted;
                                    } elseif ($prefix_or_suffix == 2) {
                                        $totalFormatted = $totalFormatted . ' ' . $reward_suffix;
                                    }
                                    $finalRewardDisplay = implode(', ', $formattedRewards) . " <small>(Total: {$totalFormatted})</small>";
                                }
                            } else {
                                $finalRewardDisplay = 'N/A';
                            }
                            ?>
                            <tr>
                                <td><span class="badge bg-dark text-white"><?= $round['round_id'] ?></span></td>
                                <td><span class="badge bg-dark text-white"><?= $round['winning_tickets'] ?></span></td>
                                <td class="text-wrap small text-primary"><?= htmlspecialchars($round['ticket_ids']) ?></td>
                                <td><span class="badge bg-dark text-white"><?= $finalRewardDisplay ?></span></td>
                                <td>
                                    <a href="/template/verify_rounds.php?round=<?= $round['round_id'] ?>" class="btn btn-sm btn-muted">🔍 Verify</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>
</body>
</html>
