<?php

/*
Widget Name: Video Player
Description: Play all your self or externally hosted videos in a customizable video player.
Author: SiteOrigin
Author URI: https://siteorigin.com
Documentation: https://siteorigin.com/widgets-bundle/video-player-widget/
*/

class SiteOrigin_Widget_Video_Widget extends SiteOrigin_Widget {
	public function __construct() {
		parent::__construct(
			'sow-video',
			__( 'SiteOrigin Video Player', 'so-widgets-bundle' ),
			array(
				'description' => __( 'Play all your self or externally hosted videos in a customizable video player.', 'so-widgets-bundle' ),
				'help'        => 'http://siteorigin.com/widgets-bundle/video-widget-documentation/',
			),
			array(),
			false,
			plugin_dir_path( __FILE__ )
		);
	}

	public function get_widget_form() {
		return array(
			'title'     => array(
				'type'  => 'text',
				'label' => __( 'Title', 'so-widgets-bundle' ),
			),
			'host_type' => array(
				'type'          => 'radio',
				'label'         => __( 'Video location', 'so-widgets-bundle' ),
				'default'       => 'self',
				'options'       => array(
					'self'     => __( 'Self hosted', 'so-widgets-bundle' ),
					'external' => __( 'Externally hosted', 'so-widgets-bundle' ),
				),

				// This field should be a video type state emitter
				'state_emitter' => array(
					'callback' => 'select',
					'args'     => array( 'video_type' ),
				),
			),

			'video' => array(
				'type'   => 'section',
				'label'  => __( 'Video File', 'so-widgets-bundle' ),
				'fields' => array(
					'self_sources'   => array(
						'type'          => 'repeater',
						'label'         => __( 'Sources', 'so-widgets-bundle' ),
						'fields'        => array(
							'self_video' => array(
								'type'     => 'media',
								'fallback' => true,
								'label'    => __( 'Select video', 'so-widgets-bundle' ),
								'default'  => '',
								'library'  => 'video',
							),
						),
						'state_handler' => array(
							'video_type[self]'     => array( 'show' ),
							'video_type[external]' => array( 'hide' ),
						),
					),
					'self_poster'    => array(
						'type'          => 'media',
						'label'         => __( 'Select cover image', 'so-widgets-bundle' ),
						'default'       => '',
						'library'       => 'image',
						'state_handler' => array(
							'video_type[self]'     => array( 'show' ),
							'video_type[external]' => array( 'hide' ),
						),
					),
					'external_video' => array(
						'type'          => 'text',
						'sanitize'      => 'url',
						'label'         => __( 'Video URL', 'so-widgets-bundle' ),
						'state_handler' => array(
							'video_type[external]' => array( 'show' ),
							'video_type[self]'     => array( 'hide' ),
						),
					),
				),
			),

			'playback' => array(
				'type'   => 'section',
				'label'  => __( 'Video Playback', 'so-widgets-bundle' ),
				'fields' => array(
					'autoplay' => array(
						'type'    => 'checkbox',
						'default' => false,
						'label'   => __( 'Autoplay', 'so-widgets-bundle' ),
					),
					'loop' => array(
						'type'    => 'checkbox',
						'default' => false,
						'label'   => __( 'Loop', 'so-widgets-bundle' ),
					),
					'fitvids' => array(
						'type'    => 'checkbox',
						'default' => true,
						'label'   => __( 'Use FitVids', 'so-widgets-bundle' ),
						'description'   => __( 'FitVids will scale the video to fill the width of the widget area while maintaining aspect ratio.', 'so-widgets-bundle' ),
					),
					'oembed'   => array(
						'type'          => 'checkbox',
						'default'       => true,
						'label'         => __( 'Use oEmbed', 'so-widgets-bundle' ),
						'description'   => __( 'Always use the embedded video rather than the MediaElement player.', 'so-widgets-bundle' ),
						'state_handler' => array(
							'video_type[external]' => array( 'show' ),
							'video_type[self]'     => array( 'hide' ),
						),
					),
				),
			),
		);
	}

	public function enqueue_frontend_scripts( $instance ) {
		$video_host = empty( $instance['host_type'] ) ? '' : $instance['host_type'];

		if ( $video_host == 'external' ) {
			$video_host = ! empty( $instance['video']['external_video'] ) ? $this->get_host_from_url( $instance['video']['external_video'] ) : '';
		}

		if ( $this->is_skinnable_video_host( $video_host ) ) {
			if ( $video_host == 'vimeo' && ! wp_script_is( 'froogaloop' ) ) {
				wp_enqueue_script( 'froogaloop' );
			}

			if ( ! wp_style_is( 'sow-html-player-responsive' ) ) {
				wp_enqueue_style(
					'html-player-responsive',
					plugin_dir_url( __FILE__ ) . 'css/html-player-responsive.css',
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
					plugin_dir_url( __FILE__ ) . 'js/so-video-widget' . SOW_BUNDLE_JS_SUFFIX . '.js',
					array( 'jquery', 'mediaelement' ),
					SOW_BUNDLE_VERSION
				);
			}

			if ( ! empty( $instance['playback']['fitvids'] ) && ! wp_script_is( 'jquery-fitvids' ) ) {
				wp_enqueue_script(
					'jquery-fitvids',
					plugin_dir_url( SOW_BUNDLE_BASE_FILE ) . 'js/lib/jquery.fitvids' . SOW_BUNDLE_JS_SUFFIX . '.js',
					array( 'jquery' ),
					1.1
				);
			}
		}
		parent::enqueue_frontend_scripts( $instance );
	}

