<?php

class SiteOrigin_Widgets_Bundle_Compatibility {

	const BEAVER_BUILDER = 'Beaver Builder';
	const ELEMENTOR = 'Elementor';
	const VISUAL_COMPOSER = 'Visual Composer';

	/**
	 * Get the singleton instance
	 *
	 * @return SiteOrigin_Widgets_Bundle_Compatibility
	 */
	public static function single() {
		static $single;
		return empty( $single ) ? $single = new self() : $single;
	}

	function __construct() {
		$builder = $this->get_active_builder();
		if ( ! empty( $builder ) ) {
			require_once $builder['file_path'];
		}
		
		if ( function_exists( 'register_block_type' ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'block-editor/widget-block.php';
		}
	}

	function get_active_builder() {

		$builders = include_once 'builders.php';

		foreach ( $builders as $builder ) {
			if ( $this->is_active( $builder ) ) {
				return $builder;
			}
		}

		return null;
	}

	function is_active( $builder ) {
		switch ( $builder[ 'name' ] ) {
			case self::BEAVER_BUILDER:
				return class_exists( 'FLBuilderModel', false );
			break;
			case self::ELEMENTOR:
				return class_exists( 'Elementor\\Plugin', false );
			break;
			case self::VISUAL_COMPOSER:
				return class_exists( 'Vc_Manager' );
			break;
		}
	}

}

SiteOrigin_Widgets_Bundle_Compatibility::single();
