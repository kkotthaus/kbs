<?php
/**
 * Plugin Name: KBS – Schutz
 * Description: Schutz vor Ausspähen und Durchprobieren (Standard aus etch-nodes): Autorenseiten leiten auf die Startseite (sonst verraten ?author=1, 2 … die Anmeldenamen), kein Autor in oEmbed, einheitliche Fehlermeldung bei der Anmeldung, XML-RPC aus. Sperre nach Fehlversuchen: 5 falsche Anmeldungen von einer IP-Adresse in 15 Minuten sperren sie für 15 Minuten, jede weitere Sperre am selben Tag doppelt so lange (höchstens 24 Stunden); gilt auch für Anwendungspasswörter; gespeichert wird nur ein Hash der IP-Adresse. WordPress-Schnittstelle ohne Anmeldung nur für die Bereiche, die die Website braucht (KBS_SCHUTZ_REST_OFFEN). Sicherheits-Kopfzeilen (HSTS, nosniff, Einbetten nur auf dieser Website, Referrer-Policy, Permissions-Policy), keine Versionsnummern (WordPress, PHP, ?ver= an Skripten und Stilen, kein Kurzlink), Block „KBS-Schutz“ in der .htaccess (xmlrpc.php, readme.html, license.txt und die Build-Daten wp-content/kbs/daten/ gesperrt; Kopfzeilen auch für Dateien). Beschrieben in der Technik-Doku (Kapitel Sicherheit). Keine Shortcodes, kein Markup.
 * Version: 1.0.0
 *
 * Gehört auf die Live-Seite. WPCodeBox: PHP, Ausführung „Always“, Einfügepunkt Root.
 * Quelle: Repository kbs, wordpress/snippets/kbs-schutz.php
 */

defined( 'ABSPATH' ) || exit;

defined( 'KBS_SCHUTZ_VERSUCHE' ) || define( 'KBS_SCHUTZ_VERSUCHE', 5 );                     // Fehlversuche bis zur Sperre
defined( 'KBS_SCHUTZ_FENSTER' ) || define( 'KBS_SCHUTZ_FENSTER', 15 * MINUTE_IN_SECONDS );  // in diesem Zeitraum
defined( 'KBS_SCHUTZ_SPERRE' ) || define( 'KBS_SCHUTZ_SPERRE', 15 * MINUTE_IN_SECONDS );    // erste Sperre; jede weitere am selben Tag doppelt so lang
defined( 'KBS_SCHUTZ_SPERRE_MAX' ) || define( 'KBS_SCHUTZ_SPERRE_MAX', DAY_IN_SECONDS );
// Bereiche der Schnittstelle, die ohne Anmeldung erreichbar bleiben: Kontaktformular (WS Form) und Bausteine von OhMyEtch.
// Matomo zählt über seine eigene Datei, nicht über die Schnittstelle. Der MCP-Zugang meldet sich mit Anwendungspasswort an.
defined( 'KBS_SCHUTZ_REST_OFFEN' ) || define( 'KBS_SCHUTZ_REST_OFFEN', array( 'ws-form/v1', 'oh-my-etch/v1' ) );
defined( 'KBS_SCHUTZ_HTACCESS_FASSUNG' ) || define( 'KBS_SCHUTZ_HTACCESS_FASSUNG', '1' );

// ---------- Nichts über die Benutzer verraten ----------

