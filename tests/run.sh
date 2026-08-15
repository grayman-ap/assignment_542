#!/usr/bin/env bash
# End-to-end verification: builds the stack, seeds data, provisions hermetic
# test accounts and runs every security test suite. Test output and evidence
# are written to evidence/generated/.
#
# Usage: tests/run.sh
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

EVIDENCE_DIR="$ROOT/evidence/generated"
RUN_ID="$(date +%Y%m%d-%H%M%S)"
RUN_EVIDENCE="$EVIDENCE_DIR/run-$RUN_ID"
mkdir -p "$RUN_EVIDENCE"

log() { printf '\n\033[1m[run] %s\033[0m\n' "$*"; }

# 1. Ensure .env exists (safe placeholders are fine for the lab).
if [ ! -f .env ]; then
  cp .env.example .env
  log "created .env from .env.example (placeholders only)"
fi

# 2. Build + start the stack.
log "building and starting containers (this may take a while on first run)..."
docker compose up -d --build

# 3. Wait for the app to accept connections.
log "waiting for app on :8085 ..."
for i in $(seq 1 60); do
  if curl -s -o /dev/null http://localhost:8085/login.php; then
    break
  fi
  sleep 2
  [ "$i" = "60" ] && { echo "app did not become ready"; exit 1; }
done
log "app is up"

# 4. Seed + test accounts + reset authentication state (rate limit / lockout
#    tables persist across runs; clear them so each run starts clean).
log "seeding data and test accounts..."
docker compose run --rm cli seed
docker compose run --rm cli make_test_users
docker compose run --rm cli sql "DELETE FROM auth_attempts" >/dev/null 2>&1
docker compose run --rm cli sql "UPDATE users SET failed_attempts = 0, locked_until = NULL" >/dev/null 2>&1

# 5. Run every test suite, capturing output.
log "running security test suites..."
overall=0
# Logging runs before rate-limiting so its login events are not throttled.
SUITES=(test_auth test_sqli test_password_hashing test_webdefences test_logging test_ratelimit)
for suite in "${SUITES[@]}"; do
  log ">> $suite"
  set +e
  bash "tests/$suite.sh" 2>&1 | tee "$RUN_EVIDENCE/$suite.txt"
  rc=${PIPESTATUS[0]}
  set -e
  if [ "$rc" -ne 0 ]; then
    overall=1
    echo "   [run] $suite FAILED"
  else
    echo "   [run] $suite passed"
  fi
done

# 6. Snapshot configuration evidence.
log "copying configuration evidence..."
mkdir -p "$EVIDENCE_DIR/config"
cp docker-compose.yml "$EVIDENCE_DIR/config/docker-compose.yml"
cp Dockerfile          "$EVIDENCE_DIR/config/Dockerfile"
cp .env.example        "$EVIDENCE_DIR/config/env.example"
cp docker/apache-vhost.conf "$EVIDENCE_DIR/config/apache-vhost.conf"
cp db/schema.sql       "$EVIDENCE_DIR/config/schema.sql"

# 7. Summary.
summary="$RUN_EVIDENCE/summary.txt"
{
  echo "IFT542 security test run $RUN_ID"
  echo "Suites: ${SUITES[*]}"
  [ "$overall" -eq 0 ] && echo "RESULT: ALL SUITES PASSED" || echo "RESULT: ONE OR MORE SUITES FAILED"
} | tee "$summary"

log "evidence written to $EVIDENCE_DIR (this run: $RUN_ID)"
exit "$overall"
