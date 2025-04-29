/*!
 * UFP Component
 * Dual Licensed under GPLv3 and Commercial
 * Author: DgtalFairness | https://universalfairnessprotocol.com
 */

// Improved Copy to Clipboard Function
function copyToClipboard() {
    var copyText = document.getElementById("ticketList");
    
    if (!copyText.value.trim()) {
        alert("⚠ No ticket list to copy!");
        return;
    }

    navigator.clipboard.writeText(copyText.value).then(() => {
        var copyBtn = document.getElementById("copyBtn");
        copyBtn.innerText = "✅ Copied!";
        setTimeout(() => { copyBtn.innerText = "Copy to Clipboard"; }, 2000);
    }).catch(err => {
        
        alert("❌ Copy failed. Please try again.");
    });
}


// Ensure function runs **AFTER** page is fully loaded
setTimeout(fetchLiveTickets, 2000); // Small delay to ensure elements exist
setInterval(fetchLiveTickets, 5000); // Keep updating every 5 seconds


// Copy live ticket list to clipboard
function copyLiveTickets() {
    var copyText = document.getElementById("liveTicketList");
    copyText.select();
    document.execCommand("copy");
    alert("Copied to clipboard!");
}

async function verifyWinningTickets() {
    let ticketList = document.getElementById("verifyTicketList").value.trim();
    let totalTickets = parseInt(document.getElementById("totalTickets").innerText);
    let firstTicket = parseInt(document.getElementById("firstTicket").value);
    let selectedTier = parseInt(document.getElementById("rehashTier").value.replace(/\D/g, "")); // Extract numeric tier value
    let resultBox = document.getElementById("winnerResult");

    if (!ticketList || isNaN(totalTickets) || isNaN(firstTicket) || isNaN(selectedTier)) {
        resultBox.innerHTML = "<span class='text-danger'>⚠ Please enter all required values.</span>";
        return;
    }

    // Convert the ticket list to SHA-256 hash
    const msgUint8 = new TextEncoder().encode(ticketList);
    const hashBuffer = await crypto.subtle.digest('SHA-256', msgUint8);
    const hashArray = Array.from(new Uint8Array(hashBuffer));
    const hashHex = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');

    // Extract numeric digits only
    let numericOnly = hashHex.replace(/\D/g, ""); // Remove non-numeric
    if (numericOnly.length < 10) {
        resultBox.innerHTML = "<span class='text-danger'>❌ Not enough numeric digits in the hash!</span>";
        return;
    }

    // Compute the 20 winners per selected tier
    let winners = [];
    let positionCounter = (selectedTier - 1) * 20 + 1; // Start numbering based on selected tier

    for (let i = 0; i < 20; i++) {
        let sliceIndex = i % (numericOnly.length - 10); // Ensure valid 10-digit slices
        let slice = numericOnly.substring(sliceIndex, sliceIndex + 10);
        let hashNumber = parseInt(slice, 10);
        let winningTicket = (hashNumber % totalTickets) + firstTicket;

        winners.push(`<li class="list-group-item" data-segment="${slice}">#${positionCounter}: Winning Ticket → <b>${winningTicket}</b> <span class="text-muted">(Extracted: ${slice})</span></li>`);
        positionCounter++; // Move to next position number
    }

    // Display results
    resultBox.innerHTML = `<b>✅ Winning Tickets (Tier ${selectedTier}):</b><br>${winners.join("<br>")}<br>SHA-256 Hash: <code>${hashHex}</code>`;
    
    highlightWinningSegments();
}


// Function to highlight winning segments after verification
async function highlightWinningSegments() {
    

    let roundId = document.getElementById("roundSelect").value;
    let selectedTier = document.getElementById("rehashTier").value.replace(/\D/g, ""); // Extract numeric tier value

    if (!roundId || !selectedTier) {
        
        return;
    }


    // Fetch only the segments needed for highlighting
    await fetch("system/ajax.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
            action: "fetch_highlight_segments",
            lottery_id: roundId,
            selected_tier: selectedTier
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 200) {
            let highlightSegments = data.highlight_segments || [];
            

            // Update UI for highlighting
            let resultBox = document.getElementById("winnerResult");
            let winnersList = resultBox.querySelectorAll("li");

            winnersList.forEach((item) => {
                let extractedSegment = item.innerText.match(/\((Extracted: (\d+))\)/);
                if (extractedSegment && extractedSegment[2]) {
                    let segmentValue = extractedSegment[2];
                    let isWinner = highlightSegments.some(s => s.segment === segmentValue);

                    if (isWinner) {
                        item.classList.add("text-success", "fw-bold");
                        item.style.display = "block";
                    } else {
                        item.style.display = "none";
                    }
                }
            });

        } else {
           
        }
    });
}

