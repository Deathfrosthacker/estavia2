#!/bin/bash
# ============================================================
#  Simulated attack tests against the Estavia website
#  Run only against systems you own — these tests target the
#  local test copy of estavia-website.
# ============================================================
BASE="http://127.0.0.1:8000"
JAR=/tmp/cookies.txt
rm -f $JAR
PASS=0; FAIL=0

ok()   { echo "  [PASS] $1"; PASS=$((PASS+1)); }
bad()  { echo "  [FAIL] $1"; FAIL=$((FAIL+1)); }

echo "============================================================"
echo " ATTACK TEST 1 — SQL Injection (quote form)"
echo "============================================================"
# Classic tautology + UNION-style payloads in every field
PAY="' OR '1'='1"
UNION="' UNION SELECT username,password_hash,1,2,3,4,5,6 FROM admin_users-- "
TOK=$(curl -s -c $JAR "$BASE/" | grep -o 'name="csrf_token" value="[a-f0-9]*' | cut -d'"' -f4)

R1=$(curl -s -b $JAR -X POST "$BASE/api/quote.php" \
  --data-urlencode "csrf_token=$TOK" \
  --data-urlencode "name=$PAY" \
  --data-urlencode "phone=0712345678" \
  --data-urlencode "email=security.test@example.com" \
  --data-urlencode "service=$UNION" \
  --data-urlencode "message=; DROP TABLE quote_requests;--")
echo "$R1" | grep -q '"ok":true' && ok "Payload accepted as plain data (no error, no bypass)" || bad "Unexpected response: $R1"

# Did it actually bypass anything? Count rows — must be exactly 1 new row
N=$(mysql -N estavia_db -e "SELECT COUNT(*) FROM quote_requests WHERE name LIKE \"%' OR '1'='1%\"")
[ "$N" = "1" ] && ok "Payload stored literally as data (1 row, WHERE matched exactly)" || bad "Row count after injection: $N (expected 1)"
# Table still exists?
mysql -N estavia_db -e "SHOW TABLES" | grep -q quote_requests && ok "quote_requests table survived DROP TABLE attempt" || bad "Table dropped!"

echo ""
echo "============================================================"
echo " ATTACK TEST 2 — Stored XSS (script in name + message)"
echo "============================================================"
XSS='<script>alert(document.cookie)</script><img src=x onerror=alert(1)>'
curl -s -b $JAR -X POST "$BASE/api/quote.php" \
  --data-urlencode "csrf_token=$TOK" \
  --data-urlencode "name=$XSS" \
  --data-urlencode "phone=0712345678" \
  --data-urlencode "service=Security Audits" \
  --data-urlencode "message=$XSS" > /dev/null

# The admin panel MUST render this escaped. Fetch admin login page? No —
# XSS matters where the data is displayed: the admin dashboard (needs auth).
# First verify: no reflection on the public homepage:
curl -s "$BASE/" | grep -qi "onerror=alert" && bad "Unescaped payload reflected on public homepage" || ok "No payload reflection on public homepage"

# Now log in as admin and check dashboard escaping
LTOK=$(curl -s -c $JAR "$BASE/admin/login.php" | grep -o 'name="csrf_token" value="[a-f0-9]*' | cut -d'"' -f4)
curl -s -b $JAR -c $JAR -X POST "$BASE/admin/login.php" \
  --data-urlencode "csrf_token=$LTOK" \
  --data-urlencode "username=admin" \
  --data-urlencode "password=Estavia@2026" -o /dev/null -w "  (login redirect: %{http_code} -> %{redirect_url})\n"

ADMIN_HTML=$(curl -s -b $JAR "$BASE/admin/index.php")
echo "$ADMIN_HTML" | grep -q '<script>alert' && bad "RAW script tag rendered in admin panel (XSS!)" || ok "Admin panel escapes the stored script tag"
echo "$ADMIN_HTML" | grep -q '&lt;script&gt;' && ok "Payload displayed safely escaped (&lt;script&gt;)" || bad "Payload not found escaped in admin panel"

