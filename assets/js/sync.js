/*!
 * UFP Component
 * Dual Licensed under GPLv3 and Commercial
 * Author: DgtalFairness | https://universalfairnessprotocol.com
 */

let lastKnownRoundId = null;
let lastRoundId = null;
let lastPrizeBreakdownRoundId = null;
let lastPrizeBreakdownData = null;
let lastTicketStatsRoundId = null;
let lastTicketStatsData = null;
let isRoundCurrentlyActive = false;

function formatReward(value) {
    const prefixOrSuffix = window.rewardConfig?.prefix_or_suffix || 0;
    const prefix = window.rewardConfig?.reward_prefix || "";
    const suffix = window.rewardConfig?.reward_suffix || "";

    const num = parseFloat(value);
    const formatted = isNaN(num) ? value : num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    if (prefixOrSuffix === 1) return `${prefix}${formatted}`;
    if (prefixOrSuffix === 2) return `${formatted} ${suffix}`;
    return formatted;
}


function fetchLiveTickets() {
    

    fetch("system/ajax.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ action: "fetch_live_ticket_list" })
    })
    .then(response => response.json())
    .then(data => {
        

        let liveTicketList = document.getElementById("liveTicketList");
        if (!liveTicketList) {

            return;
        }

        if (data.status === 200) {
            liveTicketList.value = data.ticket_list;

        } else {
            liveTicketList.value = data.message;
            
        }
    })
    .catch(error => {
        
    });
}


function forceFullSync(label = "") {
    updatePrizeBreakdown();
    updateTicketStatsWrapper();
    fetchLiveTickets();
}



function updatePrizeBreakdown() {
    if (!isRoundCurrentlyActive) {
        
        return;
    }

    $.post("system/ajax.php", { action: "fetch_prize_breakdown" }, function (response) {
        if (response.status === 200 && response.prizes && response.prizes.length > 0) {
            

            lastPrizeBreakdownRoundId = response.round_id;
            lastPrizeBreakdownData = response;

            renderPrizeBreakdown(response);

            // Just the number
            $("#prizePool").text(formatReward(response.total_prize));
        } else {
            

            if (lastPrizeBreakdownData) {
                renderPrizeBreakdown(lastPrizeBreakdownData);

                $("#prizePool").text(formatReward(lastPrizeBreakdownData.total_prize));

            }
        }
    }, "json").fail(function (xhr, status, error) {
        
    });
}


function renderPrizeBreakdown(data) {
    let prizeList = $("#prizeBreakdownList");
    prizeList.empty();

    const isLabelMode = window.rewardConfig?.use_custom_rewards === 1;
    const labels = window.rewardConfig?.reward_labels || [];

    $.each(data.prizes, function (index, item) {
        const position = item.position;
        let displayValue;

        if (isLabelMode) {
            displayValue = labels[position - 1] || '🎁 Reward';
        } else {
            const rawPrize = typeof item.prize === "string" ? item.prize.replace(/,/g, '') : item.prize;
            displayValue = formatReward(rawPrize);
        }

        prizeList.append(`
            <li class="list-group-item d-flex justify-content-between">
                <span>🏅 Position #${position}</span>
                <span><b>${displayValue}</b></span>
            </li>
        `);
    });

    if (data.round && data.round.round_id) {
        $("#prizeBreakdownRound").text(data.round.round_id);
    }
}


function updateTicketStatsWrapper() {
    // First: Check round status
    fetch("system/ajax.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ action: "fetch_round_status" })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status !== 200 || !data.round_id || data.round_id === 0) return;

        const now = Math.floor(Date.now() / 1000);
        const roundId = parseInt(data.round_id);
        const roundStart = parseInt(data.date);
        const roundEnd = parseInt(data.expected_end_date);

        // Only run if the round is active
        if (now >= roundStart && now <= roundEnd) {
            updateTicketStats();
        }
    });
}


function updateTicketStats() {
    $.post("system/ajax.php", { action: "fetch_ticket_stats" }, function (response) {
        if (response.status === 200 && Array.isArray(response.stats) && response.stats.length > 0) {
            lastTicketStatsRoundId = response.round_id;
            lastTicketStatsData = {
                stats: response.stats,
                round_id: response.round_id || null
            };


            let statsBody = $("#ticketStatsBody");
            let cardHeader = $("#ticketStatsCard .card-header");
            let emptyAlert = $("#ticketStatsEmpty");

            statsBody.empty();

            response.stats.forEach((row, index) => {
                statsBody.append(`
                    <tr>
                        <td><span class="badge bg-dark text-white">${index + 1}</span></td>
                        <td><span class="badge bg-dark text-white">${row.user_id}</span></td>
                        <td><span class="badge bg-dark text-white">${row.username}</span></td>
                        <td><span class="badge bg-dark text-white">${parseInt(row.total_tickets).toLocaleString()}</span></td>
                        <td><span class="badge bg-dark text-white">Free</span></td>
                    </tr>
                `);
            });

            if (cardHeader.length && lastTicketStatsRoundId) {
                    cardHeader.text(`🎟 Current Tickets for Round #${lastTicketStatsRoundId}`);
                }

            if (emptyAlert.length) emptyAlert.hide();
            $("#ticketStatsCard").show();

        } else {
            // If no active data, fallback to last known data
            if (lastTicketStatsData) {
                renderFallbackTicketStats(lastTicketStatsData);
            } else {
                $("#ticketStatsCard").hide();
                $("#ticketStatsEmpty").show().text("No ticket purchases in the current round yet.");
            }
        }
    });
}


