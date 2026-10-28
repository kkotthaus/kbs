<?php
/**
 * Plugin Name: KBS – Kontaktformular
 * Description: Verbindet das WS-Form-Formular „Kontakt – Anfrage“ mit der Website: setzt die Formular-ID in die Komponente „Kontaktformular“ (Block wsf-block/form-add mit kbsFormular „kontakt“), schickt die Mail an den Empfänger aus den Firmendaten, baut den Betreff aus Anliegen und Name und nimmt nichts an, solange das Formular unter Firmendaten › Kontaktformular ausgeschaltet ist. Spamschutz ohne fremden Dienst (wie kids4golf): Honeypot von WS Form, Zeitfeld (zu schnell oder ohne Browser), Name aus Zufallsbuchstaben – solche Eingänge landen in WS Form als Spam, ohne Mail; höchstens KBS_KONTAKT_MAILS_JE_STUNDE Mails je Stunde, darüber nur in WS Form. Felder erkannt an der Klasse kbs-kf-<schlüssel>. Angelegt wird das Formular mit wordpress/tools/kontakt-formular.php. Daten für Etch: {options.kbs.kontakt.formular_aktiv}.
 *
 * Gehört auf die Live-Seite. Quelle: Repository kbs, wordpress/snippets/kbs-kontakt.php
 */

defined( 'ABSPATH' ) || exit;

define( 'KBS_KONTAKT_FORMULAR_NAME', 'Kontakt – Anfrage' );
define( 'KBS_KONTAKT_FRIST_TAGE', 180 ); // gespeicherte Anfragen löscht WS Form nach dieser Frist (Datenschutzerklärung nennt sie)
define( 'KBS_KONTAKT_MIN_SEKUNDEN', 4 ); // schneller abgeschickt = Roboter
define( 'KBS_KONTAKT_MAILS_JE_STUNDE', 20 ); // Notbremse: darüber bleiben Anfragen nur in WS Form

/** Formular eingeschaltet? Firmendaten › Kontaktformular (fehlt der Wert: an). */
function kbs_kontakt_formular_aktiv(): bool {
	return 'aus' !== ( ( (array) get_option( 'firmendaten', array() ) )['kontakt_formular'] ?? 'an' );
}

/** Empfänger der Anfragen: Firmendaten › Empfänger Kontaktformular, sonst E-Mail der Firma, sonst Admin-E-Mail. */
function kbs_kontakt_empfaenger(): string {
	$o = (array) get_option( 'firmendaten', array() );
	return sanitize_email( (string) ( $o['kontakt_empfaenger'] ?? '' ) ) ?: sanitize_email( (string) ( $o['firma_email'] ?? '' ) ) ?: (string) get_option( 'admin_email' );
}

/** ID des Formulars in dieser Umgebung: Option kbs_kontakt_formular, sonst am Namen gefunden (z. B. nach einem JSON-Import). */
function kbs_kontakt_formular_id(): int {
	$id = (int) get_option( 'kbs_kontakt_formular', 0 );
	if ( $id ) {
		return $id;
	}
	global $wpdb;
	$tabelle = $wpdb->prefix . 'wsf_form';
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $tabelle ) ) !== $tabelle ) {
		return 0;
	}
	$id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$tabelle} WHERE label = %s AND status = 'publish' ORDER BY id DESC LIMIT 1", KBS_KONTAKT_FORMULAR_NAME ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	if ( $id ) {
		update_option( 'kbs_kontakt_formular', $id, false );
	}
	return $id;
}

/** Ist das ein Formular-Objekt (bzw. eine ID) des Kontaktformulars? */
function kbs_kontakt_ist_formular( $form ): bool {
	$id = is_object( $form ) ? (int) ( $form->id ?? 0 ) : (int) $form;
	return $id && kbs_kontakt_formular_id() === $id;
}

/** Feld-IDs nach Schlüssel (Klasse kbs-kf-<schlüssel> am Feld). */
function kbs_kontakt_felder( $form ): array {
	$felder = array();
	foreach ( (array) ( $form->groups ?? array() ) as $g ) {
		foreach ( (array) ( $g->sections ?? array() ) as $s ) {
			foreach ( (array) ( $s->fields ?? array() ) as $f ) {
				if ( preg_match( '/\bkbs-kf-([a-z]+)\b/', (string) ( $f->meta->class_field_wrapper ?? '' ), $m ) ) {
					$felder[ $m[1] ] = (int) $f->id;
				}
			}
		}
	}
	return $felder;
}

