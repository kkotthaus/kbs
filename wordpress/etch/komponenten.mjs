// Etch-Komponenten (wp_block). Firmenangaben kommen aus der Einstellungsseite „Firmendaten“
// über {options.kbs.firma.…} (snippets/kbs-firma.php), nie als Text im Markup.

import { el, t, text, wenn, raw, firma, FIRMA, icon, komponente } from './lib.mjs';
import { navigation, leistungen } from './daten.mjs';

const KONTAKT = 'options.kbs.kontakt';

// Logo: Bild aus den Firmendaten bzw. mitgeliefertes Firmenlogo (kbs-firma.php), sonst Wortmarke aus dem Kurznamen.
// Header: beide Varianten, CSS zeigt die passende (scheme-bild--hell/--dunkel folgen Gerät und Umschalter). Footer: immer dunkle Fläche.
const logoBild = (variante) =>
  variante === 'footer'
    ? [el('img', 'site-logo__bild', [], { attrs: { src: firma('logo_dunkel'), alt: firma('name'), width: '56', height: '48' } })]
    : [
        el('img', 'site-logo__bild scheme-bild--hell', [], { attrs: { src: firma('logo'), alt: firma('name'), width: '56', height: '48' } }),
        el('img', 'site-logo__bild scheme-bild--dunkel', [], { attrs: { src: firma('logo_dunkel'), alt: firma('name'), width: '56', height: '48' } }),
      ];
const logo = (variante) =>
  el('a', `site-logo site-logo--${variante}`, [
    wenn(`${FIRMA}.hat_logo`, logoBild(variante)),
    wenn(`${FIRMA}.hat_logo`, [
      el('span', 'site-logo__mark', [text('{options.kbs.firma.initialen}')], { attrs: { 'aria-hidden': 'true' } }),
      el('span', 'site-logo__text', [t('span', 'site-logo__name', firma('wortmarke_1')), t('span', 'site-logo__sub', firma('wortmarke_2'))]),
    ], 'isFalsy'),
  ], { attrs: { href: '/', rel: 'home' }, name: 'Logo' });

// Hauptnavigation: Untermenüs als Disclosure (Skript: snippets/kbs-navigation.php)
const navPunkt = (punkt, i) => {
  if (!punkt.kinder) {
    return el('li', 'main-nav__item', [t('a', 'main-nav__link', punkt.text, { attrs: { href: punkt.link } })]);
  }
  const id = `untermenue-${i}`;
  return el('li', 'main-nav__item main-nav__item--has-sub', [
    t('a', 'main-nav__link', punkt.text, { attrs: { href: punkt.link } }),
    el('button', 'main-nav__sub-toggle', [t('span', 'visually-hidden', `Untermenü ${punkt.text}`), icon('chevron')], {
      attrs: { type: 'button', 'aria-expanded': 'false', 'aria-controls': id, 'data-nav-sub': '' },
    }),
    el('ul', 'main-nav__sub', punkt.kinder.map(([label, href]) => el('li', 'main-nav__sub-item', [t('a', 'main-nav__sub-link', label, { attrs: { href } })])), { attrs: { id } }),
  ]);
};

const headerKomponente = {
  key: 'SiteHeader',
  name: 'Header',
  description: 'Kopfzeile mit Logo, Hauptnavigation (Untermenüs Leistungen und Websites), Telefon, Button „Erstgespräch“ und Umschalter Hell/Dunkel. Mobil als ausklappbares Menü. Daten: Firmendaten (Logo, Telefon); Skript snippets/kbs-navigation.php.',
  properties: [],
  content: el('header', 'site-header', [
    t('a', 'skip-link', 'Zum Inhalt springen', { attrs: { href: '#main' } }),
    el('div', 'site-header__inner container', [
      logo('header'),
      el('nav', 'main-nav', [
        el('button', 'main-nav__toggle', [icon('menu'), t('span', 'main-nav__toggle-text', 'Menü')], {
          attrs: { type: 'button', 'aria-expanded': 'false', 'aria-controls': 'hauptmenue', 'data-nav-toggle': '' },
        }),
        el('ul', 'main-nav__list', [
          ...navigation.map(navPunkt),
          el('li', 'main-nav__item main-nav__item--phone', [
            el('a', 'main-nav__phone', [icon('phone'), text(firma('telefon'))], { attrs: { href: firma('telefon_href') } }),
          ]),
          el('li', 'main-nav__item main-nav__item--cta', [t('a', 'btn--primary btn--s', 'Erstgespräch vereinbaren', { attrs: { href: '/kontakt/' } })]),
        ], { attrs: { id: 'hauptmenue' } }),
      ], { attrs: { 'aria-label': 'Hauptnavigation' } }),
      // Hell/Dunkel: Wahl im Browser gespeichert, ohne Wahl folgt die Seite dem Gerät (snippets/kbs-navigation.php)
      el('button', 'scheme-toggle', [t('span', 'visually-hidden', 'Dunkles Design'), icon('moon', 'scheme-toggle__moon'), icon('sun', 'scheme-toggle__sun')], {
        attrs: { type: 'button', 'aria-pressed': 'false', 'data-scheme-toggle': '' },
      }),
    ]),
  ], { name: 'Header' }),
};

