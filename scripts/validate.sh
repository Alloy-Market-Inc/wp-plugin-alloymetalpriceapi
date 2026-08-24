#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
EXPECTED_CSS="$(mktemp "${TMPDIR:-/tmp}/alloy-metal-price-css.XXXXXX")"

cleanup() {
  rm -f "$EXPECTED_CSS"
}
trap cleanup EXIT

cd "$ROOT_DIR"

cp assets/dist/css/plugin.css "$EXPECTED_CSS"
npm run build
cmp "$EXPECTED_CSS" assets/dist/css/plugin.css

find . -name '*.php' -not -path './node_modules/*' -print0 \
  | xargs -0 -n1 php -l
node --check assets/dist/js/alloy-calculator.js
php scripts/validate-payout-table-normalizer.php
