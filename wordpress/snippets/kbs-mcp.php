<?php
/**
 * Plugin Name: KBS – MCP-Erweiterung
 * Description: Eng begrenzte MCP-Funktionen zum Aufbau der Website: Seiten, Etch-Templates, Etch-Komponenten und das globale Etch-Stylesheet aus dem Build übernehmen, ACSS-Farben setzen, Firmendaten importieren, Snippets nach WPCodeBox übertragen. Nur für Administratoren und nur in der Entwicklungsumgebung.
 * Version: 1.0.0
 *
 * Quelle: Repository kbs, wordpress/snippets/kbs-mcp.php (abgeleitet aus golfplatz-mcp.php)
 */

defined( 'ABSPATH' ) || exit;

// Build-Dateien (wordpress/etch/build.mjs → dist) liegen in wp-content/kbs/. Als WPCodeBox-Snippet läuft der Code per eval(),
// __DIR__ zeigt deshalb nicht auf diesen Ordner.
defined( 'KBS_DATEN' ) || define( 'KBS_DATEN', WP_CONTENT_DIR . '/kbs' );

// Nur in der Entwicklungsumgebung (Local meldet „local“). Bewusst live nutzen: define( 'KBS_MCP_LIVE', true ) in wp-config.php.
if ( ! in_array( wp_get_environment_type(), array( 'local', 'development' ), true ) && ! defined( 'KBS_MCP_LIVE' ) ) {
	return;
}

if ( ! class_exists( 'WP_Ability' ) ) {
	return;
}

add_action(
	'wp_abilities_api_categories_init',
	function () {
		if ( ! wp_has_ability_category( 'kbs' ) ) {
			wp_register_ability_category(
				'kbs',
				array(
					'label'       => 'Kotthaus Business Service',
					'description' => 'Projektspezifische Funktionen für den Aufbau der Website.',
				)
			);
		}
	}
);

function kbs_mcp_ability( string $name, array $args, bool $readonly ): void {
	wp_register_ability(
		'kbs/' . $name,
		array_merge(
			array(
				'category'            => 'kbs',
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'meta'                => array(
					'annotations' => array(
						'readonly'    => $readonly,
						'destructive' => false,
						'idempotent'  => $readonly,
					),
					'mcp'         => array( 'public' => true ),
				),
			),
			$args
		)
	);
}

/** Beitragstypen, die diese Funktionen lesen und schreiben dürfen. */
define( 'KBS_MCP_TYPES', array( 'page', 'wp_template', 'wp_block' ) );

/** Einstellungsseiten, die aus daten/einstellungen-<seite>.json importiert werden dürfen. */
define( 'KBS_MCP_SETTINGS', array( 'firmendaten' ) );