const footerListe = (titel, links) =>
  el('nav', 'site-footer__col', [
    t('h2', 'site-footer__title', titel),
    el('ul', 'site-footer__list', links.map(([label, href]) => el('li', '', [t('a', 'site-footer__link', label, { attrs: { href } })]))),
  ], { attrs: { 'aria-label': titel } });

const footerKomponente = {
  key: 'SiteFooter',
  name: 'Footer',
  description: 'Seitenfuß mit Logo, Kurzbeschreibung, Adresse, Kontakt, Leistungen, Unternehmen und Fernwartungs-Downloads; darunter Copyright, Impressum und Datenschutz. Daten: Firmendaten.',
  properties: [],
  content: el('footer', 'site-footer', [
    el('div', 'site-footer__inner container', [
      el('div', 'site-footer__col site-footer__col--brand', [
        logo('footer'),
        t('p', 'site-footer__claim', firma('claim')),
        el('address', 'site-footer__address', [
          t('span', 'site-footer__line', firma('name')),
          t('span', 'site-footer__line', firma('strasse')),
          t('span', 'site-footer__line', `${firma('plz')} ${firma('ort')}`),
        ]),
        el('p', 'site-footer__contact', [
          el('a', 'site-footer__link site-footer__line', [icon('phone'), text(firma('telefon'))], { attrs: { href: firma('telefon_href') } }),
          el('a', 'site-footer__link site-footer__line', [icon('mail'), text(firma('email'))], { attrs: { href: `mailto:${firma('email')}` } }),
        ]),
      ]),
      footerListe('Leistungen', leistungen.map((l) => [l.titel, `/leistungen/${l.slug}/`])),
      footerListe('Unternehmen', [['Über uns', '/ueber-uns/'], ['Kontakt', '/kontakt/'], ['Fernwartung', '/fernwartung/']]),
      el('div', 'site-footer__col', [
        el('h2', 'site-footer__title site-footer__title--logo', [el('img', 'site-footer__logo', [], { attrs: { src: '{options.kbs.pcvisit.logo}', alt: '', width: '24', height: '24' } }), text('Fernwartung')]),
        t('p', 'site-footer__text', 'Nach telefonischer Absprache:'),
        el('ul', 'site-footer__list', [
          wenn('options.kbs.pcvisit.hat_kunden_url', [el('li', '', [el('a', 'site-footer__link', [icon('download'), text('PC-Visit Quick Support')], { attrs: { href: '{options.kbs.pcvisit.kunden_url}', rel: 'nofollow' } })])]),
          wenn('options.kbs.pcvisit.hat_host_url', [el('li', '', [el('a', 'site-footer__link', [icon('download'), text('PC-Visit Host')], { attrs: { href: '{options.kbs.pcvisit.host_url}', rel: 'nofollow' } })])]),
        ]),
      ]),
    ]),
    el('div', 'site-footer__bottom', [
      el('div', 'site-footer__bottom-inner container', [
        t('p', 'site-footer__copy', `© ${firma('jahr')} ${firma('name')}`),
        el('ul', 'site-footer__legal', [
          el('li', '', [t('a', 'site-footer__link', 'Impressum', { attrs: { href: '/impressum/' } })]),
          el('li', '', [t('a', 'site-footer__link', 'Datenschutz', { attrs: { href: '/datenschutz/' } })]),
        ]),
      ]),
    ]),
  ], { name: 'Footer' }),
};

