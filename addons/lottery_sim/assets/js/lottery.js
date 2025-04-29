/*!
 * UFP Component
 * Dual Licensed under GPLv3 and Commercial
 * Author: DgtalFairness | https://universalfairnessprotocol.com
 */

// Global function so sync.js can call it
function updateLotteryWidgetPrize(value) {
    const isLabelMode = window.rewardConfig?.use_custom_rewards == 1;
    const rewardCount = window.rewardConfig?.reward_labels?.length || 0;

    const display = isLabelMode 
        ? `${rewardCount} Rewards`
        : formatReward(value);

    $("#prizePool").text(display);
}


// Dynamic total cost calculator when user changes ticket input
$("#home_tickets_amount").on("input", function () {
    let amount = parseInt($(this).val());
    let price = parseFloat($(this).data("ticket-price"));

    if (!isNaN(amount) && amount > 0) {
        let total = (amount * price).toFixed(2);
        $("#home_totalCost").text(formatReward(total));
    } else {
        $("#home_totalCost").text(formatReward(0));
    }
});



// Ticket purchase form submit handler
$(document).ready(function () {
    $("#buyTicketForm").on("submit", function (e) {
        e.preventDefault(); // Prevent form reload

        let tickets = $("#home_tickets_amount").val();
        

        $.post("system/ajax.php", { action: "buy_ticket", tickets_amount: tickets }, function (response) {
            

            if (response.success) {
                showMessage(response.message, "success");
                if (typeof updateStats === "function") updateStats();       // Prize pool, tickets sold
                if (typeof updateUserStats === "function") updateUserStats(); // Balance, user ticket count
            } else {
                showMessage(response.message, "danger");
            }
        }, "json").fail(function (xhr, status, error) {
            
            showMessage("Error: " + xhr.responseText, "danger");
        });
    });

    // Display message box with fade + dismiss
    function showMessage(message, type) {
        $("#messageBox").html(
            '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
            message + 
            '<button type="button" class="btn-close close-message" aria-label="Close"></button>' +
            '</div>'
        ).fadeIn("fast");

        setTimeout(function () {
            $("#messageBox").fadeOut("slow", function () {
                $("#messageBox").html(""); // Clear message after fade
            });
        }, 5000);
    }

    // Manual dismiss button (close X)
    $(document).on("click", ".close-message", function () {
        $("#messageBox").fadeOut("slow", function () {
            $("#messageBox").html(""); // Clear when user clicks X
        });
    });
});
