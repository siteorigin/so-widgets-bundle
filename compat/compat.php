<?php

class SiteOrigin_Widgets_Bundle_Compatibility {

	const BEAVER_BUILDER = 'BEAVER_BUILDER';

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
		add_action('wp', array( $this, 'init' ), 9 );
	}

	function init() {
		if ( $this->is_active( self::BEAVER_BUILDER ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'beaver-builder/beaver-builder.php';
		}
	}

	function is_active( $builder ) {
		switch ( $builder ) {
			case self::BEAVER_BUILDER:
				return class_exists( 'FLBuilderModel' ) && FLBuilderModel::is_builder_active();
			break;
		}
	}

}

SiteOrigin_Widgets_Bundle_Compatibility::single();