const leistungskartenKomponente = {
  key: 'Leistungskarten',
  name: 'Leistungskarten',
  description: 'Alle Leistungen als Karten mit Symbol, Kurztext und drei Punkten, dazu eine Karte „Erstgespräch“. Daten: Repo (wordpress/etch/daten.mjs › leistungen), Text der letzten Karte aus den Firmendaten (Angebot).',
  properties: [],
  content: el('ul', 'service-grid', [
    ...leistungen.map((l) =>
      el('li', 'service-card', [
        el('span', 'service-card__icon', [icon(l.icon)]),
        el('h3', 'service-card__title', [t('a', 'service-card__link', l.titel, { attrs: { href: `/leistungen/${l.slug}/` } })]),
        t('p', 'service-card__text', l.kurz),
        el('ul', 'check-list check-list--compact', l.punkte.map((p) => el('li', 'check-list__item', [icon('check'), text(p)]))),
        el('span', 'service-card__more', [text('Mehr erfahren'), icon('arrow')], { attrs: { 'aria-hidden': 'true' } }),
      ]),
    ),
    el('li', 'service-card service-card--cta', [
      t('p', 'service-card__eyebrow', 'Nicht sicher, was Sie brauchen?'),
      t('h3', 'service-card__title', firma('angebot_titel')),
      t('p', 'service-card__text', firma('angebot_text')),
      t('a', 'btn--primary btn--s', 'Termin anfragen', { attrs: { href: '/kontakt/' } }),
    ]),
  ], { name: 'Leistungskarten' }),
};

const pcvisitKomponente = {
  key: 'PcVisit',
  name: 'PC-Visit Fernwartung',
  description: 'Downloads PC-Visit Quick Support (spontane Hilfe) und PC-Visit Host (dauerhafte Betreuung) mit Hinweis. Karten ohne Link werden ausgeblendet. Mit PC-Visit-Logo zur Wiedererkennung (Firmendaten › PC-Visit › Logo, sonst mitgeliefertes Signet). Daten: Firmendaten › PC-Visit ({options.kbs.pcvisit.…}).',
  properties: [],
  content: el('div', 'remote', [
    el('div', 'remote__cards', [
      wenn('options.kbs.pcvisit.hat_kunden_url', [
        el('article', 'remote-card remote-card--featured', [
          el('div', 'remote-card__head', [el('img', 'remote-card__logo', [], { attrs: { src: '{options.kbs.pcvisit.logo}', alt: '', width: '48', height: '48' } }), el('div', '', [t('p', 'remote-card__badge', 'Spontane Hilfe'), t('h3', 'remote-card__title', 'PC-Visit Quick Support')])]),
          t('p', 'remote-card__text', 'Für die schnelle Hilfe zwischendurch: Programm herunterladen, starten und uns die angezeigte Sitzungsnummer am Telefon nennen.'),
          el('a', 'btn--primary btn--s', [icon('download'), text('Quick Support herunterladen')], { attrs: { href: '{options.kbs.pcvisit.kunden_url}', rel: 'nofollow' } }),
        ]),
      ]),
      wenn('options.kbs.pcvisit.hat_host_url', [
        el('article', 'remote-card', [
          el('div', 'remote-card__head', [el('img', 'remote-card__logo', [], { attrs: { src: '{options.kbs.pcvisit.logo}', alt: '', width: '48', height: '48' } }), el('div', '', [t('p', 'remote-card__badge', 'Dauerhafte Betreuung'), t('h3', 'remote-card__title', 'PC-Visit Host')])]),
          t('p', 'remote-card__text', 'Für Rechner und Server, die wir regelmäßig betreuen. Einmal eingerichtet, können wir nach Absprache helfen, ohne dass jemand am Gerät sitzen muss.'),
          el('a', 'btn--primary btn--outline btn--s', [icon('download'), text('Host herunterladen')], { attrs: { href: '{options.kbs.pcvisit.host_url}', rel: 'nofollow' } }),
        ]),
      ]),
    ]),
    wenn('options.kbs.pcvisit.hat_hinweis', [el('p', 'remote__note', [icon('phone'), text('{options.kbs.pcvisit.hinweis} '), t('a', '', firma('telefon'), { attrs: { href: firma('telefon_href') } })])]),
  ], { name: 'PC-Visit' }),
};

