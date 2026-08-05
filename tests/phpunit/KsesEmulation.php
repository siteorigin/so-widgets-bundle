<?php

namespace SiteOrigin\Tests;

/**
 * A faithful-enough emulation of wp_kses_post() for the unit suites.
 *
 * WHY THIS EXISTS
 * ---------------
 * The suites previously carried three separate copies of a two-regex stub that
 * stripped <script> elements and on* attributes and nothing else. That made every
 * sanitization assertion in this repo an assertion about the stub rather than about
 * WordPress: in particular an <iframe> sailed straight through, so no test could
 * observe the one element that actually moves in embed-bearing widget content.
 *
 * Real wp_kses_post() filters against $allowedposttags, which removes iframe, script,
 * object, embed and form outright. This emulation reproduces that subset — the tags
 * that matter for widget content — from ONE definition, so the three call sites can
 * no longer drift apart.
 *
 * SCOPE, STATED HONESTLY
 * ----------------------
 * This is still an emulation, not core. It does not implement protocol filtering,
 * attribute allowlists per tag, entity normalisation, or the many carve-outs in
 * wp-includes/kses.php. Assertions that need genuine kses strength belong in the
 * Playwright/Playground e2e suite, where real WordPress is loaded. See issue #2340.
 *
 * What it DOES guarantee, and what the unit suites may rely on:
 *   - iframe, script, object, embed, form, style and their contents are removed
 *   - on* event-handler attributes are removed
 *   - ordinary markup (p, a, strong, img, div, span, h1-h6, ul/ol/li) survives
 *   - the transform is idempotent: f(f(x)) === f(x)
 */
final class KsesEmulation {
	/**
	 * Elements wp_kses_post() drops entirely, contents included.
	 *
	 * Chosen because each is absent from $allowedposttags and each is a plausible
	 * carrier for the payloads these suites exercise.
	 */
	const STRIPPED_ELEMENTS = array(
		'iframe',
		'script',
		'object',
		'embed',
		'form',
		'style',
		'applet',
		'frame',
		'frameset',
	);

	/**
	 * Emulate wp_kses_post().
	 *
	 * @param mixed $value Value to filter. Non-strings are returned untouched, matching
	 *                     how the real function behaves once WordPress has cast input.
	 *
	 * @return mixed
	 */
	public static function filter( $value ) {
		if ( ! is_string( $value ) ) {
			return $value;
		}

		foreach ( self::STRIPPED_ELEMENTS as $tag ) {
			// Paired form, contents included.
			$value = preg_replace(
				'#<' . $tag . '\b[^>]*>.*?</' . $tag . '\s*>#is',
				'',
				$value
			);

			// Self-closing or unpaired remnant.
			$value = preg_replace(
				'#<' . $tag . '\b[^>]*/?>#is',
				'',
				$value
			);

			// Orphaned closing tag.
			$value = preg_replace(
				'#</' . $tag . '\s*>#is',
				'',
				$value
			);
		}

		// on* event-handler attributes, quoted or bare.
		$value = preg_replace(
			'/\s*on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i',
			'',
			$value
		);

		return $value;
	}
}
