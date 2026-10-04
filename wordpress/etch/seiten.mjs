// Seiten (page) und Etch-Templates der Website.
// Erzeugen: node wordpress/etch/build.mjs → wordpress/etch/dist/
// Firmenangaben nur über firma('<feld>') bzw. {options.kbs.firma.…}, nie als Text.

import { el, t, text, raw, wenn, markup, komponente, ome, firma, FIRMA, icon, postContent, kiPlakette } from './lib.mjs';
import { leistungen, zielgruppen } from './daten.mjs';

// ---------- Bausteine ----------

// Seitenkopf aller Unterseiten. Wasserzeichen: standardmäßig das Firmenlogo (der Kopf ist hell und folgt Hell/Dunkel,
// deshalb beide Logo-Varianten, CSS zeigt die passende), auf der Fernwartungsseite das PC-Visit-Signet (wasserzeichen: 'pcvisit'). Rein dekorativ.
const WASSERZEICHEN = {
  logo: { src: firma('logo'), dunkel: firma('logo_dunkel'), breite: '560', hoehe: '480' },
  pcvisit: { src: '{options.kbs.pcvisit.logo}', breite: '480', hoehe: '480' },
};
const wasserzeichenBild = (art) => {
  const w = WASSERZEICHEN[art];
  const img = (src, mod = '') => el('img', `page-hero__watermark page-hero__watermark--${art}${mod}`, [], { attrs: { src, alt: '', 'aria-hidden': 'true', width: w.breite, height: w.hoehe } });
  if (!w.dunkel) return img(w.src);
  return [img(w.src, ' scheme-bild--hell'), img(w.dunkel, ' scheme-bild--dunkel')];
};
const seitenkopf = ({ eyebrow, titel, lead, aktionen, wasserzeichen = 'logo' }) =>
  el('section', 'page-hero page-hero--watermark', [
    wasserzeichenBild(wasserzeichen),
    el('div', 'page-hero__inner container', [
      // Brotkrumen von OhMyEtch: Pfad automatisch aus der Seitenhierarchie, mit BreadcrumbList-Schema für Google
      ome('OmeBreadcrumbs', { content: { homeLabel: 'Start', separator: '/', ariaLabel: 'Brotkrumen' } }, {}, 'Brotkrumen'),
      eyebrow && t('p', 'page-hero__eyebrow', eyebrow),
      t('h1', 'page-hero__title', titel),
      lead && t('p', 'page-hero__lead', lead),
      aktionen && el('div', 'page-hero__actions', aktionen),
    ]),
  ], { name: 'Seitenkopf' });

const abschnittKopf = (eyebrow, titel, lead, mitte = false) =>
  el('header', `section-head${mitte ? ' section-head--center' : ''}`, [
    eyebrow && t('p', 'section-head__eyebrow', eyebrow),
    t('h2', 'section-head__title', titel),
    lead && t('p', 'section-head__lead', lead),
  ]);

const abschnitt = (klasse, name, kinder) => el('section', `section${klasse ? ' ' + klasse : ''}`, [el('div', 'container', kinder)], { name });

const checkListe = (punkte, mod = '') => el('ul', `check-list${mod ? ' check-list--' + mod : ''}`, punkte.map((p) => el('li', 'check-list__item', [icon('check'), text(p)])));

const btnKontakt = (label = 'Erstgespräch vereinbaren', klasse = 'btn--primary') => t('a', klasse, label, { attrs: { href: '/kontakt/' } });
const btnTelefon = (klasse = 'btn--primary btn--outline') => el('a', klasse, [icon('phone'), text(firma('telefon'))], { attrs: { href: firma('telefon_href') } });

// Fließtext mit Zwischenüberschriften: [['h2', 'Titel'], 'Absatz', ['ul', [...]]]
const prosa = (teile) =>
  el('div', 'prose', teile.map((x) => {
    if (typeof x === 'string') return t('p', '', x);
    if (x[0] === 'ul') return checkListe(x[1]);
    return t(x[0], '', x[1]);
  }));

// Kasten neben dem Text: Ansprechpartner und Erstgespräch
const ansprechKasten = (titel = 'Ihr direkter Draht') =>
  el('aside', 'side-card', [
    el('span', 'side-card__icon', [icon('user')]),
    t('h2', 'side-card__title', titel),
    t('p', 'side-card__text', firma('angebot_text')),
    btnKontakt(firma('angebot_titel'), 'btn--primary btn--s'),
    btnTelefon('btn--primary btn--outline btn--s'),
  ], { name: 'Ansprechpartner' });

const schritte = (liste) =>
  el('ol', 'steps', liste.map(([titel, inhalt], i) =>
    el('li', 'steps__item', [t('span', 'steps__number', String(i + 1).padStart(2, '0')), t('h3', 'steps__title', titel), t('p', 'steps__text', inhalt)]),
  ));

// Häufige Fragen: Accordion von OhMyEtch (Tastatur, ARIA, Animation). Kopf mit H3, mehrere Antworten gleichzeitig offen.
const faq = (liste) =>
  ome('OmeAccordion', { settings: { type: 'multiple' } }, {
    default: liste.map(([frage, antwort]) =>
      ome('OmeAccordionItem', {}, {
        default: [
          // Frage als Slot-Inhalt des Triggers (die Eigenschaft content.label kommt bei Etch nicht an)
          ome('OmeAccordionHeader', { structure: { level: '3' } }, { default: [ome('OmeAccordionTrigger', {}, { default: [t('span', 'faq__question', frage), icon('chevron', 'faq__chevron')] })] }),
          ome('OmeAccordionContent', {}, { default: [t('p', '', antwort)] }),
        ],
      }),
    ),
  }, 'Häufige Fragen');

const vorteile = (liste) =>
  el('ul', 'benefit-grid', liste.map(([ic, titel, inhalt]) =>
    el('li', 'benefit', [el('span', 'benefit__icon', [icon(ic)]), t('h3', 'benefit__title', titel), t('p', 'benefit__text', inhalt)]),
  ));

