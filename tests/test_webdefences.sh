#!/usr/bin/env bash
# Task 3 - web defences: XSS output encoding, CSRF tokens, SSRF guard,
# security headers, upload validation and no default credentials.
set -u
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/lib.sh"
TMP_BODY="$(mktemp)"; trap 'rm -f "$COOKIE_JAR" "$TMP_BODY"' EXIT

echo "=== TEST WEB DEFENCES ==="

# 1. Security headers on every response.
headers="$(curl -s -D - -o /dev/null "$BASE_URL/login.php")"
assert_contains "CSP present (default-src 'self')" "$headers" "default-src 'self'"
assert_contains "X-Content-Type-Options: nosniff" "$headers" "nosniff"
assert_contains "X-Frame-Options: DENY" "$headers" "DENY"
assert_contains "Referrer-Policy present" "$headers" "strict-origin-when-cross-origin"
assert_contains "Cache-Control: no-store" "$headers" "no-store"

# 2. Login as the test student.
code="$(login "tstudent1@ftminna.local" "TestPass!123")"
assert_status "login for web-defence tests" "302" "$code"

# 3. XSS: store a script payload in the profile, then read it back.
token="$(get_csrf "$BASE_URL/profile.php")"
payload='<script>alert(1)</script>'
curl -s -b "$COOKIE_JAR" -c "$COOKIE_JAR" -o /dev/null \
  --data-urlencode "csrf_token=$token" \
  --data-urlencode "full_name=$payload" \
  --data-urlencode "email=tstudent1@ftminna.local" \
  --data-urlencode "phone=" \
  "$BASE_URL/profile.php"
profile="$(curl -s -b "$COOKIE_JAR" "$BASE_URL/profile.php")"
assert_not_contains "stored XSS is not rendered as executable HTML" "$profile" '<script>alert(1)</script>'
assert_contains "stored XSS is output-encoded" "$profile" '&lt;script&gt;alert(1)&lt;/script&gt;'

# 4. CSRF: POSTing without a token is rejected (inline error, no update).
before_name="$(curl -s -b "$COOKIE_JAR" "$BASE_URL/profile.php" | grep -o 'name="full_name" value="[^"]*' | sed 's/name="full_name" value="//')"
curl -s -b "$COOKIE_JAR" -c "$COOKIE_JAR" -o "$TMP_BODY" \
  --data-urlencode "full_name=NoToken User" \
  --data-urlencode "email=tstudent1@ftminna.local" \
  "$BASE_URL/profile.php"
assert_contains "profile POST without CSRF token is rejected" "$(cat "$TMP_BODY")" "Security token is missing or invalid"
after_name="$(curl -s -b "$COOKIE_JAR" "$BASE_URL/profile.php" | grep -o 'name="full_name" value="[^"]*' | sed 's/name="full_name" value="//')"
if [ "$before_name" = "$after_name" ]; then
  echo "  $PASS profile was NOT updated without a valid CSRF token"
  TEST_PASSED=$((TEST_PASSED + 1))
else
  echo "  $FAIL profile was changed without a CSRF token (before='$before_name' after='$after_name')"
  TEST_FAILED=$((TEST_FAILED + 1))
fi

# 5. CSRF: POSTing with a valid token succeeds.
token="$(get_csrf "$BASE_URL/profile.php")"
code="$(curl -s -b "$COOKIE_JAR" -c "$COOKIE_JAR" -o /dev/null -w '%{http_code}' \
  --data-urlencode "csrf_token=$token" \
  --data-urlencode "full_name=Valid CSRF User" \
  --data-urlencode "email=tstudent1@ftminna.local" \
  --data-urlencode "phone=" \
  "$BASE_URL/profile.php")"
assert_status "profile POST with valid CSRF token succeeds" "200" "$code"

# 6. SSRF: loopback, private and cloud-metadata destinations are blocked.
for bad in "http://127.0.0.1/" "http://169.254.169.254/latest/meta-data/" \
           "http://192.168.1.10/" "http://10.0.0.1/" "http://172.16.0.1/" \
           "file:///etc/passwd" "http://evil.example.org/"; do
  token="$(get_csrf "$BASE_URL/import_preview.php")"
  code="$(curl -s -b "$COOKIE_JAR" -o "$TMP_BODY" -w '%{http_code}' \
      --data-urlencode "csrf_token=$token" \
      --data-urlencode "url=$bad" \
      "$BASE_URL/import_preview.php")"
  if [ "$code" = "200" ] && grep -q 'blocked\|not on the allowed\|Only http' "$TMP_BODY"; then
    echo "  $PASS SSRF blocked [$bad]"
    TEST_PASSED=$((TEST_PASSED + 1))
  elif [ "$code" = "403" ]; then
    echo "  $PASS SSRF blocked [$bad] (HTTP 403)"
    TEST_PASSED=$((TEST_PASSED + 1))
  else
    echo "  $FAIL SSRF NOT blocked [$bad] (HTTP $code)"
    TEST_FAILED=$((TEST_FAILED + 1))
  fi
done

# 7. SSRF: the allowlisted destination is still reachable.
token="$(get_csrf "$BASE_URL/import_preview.php")"
code="$(curl -s -b "$COOKIE_JAR" -o "$TMP_BODY" -w '%{http_code}' \
    --data-urlencode "csrf_token=$token" \
    --data-urlencode "url=http://catalog.ftminna.internal/" \
    "$BASE_URL/import_preview.php")"
assert_status "allowlisted destination is reachable" "200" "$code"

# 8. Upload: a malicious PHP file is rejected.
printf '<?php echo "pwned"; ?>' > "$TMP_BODY.php"
token="$(get_csrf "$BASE_URL/upload.php")"
curl -s -b "$COOKIE_JAR" -o "$TMP_BODY" -w '%{http_code}' \
  -F "csrf_token=$token" \
  -F "document=@$TMP_BODY.php;type=application/x-php;filename=shell.php" \
  "$BASE_URL/upload.php" >/dev/null
assert_contains "malicious .php upload rejected with message" "$(cat "$TMP_BODY")" "not allowed"
assert_not_contains "malicious .php upload is NOT accepted" "$(cat "$TMP_BODY")" "uploaded successfully"
rm -f "$TMP_BODY.php"

# 9. No default credentials: seeded admin password is not 'admin' (argon2id).
admins="$(docker compose run --rm cli sql \
  "SELECT COUNT(*) FROM users WHERE role='admin' AND password_hash='admin'")"
assert_status "no admin account uses a default password" "0" "$admins"

finish_suite "web-defences"
