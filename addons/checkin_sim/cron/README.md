# Check-In Simulation Addon

This addon provides a simulated check-in mechanism to award tickets into the Universal Fairness Protocol (UFP) system.
It is intended solely for testing and demonstration purposes.

## ⚙️ Simulation Script

To simulate entries, use the following cron-compatible script:

```
/addons/checkin_sim/cron/checkin_tickets_script.php
```

Run this script every 1 minute to inject synthetic check-in tickets for testing.

### ⚠️ Warning

This script is for local testing or sandbox environments **only**. Do not run it on live production systems.

## 🕒 Example Cron

```
* * * * * php /your/path/to/addons/checkin_sim/cron/checkin_tickets_script.php
```
