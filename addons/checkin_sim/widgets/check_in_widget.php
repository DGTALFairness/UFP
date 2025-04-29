<?php
/**
 * Universal Fairness Protocol (UFP)
 * Dual Licensed under GPLv3 and Commercial License
 * Author: DgtalFairness | https://universalfairnessprotocol.com
 */
?>

<!-- Check-In Widget -->
<div class="card shadow-sm mb-4">
    <div class="card-header top-bar-header text-white text-center">
        🎟 ️Check-in to Earn Tickets
    </div>

    <div class="card-body text-center bg-light-gray">
        <button id="checkinButton" class="btn btn-warning text-dark">Check In</button>
        <p id="checkinMessage" class="mt-3"></p>
        <p><b>Last Check-in:</b> <span id="lastCheckin">N/A</span></p>
        <p><b>Next Check-in in:</b> <span id="nextCheckin">N/A</span></p>
    </div>
</div>

<!-- Load JS -->
<script src="/addons/checkin_sim/assets/js/check_in.js"></script>
