<?php
/**
 * Plugin Name: KBS – Navigation
 * Description: Skripte für die Hauptnavigation der Etch-Komponente „Header“: Menü auf kleinen Bildschirmen auf- und zuklappen ([data-nav-toggle]), Untermenüs als Disclosure ([data-nav-sub]), Escape schließt, aktueller Menüpunkt mit aria-current (auch der Elternpunkt auf Unterseiten), Umschalter Hell/Dunkel ([data-scheme-toggle], ACSS-Klassen scheme--light/scheme--dark am <html>, Wahl im localStorage „kbs-farbschema“). Setzt die Klasse „js“ am <html>, damit das Menü ohne JavaScript sichtbar bleibt. Keine Shortcodes, kein Markup.
 *
 * Gehört auf die Live-Seite. Quelle: Repository kbs, wordpress/snippets/kbs-navigation.php
 */

defined( 'ABSPATH' ) || exit;

// Der Umschalter Hell/Dunkel lässt sich im Backend ein- und ausschalten: Firmendaten › Firma & Kontakt › „Umschalter Hell/Dunkel“
// (Standard aus etch-nodes). Ausgeschaltet verschwindet die Schaltfläche, und eine gespeicherte Wahl wird nicht mehr
// angewendet – die Seite folgt dann immer dem Gerät.
add_filter(
	'rwmb_meta_boxes',
	function ( $boxen ) {
		$feld = array(
			'id'        => 'farbschema_umschalter',
			'name'      => 'Umschalter Hell/Dunkel',
			'type'      => 'switch',
			'style'     => 'rounded',
			'on_label'  => 'Ja',
			'off_label' => 'Nein',
			'std'       => 1,
			'desc'      => 'Zeigt Besuchern die Schaltfläche für das helle und dunkle Design. Ausgeschaltet verschwindet sie, und die Website folgt immer der Einstellung des Geräts.',
		);
		foreach ( $boxen as $i => $b ) {
			if ( 'firmendaten-firma' === ( $b['id'] ?? '' ) ) {
				$boxen[ $i ]['fields'][] = $feld;
				break;
			}
		}
		return $boxen;
	},
	20
);

/** Ist der Umschalter eingeschaltet? Ohne gespeicherten Wert: ja. */
function kbs_farbschema_an(): bool {
	$daten = (array) get_option( 'firmendaten', array() );
	return ! array_key_exists( 'farbschema_umschalter', $daten ) || ! empty( $daten['farbschema_umschalter'] );
}

// Solange noch nie ein Wert gespeichert wurde, den Schalter im Backend als eingeschaltet zeigen – so, wie er wirkt.
// Sonst stünde er auf „Nein“, und das nächste Speichern der Seite würde den Umschalter unbemerkt abschalten.
add_filter(
	'rwmb_farbschema_umschalter_field_meta',
	function ( $wert ) {
		return array_key_exists( 'farbschema_umschalter', (array) get_option( 'firmendaten', array() ) ) ? $wert : 1;
	}
);

// Nach dem Speichern der Einstellungsseite den Seitencache leeren, damit der Schalter sofort wirkt.
add_action( 'update_option_firmendaten', fn() => do_action( 'litespeed_purge_all' ) );

add_action(
	'wp_head',
	function () {
		if ( ! kbs_farbschema_an() ) {
			// Umschalter ausgeschaltet: Die Klasse „js“ braucht die Navigation weiterhin; keine Schema-Klasse, Schaltfläche ausblenden.
			echo "<script>document.documentElement.classList.add('js')</script><style>[data-scheme-toggle]{display:none!important}</style>\n";
			return;
		}
		// Gespeichertes Farbschema vor dem ersten Zeichnen setzen (kein Aufblitzen des anderen Schemas)
		echo "<script>(function(d){d.classList.add('js');try{var s=localStorage.getItem('kbs-farbschema');if(s==='light'||s==='dark')d.classList.add('scheme--'+s);}catch(e){}})(document.documentElement)</script>\n";
	},
	0
);

