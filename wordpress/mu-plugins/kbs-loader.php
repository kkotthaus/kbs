<?php
/**
 * Plugin Name: KBS – Lader (Übergang)
 * Description: Lädt die Snippets aus wp-content/kbs/snippets/, solange sie noch nicht in WPCodeBox liegen. Entfernen, bevor die Snippets in WPCodeBox eingeschaltet werden (kbs/snippets-sync mit aktivieren), sonst doppelte Funktionen.
 *
 * Quelle: Repository kbs, wordpress/mu-plugins/kbs-loader.php → wp-content/mu-plugins/kbs-loader.php (nur lokal)
 */

defined( 'ABSPATH' ) || exit;

foreach ( glob( WP_CONTENT_DIR . '/kbs/snippets/kbs-*.php' ) ?: array() as $kbs_datei ) {
	require_once $kbs_datei;
}
unset( $kbs_datei );
