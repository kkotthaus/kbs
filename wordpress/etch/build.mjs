// Baut alle Dateien für WordPress nach wordpress/etch/dist/:
// component-<key>.html, page-<slug>.html, template-<slug>.html, manifest.json, kbs.css,
// daten/acss-farben.json, daten/acss-buttons.json, daten/acss-schrift.json, daten/einstellungen-firmendaten.json, daten/weiterleitungen.json, snippets/*.php
// Übertragen: dist nach wp-content/kbs/ kopieren, dann MCP kbs/sync-from-files (siehe wordpress/README.md).

import { copyFileSync, mkdirSync, readdirSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, join, relative } from 'node:path';
import { fileURLToPath } from 'node:url';
import { components } from './komponenten.mjs';
import { pages, templates } from './seiten.mjs';
import { acssEinstellungen } from './acss-farben.mjs';
import { acssButtons } from './acss-buttons.mjs';
import { acssSchrift } from './acss-schrift.mjs';
import { firma, weiterleitungen } from './daten.mjs';

const hier = dirname(fileURLToPath(import.meta.url));
const dist = join(hier, 'dist');
mkdirSync(join(dist, 'daten'), { recursive: true });
mkdirSync(join(dist, 'snippets'), { recursive: true });

const schreibe = (datei, inhalt) => {
  writeFileSync(join(dist, datei), inhalt);
  console.log(`${datei}  (${inhalt.length} Zeichen)`);
};

for (const c of components) schreibe(`component-${c.key}.html`, c.content + '\n');
for (const tpl of templates) schreibe(`template-${tpl.slug}.html`, tpl.content + '\n');
for (const p of pages) schreibe(`page-${p.slug}.html`, p.content + '\n');

schreibe(
  'manifest.json',
  JSON.stringify(
    {
      components: components.map(({ content, ...meta }) => meta),
      templates: templates.map(({ slug, title }) => ({ slug, title })),
      pages: pages.map(({ content, ...meta }) => meta),
    },
    null,
    2,
  ) + '\n',
);

// Globales Etch-Stylesheet „KBS“ (Kommentare und Leerzeilen entfernt)
const css = readFileSync(join(hier, 'css/main.css'), 'utf8')
  .replace(/\/\*[\s\S]*?\*\//g, '')
  .replace(/\s*\n\s*/g, '\n')
  .replace(/\n{2,}/g, '\n')
  .trim();
schreibe('kbs.css', css + '\n');

schreibe('daten/acss-farben.json', JSON.stringify(acssEinstellungen(), null, 1) + '\n');
schreibe('daten/acss-buttons.json', JSON.stringify(acssButtons, null, 1) + '\n');
schreibe('daten/acss-schrift.json', JSON.stringify(acssSchrift, null, 1) + '\n');
schreibe('daten/einstellungen-firmendaten.json', JSON.stringify(firma, null, 2) + '\n');
schreibe('daten/weiterleitungen.json', JSON.stringify(weiterleitungen, null, 2) + '\n');

// Medien (Quelle wordpress/medien/ samt Unterordnern wie bilder/), ausgeliefert unter /wp-content/kbs/medien/
mkdirSync(join(dist, 'medien'), { recursive: true });
for (const e of readdirSync(join(hier, '../medien'), { recursive: true, withFileTypes: true })) {
  if (!e.isFile() || e.name === '.gitkeep') continue;
  const rel = relative(join(hier, '../medien'), join(e.parentPath, e.name));
  mkdirSync(dirname(join(dist, 'medien', rel)), { recursive: true });
  copyFileSync(join(e.parentPath, e.name), join(dist, 'medien', rel));
  console.log(`medien/${rel.replaceAll('\\', '/')}`);
}

// PHP-Snippets (Quelle wordpress/snippets/) – jede Datei beginnt mit der ABSPATH-Prüfung
for (const f of readdirSync(join(hier, '../snippets')).filter((n) => n.endsWith('.php'))) {
  copyFileSync(join(hier, '../snippets', f), join(dist, 'snippets', f));
  console.log(`snippets/${f}`);
}