$(document).ready(function () {
   
    // Function to dynamically show messages properly
    function showMessage(message, type) {
        $("#messageBox").html(
            '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
            message + 
            '<button type="button" class="btn-close close-message" aria-label="Close"></button>' +
            '</div>'
        ).fadeIn("fast");

        // Auto-remove message after 5 seconds
        setTimeout(function () {
            $("#messageBox").fadeOut("slow", function () {
                $("#messageBox").html("");
            });
        }, 5000);
    }

    // Handles clicking the "X" button to close the message
    $(document).on("click", ".close-message", function () {
        $("#messageBox").fadeOut("slow", function () {
            $("#messageBox").html("");
        });
    });

    // Fetch updated stats every  5 seconds
    setInterval(runUnifiedSyncController, 5000);

    setInterval(updateUserStats, 5000);

    // Function to fetch updated user stats
    function updateUserStats() {
        

        $.post("system/ajax.php", { action: "fetch_user_stats" }, function (response) {
            

            if (response.status === 200) {
    const cleanBalance = typeof response.balance === "string"
        ? parseFloat(response.balance.replace(/,/g, ""))
        : parseFloat(response.balance);

    const formattedBalance = formatReward(cleanBalance);
    $("#userBalance").text(formattedBalance);
    $("#userTickets").text(response.tickets);
    $('#userWins').text(response.user_wins);

}
 else {
                
            }
        }, "json").fail(function (xhr, status, error) {
            
        });
    }
    

    // Countdown Timer (supports both #countdown and #countdownHome)
    function startCountdown(endTime) {
        let countdownElement = document.getElementById("countdown");
        let countdownHomeElement = document.getElementById("countdownHome");
    
        let syncBeforeTriggered = false;
        let syncAfterTriggered = false;
        let interval = null;
    
        function updateCountdown() {
            let now = Date.now();
            let timeLeft = endTime * 1000 - now;
    
            // 0.5s BEFORE countdown ends
            if (!syncBeforeTriggered && timeLeft <= 500 && timeLeft > 0) {
                syncBeforeTriggered = true;
                forceFullSync("0.5s BEFORE round ends");
            }
    
            // If countdown has ended
            if (timeLeft <= 0) {
                clearInterval(interval);
                let secondsPassed = 0;
    
                // Disable Buy Button
                const buyButton = document.querySelector("#buyTicketForm button[type='submit']");
                if (buyButton) {
                    buyButton.classList.add("disabled", "btn-secondary");
                    buyButton.classList.remove("btn-success");
                    buyButton.setAttribute("disabled", "disabled");
                    buyButton.innerText = "⏳ Processing...";
                }
    
                const refreshInterval = setInterval(() => {
                    secondsPassed++;
    
                    let remaining = 65 - secondsPassed;
                    let processingText = `⏳ Round ID #${lastKnownRoundId} completed — Results are being processed. The next round ID #${lastKnownRoundId + 1} will begin in 0:${remaining < 10 ? "0" + remaining : remaining} seconds.`;
    
                    if (remaining >= 0) {
                        if (countdownElement) countdownElement.innerHTML = processingText;
                        if (countdownHomeElement) countdownHomeElement.innerHTML = processingText;
                    } else {
                        const unlockingText = `🔓 Unlocking ticket access... just a few seconds left!`;
                        if (countdownElement) countdownElement.innerHTML = unlockingText;
                        if (countdownHomeElement) countdownHomeElement.innerHTML = unlockingText;
                    }
    
                    // Check for new round every 5s, start at +5s
                    if (secondsPassed >= 5 && secondsPassed % 5 === 0) {
                        fetch("system/ajax.php", {
                            method: "POST",
                            headers: { "Content-Type": "application/x-www-form-urlencoded" },
                            body: new URLSearchParams({ action: "fetch_round_end_time" })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.status === 200 && data.expected_end_date > (Date.now() / 1000)) {
                                clearInterval(refreshInterval);
                                
    
                                let currentUnix = Math.floor(Date.now() / 1000);
                                let delayUntilBuy = data.date - currentUnix;
    
                                // 0.5s AFTER new round starts
                                setTimeout(() => {
                                    forceFullSync("0.5s AFTER round starts");
                                }, 500);
    
                                const restartCountdown = () => {
                                    startCountdown(data.expected_end_date);
                                    if (buyButton) {
                                        buyButton.classList.remove("disabled", "btn-secondary");
                                        buyButton.classList.add("btn-success");
                                        buyButton.removeAttribute("disabled");
                                        buyButton.innerText = "🎟 Buy Ticket";
                                    }
                                };
    
                                if (delayUntilBuy > 0) {
                                    const waitText = `🕐 New round created — Ticket buying begins in ${delayUntilBuy} seconds...`;
                                    if (countdownElement) countdownElement.innerHTML = waitText;
                                    if (countdownHomeElement) countdownHomeElement.innerHTML = waitText;
    
                                    setTimeout(restartCountdown, delayUntilBuy * 1000);
                                } else {
                                    restartCountdown();
                                }
                            }
                        });
                    }
                }, 1000);
                return;
            }

        // Normal Countdown
        let days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
        let hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        let minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
        let seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
        const countdownString = `${days}d ${hours}h ${minutes}m ${seconds}s`;

        if (countdownElement) countdownElement.innerHTML = countdownString;
        if (countdownHomeElement) countdownHomeElement.innerHTML = countdownString;
    }

    interval = setInterval(updateCountdown, 1000);
    updateCountdown();
}


    //// Start countdown
    if (typeof expectedEndDate !== "undefined" && expectedEndDate > 0) {
        startCountdown(expectedEndDate);
    } else {
        document.getElementById("countdown").innerHTML = "N/A";
    }
    
    // Fetch updated stats on page load
    runUnifiedSyncController();


});


