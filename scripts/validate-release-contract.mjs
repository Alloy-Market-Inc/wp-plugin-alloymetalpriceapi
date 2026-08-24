import { execFileSync } from 'node:child_process';
import { appendFileSync, readFileSync } from 'node:fs';

const packageJson = JSON.parse(readFileSync(new URL('../package.json', import.meta.url), 'utf8'));
const release = packageJson.alloyRelease;
const semanticVersion = /^\d+\.\d+\.\d+(?:-[0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*)?(?:\+[0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*)?$/;
const platformVersion = /^\d+\.\d+(?:\.\d+)?$/;

function fail(message) {
  throw new Error(message);
}

if (!semanticVersion.test(packageJson.version ?? '')) fail('package.json version must be semantic.');
if (!release || !release.componentSlug || !release.expectedDirectory || !release.headerFile) fail('alloyRelease metadata is incomplete.');
for (const field of ['minimumWordPressVersion', 'testedWordPressVersion', 'minimumPhpVersion']) {
  if (!platformVersion.test(release[field] ?? '')) fail(`alloyRelease.${field} is invalid.`);
}

const header = readFileSync(new URL(`../${release.headerFile}`, import.meta.url), 'utf8');
const match = header.match(/^\s*\*?\s*Version:\s*(.+)$/mi);
if (!match || match[1].trim() !== packageJson.version) fail('Component header and package.json versions must match.');

const tag = process.env.GITHUB_REF_NAME;
if (tag) {
  if (tag !== `v${packageJson.version}`) fail(`Tag ${tag} does not match version ${packageJson.version}.`);
  const sha = process.env.GITHUB_SHA || 'HEAD';
  try {
    execFileSync('git', ['merge-base', '--is-ancestor', sha, 'origin/release'], { stdio: 'ignore' });
  } catch {
    fail('The tagged commit is not contained in origin/release.');
  }
}

if (process.env.GITHUB_OUTPUT) {
  appendFileSync(process.env.GITHUB_OUTPUT, [
    `version=${packageJson.version}`,
    `component_slug=${release.componentSlug}`,
    `minimum_wordpress_version=${release.minimumWordPressVersion}`,
    `tested_wordpress_version=${release.testedWordPressVersion}`,
    `minimum_php_version=${release.minimumPhpVersion}`,
  ].join('\n') + '\n');
}

console.log(`Release contract valid for ${release.componentSlug} ${packageJson.version}.`);
