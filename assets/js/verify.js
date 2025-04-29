/*!
 * UFP Component
 * Dual Licensed under GPLv3 and Commercial
 * Author: DgtalFairness | https://universalfairnessprotocol.com
 */

// Ftch Past Ticket List and Stats
const roundSelect = document.getElementById("roundSelect");

// Check if a round ID was passed via query string
const urlParams = new URLSearchParams(window.location.search);

if (defaultRoundId) {
    let alreadyExists = false;

    // Check if the round ID is already in the dropdown
    for (let i = 0; i < roundSelect.options.length; i++) {
        if (roundSelect.options[i].value === defaultRoundId) {
            alreadyExists = true;
            break;
        }
    }

    // If not in the list, add it with a special note
    if (!alreadyExists) {
        const opt = document.createElement("option");
        opt.value = defaultRoundId;
        opt.textContent = `Round #${defaultRoundId} (Added via Mystats)`;
        roundSelect.appendChild(opt);
    }

    roundSelect.value = defaultRoundId;
    roundSelect.dispatchEvent(new Event("change"));
}

roundSelect.addEventListener("change", function () {
    const roundId = this.value;

    if (!roundId) {
        document.getElementById("ticketList").value = "";
        document.getElementById("copyBtn").disabled = true;
        document.getElementById("verifyBtn").disabled = true;
        resetTicketStats();
        resetRehashOptions();
        return;
    }

    fetch("../system/ajax.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ action: "fetch_ticket_list", lottery_id: roundId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 200) {
            const ticketHashElement = document.getElementById("ticketHash");
            ticketHashElement.innerText = data.ticket_hash;
            ticketHashElement.dataset.originalHash = data.ticket_hash;

            document.getElementById("ticketList").value = data.ticket_list;
            document.getElementById("copyBtn").disabled = false;
            document.getElementById("verifyBtn").disabled = false;

            populateRehashOptions(data.rehash_chain);
            calculateTicketStats(data.ticket_list, data.ticket_hash, data.segment_chain);

            // Add glowing effect to "Add to Verify" button
            const verifyBtn = document.getElementById("verifyBtn");
            if (verifyBtn) {
                verifyBtn.classList.add("breathing-glow");
            }

        } else {
            document.getElementById("ticketList").value = data.message;
            resetTicketStats();
            resetRehashOptions();
        }
    });
});




// Handle Rehash Dropdown
document.getElementById("rehashSelect").addEventListener("change", function () {
    const selectedHash = this.value;
    const selectedTier = this.selectedIndex + 1;

    if (selectedHash === "tier_1") {
        document.getElementById("roundSelect").dispatchEvent(new Event("change"));
        return;
    }

    fetch("../system/ajax.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ action: "fetch_rehash_data", rehash_key: selectedHash })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 200) {
            document.getElementById("ticketHash").innerText = data.ticket_hash;

            const chain = [...document.getElementById("rehashSelect").options]
                .map(opt => opt.value)
                .filter(v => v !== "tier_1");

            // 👇 We want the hash that was rehashed to produce this tier
            const inputHash = selectedTier === 2
                ? document.getElementById("ticketHash").dataset.originalHash // tier 2 is based on original hash
                : chain[selectedTier - 3]; // tier 3 is based on chain[0], tier 4 is chain[1], etc.

            document.getElementById("ticketList").value = inputHash || "❌ Could not resolve input hash.";

            calculateTicketStats(data.ticket_list, data.ticket_hash, data.segment_chain, selectedTier);
        }
    });
});



// Populate Rehash Dropdown
function populateRehashOptions(chain) {
    const rehash = document.getElementById("rehashSelect");
    const container = document.getElementById("rehashContainer");

    rehash.innerHTML = '<option value="tier_1">Tier 1 (Original Hash)</option>';

    if (Array.isArray(chain) && chain.length) {
        chain.forEach((hash, i) => {
            const opt = document.createElement("option");
            opt.value = hash;
            opt.textContent = `Tier ${i + 2}`;
            rehash.appendChild(opt);
        });
        container.classList.remove("d-none");
    } else {
        container.classList.add("d-none");
    }
}


