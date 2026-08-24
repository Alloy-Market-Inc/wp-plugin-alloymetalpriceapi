#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BUILD_DIR="$ROOT_DIR/build"
PACKAGE_SLUG="AlloyMetalPriceAPI"
ZIP_PATH="$BUILD_DIR/$PACKAGE_SLUG.zip"
STAGE_ROOT="$(mktemp -d "${TMPDIR:-/tmp}/alloy-metal-price-package.XXXXXX")"
PACKAGE_DIR="$STAGE_ROOT/$PACKAGE_SLUG"

cleanup() {
  rm -rf "$STAGE_ROOT"
}
trap cleanup EXIT

cd "$ROOT_DIR"
bash scripts/validate.sh

mkdir -p "$PACKAGE_DIR/assets"
cp alloy-metal-price-api.php "$PACKAGE_DIR/"
cp -R includes "$PACKAGE_DIR/"
cp -R plugin-update-checker "$PACKAGE_DIR/"
cp -R assets/dist "$PACKAGE_DIR/assets/"
rm -f "$PACKAGE_DIR/plugin-update-checker/composer.json"

mkdir -p "$BUILD_DIR"
rm -f "$ZIP_PATH"
(
  cd "$STAGE_ROOT"
  find "$PACKAGE_SLUG" -exec touch -t 198001010000 {} +
  find "$PACKAGE_SLUG" -type f -print | LC_ALL=C sort | zip -X -q "$ZIP_PATH" -@
)

ZIP_ENTRIES="$(unzip -Z1 "$ZIP_PATH")"
grep -qx "$PACKAGE_SLUG/alloy-metal-price-api.php" <<<"$ZIP_ENTRIES"
grep -qx "$PACKAGE_SLUG/assets/dist/css/plugin.css" <<<"$ZIP_ENTRIES"
grep -qx "$PACKAGE_SLUG/assets/dist/js/alloy-calculator.js" <<<"$ZIP_ENTRIES"
grep -qx "$PACKAGE_SLUG/plugin-update-checker/load-v5p7.php" <<<"$ZIP_ENTRIES"

if grep -Eq '(^|/)(assets/src|node_modules|tests|\.github|package(-lock)?\.json)(/|$)' <<<"$ZIP_ENTRIES"; then
  echo "Package contains development-only files." >&2
  exit 1
fi

shasum -a 256 "$ZIP_PATH"