// ---------- Startseite ----------

// Hero-Hintergrund: KI-Bild (wordpress/medien/bilder/, Kennzeichnung über KBS_KI_DATEIEN in kbs-ki.php), je Farbschema eine Variante.
const BILDER = '/wp-content/kbs/medien/bilder';
const KI_HERO = 'options.kbs.ki_bilder.hero_netzwerk';
const heroBild = (variante) => {
  const srcset = (typ) => [640, 1280, 1344].map((b) => `${BILDER}/hero-netzwerk-${variante}-${b}.${typ} ${b}w`).join(', ');
  return el('picture', `home-hero__bild scheme-bild--${variante}`, [
    el('source', '', [], { attrs: { type: 'image/avif', srcset: srcset('avif'), sizes: '100vw' } }),
    el('source', '', [], { attrs: { type: 'image/webp', srcset: srcset('webp'), sizes: '100vw' } }),
    el('img', '', [], { attrs: { src: `${BILDER}/hero-netzwerk-${variante}-1280.webp`, alt: `{${KI_HERO}.bild_alt}`, width: '1344', height: '768', fetchpriority: 'high', decoding: 'async' } }),
  ]);
};

// Häufige Fragen der Startseite (auch für das FAQ-Schema und llms.txt, siehe seo unten)
const startFaq = [
  ['Was kostet die IT-Betreuung?', 'Das hängt von der Zahl der Arbeitsplätze und Ihren Anforderungen ab. Nach dem kostenlosen Erstgespräch erhalten Sie ein klares Angebot – Sie wissen vorher, womit Sie rechnen können.'],
  ['Wie sicher ist die Fernwartung?', 'Eine Verbindung entsteht nur, wenn Sie das Programm selbst starten und uns die Sitzungsnummer nennen. Sie sehen alles mit und können die Sitzung jederzeit beenden.'],
  ['Kann ich meine Website später selbst ändern?', 'Ja. Wir richten die Website so ein, dass Sie Texte, Bilder, Preise und Angebote ohne Programmierkenntnisse selbst pflegen können – auf Wunsch mit kurzer Einweisung.'],
  ['Übernehmen Sie auch bestehende Systeme und Websites?', 'Ja. Wir verschaffen uns zuerst einen Überblick und sagen Ihnen offen, was bleiben kann und wo Handlungsbedarf besteht.'],
];

const startseite = markup(
  el('section', 'home-hero', [
    heroBild('hell'),
    heroBild('dunkel'),
    kiPlakette(KI_HERO),
    el('div', 'home-hero__inner container', [
      el('div', 'home-hero__content', [
        t('p', 'home-hero__eyebrow', `IT-Service und Webdesign aus ${firma('ort')}`),
        t('h1', 'home-hero__title', 'Ihre IT-Abteilung – ohne eigene IT-Abteilung.'),
        t('p', 'home-hero__lead', `Wir betreuen kleine Unternehmen in der Region ${firma('region')}: Computer, Netzwerk und Server ebenso wie moderne WordPress-Websites. Persönlich vor Ort und schnell per Fernwartung.`),
        el('div', 'home-hero__actions', [btnKontakt(firma('angebot_titel')), t('a', 'btn--primary btn--outline', 'Fernwartung starten', { attrs: { href: '/fernwartung/' } })]),
        checkListe(['Fester Ansprechpartner', 'Hilfe per Fernwartung ohne Anfahrtskosten', `Vor Ort in ${firma('ort')} und Umgebung`], 'hero'),
      ]),
      el('aside', 'hero-card', [
        t('p', 'hero-card__eyebrow', 'Akut ein Problem?'),
        t('h2', 'hero-card__title', 'Rufen Sie uns an.'),
        el('a', 'hero-card__phone', [icon('phone'), text(firma('telefon'))], { attrs: { href: firma('telefon_href') } }),
        t('p', 'hero-card__text', 'Mit PC-Visit schauen wir uns Ihren Rechner direkt aus der Ferne an – meist ist das Problem gelöst, bevor ein Techniker losfahren müsste.'),
        el('a', 'link-arrow hero-card__link', [text('So funktioniert die Fernwartung'), icon('arrow')], { attrs: { href: '/fernwartung/' } }),
      ], { attrs: { 'aria-label': 'Schnelle Hilfe' }, name: 'Schnelle Hilfe' }),
    ]),
  ], { name: 'Hero' }),

  abschnitt('', 'Leistungen', [
    abschnittKopf('Leistungen', 'Alles aus einer Hand', 'Von der Einrichtung neuer Arbeitsplätze bis zur Website, die neue Kunden bringt. Sie haben einen Ansprechpartner für alles, was mit IT zu tun hat.', true),
    komponente('Leistungskarten'),
  ]),

  abschnitt('section--tint', 'So arbeiten wir', [
    abschnittKopf('So arbeiten wir', 'In drei Schritten zu IT, die einfach läuft', null, true),
    schritte([
      ['Kennenlernen', 'Im kostenlosen Erstgespräch hören wir zu: Was läuft gut, was nervt, was ist geplant? Gerne direkt bei Ihnen vor Ort.'],
      ['Klarer Vorschlag', 'Sie bekommen eine verständliche Empfehlung mit transparenten Kosten – ohne Fachchinesisch und ohne Kleingedrucktes.'],
      ['Umsetzung und Betreuung', 'Wir richten alles ein und bleiben Ihr fester Ansprechpartner. Bei Fragen genügt ein Anruf.'],
    ]),
  ]),

  abschnitt('', 'Fernwartung', [
    el('div', 'split split--center', [
      el('div', '', [
        abschnittKopf('Fernwartung', 'Hilfe in Minuten – ohne Anfahrt', null),
        t('p', 'lead', 'Drucker streikt, E-Mail geht nicht, Programm hängt? Mit PC-Visit sehen wir Ihren Bildschirm, sobald Sie es erlauben, und lösen das Problem direkt.'),
        checkListe(['Verbindung nur, wenn Sie sie selbst starten', 'Sie sehen jederzeit, was wir tun', 'Keine Fahrtkosten, kein Warten auf einen Termin']),
        el('p', 'more-link', [el('a', 'link-arrow', [text('Mehr zur Fernwartung'), icon('arrow')], { attrs: { href: '/fernwartung/' } })]),
      ]),
      komponente('PcVisit'),
    ]),
  ]),

  abschnitt('section--tint', 'Websites', [
    el('div', 'split split--center split--reverse', [
      el('div', 'browser-mock', [
        el('div', 'browser-mock__bar', [el('span', 'browser-mock__dot'), el('span', 'browser-mock__dot'), el('span', 'browser-mock__dot'), t('span', 'browser-mock__url', 'www.ihre-firma.de')], { attrs: { 'aria-hidden': 'true' } }),
        el('div', 'browser-mock__body', [
          el('span', 'browser-mock__line browser-mock__line--title'),
          el('span', 'browser-mock__line'),
          el('span', 'browser-mock__line browser-mock__line--short'),
          el('span', 'browser-mock__button'),
          el('div', 'browser-mock__cards', [el('span', 'browser-mock__card'), el('span', 'browser-mock__card'), el('span', 'browser-mock__card')]),
        ], { attrs: { 'aria-hidden': 'true' } }),
      ], { name: 'Website-Illustration' }),
      el('div', '', [
        abschnittKopf('WordPress-Websites', 'Eine Website, die für Sie arbeitet', null),
        t('p', 'lead', 'Wir bauen Websites mit WordPress und aktuellen Buildern wie Etch: schnell, modern und so aufgebaut, dass Interessenten zu Anfragen werden.'),
        checkListe([
          'Passt sich automatisch an Smartphone, Tablet und PC an',
          'Hell- oder Dunkelmodus nach Wunsch des Besuchers',
          'Schnelle Ladezeiten und saubere Grundlage für Google',
          'Texte, Preise und Angebote pflegen Sie selbst',
        ]),
        el('div', 'button-row', [t('a', 'btn--primary btn--s', 'Website-Projekt besprechen', { attrs: { href: '/kontakt/' } }), el('a', 'link-arrow', [text('Mehr zu WordPress-Websites'), icon('arrow')], { attrs: { href: '/leistungen/wordpress-websites/' } })]),
      ]),
    ]),
  ]),

  abschnitt('', 'Für wen', [
    abschnittKopf('Für wen', 'Ideal für Unternehmen ohne eigene IT-Abteilung', `Wir arbeiten für kleine Firmen, Selbstständige und Vereine in der Region ${firma('region')}.`, true),
    el('ul', 'chip-list', zielgruppen.map((z) => t('li', 'chip', z))),
  ]),

  abschnitt('section--tint', 'Häufige Fragen', [
    el('div', 'split', [
      abschnittKopf('Häufige Fragen', 'Gut zu wissen', 'Ihre Frage ist nicht dabei? Rufen Sie uns einfach an.'),
      faq(startFaq),
    ]),
  ]),

  komponente('CtaBand', { titel: 'Lassen Sie uns über Ihre IT sprechen.' }),
);