/** Eingegebener Wert eines Feldes aus einer Übermittlung. */
function kbs_kontakt_wert( $submit, int $feld_id ): string {
	$w = $submit->meta[ 'field_' . $feld_id ] ?? '';
	$w = is_array( $w ) ? ( $w['value'] ?? '' ) : $w;
	return sanitize_text_field( is_array( $w ) ? implode( ', ', $w ) : (string) $w );
}

add_filter(
	'etch/dynamic_data/option',
	function ( $data ) {
		if ( is_array( $data ) ) {
			$data['kbs']['kontakt'] = array( 'formular_aktiv' => kbs_kontakt_formular_aktiv() );
		}
		return $data;
	}
);

// Komponente „Kontaktformular“: Block wsf-block/form-add mit kbsFormular „kontakt“ bekommt die ID dieser Umgebung
add_filter(
	'render_block_data',
	function ( $block ) {
		if ( 'wsf-block/form-add' === ( $block['blockName'] ?? '' ) && 'kontakt' === ( $block['attrs']['kbsFormular'] ?? '' ) ) {
			$block['attrs']['form_id'] = (string) kbs_kontakt_formular_id();
		}
		return $block;
	}
);

// Ausgeschaltet (Firmendaten › Kontaktformular): auch direkt abgeschickte Anfragen nicht annehmen
add_filter(
	'wsf_submit_field_validate',
	function ( $fehler, $feld_id, $wert, $wiederholung, $modus, $submit ) {
		$form = $submit->form_object ?? null;
		if ( 'submit' !== $modus || ! is_object( $form ) || ! kbs_kontakt_ist_formular( (int) ( $submit->form_id ?? 0 ) ) || kbs_kontakt_formular_aktiv() ) {
			return $fehler;
		}
		if ( (int) $feld_id === ( kbs_kontakt_felder( $form )['email'] ?? 0 ) ) {
			$fehler[] = array( 'action' => 'field_invalid_feedback', 'field_id' => (int) $feld_id, 'section_repeatable_index' => $wiederholung, 'message' => 'Das Kontaktformular ist derzeit nicht verfügbar. Bitte rufen Sie uns an oder schreiben Sie uns eine E-Mail.' );
		}
		return $fehler;
	},
	10,
	6
);

// Empfänger immer aus den Firmendaten – eine Änderung dort gilt sofort, ohne das Formular anzufassen
add_filter(
	'wsf_action_email_to',
	function ( $an, $form ) {
		return kbs_kontakt_ist_formular( $form ) ? array( kbs_kontakt_empfaenger() ) : $an;
	},
	10,
	2
);

// Betreff wie bisher: „Anfrage über die Website – <Anliegen>: <Firma oder Name>“
add_filter(
	'wsf_action_email_subject',
	function ( $betreff, $form, $submit ) {
		if ( ! kbs_kontakt_ist_formular( $form ) ) {
			return $betreff;
		}
		$f    = kbs_kontakt_felder( $form );
		$wert = fn( string $k ) => isset( $f[ $k ] ) ? kbs_kontakt_wert( $submit, $f[ $k ] ) : '';
		return sprintf( 'Anfrage über die Website%s: %s', $wert( 'anliegen' ) ? ' – ' . $wert( 'anliegen' ) : '', $wert( 'firma' ) ?: $wert( 'name' ) );
	},
	10,
	3
);

// ---------- Spamschutz (zusätzlich zum Honeypot von WS Form, ohne fremden Dienst) ----------

/**
 * Grund, warum eine Anfrage Spam ist; leer = in Ordnung.
 * - ohne Zeitfeld: ohne Browser bzw. ohne Skript abgeschickt (WS Form braucht ohnehin JavaScript).
 * - zu schnell: weniger als KBS_KONTAKT_MIN_SEKUNDEN zwischen Laden der Seite und Absenden.
 * - Name aus Zufallsbuchstaben wie „dHeVUXhroMNsrRAVEEF“.
 */
function kbs_kontakt_spam_grund( string $name ): string {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WS Form prüft die Anfrage selbst
	$t = isset( $_POST['kbs_kf_t'] ) ? sanitize_text_field( wp_unslash( $_POST['kbs_kf_t'] ) ) : '';
	if ( '' === $t || ! ctype_digit( $t ) ) {
		return 'ohne Zeitfeld';
	}
	if ( (int) $t < KBS_KONTAKT_MIN_SEKUNDEN ) {
		return 'zu schnell: ' . (int) $t . ' s';
	}
	if ( kbs_kontakt_name_zufall( $name ) ) {
		return 'Name aus Zufallsbuchstaben';
	}
	return '';
}

