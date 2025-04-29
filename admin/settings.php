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
$page_title = "Admin Settings";
$page_description = "";  // Add your page description here
$page_keywords = "";     // Add your page keywords here


include('../template/header.php');


// Check if user is logged in and is an admin
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    die("❌ Access Denied. You must be logged in.");
}

$user_query = $db->query("SELECT is_admin FROM users WHERE id = '" . (int)$user_id . "'");
$user = $user_query->fetch_assoc();

if (!$user || $user['is_admin'] != 1) {
    die("❌ Access Denied. Admins only.");
}

// Fetch current settings from the database
$settings = [];
$result = $db->query("SELECT name, value FROM settings");
while ($row = $result->fetch_assoc()) {
    $settings[$row['name']] = $row['value'];
}

// Set default values in case they are missing
$round_duration = $settings['round_duration'] ?? '60'; // Default to 60 minutes
$round_duration_type = $settings['round_duration_type'] ?? 'minutes';
$starting_prize = $settings['starting_prize'] ?? '10.00';

// Fetch number of winners and payouts from the settings table
$number_of_winners = isset($settings['number_of_winners']) ? (int)$settings['number_of_winners'] : 0;
$payouts_array = json_decode($settings['payouts'], true);
if (!is_array($payouts_array)) {
    $payouts_array = array_fill(0, $number_of_winners, round(100 / $number_of_winners, 2));
}

$ticket_middle_text = getSetting('ticket_middle_text', ':Bought-Ticket-');

?>

<div class="container mt-5">
    <div class="container mt-5 text-center">
        <a href="../" class="btn btn-secondary">⬅️ Back to Home</a>
    </div>

    
    <h2 class="text-center">⚙️ Admin Dashboard - Settings</h2>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success_message']; ?></div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger"><?= $_SESSION['error_message']; ?></div>
    <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>


