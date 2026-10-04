<?php
/**
 * Plugin Name: KBS – Kontaktformular
 * Description: Verarbeitet das Kontaktformular (Etch-Komponente „Kontaktformular“) über admin-post.php: Prüfung der Pflichtfelder und der Datenschutz-Zustimmung, Spam-Schutz ohne Cookies (verstecktes Feld, Mindestzeit, Begrenzung je IP-Hash für eine Stunde), Versand per wp_mail an den Empfänger aus den Firmendaten. Speichert keine Anfragen. Abschaltbar unter Firmendaten › Kontaktformular (dann kein Formular und keine Annahme). Daten für Etch: {options.kbs.kontakt.…} (action, zeit, formular_aktiv, gesendet, hat_fehler, fehler).
 *
 * Gehört auf die Live-Seite. Quelle: Repository kbs, wordpress/snippets/kbs-kontakt.php
 */

defined( 'ABSPATH' ) || exit;

define( 'KBS_KONTAKT_MIN_SEKUNDEN', 4 );
define( 'KBS_KONTAKT_MAX_JE_STUNDE', 5 );

/** Formular eingeschaltet? Firmendaten › Kontaktformular (fehlt der Wert: an). */
function kbs_kontakt_formular_aktiv(): bool {
	return 'aus' !== ( ( (array) get_option( 'firmendaten', array() ) )['kontakt_formular'] ?? 'an' );
}

/** Fehlertexte zu den Codes in ?kontakt=<code> */
function kbs_kontakt_fehlertexte(): array {
	return array(
		'aus'         => 'Das Kontaktformular ist derzeit nicht verfügbar. Bitte rufen Sie uns an oder schreiben Sie uns eine E-Mail.',
		'pflicht'     => 'Bitte füllen Sie Name, E-Mail und Nachricht aus und stimmen Sie der Datenschutzerklärung zu.',
		'email'       => 'Bitte prüfen Sie Ihre E-Mail-Adresse.',
		'schnell'     => 'Das ging etwas zu schnell. Bitte senden Sie das Formular noch einmal ab.',
		'limit'       => 'Sie haben in kurzer Zeit mehrere Nachrichten gesendet. Bitte versuchen Sie es später noch einmal oder rufen Sie uns an.',
		'versand'     => 'Beim Versand ist ein Fehler aufgetreten. Bitte versuchen Sie es noch einmal oder schreiben Sie uns direkt eine E-Mail.',
	);
}

