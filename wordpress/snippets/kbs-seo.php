<?php
/**
 * Plugin Name: KBS – Suchmaschinen
 * Description: Meta-Beschreibung und Open-Graph-Angaben aus dem Seitenauszug, strukturierte Daten (schema.org ProfessionalService) aus den Firmendaten, 301-Weiterleitungen von den Adressen der alten Website (daten/weiterleitungen.json). Tritt zurück, sobald SEOPress aktiv ist (außer Weiterleitungen und strukturierte Daten).
 *
 * Gehört auf die Live-Seite. Quelle: Repository kbs, wordpress/snippets/kbs-seo.php
 */

defined( 'ABSPATH' ) || exit;

defined( 'KBS_DATEN' ) || define( 'KBS_DATEN', WP_CONTENT_DIR . '/kbs' );

// Seiten bekommen einen Auszug: daraus entsteht die Meta-Beschreibung (pflegbar im Seiteneditor).
add_action( 'init', fn() => add_post_type_support( 'page', 'excerpt' ) );

/** Beschreibung der aktuellen Seite. */
function kbs_seo_beschreibung(): string {
	$id = is_front_page() ? (int) get_option( 'page_on_front' ) : get_queried_object_id();
	$text = $id ? (string) get_post_field( 'post_excerpt', $id ) : '';
	if ( '' === $text ) {
		$text = (string) get_bloginfo( 'description' );
	}
	return trim( wp_strip_all_tags( $text ) );
}

add_action(
	'wp_head',
	function () {
		if ( is_404() ) {
			return;
		}
		if ( ! defined( 'WPSEO_VERSION' ) && ! function_exists( 'seopress_init' ) && ! defined( 'SEOPRESS_VERSION' ) ) {
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
		// Strukturierte Daten nur auf der Startseite und der Kontaktseite
		if ( ! is_front_page() && ! is_page( 'kontakt' ) ) {
			return;
		}
		$o = (array) get_option( 'firmendaten', array() );
		$s = fn( string $k ) => trim( (string) ( $o[ $k ] ?? '' ) );
		if ( '' === $s( 'firma_name' ) ) {
			return;
		}
		$daten = array(
			'@context'    => 'https://schema.org',
			'@type'       => 'ProfessionalService',
			'name'        => $s( 'firma_name' ),
			'description' => $s( 'firma_claim' ),
			'url'         => home_url( '/' ),
			'telephone'   => $s( 'firma_telefon' ),
			'email'       => $s( 'firma_email' ),
			'address'     => array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => $s( 'firma_strasse' ),
				'postalCode'      => $s( 'firma_plz' ),
				'addressLocality' => $s( 'firma_ort' ),
				'addressCountry'  => 'DE',
			),
			'areaServed'  => array_values( array_filter( array_map( 'trim', preg_split( '/,|\bund\b/u', $s( 'firma_region' ) ) ), fn( $x ) => '' !== $x && 'Umgebung' !== $x ) ),
		);
		$logo = function_exists( 'kbs_firma_bild' ) ? kbs_firma_bild( $o['firma_logo'] ?? 0 ) : '';
		if ( $logo ) {
			$daten['logo'] = $logo;
		}
		echo '<script type="application/ld+json">' . wp_json_encode( array_filter( $daten ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n";
	},
	2
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