<!-- Settings Table -->
<div class="card mt-4">
    <div class="card-header top-bar-header text-white">Manage Settings</div>
    <div class="card-body">
        <p>Here you can modify system settings such as ticket price, round duration, and starting prize.</p>

        <!-- Settings Form (Pre-Filled with Existing Values) -->
        <form method="POST" action="update_settings.php">

            <div class="mb-3">
                <label for="round_duration" class="form-label">Round Duration:</label>
                <div class="row">
                    <div class="col-md-6">
                        <input type="number" name="round_duration" id="round_duration" class="form-control" value="<?= intval($round_duration) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <select name="round_duration_type" id="round_duration_type" class="form-control">
                            <option value="minutes" <?= ($round_duration_type === 'minutes') ? 'selected' : '' ?>>Minutes</option>
                            <option value="hours" <?= ($round_duration_type === 'hours') ? 'selected' : '' ?>>Hours</option>
                            <option value="days" <?= ($round_duration_type === 'days') ? 'selected' : '' ?>>Days</option>
                            <option value="weeks" <?= ($round_duration_type === 'weeks') ? 'selected' : '' ?>>Weeks</option>
                            <option value="months" <?= ($round_duration_type === 'months') ? 'selected' : '' ?>>Months</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label for="starting_prize" class="form-label">Positions Starting Reward Value:</label>
                <input type="number" step="0.01" name="starting_prize" id="starting_prize" class="form-control" value="<?= htmlspecialchars($starting_prize) ?>" required>
            </div>

            
            <hr class="my-4">
            
           <!-- Choose Prefix or Suffix using Slider -->
            <div class="mb-3">
                <label for="prefix_or_suffix" class="form-label">Choose Reward Type:</label>
                <!-- Slider/Toggle to choose Prefix or Suffix -->
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="prefixSuffixToggle" 
                           <?= ($settings['prefix_or_suffix'] == 2) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="prefixSuffixToggle">Prefix (Left) / Suffix (Right)</label>
                </div>
            </div>
            
            <input type="hidden" name="prefix_or_suffix" id="prefix_or_suffix" value="<?= ($settings['prefix_or_suffix'] == 2) ? 2 : 1 ?>">

            
            <!-- Show Prefix and Suffix Fields (Both Visible, Switchable via the slider) -->
            <div class="row mb-3">
                <!-- Reward Prefix Field -->
                <div class="col-md-6" id="prefixField">
                    <label for="reward_prefix" class="form-label">
                        Reward Prefix
                        <small>(Only symbols, no letters or numbers — 1–12 characters, no spaces)</small>
                    </label>
                    <input type="text" name="reward_prefix" id="reward_prefix" class="form-control" maxlength="12" 
                           value="<?= htmlspecialchars($settings['reward_prefix'] ?? '$') ?>" required>
                </div>
                
                <!-- Reward Suffix Field -->
                <div class="col-md-6" id="suffixField">
                    <label for="reward_suffix" class="form-label">Reward Suffix (letters only):</label>
                    <input type="text" name="reward_suffix" id="reward_suffix" class="form-control" maxlength="12" 
                           value="<?= htmlspecialchars($settings['reward_suffix'] ?? '') ?>" required>
                </div>
            </div>
            
            <!-- Reward Prefix Help Text -->
            <small id="reward_prefix_help" class="form-text text-muted mt-1">
                Example: <code id="prefix_preview">
                    10.00<?= preg_match('/^[a-zA-Z]+$/', $settings['reward_prefix'] ?? '$') 
                        ? ' ' . htmlspecialchars($settings['reward_prefix']) 
                        : htmlspecialchars($settings['reward_prefix']) ?>
                </code>
            </small>
            
            <!-- Error Message for Prefix -->
            <div id="prefix_error" class="text-danger mt-1 d-none">
                ❌ Invalid Reward Prefix. Use 1–12 characters: letters, numbers, or symbols like $, #, ₳, ¥, €, £, %. No spaces.
            </div>

            <hr class="my-4">
            
            <div class="mb-3">
              <label for="ticket_middle_text" class="form-label">
                Ticket Middle Text
                <small>
                  (Only letters, numbers, dashes, or underscores. Must be 3–40 characters.
                  <br>Use dashes <code>-</code> instead of spaces, but do <strong>not</strong> start or end with a dash.)
                </small>:
              </label>
              <input type="text" name="ticket_middle_text" id="ticket_middle_text" 
                     class="form-control" 
                     value="<?= htmlspecialchars($ticket_middle_text ?? 'Bought-Ticket') ?>" 
                     required>
              <small id="ticket_middle_help" class="form-text text-muted mt-1">
                Example: <code id="ticket_preview">User-ID-5:<?= htmlspecialchars($ticket_middle_text ?? 'Bought-Ticket') ?>-17</code>
              </small>
              <div id="ticket_middle_error" class="text-danger mt-1 d-none">
                ❌ Invalid format. Use only letters, numbers, dashes, or underscores (3–40 chars).
                No spaces allowed. Cannot start or end with a dash.
              </div>
            </div>

            <button type="submit" class="btn btn-primary w-100">Save Changes</button>
        </form>
    </div>
</div>
    
    <!-- Winner & Payout Settings -->
<div class="card mt-4">
    <div class="card-header top-bar-header text-white">⚙️ Winner & Payout Settings</div>
    <div class="card-body">
        
        <!-- Reward Mode Toggle -->
        <div class="form-check form-switch mb-3">
          <input class="form-check-input" type="checkbox" id="useRewardModeToggle" <?= (isset($settings['use_custom_rewards']) && $settings['use_custom_rewards'] == 1) ? 'checked' : '' ?>>
          <label class="form-check-label" for="useRewardModeToggle">
            Use Reward Labels instead of Payout Percentages
          </label>
        </div>
        <input type="hidden" name="use_custom_rewards" id="use_custom_rewards" value="<?= (isset($settings['use_custom_rewards']) && $settings['use_custom_rewards'] == 1) ? 1 : 0 ?>">

        
        <label for="number_of_winners"><b>Number of Winners:</b></label>
        <input type="number" id="number_of_winners" class="form-control" min="1" max="100" value="<?= htmlspecialchars($number_of_winners) ?>">

        <h5 class="mt-3" id="payoutOrRewardHeading">
            <?= (isset($settings['use_custom_rewards']) && $settings['use_custom_rewards'] == 1) ? 'Position Rewards' : 'Payout Percentages' ?>
        </h5>
        <table class="table table-bordered" id="payoutTable">
            <thead>
                <tr>
                    <th>Position</th>
                    <th id="payoutOrRewardLabel"><?= (isset($settings['use_custom_rewards']) && $settings['use_custom_rewards'] == 1) ? 'Reward' : 'Payout %' ?></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>

        <p><b>Total Payout:</b> <span id="totalPayout">100%</span></p>

        <!-- Generate JSON Button -->
        <button type="button" class="btn btn-success w-100" id="generateJsonBtn" onclick="generatePayoutJSON()">📋 Generate JSON</button>
    </div>