// ---------- Leistungsseiten ----------

const leistungInhalte = {
  'it-betreuung': {
    eyebrow: 'IT-Betreuung',
    titel: 'IT-Betreuung für kleine Unternehmen',
    absaetze: [
      ['h2', 'Wir sind Ihre IT-Abteilung'],
      'Viele kleine Firmen haben niemanden, der sich hauptberuflich um die IT kümmert. Genau dafür sind wir da: Wir betreuen Ihre Arbeitsplätze, Server, Drucker und Ihr Netzwerk und sorgen dafür, dass alles zuverlässig läuft.',
      'Statt erst zu reagieren, wenn etwas kaputt ist, halten wir Ihre Systeme aktuell und erkennen Probleme früh. Das spart Ausfallzeiten – und Nerven.',
      ['h2', 'Das übernehmen wir für Sie'],
      ['ul', [
        'Einrichtung und Wartung von PCs, Notebooks, Servern und Druckern',
        'Netzwerk, WLAN und Internetzugang',
        'Updates für Betriebssystem und Programme',
        'Virenschutz und Firewall',
        'Datensicherung und Wiederherstellung im Notfall',
        'Hilfe bei Störungen per Telefon, Fernwartung oder vor Ort',
        'Dokumentation Ihrer IT: Geräte, Zugänge, Einstellungen',
      ]],
      ['h2', 'Datensicherung, die im Ernstfall funktioniert'],
      'Ein Festplattendefekt oder ein Verschlüsselungstrojaner kann jede Firma treffen. Wir richten eine Datensicherung ein, die regelmäßig läuft und geprüft wird – damit Ihre Daten im Ernstfall schnell wieder da sind.',
    ],
    faqTitel: 'Fragen zur IT-Betreuung',
    faq: [
      ['Für wie viele Arbeitsplätze lohnt sich das?', 'Schon ab einem Arbeitsplatz. Gerade Selbstständige und kleine Teams profitieren davon, einen festen Ansprechpartner zu haben.'],
      ['Wie schnell helfen Sie bei Störungen?', 'Viele Störungen lösen wir noch am selben Tag per Fernwartung. Wenn ein Termin vor Ort nötig ist, stimmen wir ihn kurzfristig mit Ihnen ab.'],
    ],
  },
  'it-beratung': {
    eyebrow: 'IT-Beratung',
    titel: 'IT-Beratung, die man versteht',
    absaetze: [
      ['h2', 'Die richtige Entscheidung treffen'],
      'Neue Rechner, ein Server oder doch die Cloud? Welche Software passt, und was muss beim Datenschutz beachtet werden? Wir beraten Sie verständlich und passend zur Größe und zum Budget Ihres Unternehmens.',
      ['h2', 'So gehen wir vor'],
      ['ul', [
        'Bestandsaufnahme: Welche Geräte, Programme und Abläufe gibt es?',
        'Schwachstellen und Chancen: Wo hakt es, wo lässt sich Zeit sparen?',
        'Empfehlung mit Kosten und Zeitplan',
        'Beschaffung, Einrichtung und Umzug der Daten',
        'Einweisung Ihrer Mitarbeiterinnen und Mitarbeiter',
      ]],
      ['h2', 'Sicherheit von Anfang an'],
      'Ob Passwörter, Zugriffsrechte oder Datensicherung: Wir achten bei jeder Empfehlung darauf, dass Ihre Daten geschützt sind und die Lösung auch in ein paar Jahren noch zu Ihnen passt.',
    ],
    faqTitel: 'Fragen zur IT-Beratung',
    faq: [
      ['Verkaufen Sie auch Hardware?', 'Wir beschaffen auf Wunsch die passenden Geräte und richten sie fertig ein. Sie können aber auch selbst kaufen – wir beraten trotzdem.'],
      ['Was kostet eine Beratung?', 'Das Erstgespräch ist kostenlos. Danach erhalten Sie ein Angebot für die Bestandsaufnahme bzw. das konkrete Projekt.'],
    ],
  },
  'wordpress-websites': {
    eyebrow: 'WordPress-Websites',
    titel: 'Websites, die neue Kunden bringen',
    absaetze: [
      ['h2', 'Modern gebaut mit aktuellen Buildern'],
      'Wir entwickeln Websites mit WordPress und modernen Werkzeugen wie dem Builder Etch und dem Design-System Automatic.css. Das Ergebnis: sauberer Code, schnelle Ladezeiten und ein Design, das auf jedem Gerät gut aussieht.',
      'Ihre Website passt sich automatisch dem Hell- oder Dunkelmodus an, den Ihre Besucher eingestellt haben, und erfüllt die Anforderungen an Barrierefreiheit.',
      ['h2', 'Auf Anfragen ausgelegt'],
      'Eine gute Website ist mehr als eine Visitenkarte. Wir führen Ihre Besucher mit klaren Botschaften, Ansprechpartnern und Kontaktmöglichkeiten zur Anfrage – und legen die Grundlage dafür, dass Sie bei Google gefunden werden.',
      ['h2', 'Selbst pflegen, ohne Programmierkenntnisse'],
      'Was sich oft ändert – Preise, Angebote, Öffnungszeiten, Team – pflegen Sie bequem im Backend. Wir richten die Felder so ein, dass nichts kaputtgehen kann.',
      ['h2', 'Das gehört dazu'],
      ['ul', [
        'Konzept, Texte und Struktur gemeinsam mit Ihnen',
        'Individuelles Design in Ihren Farben',
        'Optimiert für Smartphone, Tablet und PC',
        'Kontaktformular, Impressum und Datenschutzerklärung',
        'Grundlagen für Suchmaschinen (SEO)',
        'Einweisung, damit Sie Inhalte selbst ändern können',
        'Auf Wunsch Updates und Backups',
      ]],
    ],
    faqTitel: 'Fragen zu WordPress-Websites',
    faq: [
      ['Wie lange dauert eine neue Website?', 'Das hängt vom Umfang ab. Eine typische Firmen-Website mit wenigen Seiten ist oft in wenigen Wochen fertig – vorausgesetzt, Texte und Bilder liegen vor. Den Zeitplan legen wir gemeinsam fest.'],
      ['Können Sie meine bestehende Website modernisieren?', 'Ja. Wir prüfen Ihre bestehende Seite und empfehlen, ob sich eine Überarbeitung lohnt oder ein Neuaufbau sinnvoller ist.'],
      ['Wer kümmert sich um Updates?', 'Auf Wunsch wir: Wir halten WordPress und alle Erweiterungen aktuell und sichern Ihre Website regelmäßig.'],
    ],
  },
};

