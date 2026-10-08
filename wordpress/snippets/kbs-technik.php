<?php
/**
 * Plugin Name: KBS – Technik-Doku
 * Description: Doku zum Aufbau der Website als eigene Seite im Backend (Menü „Technik“, dazu ein Hinweis im Dashboard): Schichten, Plugins, Snippets, CSS, Skripte, Datenfluss, was man nicht tun sollte, Prüfungen nach Updates. Nur für Administratoren (manage_options). Inhalt: kbs/technik.php, erzeugt von wordpress/etch/build.mjs aus docs/technik.md.
 * Version: 1.0.0
 *
 * Gehört auf die Live-Seite. WPCodeBox: PHP, Ausführung „Always“, Einfügepunkt Root.
 * Quelle: Repository kbs, wordpress/snippets/kbs-technik.php
 */

defined( 'ABSPATH' ) || exit;

// Build-Dateien liegen in wp-content/kbs/. Als WPCodeBox-Snippet läuft der Code per eval(), __DIR__ zeigt nicht dorthin.
defined( 'KBS_DATEN' ) || define( 'KBS_DATEN', WP_CONTENT_DIR . '/kbs' );

add_action(
	'admin_menu',
	function () {
		add_menu_page( 'Technik der Website', 'Technik', 'manage_options', 'kbs-technik', 'kbs_technik_seite', 'dashicons-admin-tools', 81 );
	}
);

function kbs_technik_seite(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Kein Zugriff.' );
	}
	// Die Datei beginnt mit einer PHP-Schutzzeile (ABSPATH-Prüfung): direkt aufgerufen liefert der Webserver nichts aus.
	$html = preg_replace( '/^<\?php.*?\?>\n?/s', '', (string) @file_get_contents( KBS_DATEN . '/technik.php' ) );
	echo '<div class="wrap kbs-handbuch">';
	echo $html ? wp_kses_post( $html ) : '<h1>Technik der Website</h1><p>Die Datei kbs/technik.php fehlt. Bitte den Build ausführen und den Ordner dist hochladen.</p>';
	echo '</div>';
}

// Lesbare Zeilenlänge, Kapitel-Verzeichnis links mitlaufend; Farben kommen aus dem WordPress-Backend.
add_action(
	'admin_head',
	function () {
		$screen = get_current_screen();
		if ( ! $screen || 'toplevel_page_kbs-technik' !== $screen->id ) {
			return;
		}
		echo '<style>
			.kbs-handbuch__layout { display: grid; grid-template-columns: minmax(12rem, 16rem) minmax(0, 60rem); gap: 2rem; align-items: start; }
			.kbs-handbuch__toc { position: sticky; top: 3rem; }
			.kbs-handbuch__toc ol { margin-left: 1.25rem; }
			.kbs-handbuch__toc li { margin-bottom: .35rem; }
			.kbs-handbuch__inhalt { font-size: 14px; line-height: 1.6; }
			.kbs-handbuch__inhalt h2 { font-size: 1.5em; margin-top: 3rem; scroll-margin-top: 3rem; }
			.kbs-handbuch__inhalt h3 { font-size: 1.15em; margin-top: 1.75rem; scroll-margin-top: 3rem; }
			.kbs-handbuch__inhalt ul, .kbs-handbuch__inhalt ol { margin-left: 1.5rem; }
			.kbs-handbuch__inhalt ul { list-style: disc; }
			.kbs-handbuch__inhalt ul ul { list-style: circle; }
			.kbs-handbuch__inhalt table { margin: 1rem 0; }
			.kbs-handbuch__inhalt td, .kbs-handbuch__inhalt th { vertical-align: top; }
			.kbs-handbuch__hinweis { margin: 1rem 0; }
			@media (max-width: 960px) { .kbs-handbuch__layout { grid-template-columns: 1fr; } .kbs-handbuch__toc { position: static; } }
		</style>';
	}
);

add_action(
	'wp_dashboard_setup',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		wp_add_dashboard_widget(
			'kbs_technik',
			'Technik der Website (Administratoren)',
			function () {
				echo '<p>Wie die Website aufgebaut ist: Plugins, Snippets, CSS und Skripte und wie sie zusammenarbeiten – mit Hinweisen, was man nicht ändern sollte.</p>';
				echo '<p><a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=kbs-technik' ) ) . '">Technik-Doku öffnen</a></p>';
			}
		);
	}
);