</div>

<!-- Raw JSON Input for Saving -->
<div class="card mt-4">
    <div class="card-header top-bar-header text-white">🛠 Save Raw Payout Data</div>
    <div class="card-body">
        <form method="POST" action="update_payouts.php">
            <!-- Hidden input to track use_custom_rewards -->
            <input type="hidden" name="use_custom_rewards" id="hidden_custom_reward_flag" value="<?= (isset($settings['use_custom_rewards']) && $settings['use_custom_rewards'] == 1) ? 1 : 0 ?>">

            <div class="mb-3">
                <label for="json_number_of_winners" class="form-label">Number of Winners:</label>
                <input type="number" name="number_of_winners" id="json_number_of_winners" class="form-control" readonly required>
            </div>

            <div class="mb-3">
                <label for="json_payouts" class="form-label">Payouts (JSON Format):</label>
                <input type="text" name="payouts" id="json_payouts" class="form-control" readonly placeholder="Click 'Generate JSON' to fill this" required>
            </div>

            <!-- Reward Labels (only visible in reward mode) -->
            <div class="mb-3" id="reward_labels_section" style="display: none;">
                <label for="json_reward_labels" class="form-label">Reward Labels (JSON Format):</label>
                <input type="text" name="reward_labels" id="json_reward_labels" class="form-control" readonly placeholder="Click 'Generate JSON' to fill this" required>
            </div>

            <button type="submit" class="btn btn-success w-100" id="savePayoutBtn" disabled>📝 Save to Database</button>
        </form>
    </div>
</div>

<?php
// Pull all known addon names from DB
$allKnownAddons = [];
$activeAddons = [];

$res1 = $db->query("SELECT addon_name FROM active_addons");
if ($res1) {
    while ($row = $res1->fetch_assoc()) {
        $allKnownAddons[] = $row['addon_name'];
    }
}

$res2 = $db->query("SELECT addon_name FROM active_addons WHERE is_active = 1");

if ($res2) {
    while ($row = $res2->fetch_assoc()) {
        $activeAddons[] = $row['addon_name'];
    }
}

// Scan /addons folder
$addonDirs = [];
$addonPath = __DIR__ . '/../addons/';
foreach (scandir($addonPath) as $file) {
    if ($file === '.' || $file === '..') continue;
    if (is_dir($addonPath . $file)) {
        $addonDirs[] = $file;
    }
}
?>

