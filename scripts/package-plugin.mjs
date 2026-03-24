import { cpSync, existsSync, mkdirSync, readdirSync, readFileSync, rmSync, statSync, writeFileSync } from 'node:fs';
import { join, resolve } from 'node:path';
import { execFileSync } from 'node:child_process';

const pluginRoot = resolve(process.cwd());
const packageJsonPath = join(pluginRoot, 'package.json');
const buildRoot = join(pluginRoot, 'build');
const pluginSlug = 'AlloyMetalPriceAPI';
const packageRoot = join(buildRoot, pluginSlug);
const zipPath = join(buildRoot, `${pluginSlug}.zip`);

const runtimeEntries = [
	'alloy-metal-price-api.php',
	'includes',
	'assets/dist',
];

function ensurePluginRoot() {
	if (! existsSync(packageJsonPath)) {
		throw new Error('package.json not found. Run this script from the plugin root.');
	}
}

function getPackageVersion() {
	const packageJson = JSON.parse(readFileSync(packageJsonPath, 'utf8'));

	return packageJson.version || '0.0.0';
}

function resetBuildDirectory() {
	rmSync(buildRoot, { recursive: true, force: true });
	mkdirSync(packageRoot, { recursive: true });
}

function copyRuntimeEntries() {
	for (const entry of runtimeEntries) {
		const sourcePath = join(pluginRoot, entry);
		const targetPath = join(packageRoot, entry);

		if (! existsSync(sourcePath)) {
			throw new Error(`Required runtime entry is missing: ${entry}`);
		}

		cpSync(sourcePath, targetPath, { recursive: true });
	}
}

function writeBuildMetadata(version) {
	const manifestPath = join(packageRoot, 'build-manifest.json');
	const manifest = {
		name: pluginSlug,
		version,
		builtAt: new Date().toISOString(),
		included: runtimeEntries,
	};

	writeFileSync(manifestPath, JSON.stringify(manifest, null, 2) + '\n');
}

function zipPackage() {
	execFileSync('zip', ['-rq', zipPath, pluginSlug], {
		cwd: buildRoot,
		stdio: 'inherit',
	});
}

function listDirectory(dirPath, baseDir = dirPath) {
	const entries = readdirSync(dirPath).sort();
	const lines = [];

	for (const entry of entries) {
		const fullPath = join(dirPath, entry);
		const relativePath = fullPath.replace(`${baseDir}/`, '');
		const suffix = statSync(fullPath).isDirectory() ? '/' : '';

		lines.push(relativePath + suffix);

		if (statSync(fullPath).isDirectory()) {
			lines.push(...listDirectory(fullPath, baseDir));
		}
	}

	return lines;
}

function main() {
	ensurePluginRoot();

	const version = getPackageVersion();

	resetBuildDirectory();
	copyRuntimeEntries();
	writeBuildMetadata(version);
	zipPackage();

	const packagedFiles = listDirectory(packageRoot);

	console.log(`Packaged ${pluginSlug} v${version}`);
	console.log(`Output directory: ${packageRoot}`);
	console.log(`Zip archive: ${zipPath}`);
	console.log('Included files:');

	for (const file of packagedFiles) {
		console.log(`- ${file}`);
	}
}

main();
