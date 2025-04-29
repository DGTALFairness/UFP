/*!
 * UFP Component
 * Dual Licensed under GPLv3 and Commercial
 * Author: DgtalFairness | https://universalfairnessprotocol.com
 */

$(document).ready(function () {
    let countdownInterval;
    let timeAgoInterval;

    function formatTime(seconds) {
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = seconds % 60;
        return minutes + " min " + remainingSeconds + " sec";
    }

    function formatTimeAgo(timestamp) {
        const now = Math.floor(Date.now() / 1000);
        let diff = now - timestamp;

        const days = Math.floor(diff / 86400);
        diff %= 86400;
        const hours = Math.floor(diff / 3600);
        diff %= 3600;
        const minutes = Math.floor(diff / 60);
        const seconds = diff % 60;

        let timeAgo = "";
        if (days > 0) timeAgo += days + "d ";
        if (hours > 0) timeAgo += hours + "h ";
        if (minutes > 0) timeAgo += minutes + "m ";
        timeAgo += seconds + "s ago";

        return timeAgo;
    }

    function updateCheckinMessage(message, success) {
        const messageBox = $("#checkinMessage");
        messageBox.text(message);
        messageBox.removeClass('text-success text-danger');
        messageBox.addClass(success ? 'text-success' : 'text-danger');
    }

    function startCountdown(seconds) {
        clearInterval(countdownInterval);
        countdownInterval = setInterval(function () {
            seconds--;
            if (seconds <= 0) {
                clearInterval(countdownInterval);
                $("#nextCheckin").text("Available Now.");
                $("#checkinButton").prop("disabled", false).removeClass("disabled");
                updateCheckinMessage("You can check in now.", true);
            } else {
                $("#nextCheckin").text(formatTime(seconds));
            }
        }, 1000);
    }


    function startTimeAgoUpdater(timestamp) {
        clearInterval(timeAgoInterval);

        function updateAgo() {
            $("#lastCheckin").text(formatTimeAgo(timestamp));
        }

        updateAgo(); // Initial
        timeAgoInterval = setInterval(updateAgo, 1000); // Every second for dynamic change
    }

   function fetchCheckinStatus() {
        $.ajax({
            url: '/system/ajax.php',
            method: 'POST',
            data: { action: 'checkin_status' },
            success: function (response) {
                const lastCheckinUnix = parseInt(response.lastCheckin_unix || 0);
                const nextSeconds = parseInt(response.nextCheckinInSeconds || 0);
    
                if (response.status === 'success') {
                    // Normal active round behavior
                    if (response.canCheckIn) {
                        $("#checkinButton").prop("disabled", false).removeClass("disabled");
                        updateCheckinMessage("You can check in now.", true);
                        $("#nextCheckin").text("Available Now.");
                    } else {
                        $("#checkinButton").prop("disabled", true).addClass("disabled");
                        updateCheckinMessage(response.message, false);
                        startCountdown(nextSeconds);
                    }
    
                    if (lastCheckinUnix > 0) {
                        startTimeAgoUpdater(lastCheckinUnix);
                    } else {
                        $("#lastCheckin").text("Never");
                    }
    
                } else if (response.noActiveRound) {
                    // No active round found — Show notice and retry every 5 seconds
                    updateCheckinMessage(response.message, false);
                    $("#nextCheckin").text("No active round found.");
                    $("#checkinButton").prop("disabled", true).addClass("disabled");
                    
                    // Retry in 5 seconds
                    setTimeout(fetchCheckinStatus, 5000);
                } else {
                    updateCheckinMessage(response.message, false);
                }
            },
            error: function () {
                updateCheckinMessage("An error occurred. Please try again.", false);
            }
        });
    }

    // On load
    fetchCheckinStatus();

   // On check-in
    $("#checkinButton").click(function () {
        $.ajax({
            url: '/system/ajax.php',
            method: 'POST',
            data: { action: 'checkin' },
            success: function (response) {
                if (response.status === 'success') {
                    updateCheckinMessage("Check-in successful!", true);
    
                    // Wait 1.5 seconds before refreshing status (so the success message is visible)
                    setTimeout(fetchCheckinStatus, 1500);
                } else {
                    updateCheckinMessage(response.message, false);
    
                    // HHandle no active round gracefully
                    if (response.noActiveRound) {
                        $("#nextCheckin").text("No active round found.");
                        $("#checkinButton").prop("disabled", true).addClass("disabled");
                    }
                }
            },
            error: function () {
                updateCheckinMessage("An error occurred. Please try again.", false);
            }
        });
    });
});
