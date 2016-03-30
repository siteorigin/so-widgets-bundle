<?php
/*
Widget Name: Video Player
Description: Play all your self or externally hosted videos in a customizable video player.
Author: SiteOrigin
Author URI: https://siteorigin.com
*/


class SiteOrigin_Widget_Video_Widget extends SiteOrigin_Widget {

	function __construct() {

		parent::__construct(
			'sow-video',
			__( 'SiteOrigin Video Player', 'so-widgets-bundle' ),
			array(
				'description' => __( 'A video player widget.', 'so-widgets-bundle' ),
				'help'        => 'http://siteorigin.com/widgets-bundle/video-widget-documentation/'
			),
			array(),
			false,
			plugin_dir_path( __FILE__ )
		);
	}

	function initialize_form(){
		return array(
			'title' => array(
				'type' => 'text',
				'label' => __( 'Title', 'so-widgets-bundle' )
			),
			'host_type' => array(
				'type' => 'radio',
				'label' => __( 'Video location', 'so-widgets-bundle' ),
				'default' => 'self',
				'options' => array(
					'self' => __( 'Self hosted', 'so-widgets-bundle' ),
					'external' => __( 'Externally hosted', 'so-widgets-bundle' ),
				),

				// This field should be a video type state emitter
				'state_emitter' => array(
					'callback' => 'select',
					'args' => array('video_type')
				)
			),

			'video' => array(
				'type' => 'section',
				'label' => __( 'Video File', 'so-widgets-bundle' ),
				'fields' => array(
					'self_video' => array(
						'type' => 'media',
						'fallback' => true,
						'label' => __( 'Select video', 'so-widgets-bundle' ),
						'description' => __( 'Select an uploaded video in mp4 format. Other formats, such as webm and ogv will work in some browsers. You can use an online service such as <a href="http://video.online-convert.com/convert-to-mp4" target="_blank">online-convert.com</a> to convert your videos to mp4.', 'so-widgets-bundle' ),
						'default'     => '',
						'library' => 'video',
						'state_handler' => array(
							'video_type[self]' => array('show'),
							'video_type[external]' => array('hide'),
						)
					),
					'self_poster' => array(
						'type' => 'media',
						'label' => __( 'Select cover image', 'so-widgets-bundle' ),
						'default'     => '',
						'library' => 'image',
						'state_handler' => array(
							'video_type[self]' => array('show'),
							'video_type[external]' => array('hide'),
						)
					),
					'external_video' => array(
						'type' => 'text',
						'sanitize' => 'url',
						'label' => __( 'Video URL', 'so-widgets-bundle' ),
						'state_handler' => array(
							'video_type[external]' => array('show'),
							'video_type[self]' => array('hide'),
						)
					),
				)
			),

			'playback' => array(
				'type' => 'section',
				'label' => __('Video Playback', 'so-widgets-bundle'),
				'fields' => array(
					'autoplay' => array(
						'type' => 'checkbox',
						'default' => false,
						'label' => __( 'Autoplay', 'so-widgets-bundle' )
					),
					'oembed' => array(
						'type' => 'checkbox',
						'default' => true,
						'label' => __( 'Use oEmbed', 'so-widgets-bundle' ),
						'description' => __( 'Always use the embedded video rather than the MediaElement player.', 'so-widgets-bundle' ),
						'state_handler' => array(
							'video_type[external]' => array('show'),
							'video_type[self]' => array('hide'),
						)
					)
				),
			),
		);
	}

	function enqueue_frontend_scripts( $instance ) {
		$video_host = $instance['host_type'];
		if( $video_host == 'external' ) {
			$video_host = !empty( $instance['video']['external_video'] ) ? $this->get_host_from_url( $instance['video']['external_video'] ) : '';
		}
		if ( $this->is_skinnable_video_host( $video_host ) ) {
			if ( $video_host == 'vimeo' && ! wp_script_is( 'froogaloop' ) ) {
				wp_enqueue_script( 'froogaloop' );
			}
			if ( ! wp_style_is( 'sow-html-player-responsive' ) ) {
				wp_enqueue_style(
					'html-player-responsive',
					plugin_dir_url(__FILE__) . 'css/html-player-responsive.css',
					array(),
					SOW_BUNDLE_VERSION
				);
			}
			if ( ! wp_style_is( 'wp-mediaelement' ) ) {
				wp_enqueue_style( 'wp-mediaelement' );
			}
			if ( ! wp_script_is( 'so-video-widget' ) ) {
				wp_enqueue_script(
					'so-video-widget',
					plugin_dir_url(__FILE__) . 'js/so-video-widget' . SOW_BUNDLE_JS_SUFFIX . '.js',
					array( 'jquery', 'mediaelement' ),
					SOW_BUNDLE_VERSION
				);
			}
		}
		parent::enqueue_frontend_scripts( $instance );
	}

