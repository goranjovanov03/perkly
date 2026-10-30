import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const localesDir = path.resolve(__dirname, '../src/locales');

const files = fs.readdirSync(localesDir).filter(f => f.endsWith('.json'));

if (files.length < 2) {
  console.error('Need at least 2 locale files');
  process.exit(1);
}

const locales = files.map(f => ({
  name: f.replace('.json', ''),
  data: JSON.parse(fs.readFileSync(path.join(localesDir, f), 'utf-8'))
}));

function flatten(obj, prefix = '') {
  return Object.keys(obj).reduce((acc, key) => {
    const newKey = prefix ? `${prefix}.${key}` : key;
    if (typeof obj[key] === 'object' && obj[key] !== null && !Array.isArray(obj[key])) {
      return { ...acc, ...flatten(obj[key], newKey) };
    }
    return { ...acc, [newKey]: true };
  }, {});
}

const keys = locales.map(l => ({
  name: l.name,
  keys: new Set(Object.keys(flatten(l.data)))
}));

const allKeys = new Set();
keys.forEach(k => k.keys.forEach(key => allKeys.add(key)));

let hasErrors = false;
keys.forEach(({ name, keys: localeKeys }) => {
  const missing = [...allKeys].filter(k => !localeKeys.has(k));
  if (missing.length > 0) {
    console.error(`Locale '${name}' is missing keys:`);
    missing.forEach(m => console.error(`  - ${m}`));
    hasErrors = true;
  }
});

if (hasErrors) {
  process.exit(1);
}

console.log('All locales have matching keys');