const kontaktdatenKomponente = {
  key: 'Kontaktdaten',
  name: 'Kontaktdaten',
  description: 'Telefon, E-Mail, Anschrift mit Routenlink und Erreichbarkeit (nur wenn gepflegt). Daten: Firmendaten.',
  properties: [],
  content: el('ul', 'contact-list', [
    el('li', 'contact-list__item', [
      el('span', 'contact-list__icon', [icon('phone')]),
      el('span', 'contact-list__body', [t('span', 'contact-list__label', 'Telefon'), t('a', 'contact-list__value', firma('telefon'), { attrs: { href: firma('telefon_href') } })]),
    ]),
    el('li', 'contact-list__item', [
      el('span', 'contact-list__icon', [icon('mail')]),
      el('span', 'contact-list__body', [t('span', 'contact-list__label', 'E-Mail'), t('a', 'contact-list__value', firma('email'), { attrs: { href: `mailto:${firma('email')}` } })]),
    ]),
    el('li', 'contact-list__item', [
      el('span', 'contact-list__icon', [icon('pin')]),
      el('span', 'contact-list__body', [
        t('span', 'contact-list__label', 'Anschrift'),
        t('span', 'contact-list__value', `${firma('strasse')}, ${firma('plz')} ${firma('ort')}`),
        el('a', 'contact-list__link', [text('Route planen'), t('span', 'visually-hidden', ' (öffnet in neuem Fenster)')], { attrs: { href: firma('route_url'), rel: 'nofollow noopener', target: '_blank' } }),
      ]),
    ]),
    wenn(`${FIRMA}.hat_erreichbarkeit`, [
      el('li', 'contact-list__item', [
        el('span', 'contact-list__icon', [icon('clock')]),
        el('span', 'contact-list__body', [t('span', 'contact-list__label', 'Erreichbarkeit'), el('span', 'contact-list__value', [raw(firma('erreichbarkeit'))])]),
      ]),
    ]),
  ], { name: 'Kontaktdaten' }),
};

// Formularfeld mit Label; Pflichtfelder mit sichtbarem Hinweis
const feld = (id, label, attrs = {}, { tag = 'input', pflicht = false, kinder = [], breit = false } = {}) =>
  el('div', `form-field${breit ? ' form-field--wide' : ''}`, [
    el('label', 'form-field__label', [text(label), pflicht ? t('span', 'form-field__required', ' (Pflichtfeld)') : null], { attrs: { for: `kf-${id}` } }),
    el(tag, `form-field__control${tag === 'textarea' ? ' form-field__control--area' : ''}`, kinder, {
      attrs: { id: `kf-${id}`, name: id, ...(pflicht ? { required: '' } : {}), ...attrs },
    }),
  ]);

const anliegen = ['IT-Betreuung', 'IT-Beratung', 'Neue Website', 'Website-Wartung', 'Fernwartung', 'Sonstiges'];

