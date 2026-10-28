#!/usr/bin/env node
// Sicherheitsprüfung der Website Kotthaus Business Service – von außen wie ein fremder Besucher (ohne Anmeldung) und mit --innen
// zusätzlich von innen per WP-CLI über SSH (nur lesend). Nichts wird verändert; einzige Ausnahme: wenige Anmeldeversuche
// mit einem erfundenen Benutzernamen (prüft, ob die Anmeldung Benutzernamen verrät und ob Fehlversuche gebremst werden).
//
// Aufruf:  node tools/sicherheit-pruefen.mjs [Adresse] [--innen=<ssh-alias>] [--bericht]
//   Adresse   Standard https://kotthaus-bs.de (live)
//   --innen   SSH-Alias mit Zugang zur Website (WP-CLI mit dem PHP des Webservers, siehe PHP_INNEN) – live: kbs-live (nur lesend; hebt am Ende nur die eigene, frische Sperre auf)
//   --bericht schreibt docs/sicherheit/pruefung-<Datum>-<Host>.md (sonst nur Ausgabe im Terminal)
// Ergebnis je Prüfung: ok | hinweis | lücke. Ende mit Fehlercode 1, wenn es Lücken gibt.
// Beschrieben in docs/technik.md (Kapitel Sicherheit) und docs/sicherheit/README.md. Vorlage aus nickut-catering.