add_action(
	'wp_abilities_api_init',
	function () {
		kbs_mcp_ability(
			'list-content',
			array(
				'label'            => 'Seiten, Templates oder Komponenten auflisten',
				'description'      => 'Listet Seiten (page), Etch-Templates (wp_template) oder Etch-Komponenten (wp_block) mit ID, Titel, Slug und Status.',
				'input_schema'     => array(
					'type'       => 'object',
					'required'   => array( 'post_type' ),
					'properties' => array( 'post_type' => array( 'type' => 'string', 'enum' => KBS_MCP_TYPES ) ),
				),
				'execute_callback' => 'kbs_mcp_list_content',
			),
			true
		);

		kbs_mcp_ability(
			'get-content',
			array(
				'label'            => 'Inhalt lesen',
				'description'      => 'Liefert Block-Markup und Metadaten einer Seite, eines Etch-Templates oder einer Etch-Komponente.',
				'input_schema'     => array(
					'type'       => 'object',
					'required'   => array( 'id' ),
					'properties' => array( 'id' => array( 'type' => 'integer' ) ),
				),
				'execute_callback' => 'kbs_mcp_get_content',
			),
			true
		);

		kbs_mcp_ability(
			'sync-from-files',
			array(
				'label'            => 'Seiten, Templates, Komponenten und Stylesheet aus dem Build übernehmen',
				'description'      => 'Liest die gebauten Dateien aus wp-content/kbs/ (manifest.json, component-<key>.html, template-<slug>.html, page-<slug>.html, kbs.css) und speichert sie: Komponenten per Key, Templates per Slug, Seiten per Pfad (mit Auszug, Startseite), globales Etch-Stylesheet „KBS“ – jeweils anlegen oder aktualisieren, nichts löschen.',
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'what' => array( 'type' => 'string', 'enum' => array( 'all', 'components', 'templates', 'pages', 'stylesheet' ), 'default' => 'all' ),
					),
				),
				'execute_callback' => 'kbs_mcp_sync_from_files',
			),
			false
		);

		kbs_mcp_ability(
			'acss-colors',
			array(
				'label'            => 'Automatic.css: Farben und Buttons lesen oder setzen',
				'description'      => 'Ohne Eingabe: liefert die Farb-Einstellungen von Automatic.css. aus_datei: übernimmt wp-content/kbs/daten/acss-farben.json und acss-buttons.json (erzeugt von etch/build.mjs) und erzeugt das CSS neu. Andere Einstellungen sind nicht erlaubt.',
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array( 'aus_datei' => array( 'type' => 'boolean' ) ),
				),
				'execute_callback' => 'kbs_mcp_acss_colors',
			),
			false
		);

		kbs_mcp_ability(
			'import-settings',
			array(
				'label'            => 'Einstellungen aus dem Build importieren',
				'description'      => 'Liest wp-content/kbs/daten/einstellungen-<seite>.json und schreibt die Felder in die Einstellungsseite. Nicht aufgeführte Felder bleiben unverändert. Mit „felder“ nur die genannten Felder; „nur_leere“: nur Felder, die noch leer sind (überschreibt nichts, was im Backend gepflegt wurde). Erlaubt: ' . implode( ', ', KBS_MCP_SETTINGS ) . '.',
				'input_schema'     => array(
					'type'       => 'object',
					'required'   => array( 'seite' ),
					'properties' => array(
						'seite'     => array( 'type' => 'string', 'enum' => KBS_MCP_SETTINGS ),
						'felder'    => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
						'nur_leere' => array( 'type' => 'boolean', 'default' => true ),
					),
				),
				'execute_callback' => 'kbs_mcp_import_settings',
			),
			false
		);

		kbs_mcp_ability(
			'flush-permalinks',
			array(
				'label'            => 'Permalinks neu erzeugen',
				'description'      => 'Setzt die Permalink-Struktur auf /%postname%/, falls noch keine gesetzt ist, und erzeugt die Regeln neu.',
				'input_schema'     => array( 'type' => 'object' ),
				'execute_callback' => function () {
					if ( '' === (string) get_option( 'permalink_structure' ) ) {
						update_option( 'permalink_structure', '/%postname%/' );
					}
					flush_rewrite_rules( false );
					return array( 'permalink_structure' => get_option( 'permalink_structure' ) );
				},
			),
			false
		);

		kbs_mcp_ability(
			'trash-content',
			array(
				'label'            => 'Seite oder Beitrag in den Papierkorb',
				'description'      => 'Verschiebt Seiten (page) oder Beiträge (post) per ID in den Papierkorb (wiederherstellbar). Endgültiges Löschen ist nicht möglich; die Startseite wird nicht angefasst.',
				'input_schema'     => array(
					'type'       => 'object',
					'required'   => array( 'ids' ),
					'properties' => array( 'ids' => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ) ) ),
				),
				'execute_callback' => function ( $input ) {
					$log = array();
					foreach ( (array) $input['ids'] as $id ) {
						$post = get_post( (int) $id );
						if ( ! $post || ! in_array( $post->post_type, array( 'page', 'post' ), true ) ) {
							$log[] = array( 'id' => (int) $id, 'status' => 'nicht gefunden oder nicht erlaubt' );
						} elseif ( (int) get_option( 'page_on_front' ) === $post->ID ) {
							$log[] = array( 'id' => $post->ID, 'status' => 'Startseite – nicht verschoben' );
						} else {
							$log[] = array( 'id' => $post->ID, 'titel' => $post->post_title, 'status' => wp_trash_post( $post->ID ) ? 'im Papierkorb' : 'Fehler' );
						}
					}
					return $log;
				},
			),
			false
		);

		kbs_mcp_ability(
			'site-title',
			array(
				'label'            => 'Titel der Website aus den Firmendaten setzen',
				'description'      => 'Setzt Einstellungen › Allgemein › Titel (Kurzname) und Untertitel (Kurzbeschreibung) aus der Einstellungsseite „Firmendaten“.',
				'input_schema'     => array( 'type' => 'object' ),
				'execute_callback' => function () {
					$o = (array) get_option( 'firmendaten', array() );
					$titel = trim( (string) ( $o['firma_kurzname'] ?? '' ) ) ?: trim( (string) ( $o['firma_name'] ?? '' ) );
					if ( '' === $titel ) {
						return new WP_Error( 'kbs_firma', 'Firmendaten sind leer.' );
					}
					update_option( 'blogname', $titel );
					update_option( 'blogdescription', trim( (string) ( $o['firma_claim'] ?? '' ) ) );
					return array( 'blogname' => get_option( 'blogname' ), 'blogdescription' => get_option( 'blogdescription' ) );
				},
			),
			false
		);

		kbs_mcp_ability(
			'snippets-sync',
			array(
				'label'            => 'PHP-Snippets nach WPCodeBox übernehmen',
				'description'      => 'Liest wp-content/kbs/snippets/*.php und legt sie in WPCodeBox im Ordner „KBS“ an bzw. aktualisiert den Code – über die WPCodeBox-Funktionen wpcodebox/* (deren Freigaben gelten). Erkennung am Schlagwort = Dateiname. aktivieren: Snippets einschalten – nur, wenn der Lader wp-content/mu-plugins/kbs-loader.php entfernt ist (sonst doppelte Funktionen). nur: Dateinamen ohne .php.',
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'aktivieren' => array( 'type' => 'boolean', 'default' => false ),
						'nur'        => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
					),
				),
				'execute_callback' => 'kbs_mcp_snippets_sync',
			),
			false
		);
	}
);

