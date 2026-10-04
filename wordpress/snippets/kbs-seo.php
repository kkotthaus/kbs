<?php
/**
 * Plugin Name: KBS – Suchmaschinen
 * Description: Suchmaschinen und KI-Suche. Seitentitel aus dem Repository (daten/seo.json, über SEOPress-Filter; ein im Seiteneditor gesetzter SEOPress-Titel geht vor), strukturierte Daten als @graph (ProfessionalService aus den Firmendaten mit Öffnungszeiten und Geschäftsführer, Service auf den Leistungsseiten, FAQPage bei häufigen Fragen), /llms.txt für KI-Suchdienste, 301-Weiterleitungen von den Adressen der alten Website (daten/weiterleitungen.json). Ohne SEO-Plugin zusätzlich Titel, Meta-Beschreibung und Open Graph aus dem Seitenauszug.
 *
 * Gehört auf die Live-Seite. Quelle: Repository kbs, wordpress/snippets/kbs-seo.php
 */

defined( 'ABSPATH' ) || exit;

defined( 'KBS_DATEN' ) || define( 'KBS_DATEN', WP_CONTENT_DIR . '/kbs' );

// Seiten bekommen einen Auszug: daraus entsteht die Meta-Beschreibung (pflegbar im Seiteneditor).
add_action( 'init', fn() => add_post_type_support( 'page', 'excerpt' ) );

/** Ist ein SEO-Plugin aktiv, das Titel und Meta-Angaben übernimmt? */
function kbs_seo_plugin(): bool {
	return defined( 'WPSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' ) || function_exists( 'seopress_init' );
}

/** SEO-Daten aus dem Build (daten/seo.json), nach Pfad (wie get_page_uri(), Startseite ''). */
function kbs_seo_daten(): array {
	static $daten = null;
	if ( null === $daten ) {
		$daten = array();
		$liste = json_decode( (string) @file_get_contents( KBS_DATEN . '/daten/seo.json' ), true );
		foreach ( is_array( $liste ) ? $liste : array() as $s ) {
			if ( is_array( $s ) && isset( $s['pfad'] ) ) {
				$daten[ (string) $s['pfad'] ] = $s;
			}
		}
	}
	return $daten;
}

/** Firmenangaben einsetzen: {options.kbs.firma.<feld>} wie im Markup. */
function kbs_seo_text( string $text ): string {
	if ( ! function_exists( 'kbs_firma_etch' ) || ! str_contains( $text, '{options.kbs.firma.' ) ) {
		return $text;
	}
	$firma = kbs_firma_etch();
	return (string) preg_replace_callback(
		'/\{options\.kbs\.firma\.([a-z0-9_]+)\}/',
		fn( $m ) => is_scalar( $firma[ $m[1] ] ?? null ) ? (string) $firma[ $m[1] ] : '',
		$text
	);
}

/** SEO-Daten der aktuellen Seite (null, wenn die Seite nicht aus dem Repository stammt). */
function kbs_seo_seite(): ?array {
	if ( is_front_page() ) {
		$pfad = '';
	} elseif ( is_page() ) {
		$pfad = (string) get_page_uri( get_queried_object_id() );
	} else {
		return null;
	}
	return kbs_seo_daten()[ $pfad ] ?? null;
}

/** Beschreibung der aktuellen Seite. */
function kbs_seo_beschreibung(): string {
	$id = is_front_page() ? (int) get_option( 'page_on_front' ) : get_queried_object_id();
	$text = $id ? (string) get_post_field( 'post_excerpt', $id ) : '';
	if ( '' === $text ) {
		$text = (string) get_bloginfo( 'description' );
	}
	return trim( wp_strip_all_tags( $text ) );
}

/** ID der aktuellen Seite (Startseite: statische Startseite). */
function kbs_seo_id(): int {
	return is_front_page() ? (int) get_option( 'page_on_front' ) : (int) get_queried_object_id();
}

// Titel aus dem Repository. Ein im Seiteneditor (SEOPress) gesetzter Titel geht vor.
add_filter(
	'seopress_titles_title',
	function ( $titel ) {
		$s = kbs_seo_seite();
		if ( ! $s || empty( $s['titel'] ) || '' !== (string) get_post_meta( kbs_seo_id(), '_seopress_titles_title', true ) ) {
			return $titel;
		}
		return esc_attr( kbs_seo_text( (string) $s['titel'] ) );
	},
	20
);

