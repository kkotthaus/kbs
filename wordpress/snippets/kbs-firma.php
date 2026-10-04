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
				'start'   => 'Startseite',
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
				$text( 'firma_region', 'Region', 'Kurzer Name der Region, in der Sie arbeiten, z. B. „Bergisches Land und Rheinland“ – erscheint in Texten als „in der Region …“.' ),
				$text( 'firma_einsatzorte', 'Einsatzorte', 'Orte mit Kunden, durch Komma getrennt, z. B. „Burscheid, Leichlingen, Köln“. Erscheint einmal sichtbar auf „Über uns“ und für Suchmaschinen und KI-Suche in den strukturierten Daten und in /llms.txt.' ),
				$text( 'firma_telefon', 'Telefon', 'Lesbar formatiert, z. B. 02174 666 47 17. Der Anruf-Link wird daraus erzeugt.' ),
				$text( 'firma_fax', 'Telefax', 'Leer = nicht anzeigen.' ),
				array( 'id' => 'firma_email', 'name' => 'E-Mail', 'type' => 'email', 'size' => 60 ),
				array( 'id' => 'firma_erreichbarkeit', 'name' => 'Erreichbarkeit', 'type' => 'textarea', 'rows' => 3, 'desc' => 'z. B. Bürozeiten, eine Angabe je Zeile. Leer = Abschnitt ausgeblendet.' ),
				array( 'id' => 'firma_logo', 'name' => 'Logo', 'type' => 'single_image', 'desc' => 'Optional. Ohne Bild erscheint das mitgelieferte Firmenlogo (wp-content/kbs/medien/kbs-logo.svg).' ),
				array( 'id' => 'firma_logo_dunkel', 'name' => 'Logo für dunkle Flächen', 'type' => 'single_image', 'desc' => 'Optional, für den Footer und den Header im dunklen Farbschema (helle Schrift). Ohne Angabe: das hochgeladene Logo bzw. die mitgelieferte helle Variante.' ),
				array( 'id' => 'vorschaubild', 'name' => 'Vorschaubild für Links', 'type' => 'single_image', 'desc' => 'Erscheint, wenn ein Link zur Website geteilt wird (WhatsApp, LinkedIn, Teams …) und bei Suchmaschinen und KI-Suche: 1200 × 630 px, JPEG oder PNG. Gilt für alle Seiten ohne eigenes Beitragsbild.' ),
				array( 'id' => 'firma_profile', 'name' => 'Profil-Links', 'type' => 'textarea', 'rows' => 4, 'desc' => 'Eine Adresse je Zeile, z. B. Google-Unternehmensprofil, LinkedIn, XING, Facebook. Nicht sichtbar auf der Website; Suchmaschinen und KI-Suche erkennen daran, dass die Profile zu dieser Firma gehören.' ),
				array( 'id' => 'kontakt_formular', 'name' => 'Kontaktformular', 'type' => 'select', 'options' => array( 'an' => 'anzeigen', 'aus' => 'ausblenden' ), 'std' => 'an', 'desc' => 'Ausgeblendet: Auf der Kontaktseite steht statt des Formulars ein Hinweis mit Telefon und E-Mail, und das Formular nimmt keine Anfragen an.' ),
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
		$boxen[] = $box(
			'start',
			'start',
			'Startseite',
			array(
				array( 'id' => 'hero_bild', 'name' => 'Hero-Bild', 'type' => 'single_image', 'desc' => 'Hintergrund rechts im Startseiten-Hero (Querformat, mind. 1344 px breit; links steht der Text). Alternativtext und KI-Kennzeichnung kommen aus der Mediathek. Leer = kein Bild.' ),
				array( 'id' => 'hero_bild_dunkel', 'name' => 'Hero-Bild für das dunkle Farbschema', 'type' => 'single_image', 'desc' => 'Optional. Leer = dasselbe Bild wie oben.' ),
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

/** Einsatzorte als Liste (Firmendaten › Einsatzorte, getrennt durch Komma, Zeilenumbruch oder „und“); leer: der Ort der Firma. */
function kbs_firma_einsatzorte(): array {
	$o     = (array) get_option( 'firmendaten', array() );
	$orte  = preg_split( '/\s*(?:,|;|\R|\bund\b)\s*/u', trim( (string) ( $o['firma_einsatzorte'] ?? '' ) ) );
	$orte  = array_values( array_unique( array_filter( array_map( 'trim', $orte ), fn( $x ) => '' !== $x ) ) );
	$ort   = trim( (string) ( $o['firma_ort'] ?? '' ) );
	return $orte ?: ( '' !== $ort ? array( $ort ) : array() );
}

/** Profil-Links (Firmendaten › Profil-Links), nur gültige http(s)-Adressen. */
function kbs_firma_profile(): array {
	$o     = (array) get_option( 'firmendaten', array() );
	$links = preg_split( '/\s+/', trim( (string) ( $o['firma_profile'] ?? '' ) ) );
	// erst prüfen, dann maskieren (esc_url_raw macht aus „xyz“ sonst „http://xyz“)
	$links = array_filter( $links, fn( $u ) => (bool) preg_match( '#^https?://[^\s/]+\.[^\s]+#i', $u ) );
	return array_values( array_unique( array_filter( array_map( 'esc_url_raw', $links ) ) ) );
}

/** Liste lesbar verbinden: „A, B und C“. */
function kbs_firma_aufzaehlung( array $teile ): string {
	$letzter = array_pop( $teile );
	return $teile ? implode( ', ', $teile ) . ' und ' . $letzter : (string) $letzter;
}

/** Bilddaten eines Anhangs für ein img-Element (src, srcset, Maße, Alternativtext samt KI-Hinweis). */
function kbs_firma_bilddaten( int $id ): array {
	$meta = (array) wp_get_attachment_metadata( $id );
	$alt  = trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );
	$ki   = function_exists( 'kbs_ki_daten' ) ? kbs_ki_daten( $id ) : array( 'hat' => false );
	return array(
		'src'    => (string) wp_get_attachment_image_url( $id, 'full' ),
		'srcset' => (string) wp_get_attachment_image_srcset( $id, 'full' ),
		'breite' => (string) ( $meta['width'] ?? '' ),
		'hoehe'  => (string) ( $meta['height'] ?? '' ),
		'alt'    => ! empty( $ki['hat'] ) ? kbs_ki_alt( $alt, (string) $ki['alt'] ) : $alt,
	);
}

/** Hero der Startseite: Bild aus Firmendaten › Startseite (dunkle Variante optional), KI-Kennzeichnung aus der Mediathek. */
function kbs_hero_etch(): array {
	$o      = (array) get_option( 'firmendaten', array() );
	$id     = fn( $v ) => is_array( $v ) ? (int) ( $v['ID'] ?? 0 ) : (int) $v;
	$hell   = $id( $o['hero_bild'] ?? 0 );
	$dunkel = $id( $o['hero_bild_dunkel'] ?? 0 ) ?: $hell;
	if ( ! $hell || ! wp_attachment_is_image( $hell ) ) {
		return array( 'hat' => false, 'ki' => array( 'hat' => false ) );
	}
	return array(
		'hat'    => true,
		'hell'   => kbs_firma_bilddaten( $hell ),
		'dunkel' => kbs_firma_bilddaten( wp_attachment_is_image( $dunkel ) ? $dunkel : $hell ),
		'ki'     => function_exists( 'kbs_ki_daten' ) ? kbs_ki_daten( $hell ) : array( 'hat' => false ),
	);
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
		'einsatzorte'   => kbs_firma_aufzaehlung( kbs_firma_einsatzorte() ),
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
			$data['kbs']['hero']    = kbs_hero_etch();
		}
		return $data;
	}
);

// Firmendaten erscheinen auf allen Seiten: nach dem Speichern den Seitencache leeren (LiteSpeed Cache; ohne Plugin wirkungslos)
add_action( 'update_option_firmendaten', fn() => do_action( 'litespeed_purge_all' ) );
add_action( 'add_option_firmendaten', fn() => do_action( 'litespeed_purge_all' ) );