/** Eine WPCodeBox-Funktion aufrufen (damit Freigabe, Rechte und Protokoll von WPCodeBox greifen). */
function kbs_mcp_wpcb( string $name, array $input = array() ) {
	$ability = function_exists( 'wp_get_ability' ) ? wp_get_ability( 'wpcodebox/' . $name ) : null;
	if ( ! $ability ) {
		return new WP_Error( 'kbs_wpcb', 'WPCodeBox-Funktion wpcodebox/' . $name . ' fehlt (Plugin aktiv, Werkzeug in den WPCodeBox-MCP-Einstellungen freigegeben?).' );
	}
	return $ability->execute( $input );
}

function kbs_mcp_snippets_sync( $input ) {
	$dateien = glob( KBS_DATEN . '/snippets/*.php' ) ?: array();
	$nur     = array_filter( (array) ( $input['nur'] ?? array() ) );
	if ( ! $dateien ) {
		return new WP_Error( 'kbs_snippets', 'Keine Dateien in wp-content/kbs/snippets/ – Build ausführen und dist kopieren.' );
	}
	$ordner = kbs_mcp_wpcb( 'list-folders' );
	if ( is_wp_error( $ordner ) ) {
		return $ordner;
	}
	$ordner_id = 0;
	foreach ( (array) ( $ordner['folders'] ?? $ordner ) as $o ) {
		if ( is_array( $o ) && 'KBS' === ( $o['name'] ?? '' ) ) {
			$ordner_id = (int) $o['id'];
		}
	}
	if ( ! $ordner_id ) {
		$neu = kbs_mcp_wpcb( 'create-folder', array( 'name' => 'KBS' ) );
		if ( is_wp_error( $neu ) ) {
			return $neu;
		}
		$ordner_id = (int) $neu['id'];
	}
	$liste = kbs_mcp_wpcb( 'list-snippets' );
	if ( is_wp_error( $liste ) ) {
		return $liste;
	}
	$vorhanden = array();
	foreach ( (array) ( $liste['snippets'] ?? array() ) as $s ) {
		foreach ( (array) ( $s['snippetTags'] ?? $s['tags'] ?? array() ) as $tag ) {
			$vorhanden[ is_array( $tag ) ? ( $tag['value'] ?? $tag['name'] ?? '' ) : (string) $tag ] = $s;
		}
	}
	$lader = file_exists( WPMU_PLUGIN_DIR . '/kbs-loader.php' );
	$log   = array();
	foreach ( $dateien as $datei ) {
		$name = basename( $datei, '.php' );
		if ( $nur && ! in_array( $name, $nur, true ) ) {
			continue;
		}
		$code  = (string) file_get_contents( $datei );
		$kopf  = get_file_data( $datei, array( 'titel' => 'Plugin Name', 'text' => 'Description' ) );
		$daten = array(
			'title'       => $kopf['titel'] ?: $name,
			'description' => mb_substr( (string) $kopf['text'], 0, 2000 ),
			'code'        => $code,
			'folderId'    => $ordner_id,
			'snippetTags' => array( 'kbs', $name ),
			'hooks'       => array( array( 'hook' => 'custom_root' ) ),
		);
		$eintrag = array( 'datei' => $name );
		$s       = $vorhanden[ $name ] ?? null;
		if ( $s ) {
			$alt = kbs_mcp_wpcb( 'get-snippet', array( 'id' => (int) $s['id'] ) );
			if ( ! is_wp_error( $alt ) && str_replace( "\r\n", "\n", (string) ( $alt['code'] ?? '' ) ) === str_replace( "\r\n", "\n", $code ) ) {
				$eintrag['aktion'] = 'unverändert';
			} else {
				$r                 = kbs_mcp_wpcb( 'update-snippet', array( 'id' => (int) $s['id'] ) + $daten );
				$eintrag['aktion'] = is_wp_error( $r ) ? 'Fehler: ' . $r->get_error_message() : 'aktualisiert';
			}
			$id    = (int) $s['id'];
			$aktiv = ! empty( $s['enabled'] );
		} else {
			$r = kbs_mcp_wpcb( 'create-snippet', $daten + array( 'codeType' => 'php' ) );
			if ( is_wp_error( $r ) ) {
				$log[] = $eintrag + array( 'aktion' => 'Fehler: ' . $r->get_error_message() );
				continue;
			}
			$eintrag['aktion'] = 'angelegt (deaktiviert)';
			$id                = (int) $r['id'];
			$aktiv             = false;
		}
		$eintrag['id'] = $id;
		if ( ! empty( $input['aktivieren'] ) && ! $aktiv ) {
			if ( $lader ) {
				$eintrag['aktiv'] = 'nicht eingeschaltet: wp-content/mu-plugins/kbs-loader.php lädt die Dateien noch';
			} else {
				$r                = kbs_mcp_wpcb( 'enable-snippet', array( 'id' => $id ) );
				$eintrag['aktiv'] = is_wp_error( $r ) ? 'Fehler: ' . $r->get_error_message() : 'eingeschaltet';
			}
		} else {
			$eintrag['aktiv'] = $aktiv ? 'ja' : 'nein';
		}
		$log[] = $eintrag;
	}
	return $log;
}

