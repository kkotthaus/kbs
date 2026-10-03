// Hilfsfunktionen, die Etch-Block-Markup erzeugen (etch/element, etch/text, etch/condition …).
// Das Format entspricht dem, was der Etch-Editor selbst speichert. Übernommen aus dem Blueprint golfplatz.

// Block-Attribute wie WordPress' serialize_block_attributes() maskieren.
// Voraussetzung: Der Sync übergibt alle Inhalte mit wp_slash(), sonst gehen die Backslashes verloren.
export const json = (o) =>
  JSON.stringify(o)
    .replace(/\\"/g, '\\u0022')
    .replace(/--/g, '\\u002d\\u002d')
    .replace(/</g, '\\u003c')
    .replace(/>/g, '\\u003e')
    .replace(/&/g, '\\u0026');

const block = (name, attrs, inner) => {
  const a = Object.keys(attrs).length ? ' ' + json(attrs) : '';
  if (inner === null) return `<!-- wp:${name}${a} /-->`;
  return `<!-- wp:${name}${a} -->\n${inner}\n<!-- /wp:${name} -->`;
};

const join = (kinder) => kinder.flat(Infinity).filter(Boolean).join('\n');

/**
 * HTML-Element. Klasse als String, weitere Attribute als Objekt.
 * el('section', 'page-hero', [...kinder], { name: 'Seitenkopf', attrs: { id: 'x' } })
 */
export const el = (tag, klasse, kinder = [], { name, attrs = {} } = {}) => {
  const attributes = { ...attrs };
  if (klasse) attributes.class = klasse;
  const a = { tag, attributes };
  if (name) a.metadata = { name };
  return block('etch/element', a, join(Array.isArray(kinder) ? kinder : [kinder]));
};

/** Text, darf dynamische Daten wie {this.title} enthalten. */
export const text = (content) => block('etch/text', { content }, null);

/** Element mit einem einzelnen Text als Inhalt. */
export const t = (tag, klasse, content, opts) => el(tag, klasse, [text(content)], opts);

/** Rohes HTML, z. B. für mehrzeilige Texte aus den Firmendaten. */
export const raw = (content) => block('etch/raw-html', { content }, null);

/**
 * Bedingung. Ohne Operator: Inhalt nur, wenn der Wert gefüllt ist.
 * wenn('options.kbs.firma.hat_fax', [...]) · sonst: wenn('x', [...], 'isFalsy')
 */
export const wenn = (leftHand, kinder, operator = 'isTruthy', rightHand = null) =>
  block(
    'etch/condition',
    {
      condition: { leftHand, operator, rightHand },
      conditionString: operator === 'isTruthy' ? leftHand : operator === 'isFalsy' ? `!${leftHand}` : `${leftHand} ${operator} ${rightHand}`,
    },
    join(kinder),
  );

/** Schleife über eine Liste aus dynamischen Daten (target). */
export const loop = ({ target, itemId = 'item' }, kinder) => block('etch/loop', { target, itemId }, join(kinder));

/**
 * Wert aus den Firmendaten (Einstellungsseite „Firmendaten“, aufbereitet von snippets/kbs-firma.php).
 * firma('telefon') → {options.kbs.firma.telefon}
 */
export const FIRMA = 'options.kbs.firma';
export const firma = (feld) => `{${FIRMA}.${feld}}`;

/**
 * Etch-Komponente einbinden. Die ID ist beim Bauen noch unbekannt: Der Platzhalter
 * "__REF_<key>__" wird beim Sync (kbs/sync-from-files) durch die echte ID ersetzt.
 */
export const komponente = (key, attributes = {}) =>
  `<!-- wp:etch/component ${JSON.stringify({ ref: `__REF_${key}__`, attributes })} -->\n\n<!-- /wp:etch/component -->`;

/**
 * OhMyEtch-Komponente einbinden (Accordion, Breadcrumbs, NavigationMenu …), per Key statt WordPress-ID:
 * Der Sync ersetzt "__REF_<Key>__" durch die ID der vorhandenen Komponente (etch_component_html_key).
 * Gruppen-Eigenschaften als Objekt: { content: { label: 'Frage' } } → '{{"label":"Frage"}}' (Etch-Format).
 * Slots: { default: [...kinder] }.
 */
export const ome = (key, eigenschaften = {}, slots = {}, name) => {
  const attributes = Object.fromEntries(
    Object.entries(eigenschaften).map(([k, v]) => [k, v !== null && typeof v === 'object' && !Array.isArray(v) ? `{${JSON.stringify(v)}}` : v]),
  );
  const a = { ref: `__REF_${key}__`, attributes };
  if (name) a.metadata = { name };
  const inhalt = Object.entries(slots)
    .map(([slot, kinder]) => `<!-- wp:etch/slot-content ${json({ name: slot })} -->\n${join([kinder]) || ''}\n<!-- /wp:etch/slot-content -->`)
    .join('\n\n');
  return `<!-- wp:etch/component ${json(a)} -->\n${inhalt}\n<!-- /wp:etch/component -->`;
};

/** SVG-Icons (24er-Raster, Strichstärke über CSS). Etch filtert SVG aus Raw-HTML, deshalb als Etch-Elemente. */
const iconPfade = {
  phone: ['M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.25 11.4 11.4 0 0 0 3.6.57 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.45.57 3.57a1 1 0 0 1-.25 1z'],
  mail: ['M4 5h16a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1zm8 7.2L4.5 7v10.5h15V7z'],
  pin: ['M12 2a7 7 0 0 1 7 7c0 5-7 13-7 13S5 14 5 9a7 7 0 0 1 7-7zm0 4.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5z'],
  clock: ['M12 3a9 9 0 1 1 0 18 9 9 0 0 1 0-18zm0 1.8a7.2 7.2 0 1 0 0 14.4 7.2 7.2 0 0 0 0-14.4zm.9 2.7v4.1l3.1 1.8-.9 1.6-4-2.3V7.5z'],
  arrow: ['M13.2 5.3 20 12l-6.8 6.7-1.3-1.3 4.6-4.5H4v-1.8h12.5L11.9 6.6z'],
  check: ['M9.5 16.2 5.3 12l-1.3 1.3 5.5 5.5L20 8.3 18.7 7z'],
  download: ['M11.1 3h1.8v9.6l3.4-3.4 1.3 1.3-5.6 5.6-5.6-5.6 1.3-1.3 3.4 3.4zM4 18.2h16V20H4z'],
  // Bildschirm mit Werkzeug: IT-Betreuung
  monitor: ['M3 4h18a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1h-7v2h3v1.8H7V19h3v-2H3a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1zm.8 1.8v9.4h16.4V5.8z'],
  // Sprechblase: Beratung
  chat: ['M4 4h16a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1H9l-4.5 3.5V17H4a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1zm.8 1.8v9.4h1.5v1.6l2.1-1.6h10.8V5.8zM7.5 9.2h9v1.6h-9zm0 3h6v1.6h-6z'],
  // Browserfenster: Websites
  browser: ['M3 4h18a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1zm.8 5.2v9h16.4v-9zm0-3.4v1.6h16.4V5.8zM6 12h7v1.6H6zm0 2.8h4.5v1.6H6z'],
  // Fernwartung: Bildschirm mit Pfeilen
  remote: ['M3 4h18a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1h-7v2h3v1.8H7V19h3v-2H3a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1zm.8 1.8v9.4h16.4V5.8zm7.4 1.7 3.3 3-3.3 3-1.2-1.2 1.1-1H7v-1.6h4.1l-1.1-1z'],
  shield: ['M12 2 4 5v6c0 5 3.4 9.4 8 11 4.6-1.6 8-6 8-11V5zm0 1.9 6.2 2.3V11c0 4-2.6 7.6-6.2 9-3.6-1.4-6.2-5-6.2-9V6.2zm-1 10.3-2.6-2.6-1.2 1.3 3.8 3.8 5.8-5.8-1.3-1.3z'],
  user: ['M12 12a4.5 4.5 0 1 1 0-9 4.5 4.5 0 0 1 0 9zm0 1.8c4.4 0 8 2.3 8 5.2V21H4v-2c0-2.9 3.6-5.2 8-5.2z'],
  menu: ['M3 6h18v1.8H3zm0 5.1h18v1.8H3zm0 5.1h18V18H3z'],
  chevron: ['M6.4 8.6 12 14.2l5.6-5.6 1.3 1.3-6.9 6.9-6.9-6.9z'],
};

export const icon = (name, klasse = '') =>
  el('svg', `icon icon--${name}${klasse ? ' ' + klasse : ''}`, iconPfade[name].map((d) => el('path', '', [], { attrs: { d } })), {
    attrs: { viewBox: '0 0 24 24', 'aria-hidden': 'true', focusable: 'false' },
  });

/** Kern-Block „Beitragsinhalt“. */
export const postContent = () => '<!-- wp:post-content {"align":"full","layout":{"type":"default"}} /-->';

export const markup = (...kinder) => join(kinder);
