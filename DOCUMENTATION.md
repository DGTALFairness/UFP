# Universal Fairness Protocol (UFP) — System Documentation

This documentation outlines the technical setup and requirements of the **Universal Fairness Protocol (UFP)** as discovered via the internal `info.php` script. This file is a developer-facing reference and should be included in the root of all distributed builds.

---

## 📦 Server Requirements

- **PHP Version:** 8.1+ recommended
- **Required Extensions:**
  - `mysqli`
  - `json`
  - `mbstring`
  - `ctype`
  - `fileinfo`
  - `curl`
  - `openssl`

Make sure these extensions are enabled in your `php.ini`.

---

## ⚙️ Cron Job Setup

To ensure rounds close, winners are selected, and fairness validation remains real-time, set the following cron:

```bash
* * * * * php /path/to/cron/cron.php >/dev/null 2>&1
```

This cron should run **every minute**. It handles:
- Ending rounds
- Generating winning segments
- Triggering auto-save of completed rounds
- Pushing updates to logs and stats

---

## 🗃️ Database Notes

All UFP installs require importing the provided `install/database.sql` to set up core tables.

Key tables include:
- `rounds` — current and active round metadata
- `current_round_tickets` — all active entries
- `winning_round_tickets` — records of SHA-256-based winners
- `fairness_ticket_logs` — segment chain and round hash tracking
- `settings` — controls core configuration
- `users` — user table (account_balance, admin flag, etc.)

Refer to the `install/database.sql` file for structure and sample data.

---

## 🔐 File Permissions

- Ensure `/cron/cron.php` is executable by your cron system
- Directories like `/uploads` (if any used by addons) must be writable by the web server

---

## 🔁 Fairness Engine Summary

The UFP system uses:
- SHA-256 hashes from the ticket pool
- Sliding-window segment extraction
- Segment-chain logging and validation
- Optional rehashing tiers (manual or auto-tiered)

Fairness logs are public and segment chains are verifiable via `/verify_rounds.php`.

---

## 🧪 Testing the System

Visit:
- `/` — to interact with the system in real-time
- `/verify_rounds.php` — to check fairness, rehashes, and winner proof


These interfaces are included as **demos** and not intended for production. They exist to help developers understand how the Universal Fairness Protocol works.

---

## 💡 Developer Tips

- `settings` table drives nearly everything in the system (ticket pricing, reward logic, etc.)
- Use `lang.php` for editable front-end wording
- All ticket actions are written using strict format: `User-ID-{id}:{middle-text}-{ticket}`
- Keep `fairness_ticket_logs.segment_chain` valid JSON or your verifier will fail
- To simulate users or tasks, use the included demo users and `task_addon_jobs`

---

## 📩 Contact

Maintainer: **DgtalFairness**  
Website: https://universalfairnessprotocol.com  
Email: info@universalfairnessprotocol.com  
License info: https://universalfairnessprotocol.org

---

## 💡 UFP Mantra

**Live List = Final List → Final List = SHA-256 Hash → SHA-256 Hash = Segment → Segment = Public Formula → Public Formula = Winner**

This mantra expresses the chain of fairness that underpins every decision in UFP. It's not just a concept — it's a system of trust that is visible, replicable, and verifiable.