function kbs_mcp_allowed_post( int $id, ?string $type = null ) {
	$post = get_post( $id );
	if ( ! $post || ! in_array( $post->post_type, KBS_MCP_TYPES, true ) || ( $type && $post->post_type !== $type ) ) {
		return new WP_Error( 'kbs_not_found', 'Inhalt nicht gefunden oder nicht erlaubt.' );
	}
	return $post;
}

/** Feste Etch-REST-Route intern ausführen. */
function kbs_mcp_etch_request( string $method, string $route, array $body = array() ): array {
	$request = new WP_REST_Request( $method, '/etch-api' . $route );
	if ( $body ) {
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $body ) );
	}
	$response = rest_do_request( $request );
	return array(
		'status' => $response->get_status(),
		'data'   => rest_get_server()->response_to_data( $response, false ),
	);
}

function kbs_mcp_list_content( $input ) {
	$type = $input['post_type'] ?? 'page';
	if ( ! in_array( $type, KBS_MCP_TYPES, true ) ) {
		return new WP_Error( 'kbs_type', 'Beitragstyp nicht erlaubt.' );
	}
	if ( 'wp_template' === $type ) {
		return kbs_mcp_etch_request( 'GET', '/templates' );
	}
	$posts = get_posts(
		array(
			'post_type'      => $type,
			'post_status'    => array( 'publish', 'draft', 'private' ),
			'posts_per_page' => -1,
			'orderby'        => 'menu_order title',
			'order'          => 'ASC',
		)
	);
	return array_map(
		fn( $p ) => array(
			'id'     => $p->ID,
			'title'  => $p->post_title,
			'slug'   => $p->post_name,
			'status' => $p->post_status,
			'parent' => $p->post_parent,
			'link'   => get_permalink( $p ),
		),
		$posts
	);
}

