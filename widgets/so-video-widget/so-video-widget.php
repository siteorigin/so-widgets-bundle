<?php

/*
Widget Name: Video widget
Description: Play all your self or externally hosted videos in a customizable video player.
Author: SiteOrigin
Author URI: http://siteorigin.com
*/


class SiteOrigin_Widget_Video_Widget extends SiteOrigin_Widget {

	private $video_hosts;

	function __construct() {

		$this->video_hosts = include plugin_dir_path( __FILE__ ) . 'data/video_hosts.php';

		$video_host_names = array();
		foreach ( $this->video_hosts as $key => $value ) {
			$video_host_names[ $key ] = $value['label'];
		}

		parent::__construct(
			'sow-video',
			__( 'SiteOrigin Video', 'siteorigin-widgets' ),
			array(
				'description' => __( 'A video player widget.', 'siteorigin-widgets' ),
				'help'        => 'http://siteorigin.com/widgets-bundle/video-widget-documentation/'
			),
			array(),
			array(
				'title' => array(
					'type' => 'text',
					'label' => __( 'Title', 'siteorigin-widgets' )
				),
				'host' => array(
					'type' => 'radio',
					'label' => __( 'Video location', 'siteorigin-widgets' ),
					'state_selector' => true,
					'default' => 'self',
					'options' => array(
						'self' => __( 'Self hosted', 'siteorigin-widgets' ),
						'external' => __( 'Externally hosted', 'siteorigin-widgets' ),
					),
				),
				'self_video' => array(
					'type' => 'media',
					'label' => __( 'Select Video', 'siteorigin-widgets' ),
					'default'     => '',
					'library' => 'video',
					'state_name' => 'self',
				),
				'video_host' => array(
					'type' => 'select',
					'label' => __( '', 'siteorigin-widgets' ),
					'state_name' => 'external',
					'prompt'  => __( 'Select external', 'siteorigin-widgets' ),
					'options' => $video_host_names,
				),
				'external_video' => array(
					'type' => 'text',
					'state_name' => 'external',
					'label' => __( 'Video ID', 'siteorigin-widgets' )
				),
				'autoplay' => array(
					'type' => 'checkbox',
					'default' => true,
					'label' => __( 'Autoplay', 'siteorigin-widgets' )
				),
				'skin' => array(
					'type' => 'select',
					'label' => __( 'Video player skin', 'siteorigin-widgets' ),
					'options' => array(
						'default' => __( 'Default', 'siteorigin-widgets' ),
						'skin_one' => __( 'Skin One', 'siteorigin-widgets' ),
						'skin_two' => __( 'Skin Two', 'siteorigin-widgets' ),
						'skin_three' => __( 'Skin Three', 'siteorigin-widgets' ),
					)
				)
			)
		);
	}

	function get_template_name( $instance ) {
		if ( !empty( $instance['skin'] ) ) {
			return $instance['skin'];
		}
		return 'default';
	}

	function get_style_name( $instance ) {
		return '';
	}
}
siteorigin_widget_register( 'video', __FILE__ );