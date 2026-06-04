#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGIN_SLUG="AlloyMetalPriceAPI"
DEFAULT_ENV="alloymarketinc"

WPE_ENV="${WPE_ENV:-$DEFAULT_ENV}"
WPE_USER="${WPE_USER:-$WPE_ENV}"
WPE_HOST="${WPE_HOST:-${WPE_ENV}.ssh.wpengine.net}"
WPE_REMOTE="${WPE_REMOTE:-${WPE_USER}@${WPE_HOST}}"
WPE_REMOTE_ROOT="${WPE_REMOTE_ROOT:-sites/${WPE_ENV}}"
REMOTE_PLUGIN_DIR="${WPE_REMOTE_ROOT}/wp-content/plugins/${PLUGIN_SLUG}"
IGNORE_FILE="${ROOT_DIR}/.wpe-deploy-ignore"

RUN_BUILD=1
DRY_RUN=0

usage() {
	cat <<EOF
Deploy the AlloyMetalPriceAPI plugin to WP Engine using SSH Gateway and rsync.

Usage:
  $(basename "$0") [--dry-run] [--skip-build] [--env <environment>]

Options:
  --dry-run      Show what would change without uploading files
  --skip-build   Skip \`npm run build\`
  --env NAME     Override the WP Engine environment name
  --help         Show this help text

Environment overrides:
  WPE_ENV
  WPE_USER
  WPE_HOST
  WPE_REMOTE
  WPE_REMOTE_ROOT

Defaults:
  WPE_ENV=$DEFAULT_ENV
  WPE_REMOTE=${WPE_USER}@${WPE_HOST}
  WPE_REMOTE_ROOT=sites/${WPE_ENV}

Examples:
  $(basename "$0") --dry-run
  $(basename "$0")
  WPE_ENV=alloymarketstg $(basename "$0")
EOF
}

while [[ $# -gt 0 ]]; do
	case "$1" in
		--dry-run)
			DRY_RUN=1
			shift
			;;
		--skip-build)
			RUN_BUILD=0
			shift
			;;
		--env)
			WPE_ENV="${2:?Missing environment name for --env}"
			WPE_USER="${WPE_ENV}"
			WPE_HOST="${WPE_ENV}.ssh.wpengine.net"
			WPE_REMOTE="${WPE_USER}@${WPE_HOST}"
			WPE_REMOTE_ROOT="sites/${WPE_ENV}"
			REMOTE_PLUGIN_DIR="${WPE_REMOTE_ROOT}/wp-content/plugins/${PLUGIN_SLUG}"
			shift 2
			;;
		--help|-h)
			usage
			exit 0
			;;
		*)
			echo "Unknown argument: $1" >&2
			usage >&2
			exit 1
			;;
	esac
done

echo "WP Engine environment: ${WPE_ENV}"
echo "SSH remote: ${WPE_REMOTE}"
echo "Remote plugin path: ${REMOTE_PLUGIN_DIR}"

if [[ ! -f "${IGNORE_FILE}" ]]; then
	echo "Missing ignore file: ${IGNORE_FILE}" >&2
	exit 1
fi

if [[ "${RUN_BUILD}" -eq 1 ]]; then
	echo "Building plugin assets..."
	(
		cd "${ROOT_DIR}"
		npm run build
	)
fi

echo "Checking SSH Gateway access..."
ssh -o BatchMode=yes "${WPE_REMOTE}" "cd ${WPE_REMOTE_ROOT} && pwd"

RSYNC_FLAGS=(-azvr --delete --checksum --itemize-changes --exclude-from="${IGNORE_FILE}")

if [[ "${DRY_RUN}" -eq 1 ]]; then
	RSYNC_FLAGS+=(--dry-run)
fi

echo "Syncing plugin files..."
rsync "${RSYNC_FLAGS[@]}" \
	"${ROOT_DIR}/" \
	"${WPE_REMOTE}:${REMOTE_PLUGIN_DIR}/"

echo "Verifying plugin directory remotely..."
ssh -o BatchMode=yes "${WPE_REMOTE}" "cd ${WPE_REMOTE_ROOT} && test -f wp-content/plugins/${PLUGIN_SLUG}/alloy-metal-price-api.php && echo 'Plugin deploy complete: wp-content/plugins/${PLUGIN_SLUG}'"

if [[ "${DRY_RUN}" -eq 1 ]]; then
	echo "Dry run complete. No files were uploaded."
else
	echo "Deploy complete."
fi