function kbs_mcp_get_content( $input ) {
	$post = kbs_mcp_allowed_post( (int) ( $input['id'] ?? 0 ) );
	if ( is_wp_error( $post ) ) {
		return $post;
	}
	return array(
		'id'         => $post->ID,
		'post_type'  => $post->post_type,
		'title'      => $post->post_title,
		'slug'       => $post->post_name,
		'status'     => $post->post_status,
		'content'    => $post->post_content,
		'properties' => 'wp_block' === $post->post_type ? get_post_meta( $post->ID, 'etch_component_properties', true ) : null,
		'key'        => 'wp_block' === $post->post_type ? get_post_meta( $post->ID, 'etch_component_html_key', true ) : null,
	);
}

/** Seite anlegen oder aktualisieren. Inhalt wird mit wp_slash() gespeichert, damit die JSON-Escapes erhalten bleiben. */
function kbs_mcp_save_page( array $input ) {
	$data = array(
		'post_type'   => 'page',
		'post_title'  => sanitize_text_field( $input['title'] ),
		'post_status' => $input['status'] ?? 'publish',
		'post_name'   => sanitize_title( $input['slug'] ),
		'post_parent' => (int) ( $input['parent'] ?? 0 ),
		'menu_order'  => (int) ( $input['order'] ?? 0 ),
	);
	if ( isset( $input['content'] ) ) {
		$data['post_content'] = wp_slash( $input['content'] );
	}
	if ( isset( $input['excerpt'] ) ) {
		$data['post_excerpt'] = sanitize_textarea_field( $input['excerpt'] );
	}
	if ( ! empty( $input['id'] ) ) {
		$data['ID'] = (int) $input['id'];
		$id         = wp_update_post( $data, true );
	} else {
		$id = wp_insert_post( $data, true );
	}
	if ( is_wp_error( $id ) ) {
		return $id;
	}
	return array( 'id' => $id, 'link' => get_permalink( $id ) );
}

function kbs_mcp_save_component( array $input ) {
	$data = array(
		'post_type'    => 'wp_block',
		'post_title'   => sanitize_text_field( $input['name'] ),
		'post_content' => wp_slash( $input['content'] ),
		'post_excerpt' => sanitize_text_field( $input['description'] ?? '' ),
		'post_status'  => 'publish',
	);
	if ( ! empty( $input['id'] ) ) {
		$data['ID'] = (int) $input['id'];
		$id         = wp_update_post( $data, true );
	} else {
		$id = wp_insert_post( $data, true );
	}
	if ( is_wp_error( $id ) ) {
		return $id;
	}
	update_post_meta( $id, 'etch_component_properties', $input['properties'] ?? array() );
	update_post_meta( $id, 'etch_component_html_key', sanitize_text_field( $input['key'] ) );
	return array( 'id' => $id );
}

