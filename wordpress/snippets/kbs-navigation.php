<?php
/**
 * Plugin Name: KBS – Navigation
 * Description: Skripte für die Hauptnavigation der Etch-Komponente „Header“: Menü auf kleinen Bildschirmen auf- und zuklappen ([data-nav-toggle]), Untermenüs als Disclosure ([data-nav-sub]), Escape schließt, aktueller Menüpunkt mit aria-current (auch der Elternpunkt auf Unterseiten), Umschalter Hell/Dunkel ([data-scheme-toggle], ACSS-Klassen scheme--light/scheme--dark am <html>, Wahl im localStorage „kbs-farbschema“). Setzt die Klasse „js“ am <html>, damit das Menü ohne JavaScript sichtbar bleibt. Keine Shortcodes, kein Markup.
 *
 * Gehört auf die Live-Seite. Quelle: Repository kbs, wordpress/snippets/kbs-navigation.php
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_head',
	function () {
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

	function setze(btn, offen) { btn.setAttribute('aria-expanded', offen ? 'true' : 'false'); }
	function schliesseSubs(ausser) { subs.forEach(function (b) { if (b !== ausser) setze(b, false); }); }

	if (toggle) {
		toggle.addEventListener('click', function () {
			var offen = toggle.getAttribute('aria-expanded') !== 'true';
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
		if (offenerSub) { setze(offenerSub, false); offenerSub.focus(); return; }
		if (toggle && toggle.getAttribute('aria-expanded') === 'true') { setze(toggle, false); nav.classList.remove('main-nav--open'); toggle.focus(); }
	});
	document.addEventListener('click', function (e) { if (!nav.contains(e.target)) schliesseSubs(null); });
	nav.addEventListener('focusout', function (e) {
		if (window.matchMedia('(min-width: 64em)').matches && !nav.contains(e.relatedTarget)) schliesseSubs(null);
	});

	// Aktuelle Seite markieren; auf Unterseiten zusätzlich den Elternpunkt
	var pfad = location.pathname.replace(/\/?$/, '/');
	nav.querySelectorAll('a[href^="/"]').forEach(function (a) {
		var ziel = a.getAttribute('href').replace(/[#?].*$/, '').replace(/\/?$/, '/');
		if (ziel === pfad) a.setAttribute('aria-current', 'page');
		else if (ziel !== '/' && pfad.indexOf(ziel) === 0 && a.classList.contains('main-nav__link')) a.classList.add('main-nav__link--active');
	});
})();

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
</script>
		<?php
	},
	50
);
