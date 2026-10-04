// Schrift-Einstellungen für Automatic.css (Automatic.css › Typografie).
// Beide Schriften (Google Fonts, OFL, Lizenzen in lizenzen/) hostet der Etch Font Manager selbst (wp-content/fonts/;
// Einrichtung siehe docs/development-environment.md, Standard etch-nodes/docs/konventionen.md#schriften):
// Fließtext Manrope, Überschriften Sora (vorgeladen). Der Font Manager setzt keine Rolle –
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