function kbs_mcp_sync_from_files( $input ) {
	$dir      = KBS_DATEN;
	$what     = $input['what'] ?? 'all';
	$log      = array();
	$manifest = json_decode( (string) @file_get_contents( $dir . '/manifest.json' ), true );
	if ( ! is_array( $manifest ) && 'stylesheet' !== $what ) {
		return new WP_Error( 'kbs_manifest', 'manifest.json fehlt oder ist ungültig.' );
	}

	// Komponenten zuerst: Ihre IDs ersetzen die Platzhalter "__REF_<key>__" in Seiten, Templates und Komponenten.
	$refs     = array();
	$mit_refs = function ( string $markup ) use ( &$refs ) {
		foreach ( $refs as $key => $id ) {
			$markup = str_replace( '"__REF_' . $key . '__"', (string) $id, $markup );
		}
		// Übrige Verweise: vorhandene Fremdkomponenten (z. B. OhMyEtch „OmeAccordion“) per Key auflösen – ohne feste IDs.
		return preg_replace_callback(
			'/"__REF_([A-Za-z0-9_-]+)__"/',
			function ( $m ) use ( &$refs ) {
				if ( ! isset( $refs[ $m[1] ] ) ) {
					$ids             = get_posts( array( 'post_type' => 'wp_block', 'post_status' => 'publish', 'posts_per_page' => 1, 'meta_key' => 'etch_component_html_key', 'meta_value' => $m[1], 'fields' => 'ids' ) );
					$refs[ $m[1] ] = $ids ? (int) $ids[0] : 0;
				}
				return $refs[ $m[1] ] ? (string) $refs[ $m[1] ] : $m[0];
			},
			$markup
		);
	};
	foreach ( (array) ( $manifest['components'] ?? array() ) as $komp ) {
		$key       = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $komp['key'] );
		$vorhanden = get_posts(
			array(
				'post_type'      => 'wp_block',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'meta_key'       => 'etch_component_html_key',
				'meta_value'     => $key,
				'fields'         => 'ids',
			)
		);
		if ( in_array( $what, array( 'all', 'components' ), true ) ) {
			$file = $dir . '/component-' . $key . '.html';
			if ( ! is_readable( $file ) ) {
				$log[] = array( 'component' => $key, 'status' => 'Datei fehlt' );
				continue;
			}
			$res   = kbs_mcp_save_component(
				array(
					'id'          => $vorhanden ? $vorhanden[0] : 0,
					'name'        => (string) $komp['name'],
					'key'         => $key,
					'description' => (string) ( $komp['description'] ?? '' ),
					'content'     => $mit_refs( (string) file_get_contents( $file ) ),
					'properties'  => (array) ( $komp['properties'] ?? array() ),
				)
			);
			$log[] = array( 'component' => $key, 'result' => is_wp_error( $res ) ? $res->get_error_message() : $res );
			if ( ! is_wp_error( $res ) ) {
				$refs[ $key ] = (int) $res['id'];
			}
		} elseif ( $vorhanden ) {
			$refs[ $key ] = (int) $vorhanden[0];
		}
	}

	if ( in_array( $what, array( 'all', 'templates' ), true ) ) {
		$vorhanden = array();
		foreach ( (array) kbs_mcp_etch_request( 'GET', '/templates' )['data'] as $tpl ) {
			$vorhanden[ $tpl['slug'] ] = (int) $tpl['id'];
		}
		foreach ( $manifest['templates'] ?? array() as $tpl ) {
			$slug = sanitize_key( $tpl['slug'] );
			$file = $dir . '/template-' . $slug . '.html';
			if ( ! is_readable( $file ) ) {
				$log[] = array( 'template' => $slug, 'status' => 'Datei fehlt' );
				continue;
			}
			// Etch speichert Templates ohne wp_slash(); vorab maskieren, sonst gehen JSON-Escapes verloren.
			$body  = array(
				'post_title'   => (string) $tpl['title'],
				'post_name'    => $slug,
				'post_content' => wp_slash( $mit_refs( (string) file_get_contents( $file ) ) ),
			);
			$res   = isset( $vorhanden[ $slug ] )
				? kbs_mcp_etch_request( 'PUT', '/templates/' . $vorhanden[ $slug ], $body )
				: kbs_mcp_etch_request( 'POST', '/templates', $body );
			$log[] = array( 'template' => $slug, 'status' => $res['status'] );
		}
	}

	if ( in_array( $what, array( 'all', 'pages' ), true ) ) {
		foreach ( (array) ( $manifest['pages'] ?? array() ) as $seite ) {
			$slug = sanitize_title( $seite['slug'] );
			$file = $dir . '/page-' . $slug . '.html';
			if ( ! is_readable( $file ) ) {
				$log[] = array( 'page' => $slug, 'status' => 'Datei fehlt' );
				continue;
			}
			$parent = 0;
			if ( ! empty( $seite['parent'] ) ) {
				$eltern = get_page_by_path( sanitize_title( $seite['parent'] ) );
				$parent = $eltern ? $eltern->ID : 0;
			}
			$pfad      = ( $parent ? get_page_uri( $parent ) . '/' : '' ) . $slug;
			$vorhanden = get_page_by_path( $pfad );
			$res       = kbs_mcp_save_page(
				array(
					'id'      => $vorhanden ? $vorhanden->ID : 0,
					'title'   => (string) $seite['title'],
					'slug'    => $slug,
					'content' => $mit_refs( (string) file_get_contents( $file ) ),
					'excerpt' => (string) ( $seite['excerpt'] ?? '' ),
					'status'  => $seite['status'] ?? 'publish',
					'parent'  => $parent,
					'order'   => (int) ( $seite['order'] ?? 0 ),
				)
			);
			$log[] = array( 'page' => $pfad, 'result' => is_wp_error( $res ) ? $res->get_error_message() : $res );
			if ( ! is_wp_error( $res ) && ! empty( $seite['front_page'] ) ) {
				update_option( 'show_on_front', 'page' );
				update_option( 'page_on_front', (int) $res['id'] );
			}
		}
	}

	if ( in_array( $what, array( 'all', 'stylesheet' ), true ) ) {
		$css = (string) @file_get_contents( $dir . '/kbs.css' );
		if ( '' === $css ) {
			return new WP_Error( 'kbs_css', 'kbs.css fehlt.' );
		}
		$id = null;
		foreach ( (array) kbs_mcp_etch_request( 'GET', '/stylesheets' )['data'] as $key => $sheet ) {
			if ( is_array( $sheet ) && ( $sheet['name'] ?? '' ) === 'KBS' ) {
				$id = (string) ( $sheet['id'] ?? $key );
			}
		}
		$body  = array( 'name' => 'KBS', 'css' => $css );
		$res   = $id
			? kbs_mcp_etch_request( 'PUT', '/stylesheets/' . rawurlencode( $id ), $body )
			: kbs_mcp_etch_request( 'POST', '/stylesheets', $body );
		$log[] = array( 'stylesheet' => 'KBS', 'status' => $res['status'], 'id' => $id ?? ( $res['data']['id'] ?? null ) );
	}

	return $log;
}