// Calculate Ticket Stats
function calculateTicketStats(list, hash, chain = [], tier = 1) {
    if (!list) return resetTicketStats();

    const ticketIds = list.trim().split(";")
        .filter(e => e.includes(":"))
        .map(e => parseInt(e.split(":")[1].replace(/\D/g, ""), 10))
        .sort((a, b) => a - b);

    if (!ticketIds.length) return resetTicketStats();

    const [first, last] = [ticketIds[0], ticketIds.at(-1)];

    document.getElementById("totalTicketsStat").innerText = ticketIds.length;
    document.getElementById("ticketRangeStat").innerText = `${first} → ${last}`;
    document.getElementById("firstTicketStat").innerText = first;
    document.getElementById("lastTicketStat").innerText = last;
    document.getElementById("ticketHash").innerText = hash;

    const winningList = document.getElementById("winningPositions");
    winningList.innerHTML = "";

    const filtered = chain.filter(s => s.tier == tier);
    if (filtered.length) {
       winningList.innerHTML = `<li class="list-group-item list-group-item-secondary text-center"><b>Tier ${tier} Segments</b></li>` +
    filtered.map(s => `
        <li class="list-group-item d-flex justify-content-center align-self-center w-75 mx-auto px-3 py-2">
            <span>Position #${s.position} - Segment: <b>${s.segment}</b></span>
           <span class="badge rounded-pill ms-3" style="background-color: #e9ecef; color: #333; font-weight: normal;">
            <i class="bi bi-eye cursor-pointer" onclick="highlightSegmentInHash('${s.segment}', 'ticketHash')"></i>
        </span>

        </li>
    `).join("");

    } else {
        winningList.innerHTML = `<li class="list-group-item text-danger">❌ No extracted segments for Tier ${tier}.</li>`;
    }

    document.getElementById("roundStats").classList.remove("d-none");
}


// Reset Stats
function resetTicketStats() {
    ["totalTicketsStat", "ticketRangeStat", "firstTicketStat", "lastTicketStat", "ticketHash"].forEach(id => {
        document.getElementById(id).innerText = "-";
    });
    document.getElementById("winningPositions").innerHTML = "";
    document.getElementById("roundStats").classList.add("d-none");
}

function resetRehashOptions() {
    const rehash = document.getElementById("rehashSelect");
    rehash.innerHTML = '<option value="tier_1">Tier 1 (Original Hash)</option>';
    document.getElementById("rehashContainer").classList.add("d-none");
}


// Copy to Clipboard
function copyToClipboard() {
    const text = document.getElementById("ticketList");
    if (!text.value.trim()) return alert("⚠ No ticket list to copy!");
    navigator.clipboard.writeText(text.value).then(() => {
        const btn = document.getElementById("copyBtn");
        btn.innerText = "✅ Copied!";
        setTimeout(() => btn.innerText = "Copy to Clipboard", 2000);
    });
}


// Transfer to Verification Form
function transferToVerification() {
    const ticketList = document.getElementById("ticketList").value.trim();
    const total = document.getElementById("totalTicketsStat").innerText;
    const first = document.getElementById("firstTicketStat").innerText;
    const tier = document.getElementById("rehashSelect").selectedIndex + 1;

    if (!ticketList || total === "-" || first === "-") return alert("⚠ Invalid ticket data");

    document.getElementById("verifyTicketList").value = ticketList;
    document.getElementById("totalTickets").value = total;
    document.getElementById("firstTicket").value = first;
    document.getElementById("rehashTier").value = `Tier ${tier}`;

    // DELAY SCROLLING to #startVerifyBtn
    setTimeout(() => {
        const startBtn = document.getElementById("startVerifyBtn");
        if (startBtn) {
            startBtn.scrollIntoView({ behavior: "smooth", block: "center" });
            startBtn.classList.add("breathing-glow");
        } else {
            
        }
    }, 500); // Give it a half-second to fully render
}




// Store user's winning tickets
let userWinningTickets = [];

