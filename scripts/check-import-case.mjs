import { existsSync, readdirSync, readFileSync, statSync } from "node:fs";
import { dirname, extname, join, normalize, relative, resolve } from "node:path";
import { fileURLToPath } from "node:url";

const projectRoot = fileURLToPath(new URL("../", import.meta.url));
const sourceRoot = join(projectRoot, "resources/js");
const sourceExtensions = [".ts", ".tsx", ".js", ".mjs", ".vue", ".css", ".json"];
const failures = [];

function filesIn(directory) {
  return readdirSync(directory, { withFileTypes: true }).flatMap((entry) => {
    const path = join(directory, entry.name);
    if (entry.isDirectory()) {
      if (entry.name !== entry.name.toLowerCase()) failures.push(`${relative(projectRoot, path)}: directory names must be lowercase`);
      return filesIn(path);
    }
    return /\.(ts|tsx|js|mjs|vue)$/.test(entry.name) ? [path] : [];
  });
}

function candidates(base) {
  if (extname(base)) return [base];
  return [base, ...sourceExtensions.map((extension) => base + extension), ...sourceExtensions.map((extension) => join(base, "index" + extension))];
}

function hasExactCase(path) {
  const absolute = normalize(path);
  const parts = relative(projectRoot, absolute).split(/[\\/]/).filter(Boolean);
  let parent = projectRoot;
  for (const part of parts) {
    if (!existsSync(parent) || !statSync(parent).isDirectory()) return false;
    if (!readdirSync(parent).includes(part)) return false;
    parent = join(parent, part);
  }
  return existsSync(parent);
}

const importPattern = /(?:\bfrom\s*|\bimport\s*\(\s*|\bimport\s*)["']([^"']+)["']/g;

for (const file of filesIn(sourceRoot)) {
  const source = readFileSync(file, "utf8");
  for (const match of source.matchAll(importPattern)) {
    const specifier = match[1];
    if (!(specifier.startsWith("@/") || specifier.startsWith("./") || specifier.startsWith("../"))) continue;
    const base = specifier.startsWith("@/") ? join(sourceRoot, specifier.slice(2)) : resolve(dirname(file), specifier);
    const resolved = candidates(base).find((candidate) => existsSync(candidate));
    if (!resolved) failures.push(`${relative(projectRoot, file)}: unresolved import ${specifier}`);
    else if (!hasExactCase(resolved)) failures.push(`${relative(projectRoot, file)}: import case does not match disk: ${specifier}`);
  }
}

if (failures.length) {
  console.error(failures.join("\n"));
  process.exit(1);
}

console.log("Case-sensitive import and directory check passed.");
