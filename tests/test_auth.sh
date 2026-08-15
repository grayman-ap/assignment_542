#!/usr/bin/env bash
# Task 2 - authentication: valid login, invalid rejection, session rotation,
# lockout, and no plaintext passwords. Demonstrates the SQLi weakness on the
# starter app and its remediation on the hardened app.
set -u
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/lib.sh"
TMP_BODY="$(mktemp)"; trap 'rm -f "$COOKIE_JAR" "$TMP_BODY"' EXIT

echo "=== TEST AUTH ==="

# 1. Valid login succeeds (session cookie rotates => 302 to dashboard).
code="$(login "tstudent1@ftminna.local" "TestPass!123")"
assert_status "valid login returns a redirect to the dashboard" "302" "$code"

# 2. Invalid credentials rejected with generic error.
code="$(login "tstudent1@ftminna.local" "WrongPassword1!")"
assert_status "invalid credentials are rejected (401)" "401" "$code"

# 3. Generic error: no account enumeration (same message for unknown user).
code="$(login "nobody@ftminna.local" "Whatever!1")"
assert_status "unknown account also rejected with 401" "401" "$code"

# 4. Session-ID regeneration on login (fixation defence).
sid_value() { awk -F'\t' '$6 == "IFT542SID" {print $7}' "$COOKIE_JAR"; }
curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" "$BASE_URL/login.php" >/dev/null
sid_before="$(sid_value)"
login "tstudent1@ftminna.local" "TestPass!123" >/dev/null
sid_after="$(sid_value)"
if [ -n "$sid_before" ] && [ -n "$sid_after" ] && [ "$sid_before" != "$sid_after" ]; then
  echo "  $PASS session ID regenerated after login"
  TEST_PASSED=$((TEST_PASSED + 1))
else
  echo "  $FAIL session ID not rotated after login (before='$sid_before' after='$sid_after')"
  TEST_FAILED=$((TEST_FAILED + 1))
fi

# 5. Lockout: 5 consecutive failures lock the account even with the right password.
code="$(login "tlock1@ftminna.local" "WrongPassword1!")"
code="$(login "tlock1@ftminna.local" "WrongPassword1!")"
code="$(login "tlock1@ftminna.local" "WrongPassword1!")"
code="$(login "tlock1@ftminna.local" "WrongPassword1!")"
code="$(login "tlock1@ftminna.local" "WrongPassword1!")"
code="$(login "tlock1@ftminna.local" "TestPass!123")"
if [ "$code" = "401" ]; then
  echo "  $PASS locked account rejected even with the correct password"
  TEST_PASSED=$((TEST_PASSED + 1))
else
  echo "  $FAIL locked account was not rejected (HTTP $code)"
  TEST_FAILED=$((TEST_FAILED + 1))
fi

# 6. Stored passwords are not plaintext (DB-level check via cli container).
hash_out="$(docker compose run --rm cli assert_hash "tstudent1@ftminna.local" "TestPass!123" 2>&1)"
if echo "$hash_out" | grep -q '^OK'; then
  echo "  $PASS password stored as Argon2id hash, not plaintext ($hash_out)"
  TEST_PASSED=$((TEST_PASSED + 1))
else
  echo "  $FAIL $hash_out"
  TEST_FAILED=$((TEST_FAILED + 1))
fi

# 7. SQLi: classic login bypass must FAIL on the hardened app (401 generic or
#    422 validation rejection - either proves the payload has no effect).
code="$(login "tstudent1@ftminna.local' OR '1'='1" "anything")"
if [ "$code" = "302" ]; then
  echo "  $FAIL SQLi payload bypassed hardened login!"
  TEST_FAILED=$((TEST_FAILED + 1))
else
  echo "  $PASS SQLi payload does not bypass hardened login (HTTP $code)"
  TEST_PASSED=$((TEST_PASSED + 1))
fi

# 8. SQLi demonstration on the STARTER app: the same class of payload bypasses
#    login because raw input is concatenated into the SQL text.
code="$(curl -s -o /dev/null -w '%{http_code}' \
    --data-urlencode "email=' OR '1'='1' -- " \
    --data-urlencode "password=nope" \
    "$VULN_URL/login.php")"
assert_status "starter app IS vulnerable (payload bypasses login)" "302" "$code"

finish_suite "auth"
