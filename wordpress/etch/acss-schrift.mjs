// Schrift-Einstellungen für Automatic.css (Automatic.css › Typografie).
// Manrope liegt selbst gehostet in medien/ (@font-face in css/main.css), Lizenz in lizenzen/.
// Build schreibt dist/daten/acss-schrift.json; übernehmen per MCP kbs/acss-colors mit { aus_datei: true }.

const MANROPE = '"Manrope", ui-sans-serif, system-ui, "Segoe UI", Roboto, Arial, sans-serif';

export const acssSchrift = {
  'text-font-family': MANROPE,
  'heading-font-family': MANROPE,
  'heading-weight': '750',
  'heading-letter-spacing': '-0.03em',
};