// Autorenseiten (?author=1, /author/<anmeldename>/) zeigen den Anmeldenamen: auf die Startseite umleiten
add_action(
	'template_redirect',
	function () {
		if ( is_author() || isset( $_GET['author'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			wp_safe_redirect( home_url( '/' ), 301 );
			exit;
		}
	},
	1
);
// Kein Autor (Name, Link zur Autorenseite) in oEmbed-Antworten
add_filter(
	'oembed_response_data',
	function ( $daten ) {
		unset( $daten['author_name'], $daten['author_url'] );
		return $daten;
	}
);
// Einheitliche Fehlermeldung bei falschen Anmeldedaten: verrät nicht, ob es den Benutzernamen gibt
add_filter(
	'authenticate',
	function ( $user ) {
		if ( is_wp_error( $user ) && array_intersect( $user->get_error_codes(), array( 'invalid_username', 'invalid_email', 'incorrect_password' ) ) ) {
			return new WP_Error( 'kbs_falsch', '<strong>' . esc_html__( 'Fehler:', 'kbs' ) . '</strong> ' . esc_html__( 'Benutzername oder Passwort ist falsch.', 'kbs' ) );
		}
		return $user;
	},
	100
);
// „Passwort vergessen“ verrät ebenfalls nicht, ob es den Benutzer gibt: auch ohne passenden Zugang dieselbe Bestätigung
// („Prüfe dein E-Mail-Postfach …“), nur ohne Mail
add_action(
	'lostpassword_post',
	function ( $fehler, $benutzer ) {
		if ( ! $benutzer && ! ( is_wp_error( $fehler ) && $fehler->has_errors() ) && ! empty( $_POST['user_login'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			wp_safe_redirect( add_query_arg( 'checkemail', 'confirm', wp_login_url() ) );
			exit;
		}
	},
	10,
	2
);

// XML-RPC wird nicht gebraucht und ist ein beliebter Weg zum Durchprobieren (auch in der .htaccess gesperrt)
add_filter( 'xmlrpc_enabled', '__return_false' );
add_action(
	'init',
	function () {
		if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
			status_header( 403 );
			exit;
		}
	},
	0
);
add_filter( 'xmlrpc_methods', fn() => array() );
add_filter( 'wp_headers', function ( $kopf ) {
	unset( $kopf['X-Pingback'] );
	return $kopf;
} );
remove_action( 'wp_head', 'rsd_link' );

// ---------- Keine Versionsnummern verraten ----------
// Erkennungsdienste (z. B. Wappalyzer) lesen Versionen aus Quelltext und Kopfzeilen; mit ihnen lassen sich bekannte Lücken
// gezielt suchen. Dass die Seite WordPress ist, bleibt erkennbar (wp-content) – das lässt sich nicht sinnvoll verbergen.
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );
// Kurzlink (?p=<ID>) in Kopf und Kopfzeile
remove_action( 'wp_head', 'wp_shortlink_wp_head' );
remove_action( 'template_redirect', 'wp_shortlink_header', 11 );
// ?ver=<Version> an Skripten und Stilen durch eine Kennung ersetzen: Browser laden nach Updates weiter neu, aber die Version
// steht nicht mehr im Quelltext. Im Backend unverändert.
function kbs_schutz_ohne_version( string $src ): string {
	if ( is_admin() || ! str_contains( $src, 'ver=' ) ) {
		return $src;
	}
	$teile = wp_parse_url( $src );
	parse_str( (string) ( $teile['query'] ?? '' ), $werte );
	if ( ! isset( $werte['ver'] ) || ! preg_match( '/^\d+\.\d/', (string) $werte['ver'] ) ) {
		return $src; // Zeitstempel o. ä. verraten nichts
	}
	return add_query_arg( 'ver', substr( hash_hmac( 'sha256', (string) $werte['ver'], wp_salt( 'nonce' ) ), 0, 8 ), $src );
}
add_filter( 'style_loader_src', 'kbs_schutz_ohne_version', 9999 );
add_filter( 'script_loader_src', 'kbs_schutz_ohne_version', 9999 );

// ---------- Sperre nach Fehlversuchen ----------

/** Schlüssel für die IP-Adresse (nur Hash, nicht die Adresse selbst). REMOTE_ADDR – weitergereichte Kopfzeilen zählen nicht. */
function kbs_schutz_schluessel(): string {
	$ip = (string) ( $_SERVER['REMOTE_ADDR'] ?? '' );
	return substr( hash_hmac( 'sha256', $ip, wp_salt( 'auth' ) ), 0, 24 );
}

/** Restzeit der Sperre in Sekunden (0 = nicht gesperrt). */
function kbs_schutz_gesperrt(): int {
	$bis = (int) get_transient( 'kbs_sperre_' . kbs_schutz_schluessel() );
	return max( 0, $bis - time() );
}

/** Fehlversuch zählen; nach KBS_SCHUTZ_VERSUCHE sperren (jede weitere Sperre am selben Tag doppelt so lang). */
function kbs_schutz_fehlversuch(): void {
	$k        = kbs_schutz_schluessel();
	$versuche = (int) get_transient( 'kbs_versuche_' . $k ) + 1;
	if ( $versuche < KBS_SCHUTZ_VERSUCHE ) {
		set_transient( 'kbs_versuche_' . $k, $versuche, KBS_SCHUTZ_FENSTER );
		return;
	}
	delete_transient( 'kbs_versuche_' . $k );
	$sperren = (int) get_transient( 'kbs_sperren_' . $k ) + 1;
	set_transient( 'kbs_sperren_' . $k, $sperren, DAY_IN_SECONDS );
	$dauer = (int) min( KBS_SCHUTZ_SPERRE_MAX, KBS_SCHUTZ_SPERRE * 2 ** ( $sperren - 1 ) );
	set_transient( 'kbs_sperre_' . $k, time() + $dauer, $dauer );
}
add_action( 'wp_login_failed', 'kbs_schutz_fehlversuch' );
add_action( 'application_password_failed_authentication', 'kbs_schutz_fehlversuch' );

// Gesperrt: Anmeldung gar nicht erst prüfen (auch nicht mit richtigem Passwort)
add_filter(
	'authenticate',
	function ( $user ) {
		$rest = kbs_schutz_gesperrt();
		if ( $rest > 0 ) {
			remove_action( 'wp_login_failed', 'kbs_schutz_fehlversuch' ); // Sperre nicht durch weitere Versuche verlängern
			$min = (int) ceil( $rest / MINUTE_IN_SECONDS );
			/* translators: %d: Minuten */
			return new WP_Error( 'kbs_gesperrt', '<strong>' . esc_html__( 'Zu viele Fehlversuche.', 'kbs' ) . '</strong> ' . esc_html( sprintf( _n( 'Bitte in %d Minute noch einmal versuchen.', 'Bitte in %d Minuten noch einmal versuchen.', $min, 'kbs' ), $min ) ) );
		}
		return $user;
	},
	99
);
// Erfolgreiche Anmeldung: Zähler zurücksetzen
add_action( 'wp_login', fn() => delete_transient( 'kbs_versuche_' . kbs_schutz_schluessel() ) );

// ---------- WordPress-Schnittstelle ohne Anmeldung nur, wo nötig ----------
// Sonst listet /wp-json/wp/v2/… ohne Anmeldung u. a. Benutzernamen und alle Medien. Die Website selbst braucht das nicht: Etch baut die Seiten auf dem Server.
add_filter(
	'rest_authentication_errors',
	function ( $fehler ) {
		if ( null !== $fehler && true !== $fehler ) {
			return $fehler; // schon entschieden (z. B. falsches Anwendungspasswort)
		}
		if ( is_user_logged_in() ) {
			return $fehler;
		}
		$route = '/' . ltrim( (string) ( $GLOBALS['wp']->query_vars['rest_route'] ?? '' ), '/' );
		if ( '/' === $route ) {
			return new WP_Error( 'kbs_rest', esc_html__( 'Nur mit Anmeldung.', 'kbs' ), array( 'status' => 401 ) );
		}
		foreach ( KBS_SCHUTZ_REST_OFFEN as $bereich ) {
			if ( str_starts_with( $route, '/' . $bereich . '/' ) || '/' . $bereich === $route ) {
				return $fehler;
			}
		}
		return new WP_Error( 'kbs_rest', esc_html__( 'Nur mit Anmeldung.', 'kbs' ), array( 'status' => 401 ) );
	},
	99
);
// Link auf die Schnittstelle nicht in jeder Seite ankündigen
remove_action( 'wp_head', 'rest_output_link_wp_head' );
remove_action( 'template_redirect', 'rest_output_link_header', 11 );

// ---------- Sicherheits-Kopfzeilen ----------
// Früh (init), damit sie für alle Antworten gelten.
add_action(
	'init',
	function () {
		if ( headers_sent() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return;
		}
		if ( is_ssl() ) {
			header( 'Strict-Transport-Security: max-age=31536000' );
		}
		header_remove( 'X-Powered-By' ); // PHP-Version (die Server-Kopfzeile setzt der Webserver)
		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-Frame-Options: SAMEORIGIN' );
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );
		header( 'Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()' );
	},
	0
);

// ---------- .htaccess: gesperrte Dateien, Kopfzeilen auch für Bilder und Dateien ----------
// Block „KBS-Schutz“, geschrieben bei admin_init (bzw. per WP-CLI kbs_schutz_htaccess()), neu bei einer neuen Fassung.

function kbs_schutz_htaccess(): bool {
	if ( get_option( 'kbs_schutz_htaccess' ) === KBS_SCHUTZ_HTACCESS_FASSUNG ) {
		return true;
	}
	$datei = ABSPATH . '.htaccess';
	if ( ! is_file( $datei ) || ! is_writable( $datei ) ) {
		return false;
	}
	require_once ABSPATH . 'wp-admin/includes/misc.php';
	$regeln = array(
		'# Schutz der Website – Snippet kbs-schutz',
		'<IfModule mod_rewrite.c>',
		'RewriteEngine On',
		'RewriteRule ^(xmlrpc\.php|readme\.html|license\.txt|wp-config-sample\.php)$ - [F,L]',
		'RewriteRule ^wp-content/kbs/daten/ - [F,L]',
		'</IfModule>',
		'<IfModule mod_headers.c>',
		'Header always set X-Content-Type-Options "nosniff"',
		'Header always set X-Frame-Options "SAMEORIGIN"',
		'Header always set Referrer-Policy "strict-origin-when-cross-origin"',
		'</IfModule>',
	);
	if ( ! insert_with_markers( $datei, 'KBS-Schutz', $regeln ) ) {
		return false;
	}
	update_option( 'kbs_schutz_htaccess', KBS_SCHUTZ_HTACCESS_FASSUNG, false );
	return true;
}
add_action( 'admin_init', 'kbs_schutz_htaccess' );