const leistungSeite = (l) => {
  const inhalt = leistungInhalte[l.slug];
  return markup(
    seitenkopf({ eyebrow: inhalt.eyebrow, titel: inhalt.titel, lead: l.kurz, aktionen: [btnKontakt(firma('angebot_titel'), 'btn--primary')] }),
    abschnitt('', 'Inhalt', [el('div', 'split split--wide-left', [prosa(inhalt.absaetze), ansprechKasten()])]),
    abschnitt('section--tint', 'Häufige Fragen', [el('div', 'split', [abschnittKopf('Häufige Fragen', inhalt.faqTitel, null), faq(inhalt.faq)])]),
    abschnitt('', 'Weitere Leistungen', [abschnittKopf('Weitere Leistungen', 'Das könnte Sie auch interessieren', null), komponente('Leistungskarten')]),
    komponente('CtaBand', { titel: 'Klingt passend? Lassen Sie uns sprechen.' }),
  );
};

// ---------- Weitere Seiten ----------

const leistungenSeite = markup(
  seitenkopf({ eyebrow: 'Leistungen', titel: 'IT und Web aus einer Hand', lead: 'Wir betreuen kleine Unternehmen, die keine eigene IT-Abteilung haben – von der Hardware bis zur Website.' }),
  abschnitt('', 'Leistungen', [t('h2', 'visually-hidden', 'Unsere Leistungen'), komponente('Leistungskarten')]),
  abschnitt('section--tint', 'Vorteile', [
    abschnittKopf('Ihre Vorteile', `Warum ${firma('kurzname')}?`, null, true),
    vorteile([
      ['user', 'Ein Ansprechpartner', 'Sie erklären Ihr Anliegen einmal – und nicht jedes Mal einer neuen Hotline.'],
      ['remote', 'Schnelle Hilfe', 'Per Fernwartung sind wir in Minuten an Ihrem Rechner, ohne Anfahrt.'],
      ['pin', 'Aus der Region', `Wir sind in ${firma('ort')} zu Hause und kommen bei Bedarf vorbei.`],
      ['shield', 'Sicherheit im Blick', 'Updates, Virenschutz und Datensicherung sind bei uns Standard, nicht Extra.'],
    ]),
  ]),
  komponente('CtaBand', { titel: 'Welche Leistung passt zu Ihnen?' }),
);

