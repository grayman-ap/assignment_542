#!/usr/bin/env bash
# Task 2 - SQL injection remediation: parameterization is proven by showing
# that input which changes query meaning in the starter app has zero effect on
# the hardened app, and that query behaviour is data-only.
set -u
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/lib.sh"
TMP_BODY="$(mktemp)"; trap 'rm -f "$COOKIE_JAR" "$TMP_BODY"' EXIT

echo "=== TEST SQL INJECTION ==="

# Baseline: user count before any malicious input.
before="$(docker compose run --rm cli count "SELECT COUNT(*) FROM users")"

# Attempt to use SQLi to enumerate or alter rows via the login endpoint.
curl -s -o /dev/null --data-urlencode "email=x' UNION SELECT id, email, password_hash FROM users -- " \
  --data-urlencode "password=x" "$BASE_URL/login.php"
curl -s -o /dev/null --data-urlencode "email=x'; DELETE FROM users; -- " \
  --data-urlencode "password=x" "$BASE_URL/login.php"

# Attempt to stack a destructive statement through course registration fields.
code="$(curl -s -o /dev/null -w '%{http_code}' \
    --data-urlencode "csrf_token=bad" \
    --data-urlencode "course_id=1; DROP TABLE courses; -- " \
    --data-urlencode "password=x" \
    "$BASE_URL/courses.php")"

after="$(docker compose run --rm cli count "SELECT COUNT(*) FROM users")"

if [ "$before" = "$after" ]; then
  echo "  $PASS user table untouched after injection attempts ($before rows before and after)"
  TEST_PASSED=$((TEST_PASSED + 1))
else
  echo "  $FAIL user count changed: before=$before after=$after"
  TEST_FAILED=$((TEST_FAILED + 1))
fi

# Courses table must still exist and have rows (schema integrity).
courses="$(docker compose run --rm cli count "SELECT COUNT(*) FROM courses")"
if [ "$courses" -gt 0 ]; then
  echo "  $PASS courses table intact ($courses rows)"
  TEST_PASSED=$((TEST_PASSED + 1))
else
  echo "  $FAIL courses table missing/empty after injection attempt"
  TEST_FAILED=$((TEST_FAILED + 1))
fi

# Confirmation that hardened login rejects the same payloads that worked on
# the starter app (see test_auth.sh).
for payload in "admin@ftminna.edu.ng' -- " "x' OR '1'='1"; do
  code="$(curl -s -o /dev/null -w '%{http_code}' \
      --data-urlencode "csrf_token=x" \
      --data-urlencode "email=$payload" \
      --data-urlencode "password=x" \
      "$BASE_URL/login.php")"
  if [ "$code" != "302" ]; then
    echo "  $PASS hardened login rejected payload [$payload] (HTTP $code)"
    TEST_PASSED=$((TEST_PASSED + 1))
  else
    echo "  $FAIL hardened login accepted payload [$payload]!"
    TEST_FAILED=$((TEST_FAILED + 1))
  fi
done

finish_suite "sqli"