// Verify Winning Tickets (Styled Pale Segments)
async function verifyWinningTickets() {
    document.getElementById("startVerifyBtn").classList.remove("breathing-glow");

    const list = document.getElementById("verifyTicketList").value.trim();
    const total = parseInt(document.getElementById("totalTickets").value);
    const first = parseInt(document.getElementById("firstTicket").value);
    const tier = parseInt(document.getElementById("rehashTier").value.replace(/\D/g, ""));
    const result = document.getElementById("winnerResult");
    const roundId = roundSelect.value;

    if (!list || isNaN(total) || isNaN(first) || isNaN(tier)) {
        result.innerHTML = "<span class='text-danger'>⚠ Please enter all required values.</span>";
        return;
    }

    const msgUint8 = new TextEncoder().encode(list);
    const hashBuffer = await crypto.subtle.digest('SHA-256', msgUint8);
    const hashArray = Array.from(new Uint8Array(hashBuffer));
    const hashHex = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
    const numeric = hashHex.replace(/\D/g, "");

    if (numeric.length < 10) {
        result.innerHTML = "<span class='text-danger'>❌ Not enough digits in hash!</span>";
        return;
    }

    const winners = [];
    let pos = (tier - 1) * 20 + 1;

    // Fetch logged-in user's winning tickets for this round
    const response = await fetch("../system/ajax.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ action: "get_user_winning_tickets", round_id: roundId })
    });
    const ticketData = await response.json();
    if (ticketData.status === 200) {
        userWinningTickets = ticketData.tickets;
    }

    for (let i = 0; i < 20; i++) {
        const slice = numeric.substring(i % (numeric.length - 10), i % (numeric.length - 10) + 10);
        const num = parseInt(slice, 10);
        const ticket = (num % total) + first;
        const isMine = userWinningTickets.includes(ticket);
        const segmentClass = isMine ? 'segment-pill segment-pill-user' : 'segment-pill segment-pill-default';


        winners.push(`
                <li class="list-group-item d-flex flex-column w-100 px-3 py-2 ${isMine ? 'bg-success-subtle text-dark fw-semibold border border-success' : ''}" data-segment="${slice}" style="font-size: 15px;">
                <div class="d-flex align-items-center justify-content-center mb-1">
                    Position #${pos} - Ticket #: ${ticket}
                </div>
                <div class="${segmentClass} w-100 text-center mx-auto">
                    Segment: <span>${slice}</span>
                    <i class="ms-2 bi bi-eye hover-grow cursor-pointer" onclick="highlightSegmentInHash('${slice}')"></i>
                </div>
            </li>
        `);
        pos++;
    }

    // Insert winners and hash
    result.innerHTML = `
    <div class="text-center mb-2">
        <b class="text-success">✅ Winners (Tier ${tier}):</b>
    </div>
    <ul class="list-group mb-3">${winners.join("")}</ul>
    <p class="text-center">
        <b>SHA-256:</b>
        <code id="shaHash">${[...hashHex].map(c => `<span>${c}</span>`).join('')}</code>
    </p>`;
    
    // Smooth scroll to the top of the result section
    result.scrollIntoView({ behavior: "smooth", block: "start" });


    // Remove old user ticket box if it exists
    const existingBox = document.getElementById("userWinningBox");
    if (existingBox) existingBox.remove();

    // Display user's winning tickets below (with round id shown)
    const container = document.createElement("div");
    container.id = "userWinningBox";
    container.className = "alert alert-info text-center mt-4";
    container.innerHTML = `
        <div><b>Round ID #${roundId}:</b></div>
        <div><b>Your Winning Tickets:</b></div>
        <div>${userWinningTickets.length ? userWinningTickets.join(", ") : "❌ You have no winning tickets in this round."}</div>
    `;
    
    result.parentNode.appendChild(container);
    
    
        highlightWinningSegments();
    }




// Highlight Segments
async function highlightWinningSegments() {
    const roundId = document.getElementById("roundSelect").value;
    const tier = document.getElementById("rehashTier").value.replace(/\D/g, "");

    if (!roundId || !tier) return;

    const res = await fetch("../system/ajax.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ action: "fetch_highlight_segments", lottery_id: roundId, selected_tier: tier })
    });
    const data = await res.json();
    if (data.status !== 200) return;

    const segments = data.highlight_segments || [];
    const items = document.getElementById("winnerResult").querySelectorAll("li");

    items.forEach(item => {
        const match = item.innerText.match(/\(Segment: (\d+)\)/);
        if (match && match[1] && segments.some(s => s.segment === match[1])) {
            item.classList.add("text-success", "fw-bold");
        } else {
            item.style.display = "none";
        }
    });
}

