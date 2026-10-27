<?php
/**
 * Formular „Kontakt – Anfrage“ in WS Form anlegen bzw. neu aufbauen (WS Form LITE, Formulare leben in der Datenbank).
 * Felder: Name, Firma, E-Mail, Telefon, Anliegen, Nachricht, Datenschutz. Die Felder tragen die Klasse kbs-kf-<schlüssel> –
 * daran erkennt snippets/kbs-kontakt.php das Formular (Empfänger aus den Firmendaten, Betreff, Schalter an/aus).
 * Aktionen: speichern (KBS_KONTAKT_FRIST_TAGE), Mail an KBS (Antwort an den Besucher), Danke-Meldung.
 * Die Formular-ID je Umgebung steht danach in der Option kbs_kontakt_formular (die Komponente „Kontaktformular“ bindet sie ein).
 * Zum Schluss wird das veröffentlichte Formular als JSON nach wordpress/formulare/kontakt-anfrage.json geschrieben
 * (nur wenn KBS_FORMULARE auf den Ordner zeigt, also lokal). Live ohne WP-CLI: dieses JSON in WS Form importieren –
 * das Snippet findet das Formular dann am Namen.
 *
 * Aufruf: wp eval-file wordpress/tools/kontakt-formular.php [neu] --user=<admin>
 *   ohne „neu“: vorhandenes Formular (Option kbs_kontakt_formular) neu aufbauen; mit „neu“: neues Formular anlegen.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WS_Form_Form' ) || ! defined( 'WS_FORM_PLUGIN_DIR_PATH' ) ) {
	WP_CLI::error( 'WS Form ist nicht aktiv.' );
}
if ( ! function_exists( 'kbs_kontakt_felder' ) ) {
	WP_CLI::error( 'Snippet kbs-kontakt.php muss aktiv sein.' );
}
$args = (array) ( $args ?? array() );
$neu  = in_array( 'neu', $args, true );

$frist   = defined( 'KBS_KONTAKT_FRIST_TAGE' ) ? (int) KBS_KONTAKT_FRIST_TAGE : 180;
$vorlage = json_decode( (string) file_get_contents( WS_FORM_PLUGIN_DIR_PATH . 'includes/templates/form/contact/wsf-form-contact-us-extended.json' ) );
if ( ! $vorlage ) {
	WP_CLI::error( 'Vorlage von WS Form nicht gefunden – hat sich der Aufbau des Plugins geändert?' );
}

// Muster je Feldtyp aus der Vorlage (vollständige Meta-Daten, wie WS Form sie erwartet)
$muster = array();
foreach ( $vorlage->groups as $g ) {
	foreach ( $g->sections as $s ) {
		foreach ( $s->fields as $f ) {
			$muster[ $f->type ] = $muster[ $f->type ] ?? $f;
		}
	}
}
foreach ( array( 'text', 'email', 'tel', 'select', 'textarea', 'checkbox', 'submit' ) as $typ ) {
	if ( empty( $muster[ $typ ] ) ) {
		WP_CLI::error( "Feldtyp $typ fehlt in der Vorlage." );
	}
}

$nr   = 920000;
$sort = 0;
$feld = function ( string $typ, string $label, string $schluessel, array $meta = array() ) use ( $muster, &$nr, &$sort ) {
	$f             = json_decode( wp_json_encode( $muster[ $typ ] ) );
	$f->id         = (string) ++$nr;
	$f->label      = $label;
	$f->sort_index = (string) ++$sort;
	foreach ( array( 'required' => '', 'help' => '', 'default_value' => '', 'placeholder' => '', 'pattern' => '', 'breakpoint_size_25' => '', 'invalid_feedback' => '' ) as $k => $leer ) {
		if ( isset( $f->meta->$k ) ) {
			$f->meta->$k = $leer;
		}
	}
	foreach ( $meta as $k => $wert ) {
		$f->meta->$k = $wert;
	}
	$f->meta->class_field_wrapper = 'kbs-kf-' . $schluessel;
	return $f;
};

// Anliegen wie in der bisherigen Komponente
$anliegen = array( 'IT-Betreuung', 'IT-Beratung', 'Neue Website', 'Website-Wartung', 'Fernwartung', 'Sonstiges' );

$felder = array(
	'name'        => $feld( 'text', 'Name', 'name', array( 'required' => 'on', 'autocomplete' => 'name', 'breakpoint_size_25' => '6' ) ),
	'firma'       => $feld( 'text', 'Firma', 'firma', array( 'autocomplete' => 'organization', 'breakpoint_size_25' => '6' ) ),
	'email'       => $feld( 'email', 'E-Mail', 'email', array( 'required' => 'on', 'autocomplete' => 'email', 'breakpoint_size_25' => '6' ) ),
	'telefon'     => $feld( 'tel', 'Telefon', 'telefon', array( 'autocomplete' => 'tel', 'intl_tel_input' => '', 'breakpoint_size_25' => '6' ) ),
	'anliegen'    => $feld( 'select', 'Worum geht es?', 'anliegen', array( 'select_field_label' => '0', 'select_field_value' => '0', 'placeholder_row' => 'Bitte wählen' ) ),
	'nachricht'   => $feld( 'textarea', 'Nachricht', 'nachricht', array( 'required' => 'on', 'rows' => '6' ) ),
	'datenschutz' => $feld( 'checkbox', 'Datenschutz', 'datenschutz', array( 'invalid_feedback' => 'Bitte bestätigen Sie den Hinweis zum Datenschutz.' ) ),
	'senden'      => $feld( 'submit', 'Anfrage senden', 'senden', array( 'class_field_button_type' => 'primary' ) ),
);

$auswahl                 = $felder['anliegen']->meta->data_grid_select;
$auswahl->columns        = array( (object) array( 'id' => 0, 'label' => 'Anliegen' ) );
$auswahl->groups         = array( $auswahl->groups[0] );
$auswahl->groups[0]->label = 'Anliegen';
$auswahl->groups[0]->rows  = array_map(
	fn( $i, $a ) => (object) array( 'id' => $i + 1, 'default' => '', 'disabled' => '', 'required' => '', 'hidden' => '', 'data' => array( $a ) ),
	array_keys( $anliegen ),
	$anliegen
);

// Spalte 0 = Beschriftung (mit Link), Spalte 1 = gespeicherter Wert. Ein Wert mit HTML lässt WS Form beim Speichern scheitern.
$zustimmung                       = $felder['datenschutz']->meta->data_grid_checkbox;
$zustimmung->columns              = array( (object) array( 'id' => 0, 'label' => 'Beschriftung' ), (object) array( 'id' => 1, 'label' => 'Wert' ) );
$zustimmung->groups[0]->rows      = array(
	(object) array( 'id' => 1, 'default' => '', 'required' => 'on', 'disabled' => '', 'hidden' => '', 'data' => array( 'Ich bin einverstanden, dass meine Angaben zur Bearbeitung der Anfrage verwendet werden. Mehr dazu in der <a href="/datenschutz/#kontakt">Datenschutzerklärung</a>.', 'zugestimmt' ) ),
);
$felder['datenschutz']->meta->checkbox_field_label = '0';
$felder['datenschutz']->meta->checkbox_field_value = '1';

$form                   = json_decode( wp_json_encode( $vorlage ) );
$form->label            = KBS_KONTAKT_FORMULAR_NAME;
$form->groups           = array( $form->groups[0] );
$form->groups[0]->label = 'Anfrage';
$form->groups[0]->sections = array( $form->groups[0]->sections[0] );
$form->groups[0]->sections[0]->fields = array_values( $felder );
$m                      = $form->meta;
$m->honeypot            = 'on'; // Spamschutz ohne fremden Dienst
$m->submit_lock         = 'on';
$m->submit_show_errors  = 'on';
$m->label_required      = 'on';

// Aktionen: speichern, Mail an KBS (Empfänger und Betreff setzt kbs-kontakt.php aus den Firmendaten), Danke-Meldung
$fid      = fn( string $k ) => '#field(' . $felder[ $k ]->id . ')';
$firma    = (array) get_option( 'firmendaten', array() );
$absender = sanitize_email( (string) ( $firma['firma_email'] ?? '' ) ) ?: '#blog_admin_email';
$aktionen = array(
	array( 'Anfrage speichern', array( 'id' => 'database', 'meta' => array( 'action_database_field_filter' => '', 'action_database_field_filter_mapping' => '', 'action_database_expire' => 'delete', 'action_database_expire_duration' => (string) $frist ), 'events' => array( 'submit' ) ) ),
	array(
		'Mail an KBS',
		array(
			'id'     => 'email',
			'meta'   => array(
				'action_email_content_type'        => 'text/html',
				'action_email_from_email'          => $absender,
				'action_email_from_name'           => 'Website #blog_name',
				'action_email_to'                  => array( array( 'action_email_email' => $absender, 'action_email_name' => '' ) ),
				'action_email_cc'                  => array(),
				'action_email_bcc'                 => array(),
				'action_email_reply_to_email'      => $fid( 'email' ),
				'action_email_subject'             => 'Anfrage über die Website',
				'action_email_message_editor'      => 'text_editor',
				'action_email_message_wrapper'     => 'on',
				'action_email_message_textarea'    => '',
				'action_email_message_text_editor' => '<p>Über das Kontaktformular der Website ist eine Anfrage gekommen. Mit „Antworten“ geht die Mail direkt an den Absender.</p><p>#email_submission</p>',
				'action_email_message_html_editor' => '',
				'action_email_attachments'         => array(),
				'action_email_headers'             => array(),
				'action_email_charset'             => '#blog_charset',
			),
			'events' => array( 'submit' ),
		),
	),
	array(
		'Meldung',
		array(
			'id'     => 'message',
			'meta'   => array(
				'action_message_message'      => '<p><strong>Vielen Dank für Ihre Anfrage!</strong> Wir melden uns in der Regel innerhalb eines Werktags bei Ihnen.</p>',
				'action_message_type'         => 'success',
				'action_message_method'       => 'before',
				'action_message_form_hide'    => 'on',
				'action_message_clear'        => 'on',
				'action_message_scroll_top'   => 'on',
				'action_message_duration'     => '',
				'action_message_message_hide' => '',
				'action_message_form_show'    => '',
			),
			'events' => array( 'submit' ),
		),
	),
);
$zeilen = array();
foreach ( $aktionen as $i => list( $name, $aktion ) ) {
	$zeilen[] = (object) array( 'id' => $i + 1, 'default' => '', 'required' => '', 'disabled' => '', 'hidden' => '', 'data' => array( $name, wp_json_encode( $aktion ) ) );
}
$m->action->groups[0]->rows = $zeilen;

// Anlegen bzw. neu aufbauen – derselbe Ablauf wie der JSON-Import in WS Form (api_post_upload_json)
$id = $neu ? 0 : (int) get_option( 'kbs_kontakt_formular', 0 );
$ws = new WS_Form_Form();
if ( $id ) {
	$ws->id = $id;
	try {
		$ws->db_read( false );
	} catch ( Exception $e ) {
		$id = 0;
	}
}
try {
	if ( ! $id ) {
		$ws = new WS_Form_Form();
		$ws->db_create();
		WP_CLI::log( 'Formular angelegt: ' . $ws->id );
	} else {
		$ws->id = $id;
		WP_CLI::log( 'Formular ' . $id . ' wird neu aufgebaut.' );
	}
	unset( $form->id );
	$ws->db_import_reset();
	$ws->db_update_from_object( $form, true, true );
	$ws->db_action_repair();
	$ws->db_meta_repair();
	$ws->db_checksum();
	$ws->db_publish();
} catch ( Exception $e ) {
	WP_CLI::error( 'WS Form: ' . $e->getMessage() );
}
update_option( 'kbs_kontakt_formular', (int) $ws->id, false );

// Prüfen: Formular mit allen Feldern
$gelesen     = new WS_Form_Form();
$gelesen->id = $ws->id;
$objekt      = $gelesen->db_read( true, true, false, false, true );
$gefunden    = kbs_kontakt_felder( $objekt );
WP_CLI::log( 'Felder: ' . implode( ', ', array_map( fn( $k, $i ) => "$k=$i", array_keys( $gefunden ), $gefunden ) ) );
$fehlt = array_diff( array( 'name', 'firma', 'email', 'telefon', 'anliegen', 'nachricht', 'datenschutz' ), array_keys( $gefunden ) );
if ( $fehlt ) {
	WP_CLI::warning( 'Felder fehlen: ' . implode( ', ', $fehlt ) );
}

// Stand als JSON ins Repo (Umgebungsvariable KBS_FORMULARE = Ordner wordpress/formulare, nur lokal gesetzt)
$ordner = (string) getenv( 'KBS_FORMULARE' );
if ( $ordner && is_dir( $ordner ) ) {
	file_put_contents( trailingslashit( $ordner ) . 'kontakt-anfrage.json', wp_json_encode( $objekt, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n" );
	WP_CLI::log( 'JSON geschrieben: ' . trailingslashit( $ordner ) . 'kontakt-anfrage.json' );
}
WP_CLI::success( 'Formular ' . $ws->id . ' veröffentlicht (Option kbs_kontakt_formular).' );