<!-- Addon Section -->
<div class="card mt-5">
    <div class="card-header top-bar-header text-white">
        🧩 Manage Addons
    </div>
    <div class="card-body">

        <?php if (count($addonDirs) !== count($allKnownAddons)): ?>
            <div class="alert alert-warning text-center">
                ⚠️ Some detected addons are not registered in the system yet.
                Click <b>Register Addons</b> to sync them.
            </div>
        <?php endif; ?>

        <!-- 🔘 Register Button -->
        <form method="post" action="scan_addons.php" class="text-center mb-3">
            <button type="submit" class="btn btn-outline-primary">
                🧩 Register Addons
            </button>
        </form>
        <small class="form-text text-muted text-center mb-3">
            This will insert any missing addon folders into the database so they can be toggled below.
        </small>

        <!-- Toggle List -->
        <form method="POST" action="update_active_addons.php">
            <p class="mb-3 text-muted text-center">Toggle which addons are active. Inactive ones will be ignored by the system.</p>

            <ul class="list-group">
                <?php foreach ($addonDirs as $addon): 
                    $isRegistered = in_array($addon, $allKnownAddons);
                    $isActive = in_array($addon, $activeAddons);
                ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span class="<?= $isRegistered ? '' : 'text-danger' ?>">
                            <b><?= htmlspecialchars($addon) ?></b>
                            <?php if (!$isRegistered): ?>
                                <small class="text-danger">(Not Registered)</small>
                            <?php endif; ?>
                        </span>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox"
                                name="active_addons[]" value="<?= htmlspecialchars($addon) ?>"
                                id="addon_<?= htmlspecialchars($addon) ?>"
                                <?= $isActive ? 'checked' : '' ?>>
                            <label class="form-check-label" for="addon_<?= htmlspecialchars($addon) ?>">
                                <?= $isActive ? 'Active' : 'Inactive' ?>
                            </label>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>

            <button type="submit" class="btn btn-success w-100 mt-4">💾 Save Addon States</button>
        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const toggle = document.getElementById("useRewardModeToggle");
    const hiddenInput = document.getElementById("hidden_custom_reward_flag");
    const rewardLabelsSection = document.getElementById("reward_labels_section");

    toggle.addEventListener("change", function () {
        hiddenInput.value = toggle.checked ? 1 : 0;
        
        // Show/Hide Reward Labels Section
        if (toggle.checked) {
            rewardLabelsSection.style.display = "block"; // Show reward labels section
            document.getElementById("json_payouts").disabled = true; // Disable payouts field
        } else {
            rewardLabelsSection.style.display = "none"; // Hide reward labels section
            document.getElementById("json_payouts").disabled = false; // Enable payouts field
        }
    });

    // Trigger change on page load to show/hide sections based on initial state
    toggle.dispatchEvent(new Event('change'));
});
</script>

 <!-- Addon Admin Settings Loader -->
<div class="card mt-5">
  <div class="card-header top-bar-header text-white">🧩 Addon Admin Panels</div>
  <div class="card-body">

    <?php
    // Scan for all addons with settings.php
    $addon_dir = __DIR__ . '/../addons';
    $addon_admin_pages = [];
    foreach (glob($addon_dir . '/*/admin/settings.php') as $path) {
        $addon_name = basename(dirname(dirname($path)));
        $addon_admin_pages[$addon_name] = "/addons/{$addon_name}/admin/settings.php";
    }
    ?>

    <?php if (!empty($addon_admin_pages)): ?>
      <div class="btn-group mb-3" role="group">
        <?php foreach ($addon_admin_pages as $addon => $url): ?>
          <button type="button" class="btn btn-outline-primary" onclick="loadAddonAdmin('<?= $url ?>')">
            <?= ucfirst(str_replace('_', ' ', $addon)) ?>
          </button>
        <?php endforeach; ?>
      </div>

      <!-- Addon Save Message Box -->
      <div id="addonSaveMessage" class="mb-3"></div>

      <!-- Iframe Container -->
      <iframe id="addonIframe" src="" style="width: 100%; height: 800px; border: 1px solid #ccc;"></iframe>

      <script>
          function loadAddonAdmin(url) {
            document.getElementById("addonIframe").src = url;
          }
        
          // Listen for success from addon iframe and show message in-place
          window.addEventListener("message", function (event) {
            if (event.data && event.data.type === "addon_save_success") {
              const box = document.getElementById("addonSaveMessage");
              box.innerHTML = '<div class="alert alert-success">' + event.data.message + '</div>';
        
              // Clear message after 5 seconds
              setTimeout(() => box.innerHTML = '', 5000);
            }
          });
        </script>


    <?php else: ?>
      <p class="text-muted">No addon admin panels found.</p>
    <?php endif; ?>
  </div>
</div>

</div>

<?php
$reward_labels_array = json_decode($settings['reward_labels'] ?? '[]', true);
if (!is_array($reward_labels_array)) {
    $reward_labels_array = [];
}
$reward_labels_array = array_pad($reward_labels_array, $number_of_winners, "");

?>

