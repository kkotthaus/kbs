<?php
/**
 * Plugin Name: KBS – Firmendaten
 * Description: Einstellungsseite „Firmendaten“ (Meta Box, Option firmendaten) mit Firma, Kontakt, Rechtlichem, PC-Visit-Downloads (mit Logo) und Angebot. Stellt die Werte Etch fertig aufbereitet bereit: {options.kbs.firma.…} und {options.kbs.pcvisit.…}. Keine Shortcodes.
 *
 * Gehört auf die Live-Seite. Quelle: Repository kbs, wordpress/snippets/kbs-firma.php
 */

defined( 'ABSPATH' ) || exit;

/*
 * Einstellungsseite und Felder im Code (statt im Meta-Box-Builder): Das Repo bleibt die einzige Quelle,
 * die Seite erscheint trotzdem normal im Backend. Pflegen dürfen alle, die Seiten bearbeiten dürfen (edit_pages).
 */
add_filter(
	'mb_settings_pages',
	function ( $seiten ) {
		$seiten[] = array(
			'id'            => 'firmendaten',
			'option_name'   => 'firmendaten',
			'menu_title'    => 'Firmendaten',
			'page_title'    => 'Firmendaten',
			'icon_url'      => 'dashicons-building',
			'position'      => 3,
			'capability'    => 'edit_pages',
			'style'         => 'no-boxes',
			'columns'       => 1,
			'tab_style'     => 'left',
			'submit_button' => 'Speichern',
			'message'       => 'Gespeichert. Die Änderungen sind sofort auf der Website sichtbar.',
			'tabs'          => array(
				'firma'   => 'Firma & Kontakt',
				'recht'   => 'Impressum & Datenschutz',
				'pcvisit' => 'PC-Visit',
				'angebot' => 'Angebot',
			),
		);
		return $seiten;
	}
);