const kontaktformularKomponente = {
  key: 'Kontaktformular',
  name: 'Kontaktformular',
  description: 'Anfrageformular (Name, Firma, E-Mail, Telefon, Anliegen, Nachricht, Datenschutz-Zustimmung) mit Spam-Schutz ohne Cookies. Abschaltbar unter Firmendaten › Kontaktformular (dann Hinweis mit Telefon und E-Mail). Daten: snippets/kbs-kontakt.php ({options.kbs.kontakt.…}); Empfänger aus den Firmendaten.',
  properties: [],
  content: el('div', 'contact-form', [
    // Ausgeschaltet (Firmendaten › Kontaktformular): Hinweis mit Telefon und E-Mail statt Formular
    wenn(`${KONTAKT}.formular_aktiv`, [
      el('div', 'form-message', [
        t('p', 'form-message__title', 'Das Kontaktformular ist gerade nicht verfügbar.'),
        el('p', '', [
          text('Rufen Sie uns gern an unter '),
          t('a', '', firma('telefon'), { attrs: { href: firma('telefon_href') } }),
          text(' oder schreiben Sie an '),
          t('a', '', firma('email'), { attrs: { href: `mailto:${firma('email')}` } }),
          text('.'),
        ]),
      ], { attrs: { id: 'formular-meldung' } }),
    ], 'isFalsy'),
    wenn(`${KONTAKT}.formular_aktiv`, [
    wenn(`${KONTAKT}.gesendet`, [
      el('div', 'form-message form-message--success', [t('p', 'form-message__title', 'Vielen Dank für Ihre Anfrage!'), t('p', '', 'Wir melden uns in der Regel innerhalb eines Werktags bei Ihnen.')], { attrs: { role: 'status', id: 'formular-meldung' } }),
    ]),
    wenn(`${KONTAKT}.hat_fehler`, [
      el('div', 'form-message form-message--error', [t('p', 'form-message__title', 'Die Nachricht wurde nicht gesendet.'), t('p', '', `{${KONTAKT}.fehler}`)], { attrs: { role: 'alert', id: 'formular-meldung' } }),
    ]),
    el('form', 'contact-form__form', [
      el('input', '', [], { attrs: { type: 'hidden', name: 'action', value: 'kbs_kontakt' } }),
      el('input', '', [], { attrs: { type: 'hidden', name: 'kbs_zeit', value: `{${KONTAKT}.zeit}` } }),
      el('input', '', [], { attrs: { type: 'hidden', name: 'kbs_zurueck', value: '{this.permalink.relative}' } }),
      // Falle für Bots: für Menschen unsichtbar, per Tab nicht erreichbar
      el('div', 'contact-form__trap', [
        t('label', '', 'Bitte dieses Feld leer lassen', { attrs: { for: 'kf-website' } }),
        el('input', '', [], { attrs: { id: 'kf-website', type: 'text', name: 'website', tabindex: '-1', autocomplete: 'off' } }),
      ], { attrs: { 'aria-hidden': 'true' } }),
      el('div', 'contact-form__grid', [
        feld('name', 'Ihr Name', { type: 'text', autocomplete: 'name' }, { pflicht: true }),
        feld('firma', 'Firma', { type: 'text', autocomplete: 'organization' }),
        feld('email', 'E-Mail', { type: 'email', autocomplete: 'email' }, { pflicht: true }),
        feld('telefon', 'Telefon', { type: 'tel', autocomplete: 'tel' }),
        feld('anliegen', 'Worum geht es?', {}, { tag: 'select', breit: true, kinder: anliegen.map((a) => t('option', '', a, { attrs: { value: a } })) }),
        feld('nachricht', 'Ihre Nachricht', { rows: '6' }, { tag: 'textarea', pflicht: true, breit: true }),
      ]),
      el('div', 'form-check', [
        el('input', 'form-check__input', [], { attrs: { id: 'kf-datenschutz', type: 'checkbox', name: 'datenschutz', value: '1', required: '' } }),
        el('label', 'form-check__label', [
          text('Ich bin einverstanden, dass meine Angaben zur Bearbeitung der Anfrage verwendet werden. Mehr dazu in der '),
          t('a', '', 'Datenschutzerklärung', { attrs: { href: '/datenschutz/#kontakt' } }),
          text('. (Pflichtfeld)'),
        ], { attrs: { for: 'kf-datenschutz' } }),
      ]),
      el('button', 'btn--primary contact-form__submit', [text('Anfrage senden'), icon('arrow')], { attrs: { type: 'submit' } }),
    ], { attrs: { method: 'post', action: `{${KONTAKT}.action}`, id: 'formular' } }),
    ]),
  ], { name: 'Kontaktformular' }),
};

const ctaKomponente = {
  key: 'CtaBand',
  name: 'Aufruf Erstgespräch',
  description: 'Dunkles Panel mit Aufruf zum kostenlosen Erstgespräch: Überschrift (Eigenschaft titel), Angebotstext, Buttons Kontakt und Telefon. Daten: Firmendaten (Angebot, Telefon).',
  properties: [{ key: 'titel', name: 'Überschrift', type: { primitive: 'string' }, default: 'Lassen Sie uns über Ihre IT oder Ihre Website sprechen.' }],
  content: el('section', 'cta-band', [
    el('div', 'cta-band__inner container', [
      el('div', 'cta-band__text', [
        t('p', 'cta-band__eyebrow', firma('angebot_titel')),
        t('h2', 'cta-band__title', '{props.titel}'),
        t('p', 'cta-band__lead', firma('angebot_text')),
      ]),
      el('div', 'cta-band__actions', [
        t('a', 'btn--primary', 'Erstgespräch vereinbaren', { attrs: { href: '/kontakt/' } }),
        el('a', 'btn--primary-light btn--outline', [icon('phone'), text(firma('telefon'))], { attrs: { href: firma('telefon_href') } }),
      ]),
    ]),
  ], { attrs: { 'aria-label': 'Erstgespräch vereinbaren' }, name: 'Aufruf Erstgespräch' }),
};

// Reihenfolge: Komponenten, die in anderen eingebunden werden, zuerst (Sync ersetzt __REF_<key>__ der Reihe nach).
export const components = [headerKomponente, footerKomponente, leistungskartenKomponente, pcvisitKomponente, kontaktdatenKomponente, kontaktformularKomponente, ctaKomponente];

export { komponente };