<script>
let useRewardMode = <?= (isset($settings['use_custom_rewards']) && $settings['use_custom_rewards'] == 1) ? 'true' : 'false' ?>;
let storedLabels = <?= json_encode($reward_labels_array, JSON_UNESCAPED_UNICODE); ?>;
let storedPayouts = <?= json_encode($payouts_array, JSON_UNESCAPED_UNICODE); ?>;

function generateInverseRankPayouts(numWinners) {
    const weights = [];
    let weightSum = 0;
    
    for (let i = 1; i <= numWinners; i++) {
        const weight = 1 / i;
        weights.push(weight);
        weightSum += weight;
    }

    return weights.map(w => parseFloat(((w / weightSum) * 100).toFixed(2)));
}
</script>


<script>
// Initialize form & lock inputs on page load
document.addEventListener("DOMContentLoaded", function () {
    updatePayoutTable(document.getElementById("number_of_winners").value);
});

// Update payout table when winners change
document.getElementById("number_of_winners").addEventListener("input", function () {
    updatePayoutTable(this.value);
});

// Function to update the payout table dynamically
function updatePayoutTable(winners) {
    const table = document.getElementById("payoutTable").querySelector("tbody");
    table.innerHTML = "";
    let total = 0;

    for (let i = 0; i < winners; i++) {
        let value = useRewardMode
            ? (storedLabels[i] || "")
            : (storedPayouts[i] !== undefined
                ? parseFloat(storedPayouts[i]).toFixed(2)
                : generateInverseRankPayouts(winners)[i]
            );

        let inputType = useRewardMode ? 'text' : 'number';
        let step = useRewardMode ? '' : 'step="0.01" min="0"';

        const row = document.createElement("tr");
        row.innerHTML = `<td>#${i + 1}</td>
                         <td><input type="${inputType}" ${step} class="form-control reward_input" value="${value}" data-position="${i}"></td>`;
        table.appendChild(row);

        if (!useRewardMode) total += parseFloat(value);
    }

    document.getElementById("totalPayout").innerText = useRewardMode ? 'N/A' : total.toFixed(2) + "%";
    document.getElementById("totalPayout").style.color = useRewardMode ? "gray" : "black";

    document.querySelectorAll(".reward_input").forEach(input => {
        if (!useRewardMode) input.addEventListener("input", recalculateTotalPayout);
    });

    if (!useRewardMode) {
        recalculateTotalPayout();
    } else {
        document.getElementById("generateJsonBtn").disabled = false;
    }
}

// On toggle switch change: re-render payout table with updated mode
document.getElementById("useRewardModeToggle").addEventListener("change", function () {
    useRewardMode = this.checked;
    document.getElementById("use_custom_rewards").value = this.checked ? 1 : 0;

    document.getElementById("payoutOrRewardHeading").innerText = this.checked ? "Position Rewards" : "Payout Percentages";
    document.getElementById("payoutOrRewardLabel").innerText = this.checked ? "Reward" : "Payout %";

    updatePayoutTable(document.getElementById("number_of_winners").value);
});

// On page load: set number of winners based on array length & render table
document.addEventListener("DOMContentLoaded", function () {
    const isRewardMode = useRewardMode;
    const positionCount = isRewardMode ? storedLabels.length : storedPayouts.length;

    // Update the number input to reflect the actual position count
    document.getElementById("number_of_winners").value = positionCount;
    document.getElementById("use_custom_rewards").value = isRewardMode ? 1 : 0;

    updatePayoutTable(positionCount);
});

// Function to generate JSON and paste it into the form (LOCKED until 100%)
function generatePayoutJSON() {
    let winners = document.getElementById("number_of_winners").value;
    let inputs = Array.from(document.querySelectorAll(".reward_input"));

    if (!useRewardMode) {
        let totalText = document.getElementById("totalPayout").innerText.replace('%', '');
        let total = parseFloat(totalText);
        if (isNaN(total) || total !== 100) {
            alert("❌ Payout must equal exactly 100% before generating JSON.");
            return;
        }
    }

    let resultArray = inputs.map(el => useRewardMode ? el.value.trim() : parseFloat(el.value) || 0);
    let resultJSON = JSON.stringify(resultArray);

    document.getElementById("json_number_of_winners").value = winners;

    if (useRewardMode) {
        document.getElementById("json_reward_labels").value = resultJSON;
        document.getElementById("json_reward_labels").disabled = false;
        document.getElementById("json_payouts").disabled = true;
    } else {
        document.getElementById("json_payouts").value = resultJSON;
        document.getElementById("json_payouts").disabled = false;
        document.getElementById("json_reward_labels").disabled = true;
    }

    document.getElementById("savePayoutBtn").disabled = false;

    alert("✅ JSON Generated & Pasted!");
}

