// Kontrastprüfung (WCAG 2.1 AA) der ACSS-Farben in beiden Farbschemata.
// Rechnet die Werte so, wie ACSS sie ausgibt (acss-farben.mjs), inkl. Tausch der Abstufungen im dunklen Schema.
// Aufruf: node wordpress/etch/kontrast.mjs   → Exit-Code 1, wenn eine Kombination durchfällt.

import { acssEinstellungen } from './acss-farben.mjs';

const w = acssEinstellungen();
const TAUSCH = { 'ultra-light': 'ultra-dark', 'ultra-dark': 'ultra-light', light: 'dark', dark: 'light', 'semi-light': 'semi-dark', 'semi-dark': 'semi-light' };

const oklch = (p, alt = false) => {
  const s = alt && w[`${p}-l-alt-oklch`] !== undefined ? '-alt' : '';
  const h = w[`${p}-h-oklch`] ?? w[`${p.split('-')[0]}-h-oklch`];
  return { l: +w[`${p}-l${s}-oklch`], c: +w[`${p}-c${s}-oklch`], h: +h };
};

// Variable → OKLCH im jeweiligen Schema
function farbe(name, dunkel) {
  if (name === 'white') return dunkel ? { l: 0, c: 0, h: 0 } : { l: 1, c: 0, h: 0 };
  if (name === 'black') return dunkel ? { l: 1, c: 0, h: 0 } : { l: 0, c: 0, h: 0 };
  // Nebentext: main.css setzt --base-semi-dark als Mischung aus Text- und Seitenfarbe (wirkt in beiden Schemata)
  if (name === 'base-semi-dark') {
    const a = farbe('base', dunkel), b = farbe('secondary-ultra-light', dunkel);
    return { l: a.l * 0.78 + b.l * 0.22, c: a.c * 0.78 + b.c * 0.22, h: a.h };
  }
  const [familie, ...rest] = name.split('-');
  const stufe = rest.join('-');
  if (!stufe) return oklch(familie, dunkel);
  if (stufe === 'hover') return oklch(`${familie}-hover`, dunkel);
  return oklch(`${familie}-${dunkel ? TAUSCH[stufe] : stufe}`);
}

function luminanz({ l, c, h }) {
  const a = c * Math.cos((h * Math.PI) / 180), b = c * Math.sin((h * Math.PI) / 180);
  const l_ = (l + 0.3963377774 * a + 0.2158037573 * b) ** 3;
  const m_ = (l - 0.1055613458 * a - 0.0638541728 * b) ** 3;
  const s_ = (l - 0.0894841775 * a - 1.291485548 * b) ** 3;
  const k = (x) => Math.min(1, Math.max(0, x));
  const r = k(4.0767416621 * l_ - 3.3077115913 * m_ + 0.2309699292 * s_);
  const g = k(-1.2684380046 * l_ + 2.6097574011 * m_ - 0.3413193965 * s_);
  const bl = k(-0.0041960863 * l_ - 0.7034186147 * m_ + 1.707614701 * s_);
  // sRGB-Werte aus linearem Licht sind hier schon linear → direkt gewichten
  return 0.2126 * r + 0.7152 * g + 0.0722 * bl;
}
const kontrast = (x, y) => { const [a, b] = [luminanz(x), luminanz(y)].sort((p, q) => q - p); return (a + 0.05) / (b + 0.05); };