// Beschreibung aus dem Seitenauszug, auch auf der Startseite (SEOPress nimmt dort sonst den Untertitel der Website).
add_filter(
	'seopress_titles_desc',
	function ( $text ) {
		if ( ! kbs_seo_seite() || '' !== (string) get_post_meta( kbs_seo_id(), '_seopress_titles_desc', true ) ) {
			return $text;
		}
		$eigen = kbs_seo_beschreibung();
		return '' !== $eigen ? esc_attr( $eigen ) : $text;
	},
	20
);

// Ohne SEO-Plugin: Titel aus dem Repository
add_filter(
	'pre_get_document_title',
	function ( $titel ) {
		if ( kbs_seo_plugin() ) {
			return $titel;
		}
		$s = kbs_seo_seite();
		return $s && ! empty( $s['titel'] ) ? kbs_seo_text( (string) $s['titel'] ) : $titel;
	}
);

/** Öffnungszeiten „Mo–Do 8:00–17:00 Uhr“ (eine Zeile je Zeitraum) → schema.org OpeningHoursSpecification. */
function kbs_seo_oeffnungszeiten( string $text ): array {
	$tage  = array( 'Mo' => 'Monday', 'Di' => 'Tuesday', 'Mi' => 'Wednesday', 'Do' => 'Thursday', 'Fr' => 'Friday', 'Sa' => 'Saturday', 'So' => 'Sunday' );
	$keys  = array_keys( $tage );
	$liste = array();
	foreach ( preg_split( '/\R/u', $text ) as $zeile ) {
		if ( ! preg_match( '/^\s*(Mo|Di|Mi|Do|Fr|Sa|So)\w*\.?(?:\s*[–-]\s*(Mo|Di|Mi|Do|Fr|Sa|So)\w*\.?)?\s+(\d{1,2})[:.](\d{2})\s*[–-]\s*(\d{1,2})[:.](\d{2})/u', $zeile, $m ) ) {
			continue;
		}
		$von     = array_search( $m[1], $keys, true );
		$bis     = '' !== $m[2] ? array_search( $m[2], $keys, true ) : $von;
		$liste[] = array(
			'@type'     => 'OpeningHoursSpecification',
			'dayOfWeek' => array_map( fn( $k ) => $tage[ $k ], array_slice( $keys, $von, max( 1, $bis - $von + 1 ) ) ),
			'opens'     => sprintf( '%02d:%s', $m[3], $m[4] ),
			'closes'    => sprintf( '%02d:%s', $m[5], $m[6] ),
		);
	}
	return $liste;
}