add_filter(
	'rwmb_meta_boxes',
	function ( $boxen ) {
		$box = fn( string $id, string $tab, string $titel, array $felder ) => array(
			'id'             => 'firmendaten-' . $id,
			'title'          => $titel,
			'settings_pages' => 'firmendaten',
			'tab'            => $tab,
			'fields'         => $felder,
		);
		$text = fn( string $id, string $name, string $desc = '', array $extra = array() ) => array_merge( array( 'id' => $id, 'name' => $name, 'type' => 'text', 'desc' => $desc, 'size' => 60 ), $extra );

		$boxen[] = $box(
			'firma',
			'firma',
			'Firma & Kontakt',
			array(
				$text( 'firma_name', 'Firmenname', 'Vollständig mit Rechtsform, z. B. für Impressum und Footer.' ),
				$text( 'firma_kurzname', 'Kurzname', 'Für die Wortmarke im Header (erstes Wort groß, Rest darunter).' ),
				$text( 'firma_claim', 'Kurzbeschreibung', 'Ein Satz, z. B. im Footer.' ),
				$text( 'firma_strasse', 'Straße und Hausnummer' ),
				$text( 'firma_plz', 'PLZ', '', array( 'size' => 10 ) ),
				$text( 'firma_ort', 'Ort' ),
				$text( 'firma_region', 'Einzugsgebiet', 'Orte, in denen Sie Kunden betreuen – erscheint in Texten („… in Burscheid, Leichlingen …“).' ),
				$text( 'firma_telefon', 'Telefon', 'Lesbar formatiert, z. B. 02174 666 47 17. Der Anruf-Link wird daraus erzeugt.' ),
				$text( 'firma_fax', 'Telefax', 'Leer = nicht anzeigen.' ),
				array( 'id' => 'firma_email', 'name' => 'E-Mail', 'type' => 'email', 'size' => 60 ),
				array( 'id' => 'firma_erreichbarkeit', 'name' => 'Erreichbarkeit', 'type' => 'textarea', 'rows' => 3, 'desc' => 'z. B. Bürozeiten, eine Angabe je Zeile. Leer = Abschnitt ausgeblendet.' ),
				array( 'id' => 'firma_logo', 'name' => 'Logo', 'type' => 'single_image', 'desc' => 'Optional. Ohne Bild erscheint das mitgelieferte Firmenlogo (wp-content/kbs/medien/kbs-logo.svg).' ),
				array( 'id' => 'firma_logo_dunkel', 'name' => 'Logo für dunkle Flächen', 'type' => 'single_image', 'desc' => 'Optional, für den Footer und den Header im dunklen Farbschema (helle Schrift). Ohne Angabe: das hochgeladene Logo bzw. die mitgelieferte helle Variante.' ),
				array( 'id' => 'kontakt_empfaenger', 'name' => 'Empfänger Kontaktformular', 'type' => 'email', 'size' => 60, 'desc' => 'Leer = E-Mail-Adresse der Firma.' ),
			)
		);
		$boxen[] = $box(
			'recht',
			'recht',
			'Impressum & Datenschutz',
			array(
				$text( 'recht_vertretung', 'Geschäftsführer', 'Vertretungsberechtigte Person(en).' ),
				$text( 'recht_registergericht', 'Registergericht', 'z. B. Amtsgericht Köln' ),
				$text( 'recht_registernummer', 'Registernummer', 'z. B. HRB 12345' ),
				$text( 'recht_ust_id', 'USt-IdNr.' ),
				$text( 'recht_verantwortlich', 'Verantwortlich nach § 18 Abs. 2 MStV', 'Name und Anschrift.' ),
				array( 'id' => 'recht_hoster', 'name' => 'Hoster der Website', 'type' => 'textarea', 'rows' => 4, 'desc' => 'Name und Anschrift des Hosters für die Datenschutzerklärung, eine Angabe je Zeile. Leer = allgemeiner Hinweis.' ),
			)
		);
		$boxen[] = $box(
			'pcvisit',
			'pcvisit',
			'PC-Visit',
			array(
				array( 'id' => 'pcvisit_kunden_url', 'name' => 'Download Quick Support', 'type' => 'url', 'size' => 80, 'desc' => 'Link zum Kunden-Modul (spontane Hilfe). Leer = Karte ausgeblendet.' ),
				array( 'id' => 'pcvisit_host_url', 'name' => 'Download Host', 'type' => 'url', 'size' => 80, 'desc' => 'Link zum Host-Modul (dauerhafte Betreuung). Leer = Karte ausgeblendet.' ),
				array( 'id' => 'pcvisit_logo', 'name' => 'Logo', 'type' => 'single_image', 'desc' => 'Optional. Ohne Bild erscheint das mitgelieferte PC-Visit-Signet (wp-content/kbs/medien/pcvisit-signet.svg).' ),
				$text( 'pcvisit_hinweis', 'Hinweis', 'Erscheint unter den Downloads, z. B. „Bitte starten Sie die Fernwartung erst nach telefonischer Absprache.“' ),
			)
		);
		$boxen[] = $box(
			'angebot',
			'angebot',
			'Angebot',
			array(
				$text( 'angebot_titel', 'Titel', 'z. B. „Kostenloses Erstgespräch“ – Buttons und Aufrufe auf der ganzen Website.' ),
				array( 'id' => 'angebot_text', 'name' => 'Text', 'type' => 'textarea', 'rows' => 3, 'desc' => 'Ein bis zwei Sätze zum Angebot.' ),
			)
		);
		return $boxen;
	}
);

/** Bild-URL aus einer Anhang-ID (single_image speichert die ID). */
function kbs_firma_bild( $id ): string {
	$id = is_array( $id ) ? (int) ( $id['ID'] ?? 0 ) : (int) $id;
	return $id ? (string) wp_get_attachment_image_url( $id, 'medium' ) : '';
}