function renderFallbackTicketStats(data) {
    if (!data || !Array.isArray(data.stats)) {
        console.warn("⚠️ No fallback stats to render.");
        return;
    }

    let statsBody = $("#ticketStatsBody");
    let cardHeader = $("#ticketStatsCard .card-header");

    statsBody.empty();

    data.stats.forEach((row, index) => {
        statsBody.append(`
            <tr>
                <td>${index + 1}</td>
                <td>${row.username}</td>
                <td><span class="badge bg-primary">${parseInt(row.total_tickets).toLocaleString()}</span></td>
                <td><span class="badge bg-success">Free</span></td>
            </tr>
        `);
    });

    if (cardHeader.length && data.round_id) {
        cardHeader.text(`🎟 Current Tickets for Round #${data.round_id}`);
    }

    $("#ticketStatsCard").show();
    $("#ticketStatsEmpty").hide();
}



function updateRoundLabel() {
    fetch("system/ajax.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ action: "fetch_round_status" })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status !== 200) return;

        const now = Math.floor(Date.now() / 1000);
        const roundLabel = document.getElementById("roundNumber");
        if (!roundLabel) return;

        const roundId = parseInt(data.round_id);
        const roundStart = parseInt(data.date);
        const roundEnd = parseInt(data.expected_end_date);

        // Skip if invalid round ID — do NOT update anything
        if (!roundId || roundId === 0) {
            isRoundCurrentlyActive = false;

            if (lastKnownRoundId) {
                roundLabel.innerHTML = `${lastKnownRoundId} Ended ⏳ (Processing...)`;
            }
            return;
        }

        // Update last known round ID
        lastKnownRoundId = roundId;

        // If still same round — update status only
        if (roundId === lastRoundId) {
            if (now < roundStart) {
                isRoundCurrentlyActive = false;
                roundLabel.innerHTML = `${roundId} Created 🕐 (Opening Soon...)`;
            } else if (now >= roundStart && now <= roundEnd) {
                isRoundCurrentlyActive = true;
                roundLabel.innerHTML = `${roundId} Active! ✅`;
            } else {
                isRoundCurrentlyActive = false;
                roundLabel.innerHTML = `${roundId} Ended ⏳ (Processing...)`;
            }
            return;
        }

        // New round detected — update everything
        lastRoundId = roundId;

        if (now < roundStart) {
            isRoundCurrentlyActive = false;
            roundLabel.innerHTML = `${roundId} Created 🕐 (Opening Soon...)`;
        } else if (now >= roundStart && now <= roundEnd) {
            isRoundCurrentlyActive = true;
            roundLabel.innerHTML = `${roundId} Active! ✅`;
        } else {
            isRoundCurrentlyActive = false;
            roundLabel.innerHTML = `${roundId} Ended ⏳ (Processing...)`;
        }
    });
}


