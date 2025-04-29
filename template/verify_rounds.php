<?php

/**
 * Universal Fairness Protocol (UFP)
 * Dual Licensed under GPLv3 and Commercial License
 * Author: DgtalFairness | https://universalfairnessprotocol.com
 */

require_once('../db/database.php');
require_once('../system/functions/functions.php');
require_once(__DIR__ . '/../system/functions/verify_rounds_helper.php');

// Dynamic Meta Tags (Customize for your implementation)
$page_title = "Verify Rounds";
$page_description = "";  // Add your page description here
$page_keywords = "";     // Add your page keywords here

// Load Header (after setting meta tags)
include('header.php');
?>

<div class="container my-5">
    <h3 class="text-center mb-4">📈 Verify Rounds</h3>

    <div class="card shadow-sm mb-4">
        <div class="card-header top-bar-header text-white text-center">
            🔍 Ticket Lookup & Rehash Verification
        </div>
        <div class="card-body text-center">
            <p class="mb-2">Use this tool to <strong>view ticket statistics</strong> and <strong>inspect winning segments</strong> across all completed rounds.</p>
            <p class="mb-2">Each round supports full <strong>rehash chain validation</strong> and public winner reconstruction.</p>
            <p class="mb-0">Designed for transparent auditing using <strong>SHA-256 segment extraction</strong> — anyone can verify the outcome.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="my-3 p-3 bg-light rounded box-shadow box-style">
                <h3 class="text-center">Select a Past Round to View Tickets</h3>
                <div class="form-group">
                    <label for="roundSelect"><b>Select a Round:</b></label>
                    <select id="roundSelect" class="form-control">
                        <script>
                            const defaultRoundId = "<?= isset($_GET['round']) ? htmlspecialchars($_GET['round']) : '' ?>";
                        </script>
                        <option value="">-- Select Round --</option>
                        <?php foreach ($pastRounds as $round): ?>
                            <option value="<?= $round['id'] ?>">
                                Round #<?= $round['id'] ?>
                                (Ended: <?= !empty($round['end_date']) && is_numeric($round['end_date']) ? date('d M Y', (int)$round['end_date']) : 'N/A' ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group d-none" id="rehashContainer">
                    <label for="rehashSelect"><b>Select Rehash Tier:</b></label>
                    <select id="rehashSelect" class="form-control">
                        <option value="tier_1">Tier 1 (Original Hash)</option>
                    </select>
                </div>

                <div id="roundStats" class="mt-3 p-3 bg-light rounded d-none">
                    <h5 class="text-center">Round Statistics</h5>
                    <p><b>Total Tickets Sold:</b> <span id="totalTicketsStat">-</span></p>
                    <p><b>Ticket Range:</b> <span id="ticketRangeStat">-</span></p>
                    <p><b>First Ticket:</b> <span id="firstTicketStat">-</span></p>
                    <p><b>Last Ticket:</b> <span id="lastTicketStat">-</span></p>

                    <h5 class="text-center mt-3">Round Hash</h5>
                    <p><b>SHA-256 Hash:</b> <code id="ticketHash">-</code></p>

                    <h5 class="text-center mt-3">Winning Positions (Numeric Extraction)</h5>
                    <ul id="winningPositions" class="list-group"></ul>
                </div>

                <div class="form-group mb-2">
                    <label for="ticketList"><b>Copy the ticket list below:</b></label>
                    <textarea id="ticketList" class="form-control" rows="10" placeholder="Select a round to load ticket list..." readonly></textarea>
                </div>
                <button onclick="copyToClipboard()" class="btn btn-primary" disabled id="copyBtn">Copy to Clipboard</button>
                <button onclick="transferToVerification()" class="btn btn-secondary" id="verifyBtn" disabled>Add to Verify Lottery Winners</button>
            </div>

            <div class="card text-center text-dark mt-3 w-100">
                <div class="card-header">
                    <b>Verify Winners</b>
                </div>

                <div class="card-body bg-light">
                    <label for="verifyTicketList"><b>Paste Final Ticket List:</b></label>
                    <textarea id="verifyTicketList" class="form-control" rows="5" placeholder="Auto-filled based on round selection" readonly></textarea>

                    <label for="totalTickets" class="mt-3"><b>Total Tickets:</b></label>
                    <input type="number" id="totalTickets" class="form-control" placeholder="Auto-filled based on ticket data" readonly />

                    <label for="firstTicket" class="mt-3"><b>First Ticket Number:</b></label>
                    <input type="number" id="firstTicket" class="form-control" placeholder="Auto-filled from ticket list" readonly />

                    <label for="rehashTier" class="mt-3"><b>Rehash Tier:</b></label>
                    <input type="text" id="rehashTier" class="form-control" placeholder="Auto-filled based on selection" readonly />

                    <button onclick="verifyWinningTickets()" id="startVerifyBtn" class="btn btn-primary mt-3">Verify Winners</button>
                    <p id="winnerResult" class="mt-2"></p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
  <div class="card-header bg-light text-dark text-center">
    <h4 class="mb-0">🔐 Generate SHA-256 Hash from Ticket List</h4>
  </div>
  <div class="card-body bg-light border rounded">
    <p>This tool allows you to manually paste a ticket list and generate its SHA-256 hash. This lets you verify that a round’s ticket list has not been tampered with.</p>

    <div class="mb-3">
      <label for="ticketInput" class="form-label"><b>🎟 Paste Your Ticket List Below:</b></label>
      <textarea id="ticketInput" class="form-control" rows="8" placeholder="Paste full ticket list here..."></textarea>
    </div>

    <div class="mb-3 text-center">
      <button id="generateHashBtn" class="btn btn-primary">🔄 Generate SHA-256 Hash</button>
    </div>

    <div id="hashResultContainer" style="display: none;">
      <p class="mb-1"><b>✅ Generated SHA-256 Hash:</b></p>
      <div class="alert alert-success" id="hashResult" style="font-family: monospace; word-break: break-all;"></div>
    </div>
  </div>
</div>

<script src="/assets/js/verify.js"></script>
<?php include('footer.php'); ?>
</body>
</html>
