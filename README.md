# Universal Fairness Protocol (UFP)

> Trust Nothing — Verify Everything

The **Universal Fairness Protocol (UFP)** is a fully transparent, cryptographically secure, and modular fairness engine built on SHA-256 hashing and public deterministic formulas.  
It empowers developers to create round-based systems where every outcome is verifiable by anyone — without relying on trust, hidden logic, or closed backend systems.

Designed for flexibility, UFP can power any kind of fairness-driven application:  
reward distributions, randomized selections, participation incentives, airdrops, lotteries, task-based campaigns, loyalty programs, giveaways and beyond.

At its core, UFP is about **proving outcomes mathematically and publicly**, ensuring **absolute transparency and public auditability**.

---

## 🌐 Core Principles

- Fully deterministic and verifiable outcomes
- Public live ticket lists → SHA-256 hashing → Sliding segment extraction → Mathematical winner resolution
- Modular addon architecture — extend functionality without modifying the core
- Self-hosted, open-source, no vendor lock-in
- Full documentation and public verification tools included
- Built for scalability across various fairness-based use cases

---

## 📜 UFP Mantra

> **Live List = Final List → Final List = SHA-256 Hash → SHA-256 Hash = Segment → Segment = Public Formula → Public Formula = Winner**

This mantra defines the entire Universal Fairness Protocol flow —  
from live entries to cryptographic sealing to winner resolution —  
making every outcome **fully reproducible** and **publicly verifiable**.

---

## 📦 What's Included

| Component | Description |
|:---|:---|
| **UFP Core Engine** | Handles automated round cycling, entry logging, hash generation, segment extraction, and reward resolution. |
| **Addon Framework** | Extend UFP easily with modular addons without touching core logic. |
| **Public Verification Tools** | Users can independently validate every round, ticket list, hash, and winner extraction. |
| **Demo Simulations** | Examples demonstrating how UFP can be adapted (Check-In, Lottery, Task-Based Systems). |
| **Transparent Documentation** | Learn how UFP works internally and how to integrate custom addons. |

---

## 📥 Installation Guide

> This project is mirrored across several platforms. The GitHub repository is the canonical source for all clone operations and version references.

1. **Clone the repository (GitHub canonical source)**:

```bash
git clone https://github.com/DGTALFairness/UFP.git
```

2. **Upload the source files to your server.**

3. **Configure your database connection**:
   - Edit `/db/database.php` with your database credentials.

4. **Import the core SQL schema**:

```bash
mysql -u your_user -p your_database_name < install/database.sql
```

5. **(Optional) Import addon demo SQL files**:

```bash
mysql -u your_user -p your_database_name < addons/checkin_sim/install/database.sql
mysql -u your_user -p your_database_name < addons/lottery_sim/install/database.sql
mysql -u your_user -p your_database_name < addons/task_sim/install/database.sql
```

6. **Set up the cron job**:
   - Add a scheduled task to run every minute:

```bash
* * * * * php /path/to/your/cron/cron.php >/dev/null 2>&1
```

7. **Access your UFP instance**:
   - Visit `/index.php` to view the live protocol engine.


---

## 🔒 Licensing

The Universal Fairness Protocol is offered under a **dual license model**:

- **GPLv3** (Open-Source License):  
  Freedom to use, modify, and distribute under the conditions of GPLv3.

- **Commercial License** (For Enterprise/SaaS Deployment):  
  Available for businesses seeking proprietary usage without GPL requirements.

Please refer to:
- [LICENSE.txt](LICENSE.txt) — Full licensing terms
- [README_LICENSING.md](README_LICENSING.md) — Human-readable explanation

Commercial licenses are available at:  
👉 [https://dgtalfairness.gumroad.com/l/UFP](https://dgtalfairness.gumroad.com/l/UFP)


---

## 👨‍💻 Authors

- Developed by **DgtalFairness**
- Universal Fairness Protocol Official Sites:
  - [universalfairnessprotocol.com](https://universalfairnessprotocol.com)
  - [universalfairnessprotocol.org](https://universalfairnessprotocol.org)

- Author Profiles:
  - [GitHub](https://github.com/DGTALFairness)
  - [GitLab](https://gitlab.com/DGTALFairness)
  - [Bitbucket](https://bitbucket.org/dgtalfairness/)
  - [Codeberg](https://codeberg.org/DGTALFairness)
  - [SourceHut](https://sr.ht/~dgtalfairness/)

- Public Repository Links:
  - [GitHub](https://github.com/DGTALFairness/UFP)
  - [GitLab](https://gitlab.com/DGTALFairness/UFP)
  - [Bitbucket](https://bitbucket.org/dgtalfairness/ufp)
  - [Codeberg](https://codeberg.org/DGTALFairness/UFP)
  - [SourceHut](https://git.sr.ht/~dgtalfairness/UFP)

---

## ⚖️ Legal Notice

The UFP demo simulations (Check-In, Lottery, Task) are provided for **educational and testing purposes only**.  
They are not intended for production gambling, wagering, or regulated gaming use.

By using UFP or its demo modules, you agree to comply with all applicable laws in your jurisdiction.  
Please refer to [LEGAL_NOTICE.md](LEGAL_NOTICE.md) for full details.

---

## 🧠 Why UFP Matters

UFP makes fairness **provable** —  
using pure cryptography, public audits, and mathematical formulas —  
eliminating the need for trust or closed backend assumptions.

Every round, every ticket, every winning position can be independently verified by anyone —  
even with a calculator.

Because fairness isn't just a feature.  
It's the foundation.

---

## 🌍 Live Demos

- [Check-In Demo](https://checkin.universalfairnessprotocol.com)
- [Lottery Demo](https://lottery.universalfairnessprotocol.com)
- [Task Demo](https://task.universalfairnessprotocol.com)


# 🚀 Trust Nothing. Verify Everything.