const fernwartungSeite = markup(
  seitenkopf({ eyebrow: 'Fernwartung mit PC-Visit', titel: 'Schnelle Hilfe per Fernwartung', lead: 'Wir schauen uns Ihren Rechner direkt aus der Ferne an – sicher, schnell und ohne Anfahrtskosten.', aktionen: [btnTelefon('btn--primary')], wasserzeichen: 'pcvisit' }),
  abschnitt('', 'Downloads', [abschnittKopf('Downloads', 'Programm wählen', 'Für die spontane Hilfe genügt der Quick Support. Den Host installieren wir gemeinsam mit Ihnen auf Geräten, die wir regelmäßig betreuen.'), komponente('PcVisit')]),
  abschnitt('section--tint', 'Ablauf', [
    abschnittKopf('Ablauf', 'So einfach geht es', null, true),
    schritte([
      ['Anrufen', 'Sie rufen uns an und schildern kurz das Problem.'],
      ['Programm starten', 'Sie laden PC-Visit Quick Support herunter und starten es.'],
      ['Nummer durchgeben', 'Sie nennen uns die angezeigte Sitzungsnummer. Erst dann entsteht die Verbindung.'],
      ['Wir helfen', 'Sie sehen auf Ihrem Bildschirm, was wir tun, und können die Sitzung jederzeit beenden.'],
    ]),
  ]),
  abschnitt('', 'Sicherheit', [
    el('div', 'split split--center', [
      el('div', '', [
        abschnittKopf('Sicherheit', 'Sie behalten die Kontrolle', null),
        checkListe([
          'Die Verbindung kommt nur zustande, wenn Sie sie selbst starten',
          'Sie sehen jederzeit, was auf Ihrem Bildschirm passiert',
          'Mit einem Klick beenden Sie die Sitzung',
          'Nach der Sitzung bleibt beim Quick Support kein Zugang bestehen',
        ]),
      ]),
      el('aside', 'side-card', [
        el('span', 'side-card__icon', [icon('remote')]),
        t('h2', 'side-card__title', 'Noch nicht Kunde?'),
        t('p', 'side-card__text', 'Auch ohne Betreuungsvertrag helfen wir Ihnen gern per Fernwartung. Rufen Sie uns an, wir besprechen vorher den Aufwand.'),
        btnTelefon('btn--primary btn--s'),
      ]),
    ]),
  ]),
);

const ueberUnsSeite = markup(
  seitenkopf({ eyebrow: 'Über uns', titel: 'Persönlich. Verständlich. Verlässlich.', lead: `${firma('name')} – Ihr IT-Partner aus ${firma('ort')}.` }),
  abschnitt('', 'Wer wir sind', [
    el('div', 'split split--wide-left', [
      prosa([
        ['h2', 'IT-Service für Unternehmen ohne IT-Abteilung'],
        'Wir betreuen vor allem kleine Firmen, Selbstständige und Vereine, die keine eigene IT-Abteilung haben – und auch keine brauchen sollen. Wir kümmern uns um Computer, Netzwerk und Server und bauen moderne WordPress-Websites.',
        'Unser Anspruch: Technik so zu erklären, dass Sie sie verstehen, und Lösungen zu finden, die zu Ihrem Unternehmen passen – nicht die teuerste, sondern die richtige.',
        ['h2', 'Aus der Region, für die Region'],
        `Unser Sitz ist in ${firma('ort')}. Wir betreuen Kunden in der Region ${firma('region')} – vor Ort, wenn es nötig ist, und per Fernwartung, wenn es schneller geht.`,
        `Zu unseren Kunden gehören Unternehmen in ${firma('einsatzorte')}.`,
      ]),
      el('aside', 'side-card', [
        el('span', 'side-card__icon', [icon('user')]),
        t('h2', 'side-card__title', firma('vertretung')),
        t('p', 'side-card__text', `Geschäftsführer der ${firma('name')}`),
        btnKontakt('Kontakt aufnehmen', 'btn--primary btn--s'),
        btnTelefon('btn--primary btn--outline btn--s'),
      ], { name: 'Geschäftsführung' }),
    ]),
  ]),
  abschnitt('section--tint', 'Werte', [
    abschnittKopf('Unsere Werte', 'Worauf Sie sich verlassen können', null, true),
    vorteile([
      ['chat', 'Klartext', 'Wir sprechen Ihre Sprache und erklären, was wir tun und warum.'],
      ['check', 'Ehrliche Empfehlungen', 'Wir empfehlen nur, was Ihnen wirklich nützt.'],
      ['clock', 'Erreichbar', 'Kurze Wege, schnelle Rückmeldung und ein fester Ansprechpartner.'],
      ['shield', 'Sorgfalt', 'Sicherheit, Datensicherung und Datenschutz gehören immer dazu.'],
    ]),
  ]),
  komponente('CtaBand', { titel: 'Lernen wir uns kennen.' }),
);

const kontaktSeite = markup(
  seitenkopf({ eyebrow: 'Kontakt', titel: 'Wir freuen uns auf Ihre Anfrage', lead: 'Schreiben Sie uns, worum es geht – wir melden uns schnell. Für dringende Fälle rufen Sie am besten direkt an.' }),
  abschnitt('', 'Kontakt', [
    el('div', 'split split--wide-left', [
      el('div', 'panel', [t('h2', 'panel__title', 'Anfrage senden'), komponente('Kontaktformular')]),
      el('div', 'column-stack', [
        el('div', 'panel', [t('h2', 'panel__title', 'So erreichen Sie uns'), komponente('Kontaktdaten')]),
        el('aside', 'side-card', [
          el('span', 'side-card__icon', [icon('remote')]),
          t('h2', 'side-card__title', 'Sofort-Hilfe per Fernwartung'),
          t('p', 'side-card__text', 'Bitte rufen Sie uns an, bevor Sie die Fernwartung starten.'),
          t('a', 'btn--primary btn--outline btn--s', 'Zur Fernwartung', { attrs: { href: '/fernwartung/' } }),
        ]),
      ]),
    ]),
  ]),
);

