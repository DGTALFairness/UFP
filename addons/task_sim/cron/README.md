# Task Simulation Addon

This addon simulates task completions that award tickets into the Universal Fairness Protocol (UFP) system.
It is designed for sandbox testing of task-based reward systems.

## ⚙️ Simulation Script

To simulate task-based entries, use the following cron-compatible script:

```
/addons/task_sim/cron/task_tickets_script.php
```

Running this script will inject synthetic "completed task" entries into the UFP ticket pool.

### ⚠️ Warning

This simulation is intended for local testing and development environments **only**.
Do not use it in production unless you are intentionally simulating traffic.

## 🕒 Example Cron

```
* * * * * php /your/path/to/addons/task_sim/cron/task_tickets_script.php
```