// Rollen (docs/farben.md): Seite, Karte, Fläche (getönte Abschnitte)
const SEITE = 'secondary-ultra-light', KARTE = 'white', FLAECHE = 'base-ultra-light';
// [Vordergrund, Hintergrund, Mindestwert, Verwendung]
const paare = [
  ['base', SEITE, 4.5, 'Fließtext'], ['base', KARTE, 4.5, 'Text auf Karten'], ['base', FLAECHE, 4.5, 'Text auf Flächen'],
  ['base-semi-dark', SEITE, 4.5, 'Nebentext'], ['base-semi-dark', KARTE, 4.5, 'Nebentext auf Karten'], ['base-semi-dark', FLAECHE, 4.5, 'Nebentext auf Flächen'],
  ['primary-dark', SEITE, 4.5, 'Links, Eyebrows'], ['primary-dark', KARTE, 4.5, 'Links auf Karten'], ['primary-dark', FLAECHE, 4.5, 'Links auf Flächen'],
  ['primary-hover', SEITE, 4.5, 'Link-Hover'], ['primary-hover', KARTE, 4.5, 'Link-Hover auf Karten'],
  ['primary-dark', 'primary-ultra-light', 4.5, 'Icon-Kachel, Hinweis'],
  // ACSS-Buttons (btn--primary, btn--primary btn--outline)
  ['white', 'primary', 4.5, 'btn--primary'], ['white', 'primary-hover', 4.5, 'btn--primary Hover'],
  ['primary', SEITE, 4.5, 'btn--outline Text'], ['primary', KARTE, 4.5, 'btn--outline Text auf Karten'], ['primary-ultra-light', 'primary-hover', 4.5, 'btn--outline Hover'],
  ['primary', SEITE, 3, 'Icons, Fokusrahmen (Grafik)'], ['primary', KARTE, 3, 'Icons auf Karten (Grafik)'],
  ['base-semi-dark', KARTE, 3, 'Rahmen Eingabefeld'],
  ['primary-dark', SEITE, 3, 'Fokusring Buttons'], ['primary-dark', KARTE, 3, 'Fokusring Buttons auf Karten'], ['primary', KARTE, 3, 'Fokusring Links'],
  ['success', 'success-ultra-light', 4.5, 'Meldung gesendet'], ['danger', 'danger-ultra-light', 4.5, 'Fehlermeldung Formular'], ['danger', KARTE, 4.5, 'Fehlertext am Feld'],
];
// Bereiche, die immer hell gerechnet werden (Hero, Seitenkopf, CTA-Band, Footer)
const immerHell = [
  ['white', 'secondary-ultra-dark', 4.5, 'Hero/Footer-Text'], ['secondary-light', 'secondary-ultra-dark', 4.5, 'Hero/Footer-Nebentext'],
  ['primary-light', 'secondary-ultra-dark', 4.5, 'Hero-Eyebrow, Footer-Links-Hover'], ['white', 'primary', 4.5, 'CTA-Band'],
  ['primary-ultra-light', 'primary', 4.5, 'CTA-Band Nebentext'],
  ['primary', 'primary-ultra-light', 4.5, 'btn--primary-light (CTA-Band)'], ['primary-ultra-dark', 'primary-light', 4.5, 'btn--primary-light Hover'],
  ['primary-light', 'secondary-ultra-dark', 4.5, 'btn--primary-light btn--outline (Hero)'], ['primary-dark', 'primary-light', 4.5, 'btn--primary-light btn--outline Hover'], ['white', 'secondary-dark', 4.5, 'Seitenkopf-Karte'],
  ['primary-light', 'secondary-ultra-dark', 3, 'Fokusring auf dunklen Flächen'], ['white', 'primary', 3, 'Fokusring im CTA-Band'],
];

let fehler = 0;
for (const [schema, dunkel] of [['Hell', false], ['Dunkel', true]]) {
  console.log(`\n${schema}`);
  const liste = [...paare.map((p) => [...p, dunkel]), ...immerHell.map((p) => [...p, false])];
  for (const [fg, bg, min, wozu, d] of liste) {
    const k = kontrast(farbe(fg, d), farbe(bg, d));
    const ok = k >= min;
    if (!ok) fehler++;
    console.log(`${ok ? '  ok ' : 'FEHLT'} ${k.toFixed(2).padStart(5)} (min ${min})  ${fg} auf ${bg} – ${wozu}`);
  }
}
console.log(fehler ? `\n${fehler} Kombination(en) unter dem Mindestwert.` : '\nAlle Kombinationen erfüllen WCAG 2.1 AA.');
process.exitCode = fehler ? 1 : 0;
