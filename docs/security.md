# Security

## 1. Login lockout (Confirmed 2026-10-30; implemented 2026-10-30)

Applies to `POST /api/v1/login`.

### 1.1 Rules (Confirmed)

| # | Rule |
|---|---|
| R1 | 3 wrong passwords → the login is **frozen for 15 minutes**. |
| R2 | After the freeze, the user has **2 more attempts**. If both fail, the account is **locked**. |
| R3 | Only a **wrong password for an existing username** counts. A correct password from an unregistered device (IMEI mismatch) does not count. Unknown usernames are not tracked. |
| R4 | The counter goes back to 0 **only on a successful login**. Failures are not forgotten over time. |
| R5 | A locked account can **only** be unlocked with the artisan command `user:unlock` (section 1.4). |
| R6 | Freeze, lock and unlock are written to `audit_logs` (section 1.5). |

Also part of the approved design:

- While frozen or locked, every attempt is rejected **without checking the password** and is **not counted**. A correct password is rejected too.
- After the freeze ends, a successful login resets the counter, so the user gets the full 3 attempts again.

Counter sequence: failures 1 and 2 → 401; failure 3 → freeze; (15 minutes later) failure 4 → 401; failure 5 → lock.

The limits are `Constants::LOGIN_ATTEMPTS_BEFORE_FREEZE` (3), `LOGIN_FREEZE_MINUTES` (15) and `LOGIN_ATTEMPTS_AFTER_FREEZE` (2).

### 1.2 Responses

| Situation | Status | Body |
|---|---|---|
| Wrong password (failures 1, 2, 4) or unknown username | 401 | `{"message": "Invalid credentials."}` |
| 3rd failure, or any attempt while frozen | 429 | `{"message": "Too many failed login attempts. Please try again in N minutes.", "retry_after": <seconds>}` and a `Retry-After` header |
| 5th failure, or any attempt while locked | 423 | `{"message": "Your account is locked due to too many failed login attempts. Please contact your administrator."}` |
| Correct password, unregistered device | 403 | `{"message": "Device not recognized. Please use your registered device."}` |

The minutes in the 429 message are rounded up. The mobile app must handle 429 and 423.

Known trade-off: the 429 and 423 responses reveal that a username exists. Unknown usernames always get the normal 401.

### 1.3 Storage

Columns on `users` (migration `2026_10_30_000000_add_login_lockout_columns_to_users_table`):

- `failed_login_attempts` — wrong passwords since the last successful login.
- `login_frozen_until` — end of the current freeze (null when not frozen).
- `locked_at` — when the account was locked (null when not locked).

The logic is in `App\Services\AuthService`. A failed attempt is counted inside a transaction that locks the user row, so parallel requests cannot go past the limits.

### 1.4 Unlocking

```
php artisan user:unlock {username}
```

- Clears the lock and any freeze, and resets the counter to 0.
- Account not locked or frozen → prints a message and changes nothing.
- Unknown username → error, exit code 1.

On Render, run it from the service shell.

### 1.5 Audit log

One `audit_logs` row per event:

| Column | Value |
|---|---|
| `action` | `login_frozen`, `login_locked` or `login_unlocked` |
| `table_name` / `record_id` | `users` / the account's id |
| `user_id` | the account's id for freeze and lock; null for the artisan unlock (no logged-in actor) |
| `ip_address`, `device_id` | request IP and the IMEI sent with the login (freeze and lock only) |
| `old_values` / `new_values` | `failed_login_attempts`, `login_frozen_until`, `locked_at` |

Passwords are never logged.

### 1.6 Not covered (open)

- No throttling for unknown usernames or by IP address. Recommended follow-up: Laravel `throttle` middleware on `/login` (needs approval).
- Existing tokens are not revoked when an account is locked.
- No back-office or API unlock.