/** Sieht ein Name wie Zufallsbuchstaben aus? Ein Wort ab 8 Buchstaben mit mindestens 3 Großbuchstaben mitten im Wort (nicht alles groß). */
function kbs_kontakt_name_zufall( string $name ): bool {
	foreach ( preg_split( '/[\s\-\'’.,]+/u', trim( $name ) ) as $wort ) {
		if ( mb_strlen( $wort ) < 8 || mb_strtoupper( $wort ) === $wort ) {
			continue;
		}
		if ( preg_match_all( '/\p{Lu}/u', mb_substr( $wort, 1 ) ) >= 3 ) {
			return true;
		}
	}
	return false;
}

// Spam: Stufe 100 – WS Form speichert die Anfrage mit Status „Spam“ und führt keine weitere Aktion aus (keine Mail)
add_filter(
	'wsf_submit_spam_check',
	function ( $stufe, $form, $submit ) {
		if ( 'submit' !== ( $submit->post_mode ?? '' ) || ! kbs_kontakt_ist_formular( $form ) ) {
			return $stufe;
		}
		$f     = kbs_kontakt_felder( $form );
		$grund = kbs_kontakt_spam_grund( isset( $f['name'] ) ? kbs_kontakt_wert( $submit, $f['name'] ) : '' );
		if ( '' === $grund ) {
			return $stufe;
		}
		error_log( 'KBS Kontaktformular: Anfrage als Spam abgelehnt (' . $grund . ')' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		return defined( 'WS_FORM_SPAM_LEVEL_MAX' ) ? WS_FORM_SPAM_LEVEL_MAX : 100;
	},
	10,
	3
);

// Notbremse: höchstens KBS_KONTAKT_MAILS_JE_STUNDE Mails je Stunde; darüber bleibt die Anfrage nur in WS Form › Übermittlungen
add_filter(
	'wsf_action_post_do',
	function ( $ausfuehren, $form, $submit, $zeile, $nur_db, $config ) {
		if ( ! $ausfuehren || 'email' !== ( $config['id'] ?? '' ) || ! kbs_kontakt_ist_formular( $form ) ) {
			return $ausfuehren;
		}
		$schwelle = class_exists( 'WS_Form_Common' ) ? absint( WS_Form_Common::get_object_meta_value( $form, 'spam_threshold', 50 ) ) : 50;
		if ( null !== ( $submit->spam_level ?? null ) && (int) $submit->spam_level >= $schwelle ) {
			return $ausfuehren; // Spam: WS Form lässt die Mail ohnehin weg, zählt nicht mit
		}
		$zahl = (int) get_transient( 'kbs_kontakt_stunde' );
		if ( $zahl >= KBS_KONTAKT_MAILS_JE_STUNDE ) {
			error_log( 'KBS Kontaktformular: Stundengrenze erreicht, Anfrage bleibt nur in WS Form' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return false;
		}
		set_transient( 'kbs_kontakt_stunde', $zahl + 1, HOUR_IN_SECONDS );
		return $ausfuehren;
	},
	10,
	6
);

// Zeitfeld: Sekunden seit dem Laden der Seite, beim Absenden ins Formular geschrieben (WS Form schickt alle Felder des Formulars mit)
add_filter(
	'render_block',
	function ( $html, $block ) {
		if ( 'wsf-block/form-add' === ( $block['blockName'] ?? '' ) && 'kontakt' === ( $block['attrs']['kbsFormular'] ?? '' ) ) {
			add_action( 'wp_footer', 'kbs_kontakt_zeitfeld_skript', 99 );
		}
		return $html;
	},
	10,
	2
);

function kbs_kontakt_zeitfeld_skript() {
	static $fertig = false;
	if ( $fertig ) {
		return;
	}
	$fertig = true;
	?>
<script>(function(){var t0=Date.now();document.addEventListener('submit',function(e){var f=e.target;if(!f||!f.querySelector||!f.querySelector('.kbs-kf-email'))return;var i=f.querySelector('input[name="kbs_kf_t"]');if(!i){i=document.createElement('input');i.type='hidden';i.name='kbs_kf_t';f.appendChild(i);}i.value=String(Math.round((Date.now()-t0)/1000));},true);})();</script>
	<?php
}
