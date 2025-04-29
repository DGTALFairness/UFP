<?php

/**
 * Universal Fairness Protocol (UFP)
 * Dual Licensed under GPLv3 and Commercial License
 * Author: DgtalFairness | https://universalfairnessprotocol.com
 */

// Ftch from settings_lottery_addon table (fallback to defaults if not found)
$settings = [];
$query = $db->query("SELECT name, value FROM settings_lottery_addon WHERE name IN ('ticket_price', 'ticket_fee')");
while ($row = $query->fetch_assoc()) {
    $settings[$row['name']] = $row['value'];
}

$ticket_price = isset($settings['ticket_price']) && is_numeric($settings['ticket_price']) ? $settings['ticket_price'] : 11.00;
$ticket_fee   = isset($settings['ticket_fee']) && is_numeric($settings['ticket_fee']) ? $settings['ticket_fee'] : 22.00;

// Format Ticket Price and Fee using admin-configured prefix/suffix
$priceFormatted = number_format($ticket_price, 2);
$feeFormatted   = number_format($ticket_fee, 2);
$zeroFormatted  = number_format(0, 2);

if ($prefix_or_suffix == 1) {
    $formattedPrice = $reward_prefix . $priceFormatted;
    $formattedFee   = $reward_prefix . $feeFormatted;
    $initialTotalCost = $reward_prefix . $zeroFormatted;
} elseif ($prefix_or_suffix == 2) {
    $formattedPrice = $priceFormatted . ' ' . $reward_suffix;
    $formattedFee   = $feeFormatted . ' ' . $reward_suffix;
    $initialTotalCost = $zeroFormatted . ' ' . $reward_suffix;
} else {
    $formattedPrice = $priceFormatted;
    $formattedFee   = $feeFormatted;
    $initialTotalCost = $zeroFormatted;
}
?>

<!-- 🎟️ Lottery Ticket Widget -->
<div class="card shadow-sm mb-4">
    <div class="card-header top-bar-header text-white text-center">
        🎟 ️Buy Tickets
    </div>

    <div class="card-body text-center bg-light-gray">
        
        <div id="messageBox" class="mb-4"></div>

        <!-- Round Details -->
        <p><b>Round #:</b> <span id="roundNumber"><?= $active_round['round_id'] ?? 'N/A' ?></span></p>
        <p><b>Total Tickets Sold:</b> <span id="totalTickets"><?= number_format($active_round['tickets_purchased'] ?? 0) ?></span></p>
        <p><b>Current Prize Pool:</b> <span id="prizePool">Loading...</span></p>
        <p><b>Ticket Price:</b> <?= $formattedPrice ?></p>
        <p><b>Ticket Fee:</b> <?= $formattedFee ?></p>
        <p><b>Ends:</b> <span id="countdown">Loading...</span></p>


        <!-- Tcket Purchase Form -->
        <?php if ($user_id): ?>
            <form id="buyTicketForm" method="POST">
                <div class="row g-2 justify-content-center">
                    <div class="col-auto">
                        <input 
                            type="number" 
                            name="tickets_amount" 
                            id="home_tickets_amount" 
                            class="form-control" 
                            placeholder="1-200" 
                            min="1" 
                            max="200" 
                            required 
                            data-ticket-price="<?= $ticket_price ?>">
                    </div>
                    <div class="col-auto">
                        <button type="submit" name="buy_ticket" class="btn btn-warning text-dark">
                            🎟 Buy Ticket
                        </button>
                    </div>
                </div>
            </form>
            <p class="mt-2"><b>Total Cost:</b> <span id="home_totalCost"><?= $initialTotalCost ?></span></p>
        <?php else: ?>
            <p class="text-muted mt-3">🔒 <a href="template/login.php">Login</a> to buy tickets.</p>
        <?php endif; ?>
    </div>
</div>

<script>
function formatReward(value) {
    const prefixOrSuffix = window.rewardConfig?.prefix_or_suffix || 0;
    const prefix = window.rewardConfig?.reward_prefix || "";
    const suffix = window.rewardConfig?.reward_suffix || "";

    const num = parseFloat(value);
    const formatted = isNaN(num) ? value : num.toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });

    if (prefixOrSuffix === 1) return `${prefix}${formatted}`;
    if (prefixOrSuffix === 2) return `${formatted} ${suffix}`;
    return formatted;
}
</script>

<script src="/addons/lottery_sim/assets/js/lottery.js"></script>