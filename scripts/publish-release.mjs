import { createHash } from 'node:crypto';
import { readFile } from 'node:fs/promises';
import { upload } from '@vercel/blob/client';
import packageJson from '../package.json' with { type: 'json' };

const release = packageJson.alloyRelease;
const [version, zipPath, changelogUrl] = process.argv.slice(2);

function fail(message) {
  console.error(message);
  process.exitCode = 1;
}

if (!version || !zipPath || !changelogUrl) {
  fail('Usage: npm run publish-release -- <version> <zip> <https-changelog-url>');
} else if (version !== packageJson.version) {
  fail(`Version ${version} does not match package.json ${packageJson.version}.`);
} else if (!process.env.UPDATE_SERVICE_URL || !process.env.UPDATE_SERVICE_PUBLISHER_TOKEN || !process.env.UPDATE_SERVICE_READ_TOKEN) {
  fail('UPDATE_SERVICE_URL and separate publisher/read credentials are required.');
} else {
  const bytes = await readFile(zipPath);
  const sha256 = createHash('sha256').update(bytes).digest('hex');
  const pathname = `packages/${release.componentSlug}/${version}/${sha256}/${release.expectedDirectory}.zip`;
  const baseUrl = process.env.UPDATE_SERVICE_URL.replace(/\/$/, '');
  const versionUrl = `${baseUrl}/v1/components/${release.componentSlug}/versions/${encodeURIComponent(version)}`;
  const readHeaders = { authorization: `Bearer ${process.env.UPDATE_SERVICE_READ_TOKEN}` };
  const existingResponse = await fetch(versionUrl, { headers: readHeaders });

  if (existingResponse.ok) {
    const existing = await existingResponse.json();
    if (existing.sha256 !== sha256) fail(`Version ${version} already exists with different bytes.`);
    else console.log(`Version ${version} already exists with the same SHA-256; no upload was needed.`);
  } else if (existingResponse.status !== 404) {
    fail(`Preflight failed with HTTP ${existingResponse.status}.`);
  } else {
    await upload(pathname, bytes, {
      access: 'private',
      contentType: 'application/zip',
      multipart: true,
      handleUploadUrl: `${baseUrl}/v1/components/${release.componentSlug}/releases`,
      headers: { authorization: `Bearer ${process.env.UPDATE_SERVICE_PUBLISHER_TOKEN}` },
      clientPayload: JSON.stringify({
        version,
        sha256,
        size: bytes.byteLength,
        minimumWordPressVersion: release.minimumWordPressVersion,
        testedWordPressVersion: release.testedWordPressVersion,
        minimumPhpVersion: release.minimumPhpVersion,
        changelogUrl,
      }),
    });

    let verified = false;
    for (let attempt = 0; attempt < 20; attempt += 1) {
      const response = await fetch(versionUrl, { headers: readHeaders });
      if (response.ok) {
        const manifest = await response.json();
        if (manifest.sha256 !== sha256) fail('Published manifest checksum does not match the uploaded package.');
        else console.log(`Published ${release.componentSlug} ${version}; SHA-256 ${sha256}.`);
        verified = true;
        break;
      }
      await new Promise((resolve) => setTimeout(resolve, 1500));
    }
    if (!verified) fail('Upload completed, but the immutable manifest was not verified in time.');
  }
}
