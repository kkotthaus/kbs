<?php
/**
 * Plugin Name: KBS – Tempo
 * Description: Ladezeit auf dem Handy: lässt das Etch-Stylesheet „DWC Mega Menu“ (rund 160 KB, Header und Navigation sind eigene Komponenten) im Frontend weg – gespeichert und im Editor bleibt es – und startet die Statistik (Matomo) erst nach dem Laden der Seite. Keine Shortcodes, kein Markup.
 *
 * Gehört auf die Live-Seite. Quelle: Repository kbs, wordpress/snippets/kbs-tempo.php
 */

defined( 'ABSPATH' ) || exit;

// Wird das Mega-Menü (EMMP) eingebunden, in der wp-config.php define( 'KBS_EMMP_AKTIV', true ); setzen
// oder dieses Snippet ausschalten – dann gibt Etch das Stylesheet wieder auf jeder Seite aus.
if ( ! defined( 'KBS_EMMP_AKTIV' ) ) {
	define( 'KBS_EMMP_AKTIV', false );
}

// Die Regeln aus „DWC Mega Menu“, die nicht nur das Menü betreffen, sondern jede Seite: Das Aussehen der Website ist
// mit ihnen gebaut (html als Flex-Spalte verändert Abstände und Zeilen). Nur diese bleiben im Frontend.
define( 'KBS_TEMPO_DWC_GLOBAL', 'html{display:flex;flex-direction:column;block-size:100%}html body{min-block-size:initial;flex-grow:1}#wpadminbar{z-index:999999!important}' );

// Etch schreibt alle globalen Stylesheets bei wp_head (Priorität 99) in den Kopf jeder Seite. Der Filter greift nur
// während dieser Ausgabe: In der Datenbank, im Etch-Editor und in der REST-API bleibt „DWC Mega Menu“ unverändert.
add_filter(
	'option_etch_global_stylesheets',
	function ( $stylesheets ) {
		if ( KBS_EMMP_AKTIV || ! is_array( $stylesheets ) || is_admin() || ! doing_action( 'wp_head' ) ) {
			return $stylesheets;
		}
		foreach ( $stylesheets as $id => $sheet ) {
			if ( 'dwc-mega-menu' === sanitize_title( $sheet['name'] ?? '' ) ) {
				$stylesheets[ $id ]['css'] = KBS_TEMPO_DWC_GLOBAL;
			}
		}
		return $stylesheets;
	}
);

// Matomo lädt sein Skript (rund 70 KB) sonst gleich zu Beginn und konkurriert mit Schrift und CSS um die Leitung.
// Nach dem Laden der Seite zählt der Besuch genauso. Matomo speichert den fertigen Code zwischen: nach einer Änderung
// hier den Code neu erzeugen (siehe docs/technik.md, „Tempo“).
add_filter(
	'matomo_tracking_code_script',
	function ( $script ) {
		if ( ! is_string( $script ) || false !== strpos( $script, 'kbs-tempo' ) ) {
			return $script;
		}
		$neu = preg_replace(
			'/\}\s*else\s*\{\s*initTracking\(\);\s*\}/',
			"} else if (document.readyState === 'complete') {\n\tinitTracking();\n} else {\n\twindow.addEventListener('load', initTracking, {once: true}); /* kbs-tempo */\n}",
			$script,
			1,
			$anzahl
		);
		return ( 1 === $anzahl && is_string( $neu ) ) ? $neu : $script;
	}
);
