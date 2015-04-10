<?php

/*
Widget Name: Video widget
Description: Play all your self or externally hosted videos in a customizable video player.
Author: SiteOrigin
Author URI: http://siteorigin.com
*/


class SiteOrigin_Widget_Video_Widget extends SiteOrigin_Widget {

	function __construct() {

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
				'host_type' => array(
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
					'fallback' => true,
					'label' => __( 'Select video', 'siteorigin-widgets' ),
					'default'     => '',
					'library' => 'video',
					'state_name' => 'self',
				),
				'self_poster' => array(
					'type' => 'media',
					'label' => __( 'Select cover image', 'siteorigin-widgets' ),
					'default'     => '',
					'library' => 'image',
					'state_name' => 'self',
				),
				'external_video' => array(
					'type' => 'text',
					'state_name' => 'external',
					'label' => __( 'Video URL', 'siteorigin-widgets' )
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

	function initialize() {
		$this->register_frontend_scripts(
			array(
				array(
					'so-video-widget',
					siteorigin_widget_get_plugin_dir_url( 'video' ) . 'js/so-video-widget' . SOW_BUNDLE_JS_SUFFIX . '.js',
					array( 'jquery', 'mediaelement' ),
					SOW_BUNDLE_VERSION
				)
			)
		);
		$this->register_frontend_styles(
			array(
				array(
					'wp-mediaelement',
				)
			)
		);
	}

	function enqueue_frontend_scripts( $instance ) {
		if ( !empty( $instance['external_video'] ) && $this->get_host_from_url( $instance['external_video'] ) == 'vimeo' ) {
			wp_enqueue_script( 'froogaloop' );
		}
		parent::enqueue_frontend_scripts( $instance );
	}

	function get_template_name( $instance ) {
//		if ( !empty( $instance['skin'] ) ) {
//			return $instance['skin'];
//		}
		return 'default';
	}

	function get_template_variables( $instance, $args ) {
		$poster = '';
		$src = '';
		$video_type = '';
		if ( $instance['host_type'] == 'self' ) {
			$vid_info = wp_get_attachment_metadata( $instance['self_video'] );
			$video_type = empty( $vid_info['fileformat'] ) ? '' : $vid_info['fileformat'];
			$src = !empty( $instance['self_video'] ) ? wp_get_attachment_url( $instance['self_video'] ) : '';
			$poster = !empty( $instance['self_poster'] ) ? wp_get_attachment_url( $instance['self_poster'] ) : '';
		}
		else {
			$video_type = $this->get_host_from_url( $instance['external_video'] );
			$src = !empty( $instance['external_video'] ) ? $instance['external_video'] : '';
		}
		return array(
			'player_id' => 'player1',
			'host_type' => $instance['host_type'],
			'src' => $src,
			'video_type' => $video_type,
			'poster' => $poster,
			'autoplay' => $instance['autoplay'],
			'skin_class' => $instance['skin']
		);
	}

	function get_style_name( $instance ) {
		if ( !empty( $instance['skin'] ) ) {
			return $instance['skin'];
		}
		return '';
	}

	function get_less_variables( $instance ) {
		$controls_url = siteorigin_widget_get_plugin_dir_url( 'video' ) . 'styles/controls-' . $instance['skin'] . '.png';
		return array(
			'controls_url' => "'" . $controls_url . "'",
		);
	}

	private function get_host_from_url( $video_url ) {
		preg_match( '/https?:\/\/(www.)?([A-Za-z0-9\-]+)\./', $video_url, $matches );
		return $matches[2];
	}
}
siteorigin_widget_register( 'video', __FILE__ );