// Seitentext als Markdown für /llms-full.txt (snippets/kbs-seo.php), erzeugt aus dem gebauten Etch-Markup.
// Nur sichtbarer Text: Überschriften, Absätze, Listen, Links und häufige Fragen. Bilder, Symbole, Knöpfe,
// Kennzeilen (Eyebrows) und eingebundene Komponenten ohne eigenen Text fallen weg.
// Firmenangaben bleiben als {options.kbs.firma.…} stehen und werden erst im Snippet eingesetzt,
// interne Links bleiben relativ (/pfad/) und werden dort zu vollen Adressen.

const BLOCK = /<!--\s*(\/)?wp:([a-z0-9/-]+)(?:\s+(\{.*?\}))?\s*(\/)?-->/gs;

// Markup → Baum aus { name, attrs, kinder }
const baum = (markup) => {
  const wurzel = { name: 'wurzel', attrs: {}, kinder: [] };
  const stapel = [wurzel];
  for (const [, ende, name, json, selbst] of markup.matchAll(BLOCK)) {
    if (ende) {
      if (stapel.length > 1) stapel.pop();
      continue;
    }
    let attrs = {};
    try {
      attrs = json ? JSON.parse(json) : {};
    } catch {
      attrs = {};
    }
    const knoten = { name, attrs, kinder: [] };
    stapel.at(-1).kinder.push(knoten);
    if (!selbst) stapel.push(knoten);
  }
  return wurzel;
};

const tag = (k) => (k.name === 'etch/element' ? k.attrs.tag : null);
const klasse = (k) => k.attrs.attributes?.class ?? '';
const hat = (k, ...namen) => namen.some((n) => klasse(k).split(/\s+/).some((c) => c === n || c.startsWith(n + '--')));

// Elemente ohne Inhalt für den Text
const WEG_TAGS = new Set(['img', 'svg', 'path', 'circle', 'rect', 'line', 'polyline', 'button', 'form', 'input', 'dl']);
const WEG_KLASSEN = ['visually-hidden', 'page-hero__eyebrow', 'section-head__eyebrow', 'home-hero__eyebrow', 'hero-card__eyebrow', 'steps__number', 'browser-mock', 'ki-plakette', 'side-card__icon', 'benefit__icon', 'faq__chevron', 'more-link', 'button-row', 'page-hero__actions', 'home-hero__actions', 'icon'];
const weg = (k) =>
  WEG_TAGS.has(tag(k)) || k.attrs.attributes?.['aria-hidden'] === 'true' || hat(k, ...WEG_KLASSEN) ||
  (tag(k) === 'a' && hat(k, 'btn', 'btn--primary', 'btn--primary-light', 'link-arrow'));

// Fließtext eines Knotens (Links als [Text](/pfad/), Zwischenüberschriften in Listenpunkten fett)
const innenText = (k) => k.kinder.map(inline).filter(Boolean).join(tag(k) && /^(li|div|article)$/.test(tag(k)) ? ' ' : '');
const inline = (k) => {
  if (weg(k)) return '';
  if (k.name === 'etch/text') return String(k.attrs.content ?? '');
  const innen = innenText(k);
  // nur Seitenadressen als Link; tel:, mailto: und Platzhalter bleiben reiner Text
  if (tag(k) === 'a' && /^(\/|https?:)/.test(k.attrs.attributes?.href ?? '') && innen) return `[${innen}](${k.attrs.attributes.href})`;
  if (/^h[1-6]$/.test(tag(k) ?? '') && innen) return `**${innen}** –`;
  return innen;
};

const BLOCKTAGS = new Set(['section', 'div', 'header', 'footer', 'aside', 'main', 'nav', 'article', 'ul', 'ol']);

// Blöcke eines Knotens: [{ ebene, text }] – ebene 0 = Absatz/Liste, sonst Überschrift
const bloecke = (k) => {
  if (weg(k)) return [];
  const t = tag(k);
  const sauber = (s) => s.replace(/\s+/g, ' ').replace(/\s+–$/, '').trim();
  if (hat(k, 'faq__question')) return [{ ebene: 4, text: sauber(inline(k)) }];
  if (t === 'h1') return [{ ebene: 0, text: `**${sauber(innenText(k))}**` }];
  if (/^h[2-6]$/.test(t ?? '')) return [{ ebene: Math.min(6, Number(t[1]) + 1), text: sauber(innenText(k)) }];
  if (t === 'ul' || t === 'ol') {
    const punkte = k.kinder.filter((c) => !weg(c)).map((c) => sauber(inline(c))).filter(Boolean);
    return punkte.length ? [{ ebene: 0, text: punkte.map((p) => `- ${p}`).join('\n') }] : [];
  }
  if (t === 'p' || t === 'span' || t === 'a' || t === 'strong' || t === 'em' || k.name === 'etch/text' || k.name === 'etch/raw-html') {
    const s = sauber(k.name === 'etch/raw-html' ? String(k.attrs.content ?? '').replace(/<br\s*\/?>/g, ', ').replace(/<[^>]+>/g, '') : inline(k));
    return s ? [{ ebene: 0, text: s }] : [];
  }
  // Container (auch Bedingungen, Komponenten und ihre Slots)
  if (!t || BLOCKTAGS.has(t) || t === 'li') return k.kinder.flatMap(bloecke);
  const s = sauber(inline(k));
  return s ? [{ ebene: 0, text: s }] : [];
};

// Überschriften ohne Inhalt darunter entfernen (z. B. „Anfrage senden“ über dem Formular)
const aufraeumen = (liste) =>
  liste.filter((b, i) => {
    if (!b.ebene) return true;
    for (const n of liste.slice(i + 1)) {
      if (!n.ebene) return true;
      if (n.ebene <= b.ebene) return false;
    }
    return false;
  });

/** Sichtbarer Text einer gebauten Seite als Markdown (Überschriften ab ###). */
export const seitentext = (markup) =>
  aufraeumen(bloecke(baum(markup)))
    .map((b) => (b.ebene ? `${'#'.repeat(b.ebene)} ${b.text}` : b.text))
    .join('\n\n');
