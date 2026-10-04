<?php
/**
 * Plugin Name: KBS – KI-Kennzeichnung
 * Description: Kennzeichnung von Bildern und Videos, die mit KI erzeugt oder verändert wurden (EU-KI-Verordnung Art. 50; Standard aus etch-nodes). In der Mediathek je Anhang die Art der KI-Nutzung (unterstützt, generiert, bearbeitet), optional Werkzeug und Position. Auf der Website erscheint am Bild eine Plakette „KI“, die beim Darüberfahren aufklappt; der Hinweis steht zusätzlich im Alternativtext. Darstellung unter Medien › KI-Kennzeichnung, eigenes Symbol unter Firmendaten. Daten für Etch: kbs_ki_daten() → { hat, kurz, logo, hat_logo, label, zusatz, text, mod, alt }. In der Mediathek: Spalte „KI“ und Filter in der Listenansicht, Plakette auf den Kacheln im Raster und im Medien-Fenster.
 *
 * Gehört auf die Live-Seite. Quelle: Repository kbs, wordpress/snippets/kbs-ki.php
 */

defined( 'ABSPATH' ) || exit;

define( 'KBS_KI_OPTION', 'kbs_ki' );

/**
 * Arten der KI-Nutzung (Schlüssel nach etch-nodes: ai, generated, modified).
 * label: volle Kennung (Alternativtext, Backend), zusatz: aufgeklappt neben „KI“, text: Erklärung.
 */
define(
	'KBS_KI_ARTEN',
	array(
		'ai'        => array( 'label' => 'KI-unterstützt', 'zusatz' => 'unterstützt', 'text' => 'Bei der Erstellung wurde KI eingesetzt' ),
		'generated' => array( 'label' => 'KI-generiert', 'zusatz' => 'generiert', 'text' => 'Vollständig mit KI erzeugt' ),
		'modified'  => array( 'label' => 'KI-bearbeitet', 'zusatz' => 'bearbeitet', 'text' => 'Mit KI verändert' ),
	)
);

/** Darstellung: Position, Stil, Größe – je Schlüssel → Bezeichnung im Backend. */
define(
	'KBS_KI_DARSTELLUNG',
	array(
		'position' => array( 'oben-rechts' => 'oben rechts', 'oben-links' => 'oben links', 'unten-rechts' => 'unten rechts', 'unten-links' => 'unten links', 'unter' => 'unter dem Bild' ),
		'stil'     => array( 'dunkel' => 'dunkel', 'hell' => 'hell', 'marke' => 'Markenrot' ),
		'groesse'  => array( 'normal' => 'normal', 'klein' => 'klein' ),
	)
);

// Felder in der Mediathek (Anhang bearbeiten und Medien-Dialog)
add_filter(
	'rwmb_meta_boxes',
	function ( $boxen ) {
		$boxen[] = array(
			'id'          => 'ki-kennzeichnung',
			'title'       => 'KI-Kennzeichnung',
			'post_types'  => array( 'attachment' ),
			'media_modal' => true,
			'fields'      => array(
				array(
					'id'          => 'ki_art',
					'name'        => 'KI-Nutzung',
					'type'        => 'select',
					'placeholder' => 'keine KI',
					'options'     => array_map( fn( $a ) => $a['label'] . ' – ' . $a['text'], KBS_KI_ARTEN ),
					'desc'        => 'Auf der Website erscheint am Bild die Plakette „KI“, beim Darüberfahren mit diesem Text.',
				),
				array(
					'id'          => 'ki_werkzeug',
					'name'        => 'Werkzeug',
					'type'        => 'text',
					'placeholder' => 'z. B. Midjourney, Firefly',
					'desc'        => 'Optional, erscheint im Hinweis (wenn unter Medien › KI-Kennzeichnung eingeschaltet).',
				),
				array(
					'id'          => 'ki_position',
					'name'        => 'Position der Plakette',
					'type'        => 'select',
					'placeholder' => 'wie unter Medien › KI-Kennzeichnung eingestellt',
					'options'     => KBS_KI_DARSTELLUNG['position'],
					'desc'        => 'Nur nötig, wenn die Plakette an der üblichen Stelle etwas Wichtiges im Bild verdeckt.',
				),
			),
		);
		return $boxen;
	}
);

