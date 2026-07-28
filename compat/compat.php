<?php

class SiteOrigin_Widgets_Bundle_Compatibility {
	/**
	 * Get the singleton instance
	 *
	 * @return SiteOrigin_Widgets_Bundle_Compatibility
	 */
	public static function single() {
		static $single;

		return empty( $single ) ? $single = new self() : $single;
	}

	public function __construct() {
		add_action( 'init' , array( $this, 'init' ) );
	}

	public function init() {
		$builder = $this->get_active_builder();

		if ( ! empty( $builder ) ) {
			require_once $builder['file_path'];
		}

		if ( function_exists( 'register_block_type' ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'block-editor/widget-block.php';
		}

		// These actions handle alerting cache plugins that they need to regenerate a page cache.
		if ( apply_filters( 'siteorigin_widgets_load_cache_compatibility', true ) ) {
			add_action( 'siteorigin_widgets_stylesheet_deleted', array( $this, 'clear_page_cache' ) );
			add_action( 'siteorigin_widgets_stylesheet_added', array( $this, 'clear_page_cache' ) );
			add_action( 'siteorigin_widgets_stylesheet_cleared', array( $this, 'clear_all_cache' ) );
		}

		// Compatibility with AMP plugin.
		if (
			function_exists( 'amp_is_enabled' ) &&
			amp_is_enabled()
		) {
			// AMP plugin is installed and enabled. Remove Slider Lazy Loading.
			add_filter( 'siteorigin_widgets_slider_attr', function ( $attr ) {
				if ( ! empty( $attr['class'] ) ) {
					$attr['class'] = str_replace( ' skip-lazy', '', $attr['class'] );
				}
				$attr['loading'] = false;

				return $attr;
			} );
		}

		// Compatibility with WooCommerce.
		if ( function_exists( 'WC' ) ) {
			add_filter( 'woocommerce_format_content', array( $this, 'woocommerce_shop_page_content' ), 10, 2 );
		}

		// Contribute this plugin's field-sanitization semantics version to
		// Page Builder's Layout Block trust-signature scheme, so a signature
		// computed before a security-relevant tightening of our own field
		// sanitizers is correctly treated as stale. Registered unconditionally:
		// apply_filters() on a filter tag that Page Builder never triggers
		// (e.g. Page Builder inactive) is a harmless no-op, matching this
		// plugin's existing pattern for other 'siteorigin_panels_*' filters
		// (see so-widgets-bundle.php and base/inc/shortcode.php).
		add_filter( 'siteorigin_panels_sanitize_version', array( $this, 'add_sanitize_version_signal' ) );
	}

	/**
	 * Append this plugin's own field-sanitization semantics version to
	 * Page Builder's composed 'siteorigin_panels_sanitize_version' filter
	 * value, so its Layout Block trust-signature scheme can detect when
	 * this plugin's sanitization rules have materially tightened.
	 *
	 * @param string $version
	 *
	 * @return string
	 */
	public function add_sanitize_version_signal( $version ) {
		// The '1' below is the version identifier for this plugin's
		// field-sanitization SEMANTICS — i.e. what content a sanitizer
		// allows through, not its code/bugfix history. Consumed by
		// siteorigin-panels' Layout Block trust-signature scheme so a stale
		// save-time signature can be detected and invalidated when this
		// plugin's sanitization rules materially tighten.
		//
		// Bump this value ONLY when a field/widget sanitizer becomes MORE
		// RESTRICTIVE in a security-relevant way (e.g. a previously-unfiltered
		// field starts running wp_kses_post(), or a bypass is closed).
		//
		// NEVER bump it for idempotency fixes, bugfixes, or any behavior
		// change that does not affect what content is allowed through.
		//
		// Bumping this invalidates EVERY existing siteorigin-panels Layout
		// Block trust signature site-wide, with NO bulk remediation path:
		// each affected post must be individually re-saved by its own author
		// to regain trusted-render status (capability-gated sanitization is
		// only meaningful under the real author's session, so there is no
		// safe way to batch/cron re-sign on their behalf). This is an
		// accepted, intentionally expensive cost for genuine security
		// tightening — do not bump casually, and do not bump for anything
		// covered by the "never" list above.
		return $version . ';sowb:1';
	}

	public function get_active_builder() {
		$builders = include_once 'builders.php';

		foreach ( $builders as $builder ) {
			if ( $this->is_builder_active( $builder ) ) {
				return $builder;
			}
		}

		return null;
	}

	public function is_builder_active( $builder ) {
		switch ( $builder[ 'name' ] ) {
			case 'Beaver Builder':
				return class_exists( 'FLBuilderModel', false );
				break;

			case 'Elementor':
				return class_exists( 'Elementor\\Plugin', false );
				break;

			case 'Visual Composer':
				return class_exists( 'Vc_Manager' );
				break;
		}
	}

	/**
	 * Work out which post a generated stylesheet belongs to.
	 *
	 * The filename is the only record of this. save_css() appends the current
	 * post id as the file is written, and the widget instance doesn't carry it:
	 * Page Builder's panels_info holds the widget's position in the layout
	 * (grid, cell, id, style), never the post it belongs to.
	 *
	 * @param $name The name of the stylesheet file.
	 *
	 * @return int|bool The post id, or false if it can't be determined.
	 */
	private function get_cache_post_id( $name ) {
		// Stylesheets are named {id_base}-{style}-{hash}[-{post_id}].css and
		// the post id is only added for widgets in a Page Builder post, so
		// most filenames end in a hash rather than an id.
		$segments = explode( '-', basename( $name, '.css' ) );
		$last = end( $segments );

		// ctype_digit() rejects both the hash and values like '1e5', which
		// is_numeric() would accept as a number.
		if ( $last === '' || ! ctype_digit( $last ) ) {
			return false;
		}

		// A hash made up entirely of digits would get this far, so only purge
		// once we know the id belongs to a real post.
		$id = (int) $last;

		return get_post_status( $id ) ? $id : false;
	}

	/**
	 * Tell cache plugins that they need to regenerate a page cache.
	 *
	 * @param $name The name of the file that's been deleted.
	 */
	public function clear_page_cache( $name ) {
		$id = $this->get_cache_post_id( $name );

		if ( $id === false ) {
			return;
		}

		if ( function_exists( 'w3tc_flush_post' ) ) {
			w3tc_flush_post( $id );
		}

		if ( class_exists( 'Swift_Performance_Cache' ) ) {
			Swift_Performance_Cache::clear_post_cache( $id );
		}

		if ( class_exists( '\Hummingbird\\WP_Hummingbird' ) ) {
			do_action( 'wphb_clear_page_cache', $id );
		}

		if ( function_exists( 'breeze_varnish_purge_cache' ) ) {
			$permalink = get_the_permalink( $id );

			if ( ! empty( $permalink ) ) {
				breeze_varnish_purge_cache( $permalink );
			}
		}

		// Use LiteSpeed's purge action rather than sending an
		// x-litespeed-purge header ourselves. The action stores the purge
		// in a queue when headers have already been sent, or when running
		// under cron or WP-CLI, so it still works during plugin updates.
		// A raw header has no such fallback and is silently dropped.
		if ( defined( 'LSCWP_V' ) || function_exists( 'run_litespeed_cache' ) ) {
			do_action( 'litespeed_purge_post', $id );
		}

		if ( function_exists( 'rocket_clean_post' ) ) {
			rocket_clean_post( $id );
		}

		if ( class_exists( 'WP_Optimize' ) ) {
			WPO_Page_Cache::instance()->delete_single_post_cache( $id );
		}
	}

	/**
	 * Tell cache plugins that they need to regenerate their all page cache.
	 */
	public function clear_all_cache() {
		if ( function_exists( 'w3tc_flush_all' ) ) {
			w3tc_flush_all();
		}

		if ( class_exists( 'Swift_Performance_Cache' ) ) {
			Swift_Performance_Cache::clear_all_cache();
		}

		if ( class_exists( '\Hummingbird\\WP_Hummingbird' ) ) {
			do_action( 'wphb_clear_page_cache' );
		}

		if ( class_exists( 'Breeze_PurgeCache' ) ) {
			Breeze_PurgeCache::breeze_cache_flush();
		}

		// See the note in clear_page_cache(): the purge action queues itself
		// when a header can't be relied on, which is the case during the
		// AJAX, cron, and WP-CLI requests that plugin updates run in.
		if ( defined( 'LSCWP_V' ) || function_exists( 'run_litespeed_cache' ) ) {
			do_action( 'litespeed_purge_all' );
		}

		if ( function_exists( 'rocket_clean_domain' ) && function_exists( 'rocket_clean_minify' ) ) {
			rocket_clean_domain();
			rocket_clean_minify( 'css' );
		}

		if ( class_exists( 'WP_Optimize' ) ) {
			// WP Optimize does a filter check to see if it should purge the cache.
			// This filter will allow us to bypass that check.
			add_filter( 'wpo_purge_page_cache_on_activate_deactivate_plugin', '__return_true' );
			WPO_Page_Cache::instance()->purge();
		}
	}

	/**
	 * Filter the content of the WooCommerce shop page to ensure that our widgets are rendered correctly.
	 *
	 * @param $content
	 *
	 * @return string
	 */
	public function woocommerce_shop_page_content( $content ) {
		if ( is_search() ) {
			return $content;
		}

		if (
			! is_post_type_archive( 'product' ) ||
			! in_array( absint( get_query_var( 'paged' ) ), array( 0, 1 ), true )
		) {
			return $content;
		}

		$shop_page = get_post( wc_get_page_id( 'shop' ) );
		if ( empty( $shop_page ) ) {
			return $content;
		}

		$blocks = parse_blocks( $shop_page->post_content );

		// Check if any SiteOrigin Widgets Bundle blocks.
		$blocks = array_filter( $blocks, array( $this, 'find_sowb_block' ) );
		if ( ! empty( $blocks ) ) {
			$content = do_blocks( $shop_page->post_content );
		}

		return $content;
	}

	public function find_sowb_block( $block ) {
		if (
			! empty( $block['blockName'] ) &&
			strpos( $block['blockName'], 'sowb/' ) === 0
		) {
			return true;
		}

		foreach ( $block['innerBlocks'] as $inner ) {
			if ( $this->find_sowb_block( $inner ) ) {
				return true;
			}
		}

		return false;
	}
}

SiteOrigin_Widgets_Bundle_Compatibility::single();
