# WP Engine Deploy Flow

This plugin has a dedicated WP Engine deploy flow that only syncs the plugin directory.

## Why This Flow Exists

This repository is only the plugin:

- `wp-content/plugins/AlloyMetalPriceAPI`

WP Engine GitPush deploys a repository root, not a subdirectory. Since this repository is not the full WP Engine site root, the safest deploy flow for this plugin is:

- WP Engine SSH Gateway
- `rsync`
- plugin-only remote path sync

This follows WP Engine's documented SSH Gateway and CI deploy patterns, which support syncing a subdirectory to a specific remote path.

Official references:

- SSH Gateway:
  https://wpengine.com/support/ssh-gateway/
- Git / GitPush:
  https://wpengine.com/support/managing-multiple-ssh-keys-git/
- GitHub Action deploy pattern using SSH Gateway and subdirectory paths:
  https://wpengine.com/support/github-action-deploy/

## Current Production Assumption

The local machine appears to already have WP Engine SSH configuration for:

- environment: `alloymarketinc`
- SSH host: `alloymarketinc.ssh.wpengine.net`

The deploy script defaults to that environment, but you can override it.

## Files In This Flow

- deploy script:
  [`scripts/deploy-wpe-plugin.sh`](/Users/jamescook/REPOS/LOCAL-SITES/alloy-marcom/app/public/wp-content/plugins/AlloyMetalPriceAPI/scripts/deploy-wpe-plugin.sh)
- rsync ignore file:
  [`/.wpe-deploy-ignore`](/Users/jamescook/REPOS/LOCAL-SITES/alloy-marcom/app/public/wp-content/plugins/AlloyMetalPriceAPI/.wpe-deploy-ignore)

## What Gets Deployed

The script syncs this plugin directory to:

- `sites/<environment>/wp-content/plugins/AlloyMetalPriceAPI/`

It excludes local-only and dev-only files such as:

- `.git/`
- `node_modules/`
- `assets/src/`
- build tooling files
- local deployment docs and scripts

The built runtime assets are included:

- `assets/dist/css/plugin.css`
- `assets/dist/js/alloy-calculator.js`

## Standard Deploy

From the plugin root:

```bash
./scripts/deploy-wpe-plugin.sh
```

What it does:

1. Runs `npm run build`
2. Verifies SSH Gateway access
3. Syncs plugin files with `rsync`
4. Verifies the remote plugin entry file exists

## Dry Run

Use this first when making structural changes:

```bash
./scripts/deploy-wpe-plugin.sh --dry-run
```

This shows what would change without uploading files.

## Alternate Environment

Override the environment if needed:

```bash
./scripts/deploy-wpe-plugin.sh --env alloymarketstg
```

Or:

```bash
WPE_ENV=alloymarketstg ./scripts/deploy-wpe-plugin.sh
```

## Skip Build

If the built assets are already current and you do not want to rebuild:

```bash
./scripts/deploy-wpe-plugin.sh --skip-build
```

## Remote Path Details

By default the script uses:

- `WPE_ENV=alloymarketinc`
- `WPE_USER=$WPE_ENV`
- `WPE_HOST=$WPE_ENV.ssh.wpengine.net`
- `WPE_REMOTE=$WPE_USER@$WPE_HOST`
- `WPE_REMOTE_ROOT=sites/$WPE_ENV`

The final plugin destination is:

- `sites/$WPE_ENV/wp-content/plugins/AlloyMetalPriceAPI/`

These can be overridden with environment variables if WP Engine naming differs.

## Future Agent Notes

- Do not use WP Engine GitPush directly from this repository unless the repository layout changes to match the full site root.
- Prefer the plugin-only `rsync` flow in this file.
- Run a dry run before the first deploy to any new environment.
- Keep [`README.md`](/Users/jamescook/REPOS/LOCAL-SITES/alloy-marcom/app/public/wp-content/plugins/AlloyMetalPriceAPI/README.md) and [`SHORTCODES.md`](/Users/jamescook/REPOS/LOCAL-SITES/alloy-marcom/app/public/wp-content/plugins/AlloyMetalPriceAPI/SHORTCODES.md) updated when shortcode behavior changes.
- If SSH access fails, confirm:
  - the SSH key is present locally
  - the key is authorized in WP Engine
  - the environment name is correct
  - the WP Engine user has access to that environment

## Suggested First Validation

Before the first live deploy, run:

```bash
./scripts/deploy-wpe-plugin.sh --dry-run
```

If that succeeds, run:

```bash
./scripts/deploy-wpe-plugin.sh
```