// Einstellungsseite Medien › KI-Kennzeichnung
add_filter(
	'mb_settings_pages',
	function ( $seiten ) {
		$seiten[] = array(
			'id'            => 'ki-kennzeichnung',
			'option_name'   => KBS_KI_OPTION,
			'menu_title'    => 'KI-Kennzeichnung',
			'page_title'    => 'KI-Kennzeichnung von Bildern und Videos',
			'parent'        => 'upload.php',
			'capability'    => 'edit_pages',
			'style'         => 'no-boxes',
			'columns'       => 1,
			'submit_button' => 'Speichern',
			'message'       => 'Einstellungen gespeichert.',
		);
		return $seiten;
	}
);
add_filter(
	'rwmb_meta_boxes',
	function ( $boxen ) {
		$d       = KBS_KI_DARSTELLUNG;
		$boxen[] = array(
			'id'             => 'ki-kennzeichnung-einstellungen',
			'title'          => 'Darstellung',
			'settings_pages' => 'ki-kennzeichnung',
			'fields'         => array(
				array(
					'type' => 'custom_html',
					'std'  => '<p>So erscheint die Plakette „KI“ an Bildern, die in der Mediathek als KI-Inhalt markiert sind. Sie ist immer sichtbar; beim Darüberfahren klappt sie auf und zeigt die Art der KI-Nutzung.</p>',
				),
				array( 'id' => 'position', 'name' => 'Position', 'type' => 'button_group', 'options' => $d['position'], 'std' => 'oben-rechts' ),
				array( 'id' => 'stil', 'name' => 'Stil', 'type' => 'button_group', 'options' => $d['stil'], 'std' => 'dunkel' ),
				array( 'id' => 'groesse', 'name' => 'Größe', 'type' => 'button_group', 'options' => $d['groesse'], 'std' => 'normal' ),
				array( 'id' => 'werkzeug', 'name' => 'Werkzeug nennen', 'type' => 'switch', 'style' => 'rounded', 'on_label' => 'Ja', 'off_label' => 'Nein', 'std' => 1, 'desc' => 'Zum Beispiel „Vollständig mit KI erzeugt (Midjourney)“.' ),
			),
		);
		return $boxen;
	}
);

// Eigenes KI-Symbol als Bild unter Firmendaten › Firma & Kontakt (z. B. das offizielle EU-Symbol); ohne Bild erscheint „KI“
add_filter(
	'rwmb_meta_boxes',
	function ( $boxen ) {
		foreach ( $boxen as $i => $b ) {
			if ( 'firmendaten-firma' === ( $b['id'] ?? '' ) ) {
				$boxen[ $i ]['fields'][] = array(
					'id'   => 'ki_logo',
					'name' => 'KI-Symbol',
					'type' => 'single_image',
					'desc' => 'Optional. Erscheint an KI-Bildern statt des Schriftzugs „KI“ (z. B. das offizielle EU-Symbol). Quadratisch oder breit, gut lesbar. Leer = „KI“.',
				);
			}
		}
		return $boxen;
	},
	20
);

/** Adresse des KI-Symbols aus den Firmendaten, sonst leer. */
function kbs_ki_logo(): string {
	static $url = null;
	if ( null === $url ) {
		$id  = (int) ( ( (array) get_option( 'firmendaten', array() ) )['ki_logo'] ?? 0 );
		$url = $id ? (string) wp_get_attachment_image_url( $id, 'medium' ) : '';
	}
	return $url;
}

/** Gewählte Darstellung, ungültige Werte auf die Vorgabe. */
function kbs_ki_darstellung(): array {
	$opt  = (array) get_option( KBS_KI_OPTION, array() );
	$wert = function ( string $k, string $vorgabe ) use ( $opt ) {
		$v = (string) ( $opt[ $k ] ?? '' );
		return isset( KBS_KI_DARSTELLUNG[ $k ][ $v ] ) ? $v : $vorgabe;
	};
	return array(
		'position' => $wert( 'position', 'oben-rechts' ),
		'stil'     => $wert( 'stil', 'dunkel' ),
		'groesse'  => $wert( 'groesse', 'normal' ),
		'werkzeug' => ! isset( $opt['werkzeug'] ) || ! empty( $opt['werkzeug'] ),
	);
}

/** Art der KI-Nutzung eines Anhangs; maßgeblich ist nur die Auswahl „KI-Nutzung“ (leer = keine KI). */
function kbs_ki_art( int $id ): string {
	if ( ! $id ) {
		return '';
	}
	$art = (string) get_post_meta( $id, 'ki_art', true );
	return isset( KBS_KI_ARTEN[ $art ] ) ? $art : '';
}