/** Nur Farb-Einstellungen, das Aussehen der Buttons und die Schrift von Automatic.css. */
function kbs_mcp_acss_farbschluessel( string $key ): bool {
	return (bool) preg_match( '/^(color-[a-z0-9-]+|option-[a-z]+-clr|option-palette-unify-[a-z-]+|auto-color-scheme|website-color-scheme|option-ref-color-tokens|btn-(primary|secondary)-(hover-)?text|link-color(-hover)?|btn-(border-radius|border-width|font-weight|line-height|padding-block|padding-inline)|text-font-family|heading-(font-family|weight|letter-spacing)|(primary|secondary|tertiary|accent|base|neutral|success|warning|danger|info)(-(ultra-light|light|semi-light|semi-dark|dark|ultra-dark|hover))?-[lch](-alt)?-oklch)$/', $key );
}

function kbs_mcp_acss_colors( $input ) {
	if ( ! class_exists( '\Automatic_CSS\API' ) ) {
		return new WP_Error( 'kbs_acss', 'Automatic.css ist nicht aktiv.' );
	}
	$werte = array();
	if ( ! empty( $input['aus_datei'] ) ) {
		$werte = json_decode( (string) @file_get_contents( KBS_DATEN . '/daten/acss-farben.json' ), true );
		if ( ! is_array( $werte ) ) {
			return new WP_Error( 'kbs_daten', 'daten/acss-farben.json fehlt oder ist ungültig.' );
		}
		// Button-Aussehen (etch/acss-buttons.mjs) und Schrift (etch/acss-schrift.mjs)
		foreach ( array( 'acss-buttons', 'acss-schrift' ) as $datei ) {
			$zusatz = json_decode( (string) @file_get_contents( KBS_DATEN . '/daten/' . $datei . '.json' ), true );
			$werte  = array_merge( $werte, is_array( $zusatz ) ? $zusatz : array() );
		}
	}
	if ( $werte ) {
		$abgelehnt = array_values( array_filter( array_keys( $werte ), fn( $k ) => ! kbs_mcp_acss_farbschluessel( (string) $k ) ) );
		if ( $abgelehnt ) {
			return new WP_Error( 'kbs_acss_key', 'Nicht erlaubte Schlüssel: ' . implode( ', ', $abgelehnt ) );
		}
		try {
			$werte = array_map( 'strval', $werte );
			// API::update_settings() hält jeden Schlüssel mit „color-“ für eine Hex-Farbe (auch website-color-scheme).
			$hex    = array_filter( $werte, fn( $k ) => str_starts_with( (string) $k, 'color-' ) && preg_match( '/^#[0-9a-f]{6}$/i', $werte[ $k ] ), ARRAY_FILTER_USE_KEY );
			$andere = array_diff_key( $werte, $hex );
			if ( $andere ) {
				$db = \Automatic_CSS\Model\Database_Settings::get_instance();
				$db->save_settings( array_merge( $db->get_vars(), $andere ), true );
			}
			// Zuletzt die Hex-Farben über die API, die dabei das CSS neu erzeugt
			\Automatic_CSS\API::update_settings( $hex, array( 'regenerate_css' => true ) );
		} catch ( \Throwable $e ) {
			return new WP_Error( 'kbs_acss_save', $e->getMessage() );
		}
		return array_intersect_key( (array) \Automatic_CSS\API::get_settings(), $werte );
	}
	return array_filter( (array) \Automatic_CSS\API::get_settings(), fn( $k ) => kbs_mcp_acss_farbschluessel( (string) $k ), ARRAY_FILTER_USE_KEY );
}

