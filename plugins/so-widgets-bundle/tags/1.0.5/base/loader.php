<?php

/**
 * Class SiteOrigin_Widgets_Loader
 *
 * The loader class handles all basic setup tasks for SiteOrigin widgets.
 */
class SiteOrigin_Widgets_Loader {
	private $file;
	private $widget_id;
	private $load_file;
	private $version;

	/**
	 * @param string $widget_id The widget ID
	 * @param string $file The current file
	 * @param string $load_file File that's loaded after the widget base is loaded
	 */
	function __construct($widget_id, $file, $load_file){
		$this->file = $file;
		$this->widget_id = $widget_id;
		$this->load_file = $load_file;

		add_action( 'widgets_init', array($this, 'widgets_init') );

	}

	/**
	 * Registers the widget that was created in this widget plugin.
	 *
	 * @action widgets_init
	 */
	function widgets_init(){
		$class_name = $this->get_class_name();
		if( class_exists( $class_name ) ) {
			register_widget($class_name);
		}
	}

	/**
	 * Get the name of the class used for this widget.
	 *
	 * @return string
	 */
	function get_class_name(){
		return 'SiteOrigin_Widget_' . str_replace( ' ', '', ucwords( str_replace('-', ' ', $this->widget_id) ) ) . '_Widget';
	}

}