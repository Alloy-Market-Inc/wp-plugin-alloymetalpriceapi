#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
if [[ "${1:-}" != "--redispatch" ]] || [[ $# -ne 1 ]]; then
  echo "Direct WP Engine deployment has been retired." >&2
  echo "Merge a pull request into production. To retry its central release, run: $0 --redispatch" >&2
  exit 2
fi

cd "$ROOT_DIR"
command -v gh >/dev/null 2>&1 || { echo "gh is required" >&2; exit 1; }
[[ -z "$(git status --porcelain)" ]] || { echo "Checkout is dirty; refusing release dispatch" >&2; exit 1; }
[[ "$(git branch --show-current)" == release ]] || { echo "Checkout must be on release" >&2; exit 1; }
git fetch origin release
sha="$(git rev-parse HEAD)"
[[ "$sha" == "$(git rev-parse origin/release)" ]] || { echo "Local release is not origin/release" >&2; exit 1; }
repository="$(gh repo view --json nameWithOwner --jq .nameWithOwner)"
merged="$(gh api "repos/$repository/commits/$sha/pulls" --jq '[.[] | select(.merged_at != null and .base.ref == "release")] | length')"
[[ "$merged" -gt 0 ]] || { echo "Release SHA is not associated with a merged release PR" >&2; exit 1; }
version="$(sed -nE 's/^[[:space:]]*\*[[:space:]]*Version:[[:space:]]*([^[:space:]]+).*/\1/p' alloy-metal-price-api.php | head -1)"
jq -n --arg component plugin --arg repository "$repository" --arg sha "$sha" --arg version "$version" \
  '{event_type:"marcom-component-release",client_payload:{component:$component,repository:$repository,sha:$sha,version:$version}}' \
  | gh api --method POST repos/Alloy-Market-Inc/wp-site-alloy-marcom/dispatches --input -
echo "Central release redispatched for $sha"