function pollForNewRound() {
    let attempts = 0;

    let pollInterval = setInterval(() => {
        

        fetch("system/ajax.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({ action: "fetch_round_end_time" })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 200 && data.expected_end_date) {
                let newEndTime = parseInt(data.expected_end_date);

                if (newEndTime * 1000 > Date.now()) {
                    
                    clearInterval(pollInterval);
                    startCountdown(newEndTime);
                }
            }
        });

        attempts++;
        if (attempts >= 30) {
            clearInterval(pollInterval);
            
        }
    }, 5000);
}


document.getElementById("refreshWinners").addEventListener("click", function () {
    fetch("system/ajax.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ action: "fetch_recent_winners" })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status !== 200) return;

        // Update reward config if returned from backend
        if (data.reward_config) window.rewardConfig = data.reward_config;

        const tbody = document.getElementById("winnersTableBody");
        tbody.innerHTML = "";

        if (!data.winners || data.winners.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8">No winners yet!</td></tr>`;
            return;
        }

        // Helper to display reward correctly
        const getDisplayReward = (w) => {
            if (window.rewardConfig?.use_custom_rewards == 1) {
                return window.rewardConfig?.reward_labels?.[w.position - 1] || '🎁 Reward';
            } else {
                let reward = parseFloat(w.reward_received).toFixed(2);
                if (window.rewardConfig?.prefix_or_suffix == 1) {
                    return window.rewardConfig?.reward_prefix + reward;
                } else if (window.rewardConfig?.prefix_or_suffix == 2) {
                    return reward + ' ' + window.rewardConfig?.reward_suffix;
                }
                return reward;
            }
        };

        data.winners.forEach(w => {
            tbody.innerHTML += `
                <tr>
                    <td><span class="badge bg-dark text-white">${Number(w.round_id).toLocaleString()}</span></td>
                    <td><span class="badge bg-dark text-white">#${w.position}</span></td>
                    <td><span class="badge bg-dark text-white">${w.user_id ?? '-'}</span></td>
                    <td><span class="badge bg-dark text-white">${w.username ?? 'Anonymous'}</span></td>
                    <td><span class="badge bg-dark text-white">#${w.winning_ticket}</span></td>
                    <td><span class="badge bg-dark text-white">${Number(w.winner_tickets).toLocaleString()}</span></td>
                    <td><span class="badge bg-dark text-white">${getDisplayReward(w)}</span></td>
                    <td><span class="badge bg-dark text-white">${timeAgo(parseInt(w.end_date))}</span></td>
                </tr>
            `;
        });
    });
});


// TimeAgo() helper
function timeAgo(timestamp) {
    const seconds = Math.floor(Date.now() / 1000) - timestamp;

    const d = Math.floor(seconds / 86400);
    const h = Math.floor((seconds % 86400) / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = seconds % 60;

    return `${String(d).padStart(2, '0')} d ${String(h).padStart(2, '0')} h ${String(m).padStart(2, '0')} m ${String(s).padStart(2, '0')} s ago`;
}