// Function to recalculate total payout and validate
function recalculateTotalPayout() {
    const totalPayoutElement = document.getElementById("totalPayout");
    const jsonButton = document.querySelector("[onclick='generatePayoutJSON()']");
    const saveBtn = document.getElementById("savePayoutBtn");

    if (useRewardMode) {
        // In reward mode, disable logic — always show N/A
        totalPayoutElement.innerText = "N/A";
        totalPayoutElement.style.color = "gray";
        jsonButton.disabled = false;
        saveBtn.disabled = false;
        return;
    }

    // Otherwise, do normal payout logic
    let total = Array.from(document.querySelectorAll(".reward_input")).reduce(
        (acc, input) => acc + (parseFloat(input.value) || 0), 0
    );

    total = parseFloat(total.toFixed(10));
    totalPayoutElement.innerText = total.toFixed(2) + "%";

    if (total !== 100) {
        totalPayoutElement.style.color = "red";
        jsonButton.disabled = true;
        saveBtn.disabled = true;
    } else {
        totalPayoutElement.style.color = "black";
        jsonButton.disabled = false;
        saveBtn.disabled = false;
    }
}
</script>

<script>
// Live validation for Ticket Middle Text
document.addEventListener("DOMContentLoaded", function () {
  const input = document.getElementById("ticket_middle_text");
  const preview = document.getElementById("ticket_preview");
  const error = document.getElementById("ticket_middle_error");

  const pattern = /^[a-zA-Z0-9_-]{3,40}$/;

  input.addEventListener("input", function () {
    const value = input.value;
    preview.textContent = `User-ID-5:${value}-17`;

    if (!pattern.test(value)) {
      input.classList.add("is-invalid");
      error.classList.remove("d-none");
    } else {
      input.classList.remove("is-invalid");
      error.classList.add("d-none");
    }
  });
});
</script>


