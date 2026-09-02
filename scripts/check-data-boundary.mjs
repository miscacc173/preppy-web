import { readdirSync, readFileSync } from "node:fs";
import { join } from "node:path";
import { fileURLToPath } from "node:url";

const roots = [fileURLToPath(new URL("../resources/js/views/", import.meta.url)), fileURLToPath(new URL("../resources/js/components/", import.meta.url))];
const violations = [];

function visit(directory) {
  for (const entry of readdirSync(directory, { withFileTypes: true })) {
    const path = join(directory, entry.name);
    if (entry.isDirectory()) visit(path);
    if (!entry.isFile() || !/\.(vue|ts)$/.test(entry.name)) continue;
    const source = readFileSync(path, "utf8");
    if (source.includes('from "@/lib/platformApi"')) violations.push(`${path}: imports raw API client; use platformRepository`);
    if (/import\s*\{[^}]*\bapi\b[^}]*\}\s*from\s*["']@\/lib\/api["']/.test(source)) violations.push(`${path}: imports raw API client; use a repository`);
    if (source.includes("@/data/mock/")) violations.push(`${path}: imports mock data into presentation code`);
    if (/\b(window\.)?(alert|confirm)\s*\(/.test(source)) violations.push(`${path}: uses a browser alert/confirm; use an application modal`);
    if (source.includes("aistar:studentOnboarding")) violations.push(`${path}: reads legacy onboarding localStorage`);
    if (source.includes("DEFAULT_SUBSCRIPTION")) violations.push(`${path}: injects default academic data`);
  }
}

roots.forEach(visit);
if (violations.length) {
  console.error(violations.join("\n"));
  process.exit(1);
}
console.log("Vue data boundary check passed.");