add_filter(
	'etch/dynamic_data/option',
	function ( $data ) {
		if ( is_array( $data ) ) {
			$code  = isset( $_GET['kontakt'] ) ? sanitize_key( wp_unslash( $_GET['kontakt'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
			$texte = kbs_kontakt_fehlertexte();
			$data['kbs']['kontakt'] = array(
				'action'     => esc_url_raw( admin_url( 'admin-post.php' ) ),
				'zeit'       => (string) time(),
				'formular_aktiv' => kbs_kontakt_formular_aktiv(),
				'gesendet'   => 'gesendet' === $code,
				'hat_fehler' => isset( $texte[ $code ] ),
				'fehler'     => $texte[ $code ] ?? '',
			);
		}
		return $data;
	}
);

/** Zurück zur Formularseite mit Status. Nur Pfade dieser Website. */
function kbs_kontakt_zurueck( string $code ): void {
	$pfad = isset( $_POST['kbs_zurueck'] ) ? (string) wp_unslash( $_POST['kbs_zurueck'] ) : '/kontakt/'; // phpcs:ignore WordPress.Security.NonceVerification
	if ( ! str_starts_with( $pfad, '/' ) || str_starts_with( $pfad, '//' ) ) {
		$pfad = '/kontakt/';
	}
	wp_safe_redirect( add_query_arg( 'kontakt', $code, home_url( $pfad ) ) . '#formular-meldung', 303 );
	exit;
}

function kbs_kontakt_verarbeiten(): void {
	// phpcs:disable WordPress.Security.NonceVerification -- öffentliches Formular ohne Anmeldung; Schutz über Falle, Mindestzeit und Begrenzung
	// Ausgeschaltet (Firmendaten › Kontaktformular): nichts annehmen, auch keine direkt abgeschickten Anfragen
	if ( ! kbs_kontakt_formular_aktiv() ) {
		kbs_kontakt_zurueck( 'aus' );
	}
	$feld = fn( string $k ) => isset( $_POST[ $k ] ) ? trim( sanitize_textarea_field( wp_unslash( $_POST[ $k ] ) ) ) : '';

	// Falle: Menschen sehen das Feld nicht. Bots bekommen scheinbar Erfolg.
	if ( '' !== $feld( 'website' ) ) {
		kbs_kontakt_zurueck( 'gesendet' );
	}
	$zeit = (int) $feld( 'kbs_zeit' );
	if ( ! $zeit || time() - $zeit < KBS_KONTAKT_MIN_SEKUNDEN ) {
		kbs_kontakt_zurueck( 'schnell' );
	}

	$name      = sanitize_text_field( $feld( 'name' ) );
	$firma     = sanitize_text_field( $feld( 'firma' ) );
	$email     = sanitize_email( $feld( 'email' ) );
	$telefon   = sanitize_text_field( $feld( 'telefon' ) );
	$anliegen  = sanitize_text_field( $feld( 'anliegen' ) );
	$nachricht = $feld( 'nachricht' );
	if ( '' === $name || '' === $feld( 'email' ) || '' === $nachricht || '1' !== $feld( 'datenschutz' ) ) {
		kbs_kontakt_zurueck( 'pflicht' );
	}
	if ( ! is_email( $email ) ) {
		kbs_kontakt_zurueck( 'email' );
	}

	// Begrenzung je IP – gespeichert wird nur ein Hash, höchstens eine Stunde (siehe Datenschutzerklärung)
	$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) wp_unslash( $_SERVER['REMOTE_ADDR'] ) : '';
	$key   = 'kbs_kf_' . substr( hash_hmac( 'sha256', $ip, wp_salt( 'nonce' ) ), 0, 32 );
	$zahl  = (int) get_transient( $key );
	if ( $zahl >= KBS_KONTAKT_MAX_JE_STUNDE ) {
		kbs_kontakt_zurueck( 'limit' );
	}
	set_transient( $key, $zahl + 1, HOUR_IN_SECONDS );
	// phpcs:enable

	$o          = (array) get_option( 'firmendaten', array() );
	$empfaenger = sanitize_email( (string) ( $o['kontakt_empfaenger'] ?? '' ) ) ?: sanitize_email( (string) ( $o['firma_email'] ?? '' ) ) ?: get_option( 'admin_email' );
	$betreff    = sprintf( 'Anfrage über die Website%s: %s', $anliegen ? ' – ' . $anliegen : '', $firma ?: $name );
	$text       = implode(
		"\n",
		array(
			'Neue Anfrage über das Kontaktformular der Website',
			'',
			'Name:     ' . $name,
			'Firma:    ' . ( $firma ?: '–' ),
			'E-Mail:   ' . $email,
			'Telefon:  ' . ( $telefon ?: '–' ),
			'Anliegen: ' . ( $anliegen ?: '–' ),
			'',
			'Nachricht:',
			$nachricht,
			'',
			'Die Zustimmung zur Datenschutzerklärung wurde erteilt.',
			'Gesendet am ' . wp_date( 'd.m.Y \u\m H:i' ) . ' Uhr von ' . home_url( '/' ),
		)
	);
	$headers = array( 'Content-Type: text/plain; charset=UTF-8', 'Reply-To: ' . str_replace( array( "\r", "\n", '<', '>' ), '', $name ) . ' <' . $email . '>' );

	kbs_kontakt_zurueck( wp_mail( $empfaenger, $betreff, $text, $headers ) ? 'gesendet' : 'versand' );
}

add_action( 'admin_post_nopriv_kbs_kontakt', 'kbs_kontakt_verarbeiten' );
add_action( 'admin_post_kbs_kontakt', 'kbs_kontakt_verarbeiten' );
