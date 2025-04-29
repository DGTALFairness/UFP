# Lottery Simulation Addon

This addon simulates real-time user entries into the UFP lottery system for testing purposes.

## ⚙️ Simulation Script

The included bot script allows automated ticket injection:

```
/addons/lottery_sim/cron/lottery_tickets_script.php
```

Use this script during testing to observe round behavior and winner selection.

### ⚠️ Warning

This is not required for production environments. Do not enable in live deployments.

## 🕒 Example Cron

```
* * * * * php /your/path/to/addons/lottery_sim/cron/lottery_tickets_script.php
```