<!-- Existing Validation Script -->
<script>
// Live validation for Reward Prefix/Suffix
document.addEventListener("DOMContentLoaded", function () {
  const suffixInput = document.getElementById("reward_prefix");
  const suffixPreview = document.getElementById("prefix_preview");
  const suffixError = document.getElementById("prefix_error");

  // Regex to validate letters and symbols
  const suffixPattern = /^[a-zA-Z0-9$#₳¥€£%]{1,12}$/;

  suffixInput.addEventListener("input", function () {
    const value = suffixInput.value;

    // Check for invalid characters and apply error
    if (!suffixPattern.test(value)) {
      suffixInput.classList.add("is-invalid");
      suffixError.classList.remove("d-none");
    } else {
      suffixInput.classList.remove("is-invalid");
      suffixError.classList.add("d-none");
    }

    // Check if the value is letters-only (this helps for prefix vs suffix)
    const needsSpace = /^[a-zA-Z]+$/.test(value); // letters only
    suffixPreview.textContent = needsSpace ? `10.00 ${value}` : `10.00${value}`;
  });
});
</script>

<!-- Existing Script for dynamically setting prefix or suffix type -->
<script>
// Dynamically set prefix or suffix type
document.addEventListener("DOMContentLoaded", function () {
  const prefixOrSuffixInput = document.getElementById("prefixSuffixToggle"); // Assuming this holds the admin choice (1 or 2)
  const suffixInput = document.getElementById("reward_prefix");

  // When the admin changes their choice, adjust behavior
  prefixOrSuffixInput.addEventListener("change", function () {
    const prefixOrSuffix = prefixOrSuffixInput.value;

    if (prefixOrSuffix == "1") { // Prefix: Allows symbols
      suffixInput.setAttribute("placeholder", "Enter prefix (symbols allowed)");
      suffixInput.setAttribute("pattern", "^[a-zA-Z0-9$#₳¥€£%]{1,12}$"); // Allow symbols
    } else if (prefixOrSuffix == "2") { // Suffix: Only letters
      suffixInput.setAttribute("placeholder", "Enter suffix (letters only)");
      suffixInput.setAttribute("pattern", "^[a-zA-Z]{1,12}$"); // Only letters allowed
    }
  });
});
</script>

<!-- Existing Script for updating reward preview based on admin choice -->
<script>
// Update the reward preview dynamically based on admin choice of prefix/suffix
document.addEventListener("DOMContentLoaded", function () {
  const suffixInput = document.getElementById("reward_prefix");
  const prefixPreview = document.getElementById("prefix_preview");

  const prefixPattern = /^[a-zA-Z0-9$#₳¥€£%]{1,12}$/; // For prefix (allows symbols)
  const suffixPattern = /^[a-zA-Z]{1,12}$/; // For suffix (only letters)

  suffixInput.addEventListener("input", function () {
    const value = suffixInput.value;

    // Show or hide error based on admin selection
    const isPrefix = prefixOrSuffixInput.checked === false;
    const isSuffix = prefixOrSuffixInput.checked === true;


    if (isPrefix && !prefixPattern.test(value)) {
      suffixInput.classList.add("is-invalid");
    } else if (isSuffix && !suffixPattern.test(value)) {
      suffixInput.classList.add("is-invalid");
    } else {
      suffixInput.classList.remove("is-invalid");
    }

    // Update preview based on admin's prefix or suffix selection
    if (isPrefix) {
      prefixPreview.textContent = `10.00 ${value}`; // Display the value with prefix
    } else if (isSuffix) {
      prefixPreview.textContent = `10.00${value}`; // Display the value with suffix
    }
  });
});
</script>

<!-- New Script for dynamically updating the Reward Prefix Help Text based on slider -->
<script>
// Dynamically update the Reward Prefix Help Text
document.addEventListener("DOMContentLoaded", function () {
    const prefixInput = document.getElementById("reward_prefix");
    const suffixInput = document.getElementById("reward_suffix");
    const helpText = document.getElementById("reward_prefix_help");
    const prefixPreview = document.getElementById("prefix_preview");
    const prefixSuffixToggle = document.getElementById("prefixSuffixToggle");

    // Function to update the preview text
    function updatePreview() {
        const prefix = prefixInput.value || '$'; // Default to '$' if empty
        const suffix = suffixInput.value || 'Points'; // Default to 'Points' if empty

        // Check if the slider is set to "Suffix" (right side)
        if (prefixSuffixToggle.checked) {
            prefixPreview.textContent = `10.00 ${suffix}`; // Add a space before the suffix
        } else {
            prefixPreview.textContent = `${prefix}10.00`; // Prefix example
        }
    }

    // Update preview on slider change or input field change
    prefixSuffixToggle.addEventListener("change", updatePreview);
    prefixInput.addEventListener("input", updatePreview);
    suffixInput.addEventListener("input", updatePreview);

    // Initial call to update the preview text based on current values
    updatePreview();
});
</script>

<script>
window.addEventListener("message", function (event) {
    if (event.data && event.data.type === "addon_save_success") {
        const messageBox = document.createElement("div");
        messageBox.className = "alert alert-success mt-3";
        messageBox.innerHTML = event.data.message;

        const container = document.querySelector(".container.mt-5");
        container.insertBefore(messageBox, container.firstChild);

        setTimeout(() => {
            messageBox.style.opacity = 0;
            setTimeout(() => messageBox.remove(), 1000);
        }, 4000);
    }
});
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const toggle = document.getElementById("prefixSuffixToggle");
    const hiddenInput = document.getElementById("prefix_or_suffix");

    // Initialize based on checked state
    hiddenInput.value = toggle.checked ? 2 : 1;

    toggle.addEventListener("change", function () {
        hiddenInput.value = toggle.checked ? 2 : 1;
    });
});
</script>

<?php include(__DIR__ . '/../template/footer.php'); ?>

</body>
</html>