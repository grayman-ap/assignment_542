#!/usr/bin/env bash
# Task 3 - logging: failed logins, denied authorisation and rejected
# validation produce structured log entries (who/what/when) without secrets.
set -u
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/lib.sh"
TMP_BODY="$(mktemp)"; trap 'rm -f "$COOKIE_JAR" "$TMP_BODY"' EXIT

echo "=== TEST LOGGING ==="

# 1. Failed login (no session).
login "tstudent1@ftminna.local" "WrongPassword1!" >/dev/null

# 2. Denied authorisation: hit a protected page without a session.
curl -s -o /dev/null "$BASE_URL/profile.php"

# 3. Successful login, then CSRF rejection on profile POST.
code="$(login "tstudent1@ftminna.local" "TestPass!123")"
curl -s -b "$COOKIE_JAR" -c "$COOKIE_JAR" -o /dev/null \
  --data-urlencode "full_name=X" \
  --data-urlencode "email=tstudent1@ftminna.local" \
  "$BASE_URL/profile.php"                          # missing CSRF token

# 4. Validation rejection: invalid email format.
token="$(get_csrf "$BASE_URL/profile.php")"
curl -s -b "$COOKIE_JAR" -c "$COOKIE_JAR" -o /dev/null \
  --data-urlencode "csrf_token=$token" \
  --data-urlencode "full_name=Bad Email" \
  --data-urlencode "email=not-an-email" \
  "$BASE_URL/profile.php"

# 5. SSRF blocked.
token="$(get_csrf "$BASE_URL/import_preview.php")"
curl -s -b "$COOKIE_JAR" -o /dev/null \
  --data-urlencode "csrf_token=$token" \
  --data-urlencode "url=http://169.254.169.254/latest/meta-data/" \
  "$BASE_URL/import_preview.php"

# Read the log through the shared volume.
log="$(docker compose exec -T app cat /var/www/html/app/logs/app.log 2>/dev/null)"

assert_contains "failed login is logged"             "$log" '"event":"login_failed"'
assert_contains "denied authorisation is logged"     "$log" '"event":"auth_denied"'
assert_contains "rejected CSRF is logged"            "$log" '"event":"validation_rejected"'
assert_contains "rejected validation is logged"      "$log" '"field":"email"'
assert_contains "blocked SSRF destination is logged" "$log" '"event":"url_preview"'

# Secrets must never appear: search for the test password anywhere.
if printf '%s' "$log" | grep -qF 'TestPass!123'; then
  echo "  $FAIL log contains a plaintext password - secret leak!"
  TEST_FAILED=$((TEST_FAILED + 1))
else
  echo "  $PASS no plaintext password in logs"
  TEST_PASSED=$((TEST_PASSED + 1))
fi

# Log lines are structured (JSON) and timestamped.
if printf '%s' "$log" | grep -q '"ts":'; then
  echo "  $PASS log entries are structured and timestamped"
  TEST_PASSED=$((TEST_PASSED + 1))
else
  echo "  $FAIL log entries are not structured JSON"
  TEST_FAILED=$((TEST_FAILED + 1))
fi

# Produce a redacted sample for evidence.
evidence="$SCRIPT_DIR/../$EVIDENCE_DIR/logs-sample.log"
mkdir -p "$(dirname "$evidence")"
{
  echo "# Redacted sample - who/what/when only; IPs partially masked; no secrets."
  printf '%s\n' "$log" \
    | sed -E 's/"ip":"([0-9]+\.[0-9]+\.)[0-9]+\.[0-9]+"/"ip":"\1x.x"/g' \
    | tail -8
} > "$evidence"
echo "  (redacted sample written to $evidence)"

finish_suite "logging"
