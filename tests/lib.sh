#!/usr/bin/env bash
# Shared harness for the automated security test suite.
# Each test_*.sh script sources this file.

set -u

BASE_URL="${BASE_URL:-http://localhost:8085}"
VULN_URL="${VULN_URL:-http://localhost:8086}"
EVIDENCE_DIR="${EVIDENCE_DIR:-evidence/generated}"
COOKIE_JAR="$(mktemp)"

TEST_PASSED=0
TEST_FAILED=0
TEST_FAILURES=()

PASS="$(printf '\033[32mPASS\033[0m')"
FAIL="$(printf '\033[31mFAIL\033[0m')"

# --- helpers ---------------------------------------------------------------

trap 'rm -f "$COOKIE_JAR"' EXIT

get_csrf() { # $1 = url
  curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" "$1" \
    | grep -o 'name="csrf_token" value="[a-f0-9]*"' \
    | head -1 | sed 's/.*value="//;s/"$//'
}

login() { # $1 email, $2 password -> prints http status code
  local token
  token="$(get_csrf "$BASE_URL/login.php")"
  curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" -o /dev/null -w '%{http_code}' \
    --data-urlencode "csrf_token=$token" \
    --data-urlencode "email=$1" \
    --data-urlencode "password=$2" \
    "$BASE_URL/login.php"
}

post_csrf() { # $1 url $2 data string (already encoded) -> status + body
  local token
  token="$(get_csrf "$1")"
  curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" -o "$TMP_BODY" -w '%{http_code}' \
    --data-urlencode "csrf_token=$token" $2 "$1"
}

assert_status() { # $1 description, $2 expected, $3 actual
  if [ "$2" = "$3" ]; then
    echo "  $PASS $1 (HTTP $3)"
    TEST_PASSED=$((TEST_PASSED + 1))
  else
    echo "  $FAIL $1 (expected HTTP $2, got $3)"
    TEST_FAILED=$((TEST_FAILED + 1))
    TEST_FAILURES+=("$1: expected $2 got $3")
  fi
}

assert_contains() { # $1 description, $2 haystack, $3 needle
  if printf '%s' "$2" | grep -qF "$3"; then
    echo "  $PASS $1"
    TEST_PASSED=$((TEST_PASSED + 1))
  else
    echo "  $FAIL $1 (missing: $3)"
    TEST_FAILED=$((TEST_FAILED + 1))
    TEST_FAILURES+=("$1: expected to contain [$3]")
  fi
}

assert_not_contains() { # $1 description, $2 haystack, $3 needle
  if printf '%s' "$2" | grep -qF "$3"; then
    echo "  $FAIL $1 (unexpected: $3)"
    TEST_FAILED=$((TEST_FAILED + 1))
    TEST_FAILURES+=("$1: should not contain [$3]")
  else
    echo "  $PASS $1"
    TEST_PASSED=$((TEST_PASSED + 1))
  fi
}

finish_suite() { # $1 = suite name
  echo
  echo "------------------------------------------------------------"
  echo "SUITE: $1  ->  PASS=$TEST_PASSED FAIL=$TEST_FAILED"
  if [ "$TEST_FAILED" -gt 0 ]; then
    for f in "${TEST_FAILURES[@]}"; do echo "  - $f"; done
  fi
  exit "$([ "$TEST_FAILED" -gt 0 ] && echo 1 || echo 0)"
}