// ---------- Rechtliches ----------

const angabe = (label, wert) => el('div', 'legal-data__row', [t('dt', 'legal-data__label', label), el('dd', 'legal-data__value', [text(wert)])]);
const wennAngabe = (feld, label, wert) => wenn(`${FIRMA}.hat_${feld}`, [angabe(label, wert)]);

const impressumSeite = markup(
  seitenkopf({ titel: 'Impressum' }),
  abschnitt('', 'Impressum', [
    el('div', 'prose prose--legal', [
      t('h2', '', 'Angaben gemäß § 5 DDG'),
      el('p', 'legal-address', [t('span', 'legal-address__line', firma('name')), t('span', 'legal-address__line', firma('strasse')), t('span', 'legal-address__line', `${firma('plz')} ${firma('ort')}`)]),
      el('dl', 'legal-data', [
        angabe('Vertreten durch den Geschäftsführer', firma('vertretung')),
        angabe('Telefon', firma('telefon')),
        wennAngabe('fax', 'Telefax', firma('fax')),
        angabe('E-Mail', firma('email')),
        wennAngabe('registernummer', 'Registereintrag', `${firma('registergericht')}, ${firma('registernummer')}`),
        wennAngabe('ust_id', 'Umsatzsteuer-Identifikationsnummer gemäß § 27 a UStG', firma('ust_id')),
      ]),
      t('h2', '', 'Verantwortlich für den Inhalt nach § 18 Abs. 2 MStV'),
      t('p', '', firma('verantwortlich')),
      t('h2', '', 'Verbraucherstreitbeilegung'),
      t('p', '', 'Wir sind nicht bereit und nicht verpflichtet, an Streitbeilegungsverfahren vor einer Verbraucherschlichtungsstelle teilzunehmen.'),
      t('h2', '', 'Haftung für Inhalte'),
      t('p', '', 'Die Inhalte dieser Website wurden mit größter Sorgfalt erstellt. Für die Richtigkeit, Vollständigkeit und Aktualität der Inhalte können wir jedoch keine Gewähr übernehmen. Als Diensteanbieter sind wir für eigene Inhalte auf diesen Seiten nach den allgemeinen Gesetzen verantwortlich. Wir sind jedoch nicht verpflichtet, übermittelte oder gespeicherte fremde Informationen zu überwachen. Sobald uns eine Rechtsverletzung bekannt wird, entfernen wir den betreffenden Inhalt umgehend.'),
      t('h2', '', 'Haftung für Links'),
      t('p', '', 'Diese Website enthält Links zu externen Websites Dritter, auf deren Inhalte wir keinen Einfluss haben. Für diese fremden Inhalte ist stets der jeweilige Anbieter oder Betreiber verantwortlich. Zum Zeitpunkt der Verlinkung waren keine Rechtsverstöße erkennbar. Werden uns Rechtsverletzungen bekannt, entfernen wir derartige Links umgehend.'),
      t('h2', '', 'Urheberrecht'),
      t('p', '', 'Die Inhalte und Werke auf diesen Seiten unterliegen dem deutschen Urheberrecht. Vervielfältigung, Bearbeitung, Verbreitung und jede Art der Verwertung außerhalb der Grenzen des Urheberrechts bedürfen der schriftlichen Zustimmung des jeweiligen Autors bzw. Erstellers.'),
    ]),
  ]),
);