/** Daten für die Plakette (Etch und Editor-Bilder). Ohne KI-Nutzung: hat = false. */
function kbs_ki_daten( int $id ): array {
	return kbs_ki_daten_fuer( kbs_ki_art( $id ), (string) get_post_meta( $id, 'ki_werkzeug', true ), (string) get_post_meta( $id, 'ki_position', true ) );
}

/** Daten für die Plakette aus Art, Werkzeug und eigener Position (leer = Einstellung); auch für Bilder ohne Anhang. */
function kbs_ki_daten_fuer( string $art, string $werkzeug = '', string $position = '' ): array {
	if ( ! isset( KBS_KI_ARTEN[ $art ] ) ) {
		return array( 'hat' => false, 'kurz' => '', 'logo' => '', 'hat_logo' => false, 'label' => '', 'zusatz' => '', 'text' => '', 'mod' => '', 'alt' => '' );
	}
	$d = kbs_ki_darstellung();
	// Position am Bild geht vor der Einstellung
	$d['position'] = isset( KBS_KI_DARSTELLUNG['position'][ $position ] ) ? $position : $d['position'];
	$werkzeug      = $d['werkzeug'] ? trim( $werkzeug ) : '';
	$a             = KBS_KI_ARTEN[ $art ];
	$text          = $a['text'] . ( '' !== $werkzeug ? ' (' . $werkzeug . ')' : '' );
	$logo          = kbs_ki_logo();
	return array(
		'hat'      => true,
		'kurz'     => 'KI',
		'logo'     => $logo,
		'hat_logo' => '' !== $logo,
		'label'    => $a['label'],
		// mit eigenem Symbol steht „KI“ nicht davor, deshalb dann die volle Kennung
		'zusatz'   => '' !== $logo ? $a['label'] : $a['zusatz'],
		'text'     => $text,
		'mod'      => 'ki-plakette--' . $d['position'] . ' ki-plakette--' . $d['stil'] . ' ki-plakette--' . $d['groesse'] . ( '' !== $logo ? ' ki-plakette--logo' : '' ),
		'alt'      => $a['label'] . ': ' . $text,
	);
}

/**
 * Mitgelieferte KI-Bilder (wordpress/medien/bilder/, kein Anhang in der Mediathek): Art, Werkzeug, Position, Beschreibung.
 * In Etch als {options.kbs.ki_bilder.<schlüssel>.…} mit den Feldern von kbs_ki_daten(), dazu bild_alt (Beschreibung + Hinweis).
 */
define(
	'KBS_KI_DATEIEN',
	array(
		// Beispiel: 'schluessel' => array( 'art' => 'generated', 'werkzeug' => '…', 'position' => 'unten-rechts', 'beschreibung' => '…' ),
	)
);

add_filter(
	'etch/dynamic_data/option',
	function ( $data ) {
		if ( is_array( $data ) ) {
			foreach ( KBS_KI_DATEIEN as $schluessel => $b ) {
				$k        = kbs_ki_daten_fuer( $b['art'], $b['werkzeug'], $b['position'] );
				$k['bild_alt'] = kbs_ki_alt( $b['beschreibung'], $k['alt'] );
				$data['kbs']['ki_bilder'][ $schluessel ] = $k;
			}
		}
		return $data;
	}
);

/** Alternativtext um den Hinweis ergänzen (Screenreader); auch bei leerem Alternativtext. */
function kbs_ki_alt( string $alt, string $hinweis ): string {
	return trim( $alt . ( '' !== $alt ? ' – ' : '' ) . $hinweis );
}

/** Markup der Plakette für Bilder aus dem Editor. Gleicher Aufbau wie in den Etch-Komponenten (lib.mjs: kiPlakette). */
function kbs_ki_plakette( array $k ): string {
	return '<span class="ki-plakette ' . esc_attr( $k['mod'] ) . '" aria-hidden="true" title="' . esc_attr( $k['alt'] ) . '">'
		. '<span class="ki-plakette__icon">' . ( $k['hat_logo'] ? '<img class="ki-plakette__logo" src="' . esc_url( $k['logo'] ) . '" alt="">' : esc_html( $k['kurz'] ) ) . '</span>'
		. '<span class="ki-plakette__text"><strong>' . esc_html( $k['zusatz'] ) . '</strong> ' . esc_html( $k['text'] ) . '</span></span>';
}

/**
 * Bilder aus dem Editor (Bild, Beitragsbild, Cover): Klasse „ki-bild“ am äußeren Element, Bild (samt Link) und Plakette
 * in span.ki-bild__rahmen (Bezug für die Position, ohne Bildunterschrift), Hinweis im Alternativtext.
 */
