// Zentrale Daten im Repo.
// - firma: Startwerte der Einstellungsseite „Firmendaten“ (Import einmalig per kbs/import-settings).
//   Danach wird im Backend gepflegt; das Markup liest nur {options.kbs.firma.…} (snippets/kbs-firma.php).
// - navigation, leistungen: Struktur der Website (Generator).
// Quelle der Startwerte: alte Website kotthaus-bs.de (Impressum, Startseite), Stand 2026-10-03.

export const firma = {
  firma_name: 'Kotthaus Business Service GmbH',
  firma_kurzname: 'Kotthaus Business Service',
  firma_claim: 'IT-Service und WordPress-Websites für kleine Unternehmen',
  firma_strasse: 'Am Schlagbaum 12',
  firma_plz: '51399',
  firma_ort: 'Burscheid',
  firma_region: 'Burscheid, Leichlingen, Wermelskirchen, Leverkusen, Odenthal und Umgebung',
  firma_telefon: '02174 666 47 17',
  firma_fax: '02174 666 47 15',
  firma_email: 'info@kotthaus-bs.de',
  firma_erreichbarkeit: 'Mo–Do 8:00–17:00 Uhr\nFr 8:00–12:00 Uhr', // eine Angabe je Zeile; leer = Abschnitt ausgeblendet
  recht_vertretung: 'Klaus Kotthaus',
  recht_registergericht: 'Amtsgericht Köln',
  recht_registernummer: 'HRB 96787',
  recht_ust_id: 'DE322384610',
  recht_verantwortlich: 'Klaus Kotthaus, Anschrift wie oben',
  recht_hoster: '',
  kontakt_formular: 'an',
  kontakt_empfaenger: '',
  pcvisit_kunden_url: 'https://gw47.pcvisit.de/v1/hosted/jumplink?func=download&productrole=guestSetup&gateway=lb3.pcvisit.de&companyid=0103297178',
  pcvisit_host_url: 'https://gw60.pcvisit.de/v1/hosted/jumplink?func=download&productrole=remoteHostSetup&gateway=stable-update.pcvisit.de&companyid=0103297178',
  pcvisit_hinweis: 'Bitte starten Sie die Fernwartung erst nach telefonischer Absprache mit uns.',
  angebot_titel: 'Kostenloses Erstgespräch',
  angebot_text: '30 Minuten, unverbindlich: Wir schauen uns Ihre IT oder Ihre Website an und sagen Ihnen ehrlich, was sich lohnt.',
};

// Hauptnavigation. Der letzte Punkt erscheint als Button.
export const navigation = [
  {
    text: 'Leistungen',
    link: '/leistungen/',
    kinder: [
      ['IT-Betreuung', '/leistungen/it-betreuung/'],
      ['IT-Beratung', '/leistungen/it-beratung/'],
      ['WordPress-Websites', '/leistungen/wordpress-websites/'],
    ],
  },
  { text: 'Fernwartung', link: '/fernwartung/' },
  { text: 'Über uns', link: '/ueber-uns/' },
  { text: 'Kontakt', link: '/kontakt/' },
];

// Leistungen: Karten auf Start- und Übersichtsseite, Seiten unter /leistungen/<slug>/
export const leistungen = [
  {
    slug: 'it-betreuung',
    icon: 'monitor',
    titel: 'IT-Betreuung',
    kurz: 'Wir kümmern uns um PCs, Server, Netzwerk, Drucker und Updates – damit Sie sich um Ihr Geschäft kümmern können.',
    punkte: ['Arbeitsplätze, Server und Netzwerk', 'Updates, Virenschutz und Datensicherung', 'Schnelle Hilfe per Fernwartung oder vor Ort'],
  },
  {
    slug: 'it-beratung',
    icon: 'chat',
    titel: 'IT-Beratung',
    kurz: 'Neue Rechner, Cloud-Dienste oder ein sicheres Netzwerk? Wir beraten passend zu Ihrer Firmengröße und Ihrem Budget.',
    punkte: ['Bestandsaufnahme und Empfehlung', 'Beschaffung und Einrichtung', 'Sicherheit und Datenschutz im Blick'],
  },
  {
    slug: 'wordpress-websites',
    icon: 'browser',
    titel: 'WordPress-Websites',
    kurz: 'Moderne, schnelle Websites mit aktuellen Buildern wie Etch – die Sie danach selbst pflegen können.',
    punkte: ['Design, das zu Ihrem Unternehmen passt', 'Für Smartphone und Suchmaschine optimiert', 'Inhalte selbst ändern, ohne Programmierkenntnisse'],
  },
];

// Für wen wir arbeiten (Startseite)
export const zielgruppen = ['Handwerksbetriebe', 'Arztpraxen und Therapeuten', 'Kanzleien und Steuerbüros', 'Einzelhandel', 'Agenturen und Büros', 'Vereine'];

// Alte Adressen von kotthaus-bs.de → neue Seiten (301, snippets/kbs-seo.php liest daten/weiterleitungen.json)
export const weiterleitungen = {
  '/leistung/': '/leistungen/',
  '/leistung/it-service/': '/leistungen/it-betreuung/',
  '/leistung/it-service/systemberatung/': '/leistungen/it-betreuung/',
  '/leistung/it-service/beratung/': '/leistungen/it-beratung/',
  '/leistung/web-service/': '/leistungen/wordpress-websites/',
  '/leistung/web-service/wordpress/': '/leistungen/wordpress-websites/',
  '/leistung/web-service/hosting/': '/leistungen/',
  '/leistung/web-service/ilias/': '/leistungen/',
  // entfallene Leistungen (2026-10-03)
  '/leistungen/hosting/': '/leistungen/',
  '/leistungen/e-learning/': '/leistungen/',
};
