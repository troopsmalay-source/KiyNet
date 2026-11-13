#!/usr/bin/env bash
# KikiNet — FreeRADIUS lab smoke test (run on the AAA lab node only).
#
# Usage:
#   RADIUS_HOST=127.0.0.1 RADIUS_SECRET=... TEST_USER=labuser TEST_PASSWORD=... \
#     ./check-radius.sh
#
# Never point this at production. Never echo the shared secret.
set -euo pipefail

RADIUS_HOST="${RADIUS_HOST:-127.0.0.1}"
RADIUS_SECRET="${RADIUS_SECRET:?set RADIUS_SECRET from the secret manager}"
TEST_USER="${TEST_USER:?set TEST_USER (fake lab identity)}"
TEST_PASSWORD="${TEST_PASSWORD:?set TEST_PASSWORD (fake lab identity)}"
RADIUS_DB="${RADIUS_DB:-kikinet_radius}"

echo "== 1. configuration check =="
radiusd -XC

echo "== 2. authentication probe (PAP) =="
if ! command -v radtest >/dev/null; then
  echo "radtest not installed (freeradius-utils)"; exit 1
fi
radtest "$TEST_USER" "$TEST_PASSWORD" "$RADIUS_HOST" 0 "$RADIUS_SECRET" | tee /tmp/kikinet-radtest.out
grep -q "Access-Accept" /tmp/kikinet-radtest.out || { echo "FAIL: expected Access-Accept"; exit 1; }

echo "== 3. accounting visibility =="
psql -d "$RADIUS_DB" -tAc \
  "select count(*) from radacct where acctstarttime > now() - interval '15 minutes'" \
  | awk '{ print "recent radacct rows:", $1 }'

echo "== 4. secret hygiene =="
if grep -RIn --exclude='*.template' "$RADIUS_SECRET" /etc/freeradius 2>/dev/null; then
  echo "WARN: shared secret found in a plain-text radius config file"
fi

echo "OK: lab probe finished. Attach the output to the PR as lab evidence."
