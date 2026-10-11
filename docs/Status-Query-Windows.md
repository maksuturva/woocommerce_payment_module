# Svea Payments: Status Query Time Windows

The `sveapafi_check_pending_payments` background cron task evaluates pending/delayed payments on a 5-minute interval. However, it does not query the Svea API every 5 minutes for every order. Instead, it respects strict "Time Window" rules to prevent rate-limiting and unnecessary API load.

## Timeline Graphic

```mermaid
gantt
    title Background Status Query Frequency (Time Since Order Creation)
    dateFormat  X
    axisFormat %s
    
    section Initial Check
    First run (Immediately)            :a1, 0, 5m
    
    section Re-check Intervals
    Every 20 mins                      :a2, 5m, 2h
    Every 2 hours                      :a3, 2h, 24h
    Every 12 hours                     :a4, 24h, 168h
    
    section Abandoned
    No more checks (7+ days)           :a5, 168h, 180h
```

## Logic Breakdown

When the cron job processes a pending payment, it calculates two values:
- **Age:** Time elapsed since the order was placed (`date_added`).
- **Idle Time:** Time elapsed since the last API check (`date_updated`).

The decision to execute an API status query follows this cascading rule set:

### 1. The Initial Check (New Orders)
- **Condition:** Has never been checked (`date_updated` is empty).
- **Rule:** Checks immediately (as soon as the 5-minute cron runs), as long as the order is less than 7 days old.

### 2. The Active Window (5 mins – 2 hours)
- **Condition:** Order age is between `5 minutes` and `2 hours`.
- **Rule:** Executes a check if the last check was more than **20 minutes ago**.

### 3. The Cooling Window (2 hours – 24 hours)
- **Condition:** Order age is between `2 hours` and `24 hours`.
- **Rule:** Executes a check if the last check was more than **2 hours ago**.

### 4. The Cold Window (Up to 7 days)
- **Condition:** Order age is less than `168 hours` (7 days).
- **Rule:** Executes a check if the last check was more than **12 hours ago**.
- *(Note: This primarily applies to orders between 24 hours and 7 days old, acting as a final net).*

### 5. Expiration (7+ days)
- **Condition:** Order age is `> 168 hours` (7 days).
- **Rule:** The order is completely ignored. No further background status queries will be made.
