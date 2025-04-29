<?php

/**
 * Universal Fairness Protocol (UFP)
 * Dual Licensed under GPLv3 and Commercial License
 * Author: DgtalFairness | https://universalfairnessprotocol.com
 */

require_once('db/database.php');
require_once('system/functions/functions.php');
require_once(__DIR__ . '/system/functions/home_helper.php');

// Dynamic Meta Tags (Customize for your implementation)
$page_title = "Home";
$page_description = "";  // Add your page description here
$page_keywords = "";     // Add your page keywords here


// ✅ Settings for JS config
$prefix_or_suffix = (int)getSetting('prefix_or_suffix');
$reward_prefix = getSetting('reward_prefix') ?? '';
$reward_suffix = getSetting('reward_suffix') ?? '';
$use_custom_rewards = (int)getSetting('use_custom_rewards');

// ✅ Reward Labels — safely loaded
$reward_labels = [];
$raw = getSetting('reward_labels');
if ($raw) {
    $decoded = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $reward_labels = $decoded;
    }
}

// ✅ Include header LAST so meta vars are used
include('template/header.php');
?>

<script>
window.rewardConfig = {
  prefix_or_suffix: <?= (int) $prefix_or_suffix ?>,
  reward_prefix: <?= json_encode($reward_prefix) ?>,
  reward_suffix: <?= json_encode($reward_suffix) ?>,
  use_custom_rewards: <?= (int) $use_custom_rewards ?>,
  reward_labels: <?= json_encode($reward_labels ?? []) ?>
};
</script>

    <div class="container my-5">
            <h3 class="text-center mb-4">📊 Home</h3>
            <div class="card shadow-sm mb-4">
              <div class="card-header top-bar-header text-white text-center">
                🔐 Welcome to Your Activity Hub
              </div>
            
              <div class="card-body text-center">
                <p class="mb-2">This page allows you to monitor live platform activity, including the <strong>Active Round</strong> details and the <strong>Live Ticket List</strong> as it updates in real time.</p>
                <p class="mb-2">View the <strong>Prize Breakdown</strong> for each round, keep track of <strong>Ticket Stats</strong>, and see <strong>Recent Winners</strong> as rounds are finalized.</p>
                <p class="mb-0">All data is refreshed automatically to ensure up-to-date results throughout your session.</p>
              </div>
            </div>

        
        
        <!-- 🔌 Addon Widgets Section -->
            <div class="card shadow-sm mb-4">
                <div class="card-header top-bar-header text-white text-center">
                    🔌 Tickets
                </div>
                <div class="card-body">
                    <?php
                    require_once(__DIR__ . '/db/database.php');
            
                    $addonDir = __DIR__ . '/addons/';
                    $widgetsLoaded = 0;
            
                    // ✅ Get all active addons from DB
                    $activeAddons = [];
                    $result = $db->query("SELECT addon_name FROM active_addons WHERE is_active = 1");
                    if ($result) {
                        while ($row = $result->fetch_assoc()) {
                            $activeAddons[] = $row['addon_name'];
                        }
                    }
            
                   // ✅ Loop through active addons only
                    foreach ($activeAddons as $addon) {
                        $widgetPath = $addonDir . $addon . '/widgets/';
                    
                        if (is_dir($widgetPath)) {
                            foreach (scandir($widgetPath) as $widgetFile) {
                                if ($widgetFile === '.' || $widgetFile === '..') continue;
                    
                                $fullPath = $widgetPath . $widgetFile;
                    
                                // ✅ Only include PHP files that contain "widget" in the filename
                                if (
                                    is_file($fullPath) &&
                                    pathinfo($widgetFile, PATHINFO_EXTENSION) === 'php' &&
                                    stripos($widgetFile, 'widget') !== false
                                ) {
                                    echo "<!-- ✅ Injected Widget: $addon/widgets/$widgetFile -->\n";
                                    include($fullPath);
                                    $widgetsLoaded++;
                                }
                            }
                        }
                    }

            
                    if ($widgetsLoaded === 0) {
                        echo "<p class='text-center text-muted'>No active addons with widgets found. Add widgets under <code>/addons/{addon_name}/widgets/</code></p>";
                    }
                    ?>
                </div>
            </div>
            
            <!-- ✅ Fade out success/error messages if any -->
            <div id="messageBox" class="mb-4"></div>
            <script>
                $(document).ready(function () {
                    if ($("#messageBox").text().trim().length > 0) {
                        setTimeout(function () {
                            $("#messageBox").fadeOut("slow");
                        }, 500);
                    }
                });
            </script>

        
        <!-- Active Round -->
        <div class="card shadow-sm mb-4 border-0">
            <div class="card-header top-bar-header text-white text-center rounded-top">
                🔄 Active Round
            </div>

            <div class="card-body text-center bg-light-gray">
                <p><b>Round #:</b> <span id="roundNumberHome"><?= $active_round['round_id'] ?? 'N/A' ?></span></p>
                <p><b>Total Entries:</b> <span id="totalTicketsHome">Loading...</span></p>
                <p><b>Current Rewards:</b> <span id="prizePoolHome">Loading...</span></p>
                <p><b>Ends:</b> <span id="countdownHome"><?= $expected_end_date ? date('Y-m-d H:i:s', $expected_end_date) : 'N/A' ?></span></p>
        
                <?php if (!$user_id): ?>
                    <p class="text-muted mt-3">🔒 <a href="template/login.php">Login</a> to participate in this round.</p>
                <?php endif; ?>
                
                <!-- Live Ticket List -->
                <div class="card shadow-sm mb-5 border-0">
                    <div class="card-header top-bar-header text-white text-center rounded-top">
                        📜 Live Ticket List
                    </div>
        
                    <div class="card-body bg-light-gray">
                        <textarea id="liveTicketList" class="form-control" rows="10" readonly>Loading live ticket list...</textarea>
                        <button onclick="copyLiveTickets()" class="btn btn-warning mt-2">📋 Copy</button>
                    </div>
                </div>
            </div>
        </div>


       <!-- Prize Breakdown -->
        <div class="card shadow-sm mb-4 border-0">
            <div class="card-header top-bar-header text-white text-center rounded-top">
                📊 Prize Breakdown for Round #<span id="prizeBreakdownRound">...</span>
            </div>


            <div class="card-body bg-light-gray">
                <div style="max-height: 410px; overflow-y: auto;">
                    <ul class="list-group" id="prizeBreakdownList">
                        <li class="list-group-item text-center text-muted">Loading prize breakdown...</li>
                    </ul>
                </div>
        
                <li class="list-group-item d-flex justify-content-between bg-light">
                    <strong>Total Prize Pool:</strong>
                    <strong id="totalPrizePool">
                    <?= $use_custom_rewards ? '🎁 Varies' : number_format($active_round['prize'] ?? 0, 2) ?>
                    </strong>

                </li>

            </div>
        </div>


        <!-- Ticket Stats -->

        <!-- Empty state alert: shown by default -->
        <div class="alert alert-warning text-center mb-4" id="ticketStatsEmpty">
            No ticket purchases in the current round yet.
        </div>
        
        <!-- Stats card: hidden by default -->
        <div class="card shadow-sm mb-4 border-0" id="ticketStatsCard" style="display: none;">
            <div class="card-header top-bar-header text-white text-center rounded-top">
                🎟 Round Ticket Purchases for Round <span id="ticketStatsRoundNumber">#?</span>
            </div>

            <div class="table-responsive" style="max-height: 410px; overflow-y: auto;">
                <table class="table table-striped table-hover text-center mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>#</th>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Total Tickets</th>
                        </tr>
                    </thead>
                    <tbody id="ticketStatsBody">
                        <!-- Populated dynamically via JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>


        <!-- Recent Winners -->
        <div class="card shadow-sm mb-4 border-0">
            <div class="card-header top-bar-header text-white position-relative rounded-top">
            <div class="text-center w-100">
                🏆 Recent Winners
            </div>

            <div class="position-absolute top-50 end-0 translate-middle-y me-3">
                <button id="refreshWinners" class="btn btn-sm btn-warning text-dark">🔁 Refresh</button>
            </div>
        </div>


            <div class="table-responsive" style="max-height: 410px; overflow-y: auto;">
                <table class="table table-striped table-hover text-center mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Round #</th>
                            <th>Position</th>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Winning Ticket</th>
                            <th>Total Tickets</th>
                            <th>Prize</th>
                            <th>Closed</th>
                        </tr>
                    </thead>
                    <tbody id="winnersTableBody">
                        <?php if (empty($winners)): ?>
                            <tr><td colspan="8">No winners yet!</td></tr>
                        <?php else: ?>
                            <?php foreach ($winners as $winner): ?>
                                <tr>
                                    <td><span class="badge bg-dark text-white"><?= number_format($winner['round_id']) ?></span></td>
                                    <td><span class="badge bg-dark text-white">#<?= $winner['position'] ?></span></td>
                                    <td><span class="badge bg-dark text-white"><?= $winner['user_id'] ?? '-' ?></span></td>
                                    <td><span class="badge bg-dark text-white"><?= htmlspecialchars($winner['username'] ?? 'Anonymous') ?></span></td>
                                    <td><span class="badge bg-dark text-white">#<?= $winner['winning_ticket'] ?></span></td>
                                    <td><span class="badge bg-dark text-white"><?= number_format($winner['winner_tickets']) ?></span></td>
                            
                                    <td>
                                        <span class="badge bg-dark text-white">
                                            <?php
                                            if (!empty($winner['use_custom_rewards']) && $winner['use_custom_rewards']) {
                                                $labelIndex = $winner['position'] - 1;
                                                $label = $reward_labels[$labelIndex] ?? '🎁 Reward';
                                                echo htmlspecialchars($label);
                                            } else {
                                                $formattedReward = number_format((float)$winner['reward_received'], 2);
                                                if ($prefix_or_suffix == 1) {
                                                    $formattedReward = $reward_prefix . $formattedReward;
                                                } elseif ($prefix_or_suffix == 2) {
                                                    $formattedReward = $formattedReward . ' ' . $reward_suffix;
                                                }
                                                echo $formattedReward;
                                            }
                                            ?>
                                        </span>
                                    </td>
                            
                                    <td><span class="badge bg-dark text-white"><?= timeAgo($winner['end_date']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>

                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const expectedEndDate = <?= $expected_end_date ?>;
    </script>
    
    <script>
    document.addEventListener("DOMContentLoaded", function () {
      if (typeof startCountdown === "function" && expectedEndDate > 0) {
        startCountdown(expectedEndDate);
      }
    });
    </script>

    <script src="/assets/js/sync.js"></script>
    <script src="/assets/js/home.js"></script>
    
<?php include('template/footer.php'); ?>

</body>
</html>