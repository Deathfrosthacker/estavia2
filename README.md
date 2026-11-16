# Estavia Risk & Security Solutions Ltd — Website v2

A complete, database-driven website for Estavia: PHP + MySQL backend, an admin
panel for quote requests, and a hardened, improved front end (navy + gold theme).

## What's new vs the old static site

| Before (v1)                          | Now (v2)                                             |
|--------------------------------------|------------------------------------------------------|
| Static HTML only — no database       | MySQL database (services, quote requests, admin users, audit of login attempts) |
| Quote form only opened WhatsApp      | Quote form saves to the database **and** WhatsApp fallback |
| No admin area                        | `/admin` panel: view, filter, update and delete quote requests |
| Services hard-coded in HTML          | Services loaded from the database (static fallback if DB is down) |
| No attack protection                 | Prepared statements, CSRF tokens, bcrypt, login rate-limiting, XSS escaping, hardened session cookies, `.htaccess` hardening (see `SECURITY.md`) |
| No SEO structure                     | Open Graph tags, JSON-LD business schema, semantic HTML |

## Project structure

```
estavia-website/
├── index.php            # Homepage (services pulled from MySQL)
├── config/config.php    # Database credentials — EDIT BEFORE UPLOAD
├── includes/            # DB connection (PDO), security helpers
├── api/quote.php        # Quote form endpoint (CSRF + prepared statements)
├── admin/               # Admin panel (login.php, index.php, logout.php)
├── assets/              # Images & logo
├── css/  js/            # Front-end styles & scripts
├── database.sql         # Import this to create the database + sample data
├── .htaccess            # Apache hardening (auto-applies on Truehost)
├── README.md            # This file
└── SECURITY.md          # Simulated attack test results & mitigations
```

## Deployment on Truehost (step by step)

1. **Create the database** — cPanel → *MySQL® Database Wizard*:
   - Database: `estavia_db`
   - User: `estavia_user`
   - Password: a strong password (save it)
   - Assign the user to the database with **ALL PRIVILEGES**.

2. **Import the schema** — cPanel → *phpMyAdmin* → select `estavia_db` →
   **Import** → choose `database.sql` → Go. This creates all tables and
   sample data (19 services, demo quote requests, admin account).

3. **Edit `config/config.php`** — replace `DB_USER` and `DB_PASS` with the
   credentials from step 1. Leave `DB_HOST` as `localhost`.

4. **Upload the files** — cPanel → *File Manager* → open
   `public_html` (or your domain's document root) → Upload the whole
   `estavia-website` folder's **contents** (all files and subfolders).
   `index.php` must sit directly in `public_html`.

5. **Point your domain** — In cPanel → *Domains*, make sure your bought domain
   is assigned to the same document root. DNS is usually already handled by
   Truehost if you bought the domain from them.

6. **Enable SSL** — cPanel → *SSL/TLS* (free Let's Encrypt). Then uncomment
   the HTTPS redirect block at the bottom of `.htaccess`.

7. **Log in to the admin panel** at `https://yourdomain.com/admin/`
   - Username: `admin`
   - Password: `Estavia@2026`
   - **Change this password immediately** (see below).

### Changing the admin password

Run this once on your PC (needs PHP installed), or ask your developer:

```bash
php -r "echo password_hash('YourNewStrongPassword', PASSWORD_DEFAULT), PHP_EOL;"
```

Then in phpMyAdmin run:

```sql
UPDATE estavia_db.admin_users
SET password_hash = 'PASTE_HASH_HERE'
WHERE username = 'admin';
```

## Local testing

```bash
mysql -u root < database.sql            # create DB + data
# edit config/config.php for local creds
php -S localhost:8000 -t .              # built-in dev server
# open http://localhost:8000/  and  http://localhost:8000/admin/
```

## Maintenance notes

- Quote requests arrive in **Admin → Quote Requests**. New requests show
  status `new`; update them to `contacted` / `quoted` / `closed` as you work.
- To add/remove services: phpMyAdmin → `services` table (or build a CRUD
  screen later using the same pattern as the admin panel).
- The front page degrades gracefully: if MySQL is down it shows a cached
  service list instead of crashing.
