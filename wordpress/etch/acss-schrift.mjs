// Schrift-Einstellungen für Automatic.css (Automatic.css › Typografie).
// Fließtext: Manrope, selbst gehostet in medien/ (@font-face in css/main.css), Lizenz in lizenzen/.
// Überschriften: Sora (Google Fonts, OFL), selbst gehostet über den Etch Font Manager (wp-content/fonts/,
// vorgeladen; Einrichtung siehe docs/development-environment.md). Der Font Manager setzt keine Rolle –
// welche Schrift wofür gilt, steht nur hier.
// Build schreibt dist/daten/acss-schrift.json; übernehmen per MCP kbs/acss-colors mit { aus_datei: true }.

const FALLBACK = 'ui-sans-serif, system-ui, "Segoe UI", Roboto, Arial, sans-serif';
const MANROPE = `"Manrope", ${FALLBACK}`;
const SORA = `"Sora", ${FALLBACK}`;

export const acssSchrift = {
  'text-font-family': MANROPE,
  'heading-font-family': SORA,
  'heading-weight': '700',
  'heading-letter-spacing': '-0.03em',
};