/** Firmendaten für Etch, fertig formatiert. */
function kbs_firma_etch(): array {
	$o = (array) get_option( 'firmendaten', array() );
	$s = fn( string $k ) => trim( (string) ( $o[ $k ] ?? '' ) );

	$kurz   = $s( 'firma_kurzname' ) ?: $s( 'firma_name' );
	$teile  = preg_split( '/\s+/', $kurz, 2 );
	$tel    = $s( 'firma_telefon' );
	$telnr  = preg_replace( '/[^0-9+]/', '', $tel );
	// Deutsche Vorwahl mit führender 0 in internationales Format für tel:-Links
	if ( '' !== $telnr && '+' !== $telnr[0] ) {
		$telnr = '+49' . ltrim( $telnr, '0' );
	}
	// Logo aus den Firmendaten, sonst das mitgelieferte Firmenlogo (wordpress/medien/kbs-logo*.svg).
	// Für dunkle Flächen (Footer, Header im dunklen Schema) eine helle Variante.
	$eigen  = kbs_firma_bild( $o['firma_logo'] ?? 0 );
	$logo   = $eigen ?: content_url( 'kbs/medien/kbs-logo.svg' );
	$dunkel = kbs_firma_bild( $o['firma_logo_dunkel'] ?? 0 ) ?: ( $eigen ?: content_url( 'kbs/medien/kbs-logo-hell.svg' ) );
	$anschrift = trim( $s( 'firma_strasse' ) . ', ' . $s( 'firma_plz' ) . ' ' . $s( 'firma_ort' ), ', ' );
	$zeilen = fn( string $k ) => implode( '<br>', array_map( 'esc_html', array_filter( array_map( 'trim', explode( "\n", $s( $k ) ) ) ) ) );

	$daten = array(
		'name'          => $s( 'firma_name' ),
		'kurzname'      => $kurz,
		'wortmarke_1'   => $teile[0] ?? '',
		'wortmarke_2'   => trim( ( $teile[1] ?? '' ) . ( str_contains( $s( 'firma_name' ), 'GmbH' ) && ! str_contains( $kurz, 'GmbH' ) ? ' GmbH' : '' ) ),
		'initialen'     => implode( '', array_map( fn( $w ) => mb_substr( $w, 0, 1 ), array_slice( preg_split( '/\s+/', $kurz ), 0, 3 ) ) ),
		'claim'         => $s( 'firma_claim' ),
		'strasse'       => $s( 'firma_strasse' ),
		'plz'           => $s( 'firma_plz' ),
		'ort'           => $s( 'firma_ort' ),
		'region'        => $s( 'firma_region' ) ?: $s( 'firma_ort' ),
		'anschrift'     => $anschrift,
		'route_url'     => 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode( $s( 'firma_name' ) . ', ' . $anschrift ),
		'telefon'       => $tel,
		'telefon_href'  => 'tel:' . $telnr,
		'fax'           => $s( 'firma_fax' ),
		'email'         => $s( 'firma_email' ),
		'erreichbarkeit' => $zeilen( 'firma_erreichbarkeit' ),
		'logo'          => $logo,
		'logo_dunkel'   => $dunkel,
		'hat_logo'      => '' !== $logo,
		'vertretung'    => $s( 'recht_vertretung' ),
		'registergericht' => $s( 'recht_registergericht' ),
		'registernummer' => $s( 'recht_registernummer' ),
		'ust_id'        => $s( 'recht_ust_id' ),
		'verantwortlich' => $s( 'recht_verantwortlich' ) ?: $s( 'recht_vertretung' ),
		'hoster'        => $zeilen( 'recht_hoster' ),
		'angebot_titel' => $s( 'angebot_titel' ) ?: 'Kostenloses Erstgespräch',
		'angebot_text'  => $s( 'angebot_text' ),
		'jahr'          => wp_date( 'Y' ),
	);
	// Schalter für Bedingungen in Etch: hat_<feld>
	foreach ( array( 'fax', 'erreichbarkeit', 'registernummer', 'ust_id', 'hoster' ) as $k ) {
		$daten[ 'hat_' . $k ] = '' !== $daten[ $k ];
	}
	return $daten;
}

/** PC-Visit-Downloads für Etch. */
function kbs_pcvisit_etch(): array {
	$o = (array) get_option( 'firmendaten', array() );
	$daten = array(
		'kunden_url' => esc_url_raw( trim( (string) ( $o['pcvisit_kunden_url'] ?? '' ) ) ),
		'host_url'   => esc_url_raw( trim( (string) ( $o['pcvisit_host_url'] ?? '' ) ) ),
		'hinweis'    => trim( (string) ( $o['pcvisit_hinweis'] ?? '' ) ),
		// Logo aus den Firmendaten, sonst das mitgelieferte Signet (wordpress/medien/)
		'logo'       => kbs_firma_bild( $o['pcvisit_logo'] ?? 0 ) ?: content_url( 'kbs/medien/pcvisit-signet.svg' ),
	);
	foreach ( array( 'kunden_url', 'host_url', 'hinweis' ) as $k ) {
		$daten[ 'hat_' . $k ] = '' !== $daten[ $k ];
	}
	return $daten;
}

add_filter(
	'etch/dynamic_data/option',
	function ( $data ) {
		if ( is_array( $data ) ) {
			$data['kbs']['firma']   = kbs_firma_etch();
			$data['kbs']['pcvisit'] = kbs_pcvisit_etch();
		}
		return $data;
	}
);