/** Strukturierte Daten der aktuellen Seite als @graph. */
function kbs_seo_schema(): array {
	$o = (array) get_option( 'firmendaten', array() );
	$s = fn( string $k ) => trim( (string) ( $o[ $k ] ?? '' ) );
	if ( '' === $s( 'firma_name' ) ) {
		return array();
	}
	$start  = home_url( '/' );
	$firma  = function_exists( 'kbs_firma_etch' ) ? kbs_firma_etch() : array();
	// Einsatzgebiet: alle Einsatzorte als Städte, dazu die Region(en) aus „Region“ (z. B. Bergisches Land, Rheinland)
	$orte     = function_exists( 'kbs_firma_einsatzorte' ) ? kbs_firma_einsatzorte() : array( $s( 'firma_ort' ) );
	$regionen = array_values( array_filter( array_map( 'trim', preg_split( '/,|\bund\b/u', $s( 'firma_region' ) ) ), fn( $x ) => '' !== $x && 'Umgebung' !== $x && ! in_array( $x, $orte, true ) ) );
	$gebiet   = array_merge(
		array_map( fn( $ort ) => array( '@type' => 'City', 'name' => $ort ), array_filter( $orte ) ),
		array_map( fn( $r ) => array( '@type' => 'AdministrativeArea', 'name' => $r ), $regionen )
	);
	$org    = array(
		'@type'                     => 'ProfessionalService',
		'@id'                       => $start . '#organisation',
		'name'                      => $s( 'firma_name' ),
		'alternateName'             => $s( 'firma_kurzname' ),
		'description'               => $s( 'firma_claim' ),
		'url'                       => $start,
		'logo'                      => $firma['logo'] ?? '',
		'image'                     => $firma['logo'] ?? '',
		'telephone'                 => $s( 'firma_telefon' ),
		'faxNumber'                 => $s( 'firma_fax' ),
		'email'                     => $s( 'firma_email' ),
		'address'                   => array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => $s( 'firma_strasse' ),
			'postalCode'      => $s( 'firma_plz' ),
			'addressLocality' => $s( 'firma_ort' ),
			'addressCountry'  => 'DE',
		),
		'areaServed'                => $gebiet,
		'openingHoursSpecification' => kbs_seo_oeffnungszeiten( $s( 'firma_erreichbarkeit' ) ),
		'founder'                   => '' !== $s( 'recht_vertretung' ) ? array( '@type' => 'Person', 'name' => $s( 'recht_vertretung' ), 'jobTitle' => 'Geschäftsführer' ) : null,
		'knowsAbout'                => array( 'IT-Betreuung', 'IT-Beratung', 'Netzwerk und WLAN', 'Server', 'Datensicherung', 'IT-Sicherheit', 'Fernwartung', 'WordPress', 'Webdesign' ),
	);
	$graph = array( array_filter( $org ) );

	$seite = kbs_seo_seite();
	$url   = is_front_page() ? $start : (string) get_permalink();
	if ( $seite && ! empty( $seite['leistung'] ) ) {
		$graph[] = array(
			'@type'       => 'Service',
			'@id'         => $url . '#leistung',
			'name'        => (string) $seite['name'],
			'serviceType' => (string) $seite['name'],
			'description' => kbs_seo_text( (string) ( $seite['beschreibung'] ?? '' ) ),
			'url'         => $url,
			'provider'    => array( '@id' => $start . '#organisation' ),
			'areaServed'  => $gebiet,
		);
	}
	if ( $seite && ! empty( $seite['faq'] ) ) {
		$graph[] = array(
			'@type'      => 'FAQPage',
			'@id'        => $url . '#fragen',
			'url'        => $url,
			'mainEntity' => array_map(
				fn( $f ) => array(
					'@type'          => 'Question',
					'name'           => kbs_seo_text( (string) $f[0] ),
					'acceptedAnswer' => array( '@type' => 'Answer', 'text' => kbs_seo_text( (string) $f[1] ) ),
				),
				(array) $seite['faq']
			),
		);
	}
	return array( '@context' => 'https://schema.org', '@graph' => $graph );
}

add_action(
	'wp_head',
	function () {
		if ( is_404() ) {
			return;
		}
		if ( ! kbs_seo_plugin() ) {
			$beschreibung = kbs_seo_beschreibung();
			$titel        = wp_get_document_title();
			$url          = is_front_page() ? home_url( '/' ) : get_permalink();
			if ( '' !== $beschreibung ) {
				printf( "<meta name=\"description\" content=\"%s\">\n", esc_attr( $beschreibung ) );
				printf( "<meta property=\"og:description\" content=\"%s\">\n", esc_attr( $beschreibung ) );
			}
			printf( "<meta property=\"og:title\" content=\"%s\">\n", esc_attr( $titel ) );
			printf( "<meta property=\"og:type\" content=\"website\">\n<meta property=\"og:locale\" content=\"de_DE\">\n" );
			printf( "<meta property=\"og:site_name\" content=\"%s\">\n", esc_attr( get_bloginfo( 'name' ) ) );
			if ( $url ) {
				printf( "<meta property=\"og:url\" content=\"%s\">\n<link rel=\"canonical\" href=\"%s\">\n", esc_url( $url ), esc_url( $url ) );
			}
		}
		$schema = kbs_seo_schema();
		if ( $schema ) {
			echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n";
		}
	},
	2
);

