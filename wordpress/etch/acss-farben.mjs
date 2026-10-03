// Farbpalette für Automatic.css v4 (Rechenweg aus dem Blueprint golfplatz).
// ACSS speichert jede Farbe und jede Abstufung als OKLCH (…-l-oklch, …-c-oklch, …-h-oklch).
// Wo das Design einen festen Wert hat, wird er exakt übernommen; die übrigen Abstufungen
// werden mit Farbton und Sättigung der Hauptfarbe auf die ACSS-Helligkeitsstufen gerechnet.
//
// Anwenden: node wordpress/etch/build.mjs (schreibt dist/daten/acss-farben.json),
// dann MCP-Funktion kbs/acss-colors mit { aus_datei: true }.
// Nach jeder Änderung: node wordpress/etch/kontrast.mjs (muss fehlerfrei durchlaufen).

const ABSTUFUNGEN = ['ultra-light', 'light', 'semi-light', 'semi-dark', 'dark', 'ultra-dark', 'hover'];
const STANDARD_L = { 'ultra-light': 0.95, light: 0.85, 'semi-light': 0.6, 'semi-dark': 0.4, dark: 0.25, 'ultra-dark': 0.12 };

// Hell: main und die Abstufungen. Im dunklen Farbschema tauscht ACSS die Abstufungen
// (ultra-light ↔ ultra-dark, light ↔ dark, semi-light ↔ semi-dark); main und hover nehmen die Werte aus „dunkel“.
export const palette = {
  // Markenrot aus dem Logo (rgb 210 36 48). Fläche mit weißer Schrift; rote Schrift und Links über primary-dark.
  primary: { main: '#D22430', hover: '#B01E29', dark: '#8E1620', light: '#F7C3C6', 'ultra-light': '#FDF1F2', 'ultra-dark': '#2A0B0E', dunkel: { main: '#F2737B', hover: '#F59AA0' } },
  // Graphit: dunkle Flächen (Hero, Footer) und Seitenhintergrund (ultra-light ↔ ultra-dark im dunklen Schema).
  secondary: { main: '#2F3A45', dark: '#1E262E', light: '#D5DDE5', 'ultra-light': '#F6F8FA', 'ultra-dark': '#0F1419', dunkel: { main: '#AFC0D0', hover: '#C8D4DF' } },
  // Text und Grautöne (Logo-Grau rgb 94 94 94 als Nebentext-Richtwert)
  base: { main: '#1F2328', 'semi-dark': '#4B535C', 'semi-light': '#68717B', light: '#D9DEE3', 'ultra-light': '#EEF1F4', dark: '#353B42', 'ultra-dark': '#1A1F25', dunkel: { main: '#E6EAEE', hover: '#CDD3D9' } },
  success: { main: '#2F6B3F', light: '#9FD3AA', 'ultra-light': '#E3EFE4', 'ultra-dark': '#14261A', dunkel: { main: '#7FC08F', hover: '#98CFA5' } },
  warning: { main: '#8A5A0E', light: '#F0CF7E', 'ultra-light': '#FBEFD8', 'ultra-dark': '#2E2310', dunkel: { main: '#E0B35A', hover: '#E8C47C' } },
  // Fehler bewusst orange statt Markenrot, damit Fehlermeldungen nicht wie Markenflächen wirken (Text steht immer dabei).
  danger: { main: '#A6400B', light: '#F3B999', 'ultra-light': '#FCEDE4', 'ultra-dark': '#2E170A', dunkel: { main: '#F29A68', hover: '#F5B08A' } },
};

// Farbschema: folgt der Einstellung des Betrachters (prefers-color-scheme), ACSS „light dark“.
// Diese Bereiche bleiben in beiden Schemata gleich (dunkle Markenflächen mit heller Schrift).
export const immerHell = ['.home-hero', '.page-hero', '.cta-band', '.site-footer'];

// sRGB-Hex → OKLCH (Björn Ottosson)
export function hexToOklch(hex) {
  const n = parseInt(hex.slice(1), 16);
  const lin = (c) => ((c /= 255) <= 0.04045 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4);
  const [r, g, b] = [lin((n >> 16) & 255), lin((n >> 8) & 255), lin(n & 255)];
  const l = Math.cbrt(0.4122214708 * r + 0.5363325363 * g + 0.0514459929 * b);
  const m = Math.cbrt(0.2119034982 * r + 0.6806995451 * g + 0.1073969566 * b);
  const s = Math.cbrt(0.0883024619 * r + 0.2817188376 * g + 0.6299787005 * b);
  const L = 0.2104542553 * l + 0.793617785 * m - 0.0040720468 * s;
  const A = 1.9779984951 * l - 2.428592205 * m + 0.4505937099 * s;
  const B = 0.0259040371 * l + 0.7827717662 * m - 0.808675766 * s;
  const C = Math.sqrt(A * A + B * B);
  let H = (Math.atan2(B, A) * 180) / Math.PI;
  if (H < 0) H += 360;
  return { l: +L.toFixed(3), c: +C.toFixed(3), h: +H.toFixed(2) };
}

export function acssEinstellungen() {
  const werte = {
    'option-palette-unify-brand-lightness': 'off',
    'option-palette-unify-status-lightness': 'off',
    'auto-color-scheme': 'on',
    // „light dark“: Hell oder Dunkel nach der Systemeinstellung des Betrachters
    'website-color-scheme': 'light dark',
    'option-ref-color-tokens': 'on',
    'color-scheme-force-light-selectors': immerHell.join(', '),
    // Buttons: Weiß statt -ultra-light (Kontrast), im dunklen Schema wird --white schwarz
    'btn-primary-text': 'var(--white)',
    'btn-primary-hover-text': 'var(--white)',
    'btn-secondary-text': 'var(--white)',
    'btn-secondary-hover-text': 'var(--white)',
    'link-color': 'var(--primary-dark)',
    'link-color-hover': 'var(--primary-hover)',
  };
  for (const [name, farben] of Object.entries(palette)) {
    const main = hexToOklch(farben.main);
    werte[`color-${name}`] = farben.main;
    werte[`color-${name}-alt`] = farben.main;
    werte[`option-${name}-clr`] = 'on';
    const setze = (prefix, { l, c, h }) => {
      werte[`${prefix}-l-oklch`] = l;
      werte[`${prefix}-c-oklch`] = c;
      werte[`${prefix}-h-oklch`] = h;
    };
    setze(name, main);
    if (farben.dunkel) {
      for (const [teil, hex] of Object.entries(farben.dunkel)) {
        const { l, c } = hexToOklch(hex);
        const prefix = teil === 'main' ? name : `${name}-hover`;
        werte[`${prefix}-l-alt-oklch`] = l;
        werte[`${prefix}-c-alt-oklch`] = c;
      }
    }
    for (const stufe of ABSTUFUNGEN) {
      let wert;
      if (farben[stufe] && typeof farben[stufe] === 'string') wert = hexToOklch(farben[stufe]);
      else if (stufe === 'hover') wert = { l: +(main.l < 0.5 ? main.l + 0.08 : main.l - 0.08).toFixed(3), c: main.c, h: main.h };
      else {
        const l = STANDARD_L[stufe];
        wert = { l, c: l >= 0.85 ? Math.min(main.c, 0.03) : main.c, h: main.h };
      }
      setze(`${name}-${stufe}`, wert);
    }
  }
  return werte;
}

// Direkt aufgerufen: Einstellungen als JSON ausgeben
if (process.argv[1] && import.meta.url.endsWith(process.argv[1].replace(/\\/g, '/').split('/').pop())) {
  process.stdout.write(JSON.stringify(acssEinstellungen()));
}