	function get_template_name( $instance ) {
		return 'default';
	}

	function get_template_variables( $instance, $args ) {
		static $player_id = 1;

		$poster = '';
		$video_host = $instance['host_type'];
		if ( $video_host == 'self' ) {

			if( !empty( $instance['video']['self_video'] ) ) {
				// Handle an attachment video
				$src = wp_get_attachment_url( $instance['video']['self_video'] );
				$vid_info = wp_get_attachment_metadata( $instance['video']['self_video'] );
				$video_type = 'video/'. empty( $vid_info['fileformat'] ) ? '' : $vid_info['fileformat'];
			}
			else if( !empty( $instance['video']['self_video_fallback'] ) ) {
				// Handle an external URL video
				$src = $instance['video']['self_video_fallback'];
				$vid_info = wp_check_filetype( basename( $instance['video']['self_video_fallback'] ) );
				$video_type = $vid_info['type'];
			}

			$poster = !empty( $instance['video']['self_poster'] ) ? wp_get_attachment_url( $instance['video']['self_poster'] ) : '';
		}
		else {
			$video_host = $this->get_host_from_url( $instance['video']['external_video'] );
			$video_type = 'video/' . $video_host;
			$src = !empty( $instance['video']['external_video'] ) ? $instance['video']['external_video'] : '';
		}

		$return = array(
			'player_id' => 'sow-player-' . ($player_id++),
			'host_type' => $instance['host_type'],
			'src' => $src,
			'video_type' => $video_type,
			'is_skinnable_video_host' => $this->is_skinnable_video_host( $video_host ),
			'poster' => $poster,
			'autoplay' => ! empty( $instance['playback']['autoplay'] ),
			'skin_class' => 'default'
		);

		// Force oEmbed for this video
		if( $instance['host_type'] == 'external' && $instance['playback']['oembed'] ) {
			$return['is_skinnable_video_host'] = false;
		}

		return $return;
	}

	function get_style_name( $instance ) {
		// For now, we'll only use the default style
		return '';
	}

	/**
	 * Gets a video source embed
	 */
	function get_video_oembed( $src, $autoplay = false ){
		if( empty($src) ) return '';

		global $content_width;

		$video_width = !empty($content_width) ? $content_width : 640;

		$hash = md5( serialize( array(
			'src' => $src,
			'width' => $video_width,
			'autoplay' => $autoplay,
		) ) );

		$html = get_transient('sow-vid-embed[' . $hash . ']');
		if( empty($html) ) {
			$html = wp_oembed_get( $src, array( 'width' => $video_width ) );

			if( $autoplay) {
				$html = preg_replace_callback( '/src=["\'](http[^"\']*)["\']/', array( $this, 'autoplay_callback' ), $html );
			}

			if( !empty($html) ) {
				set_transient( 'sow-vid-embed[' . $hash . ']', $html, 30*86400 );
			}
		}
		return $html;
	}

	/**
	 * The preg_replace callback that adds autoplay.
	 *
	 * @param $match
	 *
	 * @return mixed
	 */
	function autoplay_callback($match){
		return str_replace($match[1], add_query_arg('autoplay', 1, $match[1]), $match[0]);
	}

	/**
	 * Get the video host from the URL
	 *
	 * @param $video_url
	 *
	 * @return string
	 */
	private function get_host_from_url( $video_url ) {
		preg_match( '/https?:\/\/(www.)?([A-Za-z0-9\-]+)\./', $video_url, $matches );
		return ( ! empty( $matches ) && count( $matches ) > 2 ) ? $matches[2] : '';
	}

	/**
	 * Check if the current host is skinnable
	 *
	 * @param $video_host
	 *
	 * @return bool
	 */
	private function is_skinnable_video_host( $video_host ) {
		global $wp_version;
		return $video_host == 'self' || ( ($video_host == 'youtube' || $video_host == 'vimeo') && $wp_version >= 4.2 );
	}
}
siteorigin_widget_register( 'video', __FILE__, 'SiteOrigin_Widget_Video_Widget' );
