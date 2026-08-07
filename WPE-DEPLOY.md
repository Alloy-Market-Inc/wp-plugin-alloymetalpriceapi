# AlloyMetalPriceAPI Release Flow

Direct WP Engine deployment from this repository is retired.

1. Develop and validate the plugin on a feature branch.
2. Open and merge a pull request into `production`.
3. The repository workflow dispatches the exact merge SHA and plugin version to `Alloy-Market-Inc/wp-site-alloy-marcom`.
4. The private operations workflow builds an immutable plugin artifact, refreshes production into staging, runs staging QA, promotes the same bytes to production, runs read-only QA, and records the verified SHA.

WP Engine credentials are held only by the private operations repository. This repository's dispatch token cannot deploy files directly.

To retry a failed dispatch after fixing its external blocker:

```bash
./scripts/deploy-wpe-plugin.sh --redispatch
```

The command only accepts the clean current `origin/release` SHA and requires an associated merged release-branch PR. It does not upload files.