add_filter(
	'render_block',
	function ( $html, $block ) {
		$name = $block['blockName'] ?? '';
		if ( ! in_array( $name, array( 'core/image', 'core/post-featured-image', 'core/cover' ), true ) || ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
			return $html;
		}
		$id = 'core/post-featured-image' === $name ? (int) get_post_thumbnail_id() : (int) ( $block['attrs']['id'] ?? 0 );
		$k  = kbs_ki_daten( $id );
		if ( ! $k['hat'] ) {
			return $html;
		}
		$p = new WP_HTML_Tag_Processor( $html );
		if ( $p->next_tag() ) {
			$p->add_class( 'ki-bild' );
		}
		if ( $p->next_tag( 'img' ) ) {
			$p->set_attribute( 'alt', kbs_ki_alt( (string) $p->get_attribute( 'alt' ), $k['alt'] ) );
		}
		$html = $p->get_updated_html();
		// Plakette direkt nach dem Bild (bzw. nach dem Link um das Bild) einfügen
		$nach = preg_match( '#<a\b[^>]*>\s*<img\b[^>]*>\s*</a>#i', $html, $m ) ? $m[0] : ( preg_match( '#<img\b[^>]*>#i', $html, $m ) ? $m[0] : '' );
		return '' !== $nach ? str_replace( $nach, '<span class="ki-bild__rahmen">' . $nach . kbs_ki_plakette( $k ) . '</span>', $html ) : $html;
	},
	10,
	2
);

// ---------- Mediathek: Kennzeichnung schon im Backend sichtbar ----------
// Listenansicht: Spalte „KI“ und Filter; Raster und Medien-Fenster: Plakette auf der Kachel (aktualisiert sich nach dem Speichern).
// Im Backend in allen Projekten gleich (neutral), nur die Kennung kommt aus KBS_KI_ARTEN.

/** Kennung und Werkzeug eines Anhangs für das Backend (leer = keine KI). */
function kbs_ki_backend( int $id ): array {
	$art = kbs_ki_art( $id );
	return '' === $art
		? array( 'art' => '', 'label' => '', 'werkzeug' => '' )
		: array( 'art' => $art, 'label' => KBS_KI_ARTEN[ $art ]['label'], 'werkzeug' => trim( (string) get_post_meta( $id, 'ki_werkzeug', true ) ) );
}

add_filter(
	'manage_media_columns',
	function ( $spalten ) {
		$neu = array();
		foreach ( $spalten as $schluessel => $name ) {
			$neu[ $schluessel ] = $name;
			if ( 'title' === $schluessel ) {
				$neu['ki'] = 'KI';
			}
		}
		return isset( $neu['ki'] ) ? $neu : $neu + array( 'ki' => 'KI' );
	}
);
add_action(
	'manage_media_custom_column',
	function ( $spalte, $id ) {
		if ( 'ki' !== $spalte ) {
			return;
		}
		$k = kbs_ki_backend( (int) $id );
		if ( '' === $k['art'] ) {
			echo '<span aria-hidden="true">–</span><span class="screen-reader-text">keine KI</span>';
			return;
		}
		echo '<span class="ki-admin-plakette">' . esc_html( $k['label'] ) . '</span>';
		if ( '' !== $k['werkzeug'] ) {
			echo '<br><small>' . esc_html( $k['werkzeug'] ) . '</small>';
		}
	},
	10,
	2
);

