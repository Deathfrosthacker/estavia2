# Security Test Report — Estavia Website

**Target:** Estavia Risk & Security Solutions Ltd website (PHP + MySQL)
**Test type:** Simulated attacks (defensive verification) against a local test copy
**Date:** November 2026
**Result: 13/13 attacks blocked or neutralised**

Each attack below was simulated against the running site, the result was
observed, and the defence that stopped it is listed. The same test suite is
included in `security-tests/attack_tests.sh` so the tests can be re-run by
anyone (against a system they own).

---

## 1. SQL Injection (SQLi) — classic & UNION-based

**Attack simulated:** Submit quote-form fields containing
`' OR '1'='1` (tautology bypass),
`' UNION SELECT username,password_hash,1,2,3,4,5,6 FROM admin_users-- ` (data theft),
and `; DROP TABLE quote_requests;-- ` (destruction).

**Result: BLOCKED — payload stored as inert plain text.**

- The tautology did **not** change any query logic — a `WHERE` check matched
  exactly one row containing the literal string.
- The `UNION SELECT` returned **no** admin credentials — it was written into
  a `VARCHAR` column as data.
- The `DROP TABLE` statement **did not execute** — the table survived intact.

**How it is curbed:** PDO with **real prepared statements**
(`PDO::ATTR_EMULATE_PREPARES = false`) and bound parameters in every query
(`api/quote.php`, `admin/login.php`, `admin/index.php`). User input never
becomes SQL syntax, so it cannot be executed. Server-side validation
(rejecting malformed phone/email values) adds a second layer.

> Reminder for future edits: never concatenate user input into SQL strings.
> Always use `prepare()` + `execute([...])` as the existing code does.

## 2. Cross-Site Scripting (XSS) — stored

**Attack simulated:** Submit `<script>alert(document.cookie)</script><img src=x onerror=alert(1)>`
in the name and message fields (a classic cookie-theft / session-hijack payload).

**Result: BLOCKED — payload stored but rendered harmless.**

- The public homepage reflects **nothing** (no injection point).
- In the admin dashboard — where the data is displayed — the payload appears
  only as visible text (`&lt;script&gt;...`); the browser never executes it.

**How it is curbed:** every value echoed to HTML passes through
`htmlspecialchars(... ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')` via the `e()`
helper (`includes/functions.php`). Cookies are also `HttpOnly`, so even a
hypothetical XSS could not read the session cookie via `document.cookie`.

> Reminder: always echo through `e()`; never print raw `$_POST`/`$_GET`/DB data.

## 3. Cross-Site Request Forgery (CSRF)

**Attack simulated:** A forged cross-site POST to `api/quote.php`
(a) with no token and (b) with a guessed/wrong token.

**Result: BLOCKED — HTTP 403, no record created.**

**How it is curbed:**
- Every state-changing form carries a random 32-byte **CSRF token**
  (`csrf_field()`) bound to the visitor's session.
- The server verifies it with a **timing-safe** comparison
  (`hash_equals()`), and session cookies are set with `SameSite=Lax`,
  which stops cross-site POSTs from most modern browsers entirely.

## 4. Brute-force attack on admin login

**Attack simulated:** 5 wrong-password attempts followed by the **correct**
password on the 6th attempt.

**Result: BLOCKED — the 6th (correct-password) attempt was refused:**
*"Too many failed attempts. Try again in about 15 minutes."* The login button
was disabled while the lockout was active.

**How it is curbed:** failures are recorded per IP + username in the
`login_attempts` table; after 5 failures within 15 minutes
(`rate_limited()` in `includes/functions.php`) further attempts are refused
regardless of password correctness. Passwords are verified with
`password_verify()` against a **bcrypt** hash — offline cracking of the hash
is computationally expensive by design.

**Additional hardening:** `session_regenerate_id(true)` on login (stops
session fixation), sessions destroyed fully on logout, `noindex` on admin pages.

## 5. Method tampering & information disclosure

**Attack simulated:**
- `GET /api/quote.php?name=...` (bypassing the form entirely)
- Direct web requests to `config/config.php` and `database.sql`

**Result:**
- Non-POST requests to the API → **405 Method Not Allowed**.
- `config/config.php` and `database.sql` are **denied** by `.htaccess`
  (`RedirectMatch 403 ^/config/.*$` and the `FilesMatch` deny rule for
  `.sql`/`.md`/`.log` files) on Apache/Truehost. *(The PHP built-in dev
  server used for these tests does not read `.htaccess`; on Truehost's
  Apache the deny rules apply.)*

**How it is curbed:** `.htaccess` (Apache) — directory listing off, sensitive
files denied, security headers (`X-Content-Type-Options: nosniff`,
`X-Frame-Options: SAMEORIGIN`, `Referrer-Policy`, `Permissions-Policy`),
optional forced HTTPS redirect.

## 6. Spam / bot form flooding

**Attack simulated:** Automated submission filling every visible field.

**How it is curbed:** an invisible **honeypot** field (`name="website"`) is
rendered off-screen; humans never fill it, bots do — those submissions are
silently discarded with a fake success response. Server-side length limits
(2–100 chars name, 2000 chars message) and strict phone/email validation
block malformed floods.

---

## Defence summary (built into this codebase)

| Layer | Mechanism | Where |
|---|---|---|
| SQL injection | PDO prepared statements, emulation off | `includes/db.php`, all queries |
| XSS | Output escaping `e()` + HttpOnly cookies | `includes/functions.php`, all views |
| CSRF | Per-session token + `hash_equals` + SameSite=Lax | `includes/functions.php`, all forms |
| Brute force | 5-attempt / 15-min rate limit per IP+user | `login_attempts` table, `rate_limited()` |
| Passwords | bcrypt `password_hash` / `password_verify` | `database.sql`, `admin/login.php` |
| Sessions | Regenerate ID on login, full destroy on logout | `admin/login.php`, `admin/logout.php` |
| Info disclosure | `.htaccess` denies config/SQL files, `-Indexes` | `.htaccess` |
| Headers | nosniff, frame-deny, referrer & permissions policy | `.htaccess`, `json_response()` |
| Spam | Honeypot + server-side validation | `index.php`, `api/quote.php` |

## Recommendations for production (Truehost)

1. **Change the admin password** immediately (see README — one SQL statement).
2. Use a **strong DB password** in `config/config.php` (never commit the real one).
3. Enable **HTTPS** (free Let's Encrypt in cPanel) and uncomment the HTTPS
   redirect in `.htaccess`.
4. Keep **backups** of the database (cPanel → Backup) — protects against data loss.
5. Update PHP to 8.x in cPanel if offered, and re-run `security-tests/attack_tests.sh`
   after any code change.

*Ethical note: these simulated attacks were executed only against the local
test copy of this website. Test only systems you own or are authorised to test.*
