==========================================
ADD-ON AJAX INTEGRATION GUIDE
==========================================

This system supports addon-specific AJAX actions using modular case injection.

Your addon can respond to AJAX requests by creating a file located at:

addons/your_addon_name/system/ajax_cases.php

The system will automatically include this file if an unrecognized action is received.

------------------------------------------
HOW IT WORKS:
------------------------------------------

1. The core AJAX system checks if the action is known.
2. If not, it loops through all addons and loads any ajax_cases.php files.
3. If your addon handles the action, you must set $handled = true;
4. All AJAX responses must use: safe_json_response([...]);


------------------------------------------
TEMPLATE STRUCTURE:
------------------------------------------

if (!isset($action)) return;

if ($action === 'your_custom_action') {
    $handled = true;

    // Your logic here...
    safe_json_response([
        'status' => 'success',
        'message' => 'Handled by addon!'
    ]);
}

if ($action === 'another_action') {
    $handled = true;

    // More logic...
    safe_json_response([
        'status' => 'success',
        'data' => ['foo' => 'bar']
    ]);
}

------------------------------------------
REQUIREMENTS FOR EACH ACTION HANDLER:
------------------------------------------

- $action is auto-set by the core before your file is included
- You MUST set $handled = true; once you handle an action
- You MUST end with safe_json_response([...]) to return a response and exit cleanly
- $db is available for database access
- $_SESSION['user_id'] is available if login is required

------------------------------------------
EXAMPLE USE CASE:
------------------------------------------

if ($action === 'delete_item') {
    $handled = true;

    $user_id = $_SESSION['user_id'] ?? null;
    $item_id = $_POST['item_id'] ?? null;

    if (!$user_id || !$item_id) {
        safe_json_response(['status' => 'error', 'message' => 'Missing user or item ID']);
    }

    $stmt = $db->prepare("DELETE FROM items WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $item_id, $user_id);
    $stmt->execute();
    $stmt->close();

    safe_json_response(['status' => 'success', 'message' => 'Item deleted']);
}

------------------------------------------
HOW TO TEST:
------------------------------------------

Send a POST request to:

/system/ajax.php

Include the following POST data:
action = your_custom_action

Your addon’s ajax_cases.php will be triggered automatically if the action matches.

------------------------------------------
WIDGET SUPPORT (OPTIONAL):
------------------------------------------

To inject frontend widgets, create PHP files under:

addons/your_addon_name/widgets/

These will automatically appear in the "Addon Widgets" section of the homepage.

------------------------------------------
SUMMARY:
------------------------------------------

✓ No need to edit core ajax.php
✓ Actions handled in addon folder
✓ Easy structure for developers
✓ Supports infinite addon extensions



==========================================
TICKET INTEGRATION REQUIREMENT
==========================================

If your addon is meant to register tickets (such as check-ins, bonus entries, social rewards, etc.), you MUST insert data into the following table:

current_round_tickets

This table powers the Universal Fairness Protocol's live tracking, prize distribution, and fairness verification system.

------------------------------------------
REQUIRED INSERT FORMAT:
------------------------------------------

You must insert using this format:

INSERT INTO current_round_tickets (round_id, user_id, date)
VALUES (?, ?, ?)

All 3 columns are REQUIRED:

- round_id: ID of the currently active round
- user_id: ID of the user earning the ticket
- date: Unix timestamp (use time()) representing the moment the ticket was earned

------------------------------------------
WHY THIS IS REQUIRED:
------------------------------------------

The system depends on this table to:

✓ Track user participation  
✓ Build live ticket lists  
✓ Generate fairness hashes  
✓ Distribute round prizes  

If your addon does not insert into this table properly:

✘ The user will not be counted  
✘ Tickets will not show up  
✘ They will NOT be eligible for prizes  

------------------------------------------
EXAMPLE TICKET INSERT:
------------------------------------------

$time = time();
$stmt = $db->prepare("INSERT INTO current_round_tickets (round_id, user_id, date) VALUES (?, ?, ?)");
$stmt->bind_param("iii", $round_id, $user_id, $time);
$stmt->execute();
$stmt->close();

Each call to this INSERT = 1 ticket.

You may insert multiple times if your addon gives multiple tickets.

No other tables need to be touched — just use this insert format.

------------------------------------------
REMINDER:
------------------------------------------

All addon actions should be placed inside:

addons/your_addon_name/system/ajax_cases.php

The core system will detect and route requests to your file automatically.

No edits to /system/ajax.php are necessary.

------------------------------------------

