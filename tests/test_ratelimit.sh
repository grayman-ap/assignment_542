#!/usr/bin/env bash
# Task 2 - additional control: rate limiting. A burst of failed logins from
# the same source must eventually be throttled (HTTP 429) before any work.
set -u
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/lib.sh"
TMP_BODY="$(mktemp)"; trap 'rm -f "$COOKIE_JAR" "$TMP_BODY"' EXIT

echo "=== TEST RATE LIMITING ==="

# Use a non-existent account: it never locks out, so the source-IP sliding
# window is what eventually throttles the burst.
probe="ratelimit-probe-$(date +%s)@ftminna.local"

throttled=0
for i in $(seq 1 30); do
  code="$(login "$probe" "WrongPassword1!")"
  if [ "$code" = "429" ]; then
    throttled=1
    echo "  $PASS rate limiting engaged after burst (attempt #$i returned 429)"
    TEST_PASSED=$((TEST_PASSED + 1))
    break
  fi
done

if [ "$throttled" = "0" ]; then
  echo "  $FAIL 30 rapid failed logins did not trigger rate limiting"
  TEST_FAILED=$((TEST_FAILED + 1))
fi

# After throttling, even a correct login is refused (defence-in-depth).
code="$(login "tstudent1@ftminna.local" "TestPass!123")"
if [ "$code" = "429" ]; then
  echo "  $PASS throttled source cannot authenticate during the window"
  TEST_PASSED=$((TEST_PASSED + 1))
else
  echo "  $FAIL throttled source was still able to attempt login (HTTP $code)"
  TEST_FAILED=$((TEST_FAILED + 1))
fi

finish_suite "rate-limit"