function highlightSegmentInHash(segment, targetId = "shaHash") {
    const container = document.getElementById(targetId);
    if (!container) return;

    // Wrap characters in spans if not done already
    if (!container.querySelector("span")) {
        const original = container.textContent;
        container.innerHTML = [...original].map(c => `<span>${c}</span>`).join("");
    }

    const hashSpans = container.querySelectorAll("span");

    // Extract numeric characters only
    const allDigits = Array.from(hashSpans)
        .map(span => span.textContent)
        .filter(char => /\d/.test(char));

    // Generate sliding windows of 10-digit numeric segments
    const slidingSegments = [];
    for (let i = 0; i <= allDigits.length - 10; i++) {
        slidingSegments.push({
            segment: allDigits.slice(i, i + 10).join(""),
            index: i
        });
    }

    // Reset all highlights
    hashSpans.forEach(span => {
        span.style.backgroundColor = "";
        span.style.borderRadius = "";
        span.classList.remove("breathing-glow");
    });

    // Match the segment and highlight the corresponding span positions
    const match = slidingSegments.find(s => s.segment === segment);
    if (match) {
        let digitCount = 0;
        let firstMatchEl = null;

        for (let i = 0; i < hashSpans.length; i++) {
            const char = hashSpans[i].textContent;
            if (/\d/.test(char)) {
                if (digitCount >= match.index && digitCount < match.index + 10) {
                    hashSpans[i].classList.remove("breathing-glow"); // reset
                    void hashSpans[i].offsetWidth;                   // force reflow
                    hashSpans[i].classList.add("breathing-glow");
                    hashSpans[i].style.borderRadius = "3px";
                
                    if (!firstMatchEl) {
                        firstMatchEl = hashSpans[i];
                    }
                }

                digitCount++;
            }
        }

        // Smooth scroll to first highlighted span
        if (firstMatchEl) {
            const scrollPadding = 100;
            const topPos = firstMatchEl.getBoundingClientRect().top + window.pageYOffset - scrollPadding;

            window.scrollTo({
                top: topPos,
                behavior: "smooth"
            });
        }
    }
}

window.addEventListener("DOMContentLoaded", () => {
    if (typeof defaultRoundId !== "undefined" && defaultRoundId) {
        const roundSelect = document.getElementById("roundSelect");
        const found = [...roundSelect.options].some(opt => opt.value === defaultRoundId);

        if (!found) {
            const opt = document.createElement("option");
            opt.value = defaultRoundId;
            opt.textContent = `Round #${defaultRoundId} (Added via Mystats)`;
            roundSelect.appendChild(opt);
        }

        roundSelect.value = defaultRoundId;
        roundSelect.dispatchEvent(new Event("change"));

        // SCROLL to Verify Button
        const verifyBtn = document.getElementById("verifyBtn");
        if (verifyBtn) {
            setTimeout(() => {
                verifyBtn.scrollIntoView({ behavior: "smooth", block: "center" });
                verifyBtn.classList.add("breathing-glow");
            }, 800); // Small delay to allow page/rendered content to load
        }
    }
});


verifyBtn.addEventListener("click", () => {
    verifyBtn.classList.remove("breathing-glow");
});

roundSelect.addEventListener("change", function () {
    const roundId = this.value;

    if (!roundId) {
        document.getElementById("ticketList").value = "";
        document.getElementById("copyBtn").disabled = true;
        document.getElementById("verifyBtn").disabled = true;
        resetTicketStats();
        resetRehashOptions();
        return;
    }

    fetch("../system/ajax.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ action: "fetch_ticket_list", lottery_id: roundId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 200) {
            const ticketHashElement = document.getElementById("ticketHash");
            ticketHashElement.innerText = data.ticket_hash;
            ticketHashElement.dataset.originalHash = data.ticket_hash;

            document.getElementById("ticketList").value = data.ticket_list;
            document.getElementById("copyBtn").disabled = false;
            document.getElementById("verifyBtn").disabled = false;

            populateRehashOptions(data.rehash_chain);
            calculateTicketStats(data.ticket_list, data.ticket_hash, data.segment_chain);

            // Add glowing effect to "Add to Verify" button
            const verifyBtn = document.getElementById("verifyBtn");
            if (verifyBtn) {
                verifyBtn.classList.add("breathing-glow");
                
                // Ensure smooth scroll to the "Add to Verify" button after it's enabled
                setTimeout(() => {
                    verifyBtn.scrollIntoView({ behavior: "smooth", block: "center" });
                }, 500);  // Adjust delay time if needed
            }
        } else {
            document.getElementById("ticketList").value = data.message;
            resetTicketStats();
            resetRehashOptions();
        }
    });
});


document.getElementById('generateHashBtn').addEventListener('click', async function () {
  const input = document.getElementById('ticketInput').value.trim();
  const resultContainer = document.getElementById('hashResultContainer');
  const hashDisplay = document.getElementById('hashResult');

  if (!input) {
    resultContainer.style.display = 'none';
    alert('⚠️ Please paste a ticket list before generating.');
    return;
  }

  // Encode and hash
  const encoder = new TextEncoder();
  const data = encoder.encode(input);
  const hashBuffer = await crypto.subtle.digest('SHA-256', data);
  const hashArray = Array.from(new Uint8Array(hashBuffer));
  const hashHex = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');

  // Display result
  hashDisplay.textContent = hashHex;
  resultContainer.style.display = 'block';
});