function kbs_mcp_import_settings( $input ) {
	$seite = (string) ( $input['seite'] ?? '' );
	if ( ! in_array( $seite, KBS_MCP_SETTINGS, true ) ) {
		return new WP_Error( 'kbs_seite', 'Einstellungsseite nicht erlaubt.' );
	}
	$werte = json_decode( (string) @file_get_contents( KBS_DATEN . '/daten/einstellungen-' . $seite . '.json' ), true );
	if ( ! is_array( $werte ) ) {
		return new WP_Error( 'kbs_daten', 'Datei fehlt oder ist ungültig.' );
	}
	if ( ! empty( $input['felder'] ) && is_array( $input['felder'] ) ) {
		$werte = array_intersect_key( $werte, array_flip( array_map( 'sanitize_key', $input['felder'] ) ) );
	}
	$nur_leere = ! isset( $input['nur_leere'] ) || ! empty( $input['nur_leere'] );
	$option    = (array) get_option( $seite, array() );
	$log       = array( 'gesetzt' => array(), 'uebersprungen' => array() );
	foreach ( $werte as $feld => $wert ) {
		$feld = sanitize_key( $feld );
		if ( $nur_leere && isset( $option[ $feld ] ) && '' !== $option[ $feld ] ) {
			$log['uebersprungen'][] = $feld;
			continue;
		}
		$option[ $feld ]  = $wert;
		$log['gesetzt'][] = $feld;
	}
	update_option( $seite, $option );
	return $log;
}