import { execFileSync } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const wurzel = join(dirname(fileURLToPath(import.meta.url)), '..');
const argumente = process.argv.slice(2);
const BASIS = (argumente.find((a) => /^https?:\/\//.test(a)) || 'https://kotthaus-bs.de').replace(/\/$/, '');
const INNEN = (argumente.find((a) => a.startsWith('--innen=')) || '').slice(8);
const BERICHT = argumente.includes('--bericht');
const PHP_INNEN = '/usr/local/lsws/lsphp85/bin/php /usr/local/bin/wp'; // System-PHP der Server hat zeitweise kein mysqli
const WPDIR = INNEN === 'kbs-live' ? '/srv/www/kbs' : '~/public_html'; // live: WordPress in /srv/www/kbs
const HOST = new URL(BASIS).host;
const AGENT = 'Mozilla/5.0 (compatible; kbs-sicherheitspruefung)'; // mit Browser-Kennung, sonst blockt die Firewall (Security Ninja)

const ergebnisse = [];
const melde = (bereich, pruefung, ergebnis, details) => {
  ergebnisse.push({ bereich, pruefung, ergebnis, details });
  const z = { ok: '✓', hinweis: '!', 'lücke': '✗' }[ergebnis];
  console.log(`${z} [${bereich}] ${pruefung}: ${details}`);
};

async function hole(pfad, optionen = {}) {
  const url = pfad.startsWith('http') ? pfad : BASIS + pfad;
  const r = await fetch(url, { redirect: 'manual', ...optionen, headers: { 'User-Agent': AGENT, ...(optionen.headers || {}) } });
  const puffer = Buffer.from(await r.arrayBuffer());
  return { status: r.status, kopf: r.headers, text: puffer.toString('utf8'), puffer, ort: r.headers.get('location') || '' };
}

// ---------- Prüfungen von außen ----------
async function verbindung() {
  const http = await hole(BASIS.replace(/^https:/, 'http:') + '/');
  melde('Verbindung', 'Umleitung von http auf https', [301, 308].includes(http.status) && http.ort.startsWith('https://') ? 'ok' : 'lücke', `http:// antwortet mit ${http.status}${http.ort ? ' → ' + http.ort : ''}`);
  const s = await hole('/');
  const k = s.kopf;
  const hsts = k.get('strict-transport-security');
  melde('Verbindung', 'HSTS (Browser immer verschlüsselt)', hsts ? 'ok' : 'lücke', hsts || 'Kopfzeile Strict-Transport-Security fehlt');
  melde('Kopfzeilen', 'X-Content-Type-Options', k.get('x-content-type-options') === 'nosniff' ? 'ok' : 'lücke', k.get('x-content-type-options') || 'fehlt');
  const rahmen = k.get('x-frame-options') || (/frame-ancestors/i.test(k.get('content-security-policy') || '') ? 'CSP frame-ancestors' : '');
  melde('Kopfzeilen', 'Schutz gegen Einbetten in fremde Seiten', rahmen ? 'ok' : 'lücke', rahmen || 'X-Frame-Options bzw. CSP frame-ancestors fehlt');
  melde('Kopfzeilen', 'Referrer-Policy', k.get('referrer-policy') ? 'ok' : 'hinweis', k.get('referrer-policy') || 'fehlt (Browser-Standard gibt die Herkunft gekürzt weiter)');
  melde('Kopfzeilen', 'Permissions-Policy', k.get('permissions-policy') ? 'ok' : 'hinweis', k.get('permissions-policy') || 'fehlt');
  const verrat = [k.get('x-powered-by'), /\d/.test(k.get('server') || '') ? k.get('server') : ''].filter(Boolean).join(', ');
  melde('Kopfzeilen', 'Server verrät keine Versionen', verrat ? 'hinweis' : 'ok', verrat || 'keine Versionsangaben');
  const gen = (s.text.match(/<meta name="generator" content="([^"]+)"/gi) || []).join(' ');
  melde('Versionen', 'WordPress-Version im Quelltext', /WordPress\s*\d/i.test(gen) ? 'hinweis' : 'ok', gen ? gen.replace(/<meta name="generator" content="|"/g, '') : 'nicht genannt');
  const readme = await hole('/readme.html');
  melde('Versionen', 'readme.html', readme.status === 200 ? 'hinweis' : 'ok', readme.status === 200 ? 'öffentlich (nennt WordPress)' : `nicht erreichbar (${readme.status})`);
  const umgebung = s.kopf.get('x-robots-tag') || (s.text.match(/<meta name=['"]robots['"] content=['"]([^'"]+)/i) || [])[1] || '';
  return { start: s, umgebung };
}

// Was Erkennungsdienste (z. B. Wappalyzer) aus der Startseite lesen. Versionsnummern sind das, was zählt.
function technik(start) {
  const k = start.kopf;
  const verrat = [k.get('x-powered-by') && `X-Powered-By: ${k.get('x-powered-by')}`, /\d/.test(k.get('server') || '') && `Server: ${k.get('server')}`].filter(Boolean);
  melde('Technik verraten', 'Server und PHP ohne Versionsnummer', verrat.length ? 'hinweis' : 'ok', verrat.length ? verrat.join(', ') : `Server: ${k.get('server') || '–'}, kein X-Powered-By`);
  const versionen = [...new Set((start.text.match(/\/wp-(?:content\/plugins\/[a-z0-9-]+|includes)\/[^"' ]*\?ver=\d+\.\d+[\d.]*/gi) || []).map((u) => {
    const [, wo] = u.match(/\/wp-(?:content\/plugins\/([a-z0-9-]+)|(includes))/) || [];
    return `${wo || 'wp-includes'} ${u.split('ver=')[1]}`;
  }))];
  melde('Technik verraten', 'Keine Versionsnummern von WordPress und Plugins (?ver=)', versionen.length ? 'hinweis' : 'ok', versionen.length ? versionen.slice(0, 8).join(', ') : 'keine');
  const kurz = k.get('link') || '';
  melde('Technik verraten', 'Keine Link-Kopfzeilen (Schnittstelle, Kurzlink)', /rel="?(https:\/\/api\.w\.org\/|shortlink)/.test(kurz) ? 'hinweis' : 'ok', kurz || 'keine');
}

async function benutzernamen() {
  const api = await hole('/wp-json/wp/v2/users');
  let namen = [];
  try { namen = JSON.parse(api.text).map((u) => u.slug).filter(Boolean); } catch { /* keine Liste */ }
  melde('Benutzernamen', 'Schnittstelle /wp-json/wp/v2/users', namen.length ? 'lücke' : 'ok', namen.length ? `liefert ${namen.length} Benutzernamen (z. B. ${namen[0]})` : `keine Liste (${api.status})`);
  const autor = await hole('/?author=1');
  const slug = (autor.ort.match(/\/author\/([^/]+)/) || [])[1];
  melde('Benutzernamen', 'Autorenseite /?author=1', slug ? 'lücke' : 'ok', slug ? `leitet auf /author/${slug}/ – verrät den Benutzernamen` : `kein Benutzername (${autor.status})`);
  const oembed = await hole(`/wp-json/oembed/1.0/embed?url=${encodeURIComponent(BASIS + '/')}`);
  let autorName = '';
  try { autorName = JSON.parse(oembed.text).author_name || ''; } catch { /* nichts */ }
  melde('Benutzernamen', 'oEmbed nennt den Autor', autorName && !/kotthaus|kbs/i.test(autorName) ? 'hinweis' : 'ok', autorName ? `author_name: ${autorName}` : 'kein Autor genannt');
  for (const pfad of ['/wp-sitemap-users-1.xml', '/author.xml']) {
    const sm = await hole(pfad);
    melde('Benutzernamen', `Sitemap ${pfad}`, sm.status === 200 && /<loc>/.test(sm.text) ? 'lücke' : 'ok', sm.status === 200 && /<loc>/.test(sm.text) ? 'listet Autoren' : `nicht vorhanden (${sm.status})`);
  }
}

async function anmeldung() {
  const xml = await hole('/xmlrpc.php', { method: 'POST', headers: { 'Content-Type': 'text/xml' }, body: '<?xml version="1.0"?><methodCall><methodName>system.listMethods</methodName></methodCall>' });
  const offen = /<methodResponse>/.test(xml.text) && /wp\.getUsersBlogs|system\.multicall/.test(xml.text);
  melde('Anmeldung', 'XML-RPC (alter Zugang für Passwort-Angriffe)', offen ? 'lücke' : 'ok', offen ? 'xmlrpc.php nimmt Anfragen an (auch system.multicall: viele Passwörter je Anfrage)' : `abgeschaltet bzw. gesperrt (${xml.status})`);
  const fremd = `pruefung-${Math.random().toString(36).slice(2, 8)}`;
  let verraet = false;
  let gebremst = false;
  let letzte = 0;
  for (let n = 1; n <= 6; n++) {
    const r = await hole('/wp-login.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', Cookie: 'wordpress_test_cookie=WP%20Cookie%20check' },
      body: new URLSearchParams({ log: fremd, pwd: 'falsch-' + n, 'wp-submit': 'Anmelden', testcookie: '1' }).toString(),
    });
    letzte = r.status;
    if (n === 1) verraet = /nicht registriert|is not registered|Unbekannter Benutzername|Unknown username/i.test(r.text);
    if (r.status === 429 || r.status === 403 || /gesperrt|zu viele|too many|locked/i.test(r.text)) { gebremst = true; break; }
  }
  melde('Anmeldung', 'Fehlermeldung verrät keine Benutzernamen', verraet ? 'lücke' : 'ok', verraet ? 'meldet „Benutzername nicht registriert“ – so lässt sich prüfen, welche Namen es gibt' : 'einheitliche Meldung');
  melde('Anmeldung', 'Fehlversuche werden gebremst', gebremst ? 'ok' : 'lücke', gebremst ? 'nach wenigen Fehlversuchen gesperrt' : `6 Fehlversuche ohne Sperre (letzte Antwort ${letzte})`);
}

async function schnittstellen() {
  const medien = await hole('/wp-json/wp/v2/media?per_page=5');
  let liste = [];
  try { liste = JSON.parse(medien.text); } catch { /* nichts */ }
  melde('Schnittstelle', 'Mediathek ohne Anmeldung', Array.isArray(liste) && liste.length ? 'hinweis' : 'ok', Array.isArray(liste) && liste.length ? 'listet Medien auf (/wp-json/wp/v2/media)' : `nicht aufgelistet (${medien.status})`);
  const seiten = await hole('/wp-json/wp/v2/pages');
  melde('Schnittstelle', 'WordPress-Schnittstelle nur mit Anmeldung', seiten.status === 401 ? 'ok' : 'hinweis', `/wp-json/wp/v2/pages antwortet mit ${seiten.status}`);
}

async function ablagen() {
  for (const pfad of ['/wp-content/uploads/', '/wp-content/kbs/', '/wp-content/kbs/medien/', '/wp-content/']) {
    const r = await hole(pfad);
    const liste = r.status === 200 && /Index of|<title>Index/i.test(r.text);
    melde('Dateien', `Ordnerauflistung ${pfad}`, liste ? 'lücke' : 'ok', liste ? 'zeigt alle Dateien des Ordners' : `keine Auflistung (${r.status})`);
  }
}

async function dateien() {
  const liste = [
    ['/wp-content/debug.log', 'Fehlerprotokoll'], ['/debug.log', 'Fehlerprotokoll'], ['/.env', 'Umgebungsdatei'], ['/wp-config.php.bak', 'Sicherung der Konfiguration'],
    ['/wp-config.php~', 'Sicherung der Konfiguration'], ['/wp-config.old', 'Sicherung der Konfiguration'], ['/installer.php', 'Duplicator-Installer'], ['/installer-backup.php', 'Duplicator-Installer'],
    ['/wp-content/backups-dup-pro/', 'Duplicator-Sicherungen'], ['/wp-content/backups-dup-lite/', 'Duplicator-Sicherungen'], ['/.git/config', 'Git-Daten'],
    ['/wp-content/kbs/daten/seo.json', 'Daten des Builds'], ['/wp-content/kbs/daten/weiterleitungen.json', 'Daten des Builds'],
  ];
  for (const [pfad, was] of liste) {
    const r = await hole(pfad);
    const da = r.status === 200 && r.puffer.length > 0 && !/<html/i.test(r.text.slice(0, 300));
    const json = pfad.endsWith('.json');
    melde('Dateien', `${was} ${pfad}`, da ? (json ? 'hinweis' : 'lücke') : 'ok', da ? (json ? `öffentlich (${r.puffer.length} Bytes) – Inhalt prüfen` : `öffentlich erreichbar (${r.puffer.length} Bytes)`) : `nicht erreichbar (${r.status})`);
  }
  for (const pfad of ['/wp-content/kbs/technik.php', '/wp-content/kbs/snippets/kbs-mcp.php', '/wp-content/kbs/snippets/kbs-schutz.php']) {
    const r = await hole(pfad);
    melde('Dateien', `PHP-Datei des Builds ${pfad}`, r.status === 200 && r.text.trim().length > 0 ? 'lücke' : 'ok', r.status === 200 && r.text.trim() ? 'gibt Inhalt aus' : `gibt nichts aus (${r.status})`);
  }
}

async function suchmaschinen(umgebung) {
  const robots = await hole('/robots.txt');
  melde('Betrieb', 'robots.txt erreichbar', robots.status === 200 ? 'ok' : 'hinweis', `Antwort ${robots.status}`);
  const sitemap = await hole('/sitemaps.xml');
  melde('Betrieb', 'Sitemap (SEOPress)', sitemap.status === 200 ? 'ok' : 'hinweis', `/sitemaps.xml antwortet mit ${sitemap.status}`);
  melde('Betrieb', 'Suchmaschinen erlaubt (live)', /noindex/i.test(umgebung) ? 'hinweis' : 'ok', umgebung || 'kein noindex');
  const anhang = await hole('/?attachment_id=1');
  melde('Betrieb', 'Anhangseiten', anhang.status === 200 && /<img/i.test(anhang.text) ? 'hinweis' : 'ok', `Antwort ${anhang.status}`);
}

// ---------- Prüfungen von innen (WP-CLI, nur lesend) ----------
const PHP_PRUEFUNG = String.raw`<?php
$a = array();
$admins = get_users( array( 'role' => 'administrator', 'fields' => array( 'user_login' ) ) );
$a['admins'] = array_map( fn( $u ) => $u->user_login, $admins );
$a['rollen'] = array();
foreach ( get_users() as $u ) { foreach ( $u->roles as $r ) { $a['rollen'][ $r ] = ( $a['rollen'][ $r ] ?? 0 ) + 1; } }
$a['debug'] = defined( 'WP_DEBUG' ) && WP_DEBUG; $a['debug_display'] = defined( 'WP_DEBUG_DISPLAY' ) ? (bool) WP_DEBUG_DISPLAY : true; $a['debug_log'] = defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG;
$a['datei_editor'] = defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT;
$a['umgebung'] = wp_get_environment_type();
$a['config_rechte'] = substr( sprintf( '%o', fileperms( ABSPATH . 'wp-config.php' ) ), -3 );
$a['anwendungspasswoerter'] = 0; foreach ( get_users() as $u ) { $a['anwendungspasswoerter'] += count( WP_Application_Passwords::get_user_application_passwords( $u->ID ) ); }
require_once ABSPATH . 'wp-admin/includes/plugin.php'; $up = get_site_transient( 'update_plugins' ); /* letzter Stand, ohne neue Abfrage */ $a['plugin_updates'] = array_keys( (array) ( $up->response ?? array() ) );
$a['wp'] = get_bloginfo( 'version' );
$a['inaktiv'] = array_values( array_diff( array_keys( get_plugins() ), (array) get_option( 'active_plugins', array() ) ) );
$a['mcp'] = (bool) get_option( 'wpcb_mcp_enabled' );
$a['anhangseiten'] = (int) get_option( 'wp_attachment_pages_enabled', 0 );
$a['kommentare'] = get_option( 'default_comment_status' );
$a['registrierung'] = (int) get_option( 'users_can_register' );
$a['htaccess'] = is_file( ABSPATH . '.htaccess' ) && str_contains( (string) file_get_contents( ABSPATH . '.htaccess' ), 'BEGIN KBS-Schutz' );
echo wp_json_encode( $a );
`;

// Sperre aus der eigenen Prüfung der Anmeldung wieder aufheben (nur Sperren der letzten 5 Minuten) – einziger schreibender Schritt
const PHP_SPERRE = String.raw`<?php
// Sperre aus der eigenen Prüfung aufheben: nur Sperren, die in den letzten 5 Minuten entstanden sind (erste Sperre 15 Minuten)
global $wpdb;
$jetzt = time();
$n     = 0;
foreach ( $wpdb->get_results( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_timeout\_kbs\_sperre\_%'" ) as $z ) {
	$k = substr( $z->option_name, strlen( '_transient_timeout_' ) );
	if ( (int) $z->option_value - $jetzt > KBS_SCHUTZ_SPERRE - 5 * MINUTE_IN_SECONDS ) {
		$s = substr( $k, strlen( 'kbs_sperre_' ) );
		delete_transient( $k );
		delete_transient( 'kbs_sperren_' . $s );
		delete_transient( 'kbs_versuche_' . $s );
		$n++;
	}
}
echo "aufgehoben: $n\n";
`;

function sperreAufheben() {
  if (!INNEN) {
    console.log('Achtung: Die Prüfung der Anmeldung hat diese IP-Adresse für 15 Minuten von der Anmeldung gesperrt (mit --innen wird die Sperre am Ende aufgehoben).');
    return;
  }
  try {
    const aus = execFileSync('ssh', [INNEN, `cd ${WPDIR} && cat > /tmp/kbs-sperre.php && ${PHP_INNEN} eval-file /tmp/kbs-sperre.php 2>/dev/null; rm -f /tmp/kbs-sperre.php`], { input: PHP_SPERRE, encoding: 'utf8', timeout: 60000 });
    console.log(`Sperre aus der Prüfung der Anmeldung: ${aus.trim().split('\n').pop()}`);
  } catch (f) {
    console.log(`Sperre konnte nicht aufgehoben werden: ${String(f.message).split('\n')[0]}`);
  }
}

function innen() {
  if (!INNEN) return;
  let a;
  try {
    const aus = execFileSync('ssh', [INNEN, `cd ${WPDIR} && cat > /tmp/kbs-sicherheit.php && ${PHP_INNEN} eval-file /tmp/kbs-sicherheit.php 2>/dev/null; rm -f /tmp/kbs-sicherheit.php`], { input: PHP_PRUEFUNG, encoding: 'utf8', timeout: 180000 });
    a = JSON.parse(aus.slice(aus.indexOf('{')));
  } catch (f) {
    melde('Innen', 'WP-CLI über SSH', 'hinweis', `nicht möglich: ${String(f.message).split('\n')[0]}`);
    return;
  }
  melde('Zugänge', 'Administratoren', a.admins.length <= 1 ? 'ok' : 'hinweis', `${a.admins.length} (Anmeldenamen stehen bewusst nicht im Bericht)`);
  melde('Zugänge', 'Rollen', 'ok', Object.entries(a.rollen).map(([r, n]) => `${r} ${n}`).join(', '));
  melde('Zugänge', 'Selbst registrieren abgeschaltet', a.registrierung ? 'lücke' : 'ok', a.registrierung ? 'jeder kann sich ein Konto anlegen' : 'aus');
  melde('Zugänge', 'Anwendungspasswörter', a.anwendungspasswoerter ? 'hinweis' : 'ok', `${a.anwendungspasswoerter} vorhanden${a.anwendungspasswoerter ? ' (z. B. für MCP – live widerrufen)' : ''}`);
  melde('Konfiguration', 'Fehlerausgabe auf der Website', a.debug && a.debug_display ? 'lücke' : 'ok', a.debug ? `WP_DEBUG an, Anzeige ${a.debug_display ? 'an' : 'aus'}, Protokoll ${a.debug_log ? 'an' : 'aus'}` : 'WP_DEBUG aus');
  melde('Konfiguration', 'Datei-Editor im Backend gesperrt', a.datei_editor ? 'ok' : 'hinweis', a.datei_editor ? 'DISALLOW_FILE_EDIT' : 'Themes und Plugins ließen sich im Backend bearbeiten (nur Administratoren)');
  melde('Konfiguration', 'Rechte der wp-config.php', ['600', '640', '400', '440'].includes(a.config_rechte) ? 'ok' : 'hinweis', a.config_rechte);
  melde('Konfiguration', 'Umgebung', 'ok', a.umgebung);
  melde('Konfiguration', 'Schutz-Regeln in der .htaccess', a.htaccess ? 'ok' : 'lücke', a.htaccess ? 'Block KBS-Schutz vorhanden' : 'fehlt – einmal als Administrator im Backend anmelden');
  melde('Aktualität', 'Plugins mit Updates', a.plugin_updates.length ? 'hinweis' : 'ok', a.plugin_updates.length ? a.plugin_updates.join(', ') : 'alle aktuell');
  melde('Aktualität', 'Inaktive Plugins', a.inaktiv.length ? 'hinweis' : 'ok', a.inaktiv.length ? a.inaktiv.join(', ') : 'keine');
  melde('Konfiguration', 'WPCodeBox-MCP', a.umgebung === 'production' && a.mcp ? 'lücke' : 'ok', `${a.mcp ? 'an' : 'aus'} (live muss es aus sein)`);
  melde('Konfiguration', 'Kommentare', a.kommentare === 'open' ? 'hinweis' : 'ok', a.kommentare);
}

// ---------- Ablauf und Bericht ----------
const { start, umgebung } = await verbindung();
technik(start);
await benutzernamen();
await anmeldung();
await schnittstellen();
await ablagen();
await dateien();
await suchmaschinen(umgebung);
innen();
sperreAufheben();

const zahl = (e) => ergebnisse.filter((x) => x.ergebnis === e).length;
console.log(`\n${HOST}: ${zahl('ok')} in Ordnung, ${zahl('hinweis')} Hinweise, ${zahl('lücke')} Lücken`);

if (BERICHT) {
  const datum = new Date().toISOString().slice(0, 10);
  const datei = join(wurzel, 'docs', 'sicherheit', `pruefung-${datum}-${HOST.split('.')[0]}.md`);
  mkdirSync(dirname(datei), { recursive: true });
  const zeichen = { ok: '✓ in Ordnung', hinweis: '! Hinweis', 'lücke': '✗ Lücke' };
  const zeilen = [
    `# Sicherheitsprüfung ${HOST} – ${datum}`,
    '',
    `Geprüft mit \`node tools/sicherheit-pruefen.mjs ${BASIS}${INNEN ? ` --innen=${INNEN}` : ''} --bericht\`: von außen wie ein fremder Besucher ohne Anmeldung${INNEN ? ', dazu von innen per WP-CLI (nur lesend)' : ''}. Verändert wurde nichts; für die Prüfung der Anmeldung gab es sechs Fehlversuche mit einem erfundenen Benutzernamen${INNEN ? ', die dadurch entstandene Sperre der eigenen IP-Adresse wurde danach aufgehoben' : ''}.`,
    '',
    `**Ergebnis:** ${zahl('ok')} in Ordnung, ${zahl('hinweis')} Hinweise, ${zahl('lücke')} Lücken.`,
    '',
    '| Bereich | Prüfung | Ergebnis | Befund |',
    '| --- | --- | --- | --- |',
    ...['lücke', 'hinweis', 'ok'].flatMap((e) => ergebnisse.filter((x) => x.ergebnis === e).map((x) => `| ${x.bereich} | ${x.pruefung} | ${zeichen[x.ergebnis]} | ${String(x.details).replace(/\|/g, '/')} |`)),
    '',
  ];
  writeFileSync(datei, zeilen.join('\n'));
  console.log(`Bericht: ${datei}`);
}
process.exit(zahl('lücke') ? 1 : 0);