/** Inhalt von /llms.txt (Markdown nach llmstxt.org): Firma, Kontakt, Seiten mit Kurzbeschreibung, häufige Fragen. */
function kbs_seo_llms(): string {
	$f = function_exists( 'kbs_firma_etch' ) ? kbs_firma_etch() : array();
	$z = array( '# ' . ( $f['name'] ?? get_bloginfo( 'name' ) ), '' );
	if ( ! empty( $f['claim'] ) ) {
		$z[] = '> ' . $f['claim'] . ( ! empty( $f['region'] ) ? ' in der Region ' . $f['region'] : '' ) . '.'
			. ( ! empty( $f['einsatzorte'] ) ? ' Einsatzorte: ' . $f['einsatzorte'] . '.' : '' );
		$z[] = '';
	}
	$kontakt = array_filter(
		array(
			'Anschrift'      => $f['anschrift'] ?? '',
			'Telefon'        => $f['telefon'] ?? '',
			'E-Mail'         => $f['email'] ?? '',
			'Erreichbarkeit' => isset( $f['erreichbarkeit'] ) ? str_replace( '<br>', ', ', html_entity_decode( (string) $f['erreichbarkeit'] ) ) : '',
			'Geschäftsführer' => $f['vertretung'] ?? '',
			'Angebot'        => trim( ( $f['angebot_titel'] ?? '' ) . ( ! empty( $f['angebot_text'] ) ? ' – ' . $f['angebot_text'] : '' ) ),
		)
	);
	foreach ( $kontakt as $k => $v ) {
		$z[] = '- ' . $k . ': ' . $v;
	}
	$abschnitte = array();
	$fragen     = array();
	foreach ( kbs_seo_daten() as $pfad => $s ) {
		$url = home_url( '' === $pfad ? '/' : '/' . $pfad . '/' );
		if ( ! empty( $s['llms'] ) ) {
			$abschnitte[ (string) $s['llms'] ][] = '- [' . $s['name'] . '](' . $url . '): ' . kbs_seo_text( (string) ( $s['beschreibung'] ?? '' ) );
		}
		// Fragen je Seite gruppiert, damit der Bezug klar ist (Startseite: allgemein)
		foreach ( (array) ( $s['faq'] ?? array() ) as $faq ) {
			$fragen[ '' === $pfad ? 'Allgemein' : (string) $s['name'] ][] = '### ' . kbs_seo_text( (string) $faq[0] ) . "\n\n" . kbs_seo_text( (string) $faq[1] );
		}
	}
	// Feste Reihenfolge: Leistungen, Seiten, Rechtliches
	foreach ( array( 'Leistungen', 'Seiten', 'Rechtliches' ) as $titel ) {
		if ( ! empty( $abschnitte[ $titel ] ) ) {
			array_push( $z, '', '## ' . $titel, '', ...$abschnitte[ $titel ] );
		}
	}
	foreach ( $fragen as $bereich => $liste ) {
		array_push( $z, '', '## Häufige Fragen: ' . $bereich, '', implode( "\n\n", $liste ) );
	}
	return implode( "\n", $z ) . "\n";
}

// SEOPress Pro liefert /llms.txt selbst aus (Modul „llms.txt“): Inhalt aus dem Repository statt der allgemeinen Vorlage.
add_filter( 'seopress_llms_txt_file', fn() => kbs_seo_llms(), 20 );

// Ohne SEOPress-Modul: /llms.txt selbst ausliefern (vor dem Laden der Seite, ohne Datei im Webroot)
add_action(
	'parse_request',
	function () {
		$pfad = (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH );
		if ( '/llms.txt' !== untrailingslashit( $pfad ) ) {
			return;
		}
		status_header( 200 );
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'X-Robots-Tag: noindex' );
		echo kbs_seo_llms(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- reiner Text
		exit;
	}
);

// 301 von den Adressen der alten Website
add_action(
	'template_redirect',
	function () {
		if ( ! is_404() ) {
			return;
		}
		$karte = json_decode( (string) @file_get_contents( KBS_DATEN . '/daten/weiterleitungen.json' ), true );
		if ( ! is_array( $karte ) ) {
			return;
		}
		$pfad = '/' . trim( (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH ), '/' ) . '/';
		if ( isset( $karte[ $pfad ] ) ) {
			wp_safe_redirect( home_url( $karte[ $pfad ] ), 301 );
			exit;
		}
	}
);