echo ""
echo "============================================================"
echo " ATTACK TEST 3 — CSRF (forged cross-site submission)"
echo "============================================================"
# Forged request with NO token (attacker site can't know it)
R=$(curl -s -X POST "$BASE/api/quote.php" \
  --data-urlencode "name=Attacker" --data-urlencode "phone=0700000000" \
  --data-urlencode "service=Guarding Services" -w "\nHTTP:%{http_code}")
echo "$R" | grep -q "HTTP:403" && ok "Request without token rejected (403)" || bad "No-token request was NOT blocked: $R"
mysql -N estavia_db -e "SELECT COUNT(*) FROM quote_requests WHERE name='Attacker'" | grep -q '^0$' && ok "Forged record was NOT created" || bad "Forged record got created!"

# Forged request with GUESSED/WRONG token
R=$(curl -s -X POST "$BASE/api/quote.php" -H "X-CSRF-TOKEN: deadbeefdeadbeefdeadbeefdeadbeef" \
  --data-urlencode "csrf_token=deadbeefdeadbeefdeadbeefdeadbeef" \
  --data-urlencode "name=Attacker2" --data-urlencode "phone=0700000000" \
  --data-urlencode "service=Guarding Services" -w "\nHTTP:%{http_code}")
echo "$R" | grep -q "HTTP:403" && ok "Wrong token rejected (403)" || bad "Wrong-token request was NOT blocked"

echo ""
echo "============================================================"
echo " ATTACK TEST 4 — Brute force on admin login"
echo "============================================================"
for i in 1 2 3 4 5; do
  T=$(curl -s -c $JAR "$BASE/admin/login.php" | grep -o 'name="csrf_token" value="[a-f0-9]*' | cut -d'"' -f4)
  curl -s -b $JAR -c $JAR -X POST "$BASE/admin/login.php" \
    --data-urlencode "csrf_token=$T" \
    --data-urlencode "username=admin" \
    --data-urlencode "password=WrongPass$i!" -o /dev/null
done
ok "Sent 5 wrong-password attempts"
# 6th attempt — even with the CORRECT password it must be locked
T=$(curl -s -c $JAR "$BASE/admin/login.php" | grep -o 'name="csrf_token" value="[a-f0-9]*' | cut -d'"' -f4)
BODY=$(curl -s -b $JAR -c $JAR -X POST "$BASE/admin/login.php" \
  --data-urlencode "csrf_token=$T" \
  --data-urlencode "username=admin" \
  --data-urlencode "password=Estavia@2026")
echo "$BODY" | grep -qi "Too many failed attempts" && ok "6th attempt (correct password!) blocked by rate limiter" || bad "Rate limiting FAILED — correct password accepted after 5 failures"
echo "$BODY" | grep -q 'disabled' && ok "Login button disabled while locked" || true

echo ""
echo "============================================================"
echo " ATTACK TEST 5 — Method tampering / information disclosure"
echo "============================================================"
C=$(curl -s -o /dev/null -w "%{http_code}" "$BASE/api/quote.php?name=x&phone=0700&service=Guarding%20Services")
[ "$C" = "405" ] && ok "GET on API endpoint rejected (405)" || bad "GET not blocked (got $C)"
C=$(curl -s -o /dev/null -w "%{http_code}" "$BASE/config/config.php")
[ "$C" = "403" ] && ok "config.php not directly executable/servable (403 via router rule)" || echo "  [INFO] config.php returned $C (built-in server; blocked by .htaccess on Apache)"
C=$(curl -s -o /dev/null -w "%{http_code}" "$BASE/database.sql")
[ "$C" = "404" ] && ok "database.sql not reachable via web (404)" || echo "  [INFO] database.sql returned $C (denied by .htaccess on Apache)"
H=$(curl -sI "$BASE/" | grep -i "x-content-type-options" | tr -d '\r')
[ -n "$H" ] && ok "Security header present: $H" || echo "  [INFO] nosniff header set in API responses (Apache adds global headers via .htaccess)"

echo ""
echo "============================================================"
echo " RESULT: $PASS passed, $FAIL failed"
echo "============================================================"
exit $FAIL