add_action(
	'wp_footer',
	function () {
		?>
<script>
(function () {
	var nav = document.querySelector('.main-nav');
	if (!nav) return;
	var toggle = nav.querySelector('[data-nav-toggle]');
	var subs = nav.querySelectorAll('[data-nav-sub]');

	function setze(btn, offen) {
		btn.setAttribute('aria-expanded', offen ? 'true' : 'false');
		// Geschlossenes Untermenü sofort verstecken (ohne Ausblenden): sonst ist es während der Animation noch per Tab erreichbar.
		// Nach der Animationsdauer wieder freigeben, damit das Öffnen per Maus (:hover) weiter funktioniert.
		var sub = btn.hasAttribute('data-nav-sub') && document.getElementById(btn.getAttribute('aria-controls'));
		if (!sub) return;
		clearTimeout(sub.kbsZu);
		sub.classList.toggle('main-nav__sub--zu', !offen);
		if (!offen) sub.kbsZu = setTimeout(function () { sub.classList.remove('main-nav__sub--zu'); }, 400);
	}
	function schliesseSubs(ausser) { subs.forEach(function (b) { if (b !== ausser) setze(b, false); }); }
	function menueOffen() { return !!toggle && toggle.getAttribute('aria-expanded') === 'true'; }
	function schliesseMenue() { setze(toggle, false); nav.classList.remove('main-nav--open'); }
	// Per Maus (:hover) geöffnetes Untermenü ausblenden, bis die Maus den Menüpunkt verlässt (WCAG 1.4.13: mit Escape schließbar)
	function versteckeHover() {
		var sub = nav.querySelector('.main-nav__item--has-sub:hover > .main-nav__sub');
		if (!sub) return false;
		clearTimeout(sub.kbsZu);
		sub.classList.add('main-nav__sub--zu');
		sub.parentNode.addEventListener('mouseleave', function () { sub.classList.remove('main-nav__sub--zu'); }, { once: true });
		return true;
	}

	if (toggle) {
		toggle.addEventListener('click', function () {
			var offen = !menueOffen();
			setze(toggle, offen);
			nav.classList.toggle('main-nav--open', offen);
		});
	}
	subs.forEach(function (b) {
		b.addEventListener('click', function () {
			var offen = b.getAttribute('aria-expanded') !== 'true';
			schliesseSubs(b);
			setze(b, offen);
		});
	});
	document.addEventListener('keydown', function (e) {
		if (e.key !== 'Escape') return;
		var offenerSub = nav.querySelector('[data-nav-sub][aria-expanded="true"]');
		if (offenerSub) { setze(offenerSub, false); versteckeHover(); offenerSub.focus(); return; }
		if (versteckeHover()) return;
		if (menueOffen()) { schliesseMenue(); toggle.focus(); }
	});
	document.addEventListener('click', function (e) { if (!nav.contains(e.target)) schliesseSubs(null); });
	nav.addEventListener('focusout', function (e) {
		if (nav.contains(e.relatedTarget)) return;
		if (window.matchMedia('(min-width: 64em)').matches) { schliesseSubs(null); return; }
		// Mobil: Menü schließen, wenn der Fokus weiterzieht – sonst läuft er hinter dem offenen Menü weiter (WCAG 2.4.7)
		if (e.relatedTarget && menueOffen()) schliesseMenue();
	});

	// Aktuelle Seite markieren; auf Unterseiten zusätzlich den Elternpunkt
	var pfad = location.pathname.replace(/\/?$/, '/');
	nav.querySelectorAll('a[href^="/"]').forEach(function (a) {
		var ziel = a.getAttribute('href').replace(/[#?].*$/, '').replace(/\/?$/, '/');
		if (ziel === pfad) a.setAttribute('aria-current', 'page');
		else if (ziel !== '/' && pfad.indexOf(ziel) === 0 && a.classList.contains('main-nav__link')) a.classList.add('main-nav__link--active');
	});
})();

<?php if ( kbs_farbschema_an() ) : ?>
// Hell/Dunkel: ohne gespeicherte Wahl folgt die Seite dem Gerät (ACSS „light dark“).
// Entspricht die neue Wahl der Geräteeinstellung, wird die gespeicherte Wahl gelöscht.
(function () {
	var btn = document.querySelector('[data-scheme-toggle]');
	if (!btn) return;
	var html = document.documentElement, mq = window.matchMedia('(prefers-color-scheme: dark)'), KEY = 'kbs-farbschema';
	function system() { return mq.matches ? 'dark' : 'light'; }
	function aktuell() {
		if (html.classList.contains('scheme--dark')) return 'dark';
		if (html.classList.contains('scheme--light')) return 'light';
		return system();
	}
	function zeige() { btn.setAttribute('aria-pressed', aktuell() === 'dark' ? 'true' : 'false'); }
	btn.addEventListener('click', function () {
		var neu = aktuell() === 'dark' ? 'light' : 'dark';
		html.classList.remove('scheme--light', 'scheme--dark');
		if (neu !== system()) html.classList.add('scheme--' + neu);
		try {
			if (neu === system()) localStorage.removeItem(KEY);
			else localStorage.setItem(KEY, neu);
		} catch (e) {}
		zeige();
	});
	if (mq.addEventListener) mq.addEventListener('change', zeige);
	zeige();
})();
<?php endif; ?>
</script>
		<?php
	},
	50
);