// Filter über der Liste: alle, nur KI, ohne KI, je Art
add_action(
	'restrict_manage_posts',
	function ( $typ ) {
		if ( 'attachment' !== $typ ) {
			return;
		}
		$wahl     = sanitize_key( wp_unslash( $_GET['ki_filter'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$optionen = array( '' => 'KI: alle Medien', 'ja' => 'nur mit KI', 'nein' => 'ohne KI' ) + array_map( fn( $a ) => '– ' . $a['label'], KBS_KI_ARTEN );
		echo '<label for="ki-filter" class="screen-reader-text">Nach KI-Kennzeichnung filtern</label><select name="ki_filter" id="ki-filter">';
		foreach ( $optionen as $wert => $text ) {
			echo '<option value="' . esc_attr( $wert ) . '"' . selected( $wahl, $wert, false ) . '>' . esc_html( $text ) . '</option>';
		}
		echo '</select>';
	}
);
add_action(
	'pre_get_posts',
	function ( $q ) {
		if ( ! is_admin() || ! $q->is_main_query() || 'attachment' !== $q->get( 'post_type' ) ) {
			return;
		}
		$wahl = sanitize_key( wp_unslash( $_GET['ki_filter'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( '' === $wahl ) {
			return;
		}
		$arten = array_keys( KBS_KI_ARTEN );
		if ( 'ja' === $wahl ) {
			$bedingung = array( 'key' => 'ki_art', 'value' => $arten, 'compare' => 'IN' );
		} elseif ( 'nein' === $wahl ) {
			$bedingung = array( 'relation' => 'OR', array( 'key' => 'ki_art', 'compare' => 'NOT EXISTS' ), array( 'key' => 'ki_art', 'value' => $arten, 'compare' => 'NOT IN' ) );
		} elseif ( in_array( $wahl, $arten, true ) ) {
			$bedingung = array( 'key' => 'ki_art', 'value' => $wahl );
		} else {
			return;
		}
		$meta   = (array) $q->get( 'meta_query' );
		$meta[] = $bedingung;
		$q->set( 'meta_query', $meta );
	}
);

// Raster und Medien-Fenster: Kennung in den Bilddaten mitgeben …
add_filter(
	'wp_prepare_attachment_for_js',
	function ( $daten, $anhang ) {
		$k                = kbs_ki_backend( (int) $anhang->ID );
		$daten['kiArt']   = $k['art'];
		$daten['kiLabel'] = $k['label'];
		return $daten;
	},
	10,
	2
);

/** Stile der Plakette im Backend (Liste, Raster, Medien-Fenster – auch im Etch-Builder). */
function kbs_ki_backend_css(): string {
	return '<style id="kbs-ki-backend">'
		. '.ki-admin-plakette{display:inline-block;padding:2px 8px;border-radius:999px;background:#1d2327;color:#fff;font-size:11px;font-weight:600;line-height:1.6;white-space:nowrap;letter-spacing:.02em}'
		. '.ki-admin-plakette--kachel{position:absolute;top:6px;right:6px;z-index:2;max-width:calc(100% - 12px);overflow:hidden;text-overflow:ellipsis;pointer-events:none;box-shadow:0 0 0 1px rgba(255,255,255,.55)}'
		. '.fixed .column-ki{width:9em}'
		. '</style>';
}
add_action(
	'admin_head-upload.php',
	function () {
		echo kbs_ki_backend_css(); // phpcs:ignore WordPress.Security.EscapeOutput
	}
);

// … und auf jede Kachel eine Plakette setzen. print_media_templates läuft überall, wo das Medien-Fenster geladen wird.
add_action(
	'print_media_templates',
	function () {
		echo kbs_ki_backend_css(); // phpcs:ignore WordPress.Security.EscapeOutput
		?>
<script>
(function () {
	function einrichten() {
		if (!window.wp || !wp.media || !wp.media.view || !wp.media.view.Attachment || wp.media.view.Attachment.prototype.kbsKi) return;
		var A = wp.media.view.Attachment.prototype, init = A.initialize, render = A.render;
		A.kbsKi = true;
		A.initialize = function () {
			init.apply(this, arguments);
			// Nach dem Speichern der Felder liefert WordPress neue Bilddaten; dann neu zeichnen
			this.listenTo(this.model, 'change:kiLabel', this.render);
		};
		A.render = function () {
			render.apply(this, arguments);
			var vorschau = this.el.querySelector('.attachment-preview');
			if (!vorschau) return this;
			var label = this.model.get('kiLabel'), plakette = vorschau.querySelector('.ki-admin-plakette');
			if (label) {
				if (!plakette) {
					plakette = document.createElement('span');
					plakette.className = 'ki-admin-plakette ki-admin-plakette--kachel';
					plakette.setAttribute('aria-hidden', 'true');
					vorschau.appendChild(plakette);
				}
				plakette.textContent = label;
			} else if (plakette) {
				plakette.parentNode.removeChild(plakette);
			}
			return this;
		};
	}
	einrichten();
	document.addEventListener('DOMContentLoaded', einrichten);
})();
</script>
		<?php
	}
);

// Darstellung der KI-Kennzeichnung wirkt auf alle Seiten: nach dem Speichern den Seitencache leeren (LiteSpeed Cache)
add_action( 'update_option_' . KBS_KI_OPTION, fn() => do_action( 'litespeed_purge_all' ) );
add_action( 'add_option_' . KBS_KI_OPTION, fn() => do_action( 'litespeed_purge_all' ) );
