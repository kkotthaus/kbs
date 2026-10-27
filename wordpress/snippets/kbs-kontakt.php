<?php
/**
 * Plugin Name: KBS – Kontaktformular
 * Description: Verbindet das WS-Form-Formular „Kontakt – Anfrage“ mit der Website: setzt die Formular-ID in die Komponente „Kontaktformular“ (Block wsf-block/form-add mit kbsFormular „kontakt“), schickt die Mail an den Empfänger aus den Firmendaten, baut den Betreff aus Anliegen und Name und nimmt nichts an, solange das Formular unter Firmendaten › Kontaktformular ausgeschaltet ist. Felder erkannt an der Klasse kbs-kf-<schlüssel>. Angelegt wird das Formular mit wordpress/tools/kontakt-formular.php. Daten für Etch: {options.kbs.kontakt.formular_aktiv}.
 *
 * Gehört auf die Live-Seite. Quelle: Repository kbs, wordpress/snippets/kbs-kontakt.php
 */

defined( 'ABSPATH' ) || exit;

define( 'KBS_KONTAKT_FORMULAR_NAME', 'Kontakt – Anfrage' );
define( 'KBS_KONTAKT_FRIST_TAGE', 180 ); // gespeicherte Anfragen löscht WS Form nach dieser Frist (Datenschutzerklärung nennt sie)

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