function runUnifiedSyncController() {
    fetch("system/ajax.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ action: "fetch_sync_snapshot" })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status !== 200) {
            
            return;
        }

        // Update round label
        const round = data.round;
        const roundLabel = document.getElementById("roundNumber");
        const roundLabelHome = document.getElementById("roundNumberHome");
        const countdownHome = document.getElementById("countdownHome");

        if (round && round.round_id && roundLabel) {
            const now = Math.floor(Date.now() / 1000);
            const roundId = parseInt(round.round_id);
            const roundStart = parseInt(round.date);
            const roundEnd = parseInt(round.expected_end_date);
            lastKnownRoundId = roundId;

            let labelText = "";
            if (roundId === lastRoundId) {
                if (now < roundStart) {
                    isRoundCurrentlyActive = false;
                    labelText = `${roundId} Created 🕐 (Opening Soon...)`;
                } else if (now >= roundStart && now <= roundEnd) {
                    isRoundCurrentlyActive = true;
                    labelText = `${roundId} Active! ✅`;
                } else {
                    isRoundCurrentlyActive = false;
                    labelText = `${roundId} Ended ⏳ (Processing...)`;
                }
            } else {
                lastRoundId = roundId;
                if (now < roundStart) {
                    isRoundCurrentlyActive = false;
                    labelText = `${roundId} Created 🕐 (Opening Soon...)`;
                } else if (now >= roundStart && now <= roundEnd) {
                    isRoundCurrentlyActive = true;
                    labelText = `${roundId} Active! ✅`;
                } else {
                    isRoundCurrentlyActive = false;
                    labelText = `${roundId} Ended ⏳ (Processing...)`;
                }
            }

            roundLabel.innerHTML = labelText;
            if (roundLabelHome) roundLabelHome.innerHTML = labelText;

            if (countdownHome && roundEnd > 0) {
                const secondsLeft = roundEnd - now;
                if (secondsLeft > 0) {
                    const d = Math.floor(secondsLeft / 86400);
                    const h = Math.floor((secondsLeft % 86400) / 3600);
                    const m = Math.floor((secondsLeft % 3600) / 60);
                    const s = secondsLeft % 60;
                    countdownHome.innerHTML = `${d}d ${h}h ${m}m ${s}s`;
                } else {
                    countdownHome.innerHTML = `⏳ Processing...`;
                }
            }
        }

        // Prize Breakdown
        if (data.prizes && data.prizes.length > 0) {
            lastPrizeBreakdownRoundId = round.round_id;
            lastPrizeBreakdownData = data;

            renderPrizeBreakdown(data);

            if (data.reward_config) {
                window.rewardConfig = {
                    prefix_or_suffix: parseInt(data.reward_config.prefix_or_suffix) || 0,
                    reward_prefix: data.reward_config.reward_prefix || "",
                    reward_suffix: data.reward_config.reward_suffix || "",
                    use_custom_rewards: parseInt(data.reward_config.use_custom_rewards) || 0,
                    reward_labels: Array.isArray(data.reward_config.reward_labels) ? data.reward_config.reward_labels : []
                };
            }

            const rewardDisplay = window.rewardConfig?.use_custom_rewards == 1
                ? `${window.rewardConfig?.reward_labels?.length || 0} Rewards`
                : formatReward(data.total_prize);

            $("#prizePool").text(rewardDisplay);
            $("#prizePoolHome").text(rewardDisplay);
            $("#totalPrizePool").text(rewardDisplay);

            if (typeof updateLotteryWidgetPrize === "function") {
                updateLotteryWidgetPrize(data.total_prize);
            }
        }

        // Ticket Stats
        const statsBody = $("#ticketStatsBody");
        const cardHeader = $("#ticketStatsCard .card-header");
        const emptyAlert = $("#ticketStatsEmpty");
        const statsCard = $("#ticketStatsCard");

        if (data.ticket_stats && data.ticket_stats.length > 0) {
            lastTicketStatsRoundId = round.round_id;
            lastTicketStatsData = data;

            statsBody.empty();
            data.ticket_stats.forEach((row, index) => {
                statsBody.append(`
                    <tr>
                        <td><span class="badge bg-dark text-white">${index + 1}</span></td>
                        <td><span class="badge bg-dark text-white">${row.user_id}</span></td>
                        <td><span class="badge bg-dark text-white">${row.username}</span></td>
                        <td><span class="badge bg-dark text-white">${parseInt(row.total_tickets).toLocaleString()}</span></td>
                    </tr>
                `);
            });

            if (cardHeader.length) {
                cardHeader.text(`🎟 Current Tickets for Round #${round.round_id}`);
            }


            statsCard.show();
            emptyAlert.hide();
        } else {
            statsCard.hide();
            emptyAlert.show().text("No ticket purchases in the current round yet.");
        }

        // Live Ticket List
        const liveTicketList = document.getElementById("liveTicketList");
        if (liveTicketList) {
            liveTicketList.value = data.ticket_list;
        }

        // Total Tickets
        $("#totalTickets").text(data.tickets_sold);
        $("#totalTicketsHome").text(data.tickets_sold);

        // Update user tickets and wins
        if (typeof data.user_tickets !== "undefined") {
            const t = parseInt(data.user_tickets);
            if (!isNaN(t)) {
                const el = document.getElementById("userTickets");
                if (el) el.innerText = t.toLocaleString();
            }
        }

        if (typeof data.user_wins !== "undefined") {
            const w = parseInt(data.user_wins);
            if (!isNaN(w)) {
                const el = document.getElementById("userWins");
                if (el) el.innerText = w.toLocaleString();
            }
        }
    })
    .catch(error => {
        
    });
}

setInterval(runUnifiedSyncController, 5000);
runUnifiedSyncController(); // run immediately on load