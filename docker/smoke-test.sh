#!/bin/sh
# Checks a running production image from the outside, like its first visitor.
# Usage: sh docker/smoke-test.sh [base URL]
set -e

URL="${1:-http://127.0.0.1:10000}"
COOKIES="$(mktemp)"
trap 'rm -f "$COOKIES"' EXIT

fail() {
    echo "FAILED: $1" >&2
    exit 1
}

# The first start migrates and seeds, which takes a while on a small instance.
curl --silent --fail --retry 90 --retry-delay 1 --retry-all-errors --output /dev/null "$URL/up" \
    || fail "the health check never answered"

status() {
    curl --silent --output /dev/null --write-out '%{http_code}' "$URL$1"
}

[ "$(status /register)" = "404" ] || fail "/register should not exist with registration off"
[ "$(status /does-not-exist)" = "404" ] || fail "an unknown page should answer 404"

LANDING="$(curl --silent --cookie-jar "$COOKIES" "$URL/")"

case "$LANDING" in
*'demoEnabled'*) ;;
*) fail "the landing page did not render" ;;
esac

ASSET="$(printf '%s' "$LANDING" | grep -o '/build/assets/app-[^"]*\.js' | head -n 1)"
[ -n "$ASSET" ] || fail "the landing page links no built assets"
[ "$(status "$ASSET")" = "200" ] || fail "the built assets are not served"

# The token is URL-encoded in the cookie, and base64 only needs these three.
TOKEN="$(awk '$6 == "XSRF-TOKEN" { print $7 }' "$COOKIES" | sed 's/%3D/=/g; s/%2B/+/g; s/%2F/\//g')"

# One click on "Try the live demo": session, database, seeder and board page.
BOARD="$(curl --silent --location --cookie "$COOKIES" --cookie-jar "$COOKIES" \
    --header "X-XSRF-TOKEN: $TOKEN" --data '' "$URL/demo/demo")"

case "$BOARD" in
*'Write the onboarding guide'*) ;;
*) fail "the demo did not open its board" ;;
esac

echo "Smoke test passed: $URL"