const datenschutzSeite = markup(
  seitenkopf({ titel: 'Datenschutzerklärung', lead: 'Wir nehmen den Schutz Ihrer Daten ernst. Hier erfahren Sie, welche Daten wir beim Besuch dieser Website verarbeiten und welche Rechte Sie haben.' }),
  abschnitt('', 'Datenschutzerklärung', [
    el('div', 'legal-layout', [
    // Inhaltsverzeichnis von OhMyEtch, aus den H2 der Erklärung erzeugt
    el('aside', 'legal-layout__toc', [ome('OmeTableOfContents', { content: { showLabel: true, label: 'Inhalt' }, settings: { depth: '2', offset: '96' }, targeting: { targetSelector: '.prose--legal' }, mobile: { enabled: true, breakpoint: '960', initialState: 'collapsed' } }, {}, 'Inhaltsverzeichnis')]),
    el('div', 'prose prose--legal', [
      t('h2', '', '1. Verantwortlicher'),
      t('p', '', 'Verantwortlich für die Datenverarbeitung auf dieser Website im Sinne der Datenschutz-Grundverordnung (DSGVO) ist:'),
      el('p', 'legal-address', [t('span', 'legal-address__line', firma('name')), t('span', 'legal-address__line', firma('strasse')), t('span', 'legal-address__line', `${firma('plz')} ${firma('ort')}`), t('span', 'legal-address__line', `Telefon: ${firma('telefon')}`), t('span', 'legal-address__line', `E-Mail: ${firma('email')}`)]),
      t('p', '', `Vertreten durch den Geschäftsführer ${firma('vertretung')}.`),

      t('h2', '', '2. Allgemeines zur Datenverarbeitung'),
      t('p', '', 'Wir verarbeiten personenbezogene Daten nur, soweit dies für eine funktionsfähige Website, die Beantwortung Ihrer Anfragen oder die Erbringung unserer Leistungen erforderlich ist. Rechtsgrundlagen sind insbesondere Art. 6 Abs. 1 lit. a DSGVO (Einwilligung), lit. b (Vertrag oder vorvertragliche Maßnahmen), lit. c (rechtliche Verpflichtung) und lit. f (berechtigtes Interesse).'),
      t('p', '', 'Personenbezogene Daten werden gelöscht, sobald der Zweck der Speicherung entfällt und keine gesetzlichen Aufbewahrungspflichten entgegenstehen (z. B. handels- und steuerrechtliche Fristen von sechs bzw. zehn Jahren).'),

      t('h2', '', '3. Hosting und Server-Logfiles'),
      wenn(`${FIRMA}.hat_hoster`, [t('p', '', 'Diese Website wird bei folgendem Anbieter gehostet:'), el('p', '', [raw(firma('hoster'))])]),
      wenn(`${FIRMA}.hat_hoster`, [t('p', '', 'Diese Website wird bei einem externen Dienstleister (Hoster) betrieben.')], 'isFalsy'),
      t('p', '', 'Mit dem Hoster besteht ein Vertrag zur Auftragsverarbeitung nach Art. 28 DSGVO. Er verarbeitet Ihre Daten nur nach unseren Weisungen.'),
      t('p', '', 'Beim Aufruf der Website speichert der Server automatisch Informationen in sogenannten Server-Logfiles, die Ihr Browser übermittelt: IP-Adresse, Datum und Uhrzeit des Zugriffs, aufgerufene Seite, Referrer-URL, Browsertyp und -version sowie Betriebssystem. Diese Daten sind technisch erforderlich, um die Website auszuliefern und ihre Sicherheit zu gewährleisten (Art. 6 Abs. 1 lit. f DSGVO). Die Logfiles werden nach kurzer Zeit automatisch gelöscht.'),

      t('h2', '', '4. SSL-/TLS-Verschlüsselung'),
      t('p', '', 'Diese Website nutzt aus Sicherheitsgründen eine SSL- bzw. TLS-Verschlüsselung. Eine verschlüsselte Verbindung erkennen Sie an „https://“ in der Adresszeile Ihres Browsers.'),

      t('h2', '', '5. Cookies, Schriften und externe Inhalte'),
      t('p', '', 'Diese Website setzt für Besucher keine Cookies zu Analyse- oder Werbezwecken und verwendet keine Analyse- oder Tracking-Werkzeuge. Schriften werden nicht von externen Servern geladen; es werden keine Inhalte Dritter (z. B. Karten oder Videos) eingebettet. Technisch notwendige Cookies werden nur gesetzt, wenn Sie sich im Verwaltungsbereich der Website anmelden.'),
      t('p', '', 'Die Darstellung in hellen oder dunklen Farben richtet sich nach der Einstellung Ihres Geräts. Schalten Sie die Darstellung über den Umschalter im Seitenkopf um, wird Ihre Wahl ausschließlich lokal in Ihrem Browser gespeichert (Local Storage), damit sie beim nächsten Seitenaufruf erhalten bleibt. Diese Angabe wird nicht an uns oder Dritte übertragen; Sie können sie jederzeit über die Einstellungen Ihres Browsers löschen. Die Speicherung ist für die von Ihnen gewünschte Funktion unbedingt erforderlich (§ 25 Abs. 2 Nr. 2 TDDDG).'),

      t('h2', '', '6. Kontaktformular, E-Mail und Telefon', { attrs: { id: 'kontakt' } }),
      t('p', '', 'Wenn Sie uns über das Kontaktformular, per E-Mail oder telefonisch kontaktieren, verarbeiten wir Ihre Angaben (z. B. Name, Firma, E-Mail-Adresse, Telefonnummer und Ihre Nachricht), um Ihre Anfrage zu bearbeiten. Rechtsgrundlage ist Art. 6 Abs. 1 lit. b DSGVO, wenn Ihre Anfrage mit einem Vertrag zusammenhängt oder der Durchführung vorvertraglicher Maßnahmen dient, im Übrigen Ihre Einwilligung (Art. 6 Abs. 1 lit. a DSGVO) bzw. unser berechtigtes Interesse an der Beantwortung von Anfragen (Art. 6 Abs. 1 lit. f DSGVO).'),
      t('p', '', 'Die Angaben aus dem Kontaktformular werden per E-Mail an uns übermittelt und nicht in einer Datenbank der Website gespeichert. Zum Schutz vor Missbrauch wird Ihre IP-Adresse kurzzeitig in verschlüsselter (gehashter) Form zwischengespeichert und nach spätestens einer Stunde gelöscht. Wir löschen Ihre Anfrage, sobald sie abschließend bearbeitet ist, sofern keine gesetzlichen Aufbewahrungspflichten bestehen. Ihre Einwilligung können Sie jederzeit mit Wirkung für die Zukunft widerrufen.'),

      t('h2', '', '7. Fernwartung mit PC-Visit'),
      t('p', '', 'Für die Fernwartung setzen wir die Software PC-Visit der pcvisit Software AG, Manfred-von-Ardenne-Ring 20, 01099 Dresden, ein. Wenn Sie das Programm über die Links auf dieser Website herunterladen, wird eine Verbindung zu den Servern von pcvisit aufgebaut; dabei werden technisch notwendige Daten wie Ihre IP-Adresse übertragen.'),
      t('p', '', 'Während einer Fernwartungssitzung können wir Inhalte Ihres Bildschirms sehen und auf Ihren Rechner zugreifen. Eine Sitzung kommt nur zustande, wenn Sie das Programm selbst starten und uns die Sitzungsdaten mitteilen; Sie können sie jederzeit beenden. Rechtsgrundlage ist Art. 6 Abs. 1 lit. b DSGVO (Erbringung der vereinbarten Leistung).'),

      t('h2', '', '8. Ihre Rechte'),
      t('p', '', 'Sie haben im Rahmen der gesetzlichen Bestimmungen jederzeit das Recht auf'),
      el('ul', '', [
        'Auskunft über Ihre gespeicherten Daten (Art. 15 DSGVO)',
        'Berichtigung unrichtiger Daten (Art. 16 DSGVO)',
        'Löschung Ihrer Daten (Art. 17 DSGVO)',
        'Einschränkung der Verarbeitung (Art. 18 DSGVO)',
        'Datenübertragbarkeit (Art. 20 DSGVO)',
        'Widerruf einer erteilten Einwilligung mit Wirkung für die Zukunft (Art. 7 Abs. 3 DSGVO)',
      ].map((x) => t('li', '', x))),
      t('h3', '', 'Widerspruchsrecht (Art. 21 DSGVO)'),
      t('p', '', 'Verarbeiten wir Ihre Daten auf Grundlage eines berechtigten Interesses (Art. 6 Abs. 1 lit. f DSGVO), können Sie aus Gründen, die sich aus Ihrer besonderen Situation ergeben, jederzeit Widerspruch gegen die Verarbeitung einlegen. Gegen die Verarbeitung zu Zwecken der Direktwerbung können Sie jederzeit ohne Angabe von Gründen widersprechen.'),
      t('h3', '', 'Beschwerderecht'),
      t('p', '', 'Sie haben das Recht, sich bei einer Datenschutz-Aufsichtsbehörde zu beschweren. Für uns zuständig ist die Landesbeauftragte für Datenschutz und Informationsfreiheit Nordrhein-Westfalen, Kavalleriestraße 2–4, 40213 Düsseldorf.'),
      t('p', '', 'Zur Ausübung Ihrer Rechte genügt eine formlose Nachricht an die oben genannten Kontaktdaten.'),

      t('h2', '', '9. Aktualität'),
      t('p', '', 'Diese Datenschutzerklärung hat den Stand Oktober 2026. Wir passen sie an, wenn sich die Website oder die rechtlichen Vorgaben ändern.'),
    ]),
    ]),
  ]),
);