	public function get_template_name( $instance ) {
		return 'default';
	}

	public function get_template_variables( $instance, $args ) {
		static $player_id = 1;

		$self_sources = array();
		$external_src = '';
		$external_video_type = '';
		$poster = '';
		$video_host = $instance['host_type'];

		if ( $video_host == 'self' ) {
			if ( isset( $instance['video']['self_sources'] ) ) {
				foreach ( $instance['video']['self_sources'] as $source ) {
					$src = '';
					$video_type = '';

					if ( ! empty( $source['self_video'] ) ) {
						// Handle an attachment video
						$src = wp_get_attachment_url( $source['self_video'] );
						$video_type = get_post_mime_type( $source['self_video'] );
					} elseif ( ! empty( $source['self_video_fallback'] ) ) {
						// Handle an external URL video
						$src = $source['self_video_fallback'];
						$vid_info = wp_check_filetype( basename( $source['self_video_fallback'] ) );
						$video_type = $vid_info['type'];
					}

					if ( ! empty( $src ) ) {
						$self_sources[] = array( 'src' => $src, 'video_type' => $video_type );
					}
				}
			}
			$poster = ! empty( $instance['video']['self_poster'] ) ? wp_get_attachment_url( $instance['video']['self_poster'] ) : '';
		} else {
			$video_host = $this->get_host_from_url( $instance['video']['external_video'] );
			$external_video_type = 'video/' . $video_host;
			$external_src = ! empty( $instance['video']['external_video'] ) ? $instance['video']['external_video'] : '';

			if ( ! $instance['playback']['oembed'] ) {
				// Add video as self_source to allow MediaElements to pick up on it.
				$self_sources[] = array(
					'src' => $external_src,
					'type' => 'mp4',
				);
			}
		}

		$return = array(
			'player_id'               => 'sow-player-' . ( $player_id ++ ),
			'host_type'               => $instance['host_type'],
			'src'                     => $external_src,
			'sources'                 => $self_sources,
			'video_type'              => $external_video_type,
			'is_skinnable_video_host' => $this->is_skinnable_video_host( $video_host ),
			'poster'                  => $poster,
			'autoplay'                => ! empty( $instance['playback']['autoplay'] ),
			'loop'                    => ! empty( $instance['playback']['loop'] ),
			'skin_class'              => 'default',
			'fitvids'                 => ! empty( $instance['playback']['fitvids'] ),
		);

		if ( $instance['host_type'] == 'external' && $instance['playback']['oembed'] ) {
			// Force oEmbed for this video if oEmbed is enabled.
			$return['is_skinnable_video_host'] = false;
		}

		return $return;
	}

	public function get_style_name( $instance ) {
		// For now, we'll only use the default style
		return '';
	}

	/**
	 * Get the video host from the URL
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
	 * @return bool
	 */
	private function is_skinnable_video_host( $video_host ) {
		global $wp_version;

		return $video_host == 'self' || ( ( $video_host == 'youtube' || $video_host == 'vimeo' ) && $wp_version >= 4.2 );
	}

	/**
	 * Update older versions of widget to use multiple sources.
	 *
	 * @return mixed
	 */
	public function modify_instance( $instance ) {
		$video_src = array();

		if ( isset( $instance['video']['self_video'] ) && ! empty( $instance['video']['self_video'] ) ) {
			$video_src['self_video'] = $instance['video']['self_video'];
			unset( $instance['video']['self_video'] );
		}

		if ( isset( $instance['video']['self_video_fallback'] ) && ! empty( $instance['video']['self_video_fallback'] ) ) {
			$video_src['self_video_fallback'] = $instance['video']['self_video_fallback'];
			unset( $instance['video']['self_video_fallback'] );
		}

		if ( ! empty( $video_src ) ) {
			if ( ! isset( $instance['video']['self_sources'] ) ) {
				$instance['video']['self_sources'] = array();
			}
			$instance['video']['self_sources'][] = $video_src;
		}

		// Prevent FitVids from being enabled for widgets created before FitVids was added.
		if ( ! isset( $instance['playback']['fitvids'] ) ) {
			$instance['playback']['fitvids'] = false;
		}

		return $instance;
	}
}

siteorigin_widget_register( 'video', __FILE__, 'SiteOrigin_Widget_Video_Widget' );