// ---------- Ausgabe ----------

// seo (nicht im Manifest, sondern in daten/seo.json für snippets/kbs-seo.php):
// titel → <title> (SEOPress-Filter; ein im Seiteneditor gesetzter SEOPress-Titel geht vor), faq → FAQPage-Schema und llms.txt,
// leistung → Service-Schema, llms → Abschnitt in /llms.txt (ohne: nicht aufgeführt). Firmenangaben über firma('<feld>').
const marke = ` | ${firma('kurzname')}`;
export const pages = [
  { slug: 'startseite', title: 'Startseite', order: 0, front_page: true, excerpt: 'IT-Betreuung, IT-Beratung und moderne WordPress-Websites für kleine Unternehmen in Burscheid und Umgebung. Schnelle Hilfe per Fernwartung mit PC-Visit.', content: startseite,
    seo: { titel: `IT-Service und WordPress-Websites in ${firma('ort')}${marke}`, faq: startFaq } },
  { slug: 'leistungen', title: 'Leistungen', order: 10, excerpt: 'IT-Betreuung, IT-Beratung und WordPress-Websites für kleine Unternehmen ohne eigene IT-Abteilung.', content: leistungenSeite,
    seo: { titel: `IT-Betreuung, IT-Beratung und Websites in ${firma('ort')}${marke}`, llms: 'Seiten' } },
  ...leistungen.map((l, i) => ({ slug: l.slug, parent: 'leistungen', title: l.titel, order: i + 1, excerpt: l.kurz, content: leistungSeite(l),
    seo: { titel: `${l.titel} für kleine Unternehmen in ${firma('ort')}${marke}`, faq: leistungInhalte[l.slug]?.faq, leistung: true, llms: 'Leistungen' } })),
  { slug: 'fernwartung', title: 'Fernwartung', order: 20, excerpt: 'Schnelle Hilfe per Fernwartung mit PC-Visit: Quick Support herunterladen, Sitzungsnummer nennen, fertig. Ohne Anfahrtskosten.', content: fernwartungSeite,
    seo: { titel: `Fernwartung mit PC-Visit – schnelle IT-Hilfe${marke}`, llms: 'Seiten' } },
  { slug: 'ueber-uns', title: 'Über uns', order: 30, excerpt: 'Kotthaus Business Service GmbH aus Burscheid: persönlicher IT-Service und WordPress-Websites für kleine Unternehmen.', content: ueberUnsSeite,
    seo: { titel: `Über uns – IT-Service aus ${firma('ort')}${marke}`, llms: 'Seiten' } },
  { slug: 'kontakt', title: 'Kontakt', order: 40, excerpt: 'Kontakt zu Kotthaus Business Service in Burscheid: Anfrage senden, anrufen oder kostenloses Erstgespräch vereinbaren.', content: kontaktSeite,
    seo: { titel: `Kontakt und kostenloses Erstgespräch${marke}`, llms: 'Seiten' } },
  { slug: 'impressum', title: 'Impressum', order: 90, excerpt: 'Impressum der Kotthaus Business Service GmbH.', content: impressumSeite, seo: { titel: `Impressum${marke}`, llms: 'Rechtliches' } },
  { slug: 'datenschutz', title: 'Datenschutz', order: 91, excerpt: 'Datenschutzerklärung der Kotthaus Business Service GmbH.', content: datenschutzSeite, seo: { titel: `Datenschutz${marke}`, llms: 'Rechtliches' } },
];

// Rahmen jeder Seite: Header, Inhalt, Footer
const rahmen = (...inhalt) => markup(komponente('SiteHeader'), el('main', 'site-main', inhalt, { attrs: { id: 'main' }, name: 'Main' }), komponente('SiteFooter'));

export const templates = [
  // Standard für alle Seiten und Beiträge ohne eigenes Template (ersetzt index.html des Etch-Themes)
  { slug: 'index', title: 'Index', content: rahmen(postContent()) },
  {
    slug: '404',
    title: 'Seite nicht gefunden',
    content: rahmen(
      seitenkopf({ eyebrow: 'Fehler 404', titel: 'Diese Seite gibt es nicht (mehr).', lead: 'Vielleicht hilft Ihnen einer dieser Links weiter – oder Sie rufen uns einfach an.', aktionen: [t('a', 'btn--primary', 'Zur Startseite', { attrs: { href: '/' } }), t('a', 'btn--primary btn--outline', 'Leistungen', { attrs: { href: '/leistungen/' } })] }),
      abschnitt('', 'Leistungen', [t('h2', 'visually-hidden', 'Unsere Leistungen'), komponente('Leistungskarten')]),
    ),
  },
];
