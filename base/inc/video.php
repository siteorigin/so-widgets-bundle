<?php

/**
 * This class handles video related functionality.
 *
 * Class SiteOrigin_Video
 */
class SiteOrigin_Video {
	public $src;

	/**
	 * Check whether it's possible to oEmbed by testing if a provider URL can be obtained.
	 *
	 * @param string $url The URL of the video to be embedded.
	 *
	 * @return bool Whether it's possible to embed this video.
	 */
	public function can_oembed( $url ) {
		$wp_oembed = new WP_oEmbed();
		$provider = $wp_oembed->get_provider( $url, array( 'discover' => false ) );

		return ! empty( $provider );
	}

	/**
	 * Gets a video source embed
	 *
	 * @param string $src            The URL of the video.
	 * @param bool   $autoplay       Whether to start playing the video automatically once loaded. ( YouTube only )
	 * @param bool   $related_videos Deprecated.
	 *
	 * @return false|mixed|string|string[]|null
	 */
	public function get_video_oembed( $src, $autoplay = false, $related_videos = false, $loop = false, $js_api = false ) {
		if ( empty( $src ) ) {
			return '';
		}

		global $content_width;

		$video_width = ! empty( $content_width ) ? $content_width : 640;

		$hash = md5( serialize( array(
			'src'      => $src,
			'width'    => $video_width,
			'autoplay' => $autoplay,
			'loop'     => $loop,
		) ) );
		// Standardize YouTube video URL.
		if ( strpos(  $src, 'youtu.be' ) !== false ) {
			// Remove any parameters from URL as it'll fail to embed.
			$src = preg_replace( '/\?.*/', '', $src );
			$src = str_replace( 'youtu.be/', 'youtube.com/watch?v=', $src );
		}

		if ( strpos( $src, 'youtube.com/watch' ) !== false ) {
			$src_parse = parse_url( $src, PHP_URL_QUERY );
			// Check if the URL was encoded.
			if ( strpos( $src_parse, '&amp;' ) !== false ) {
				$src_parse = str_replace( '&amp;', '&', $src_parse );
			}
			parse_str( $src_parse, $src_parse );
			$this->src = ! empty( $src_parse['v'] ) ? 'https://www.youtube.com/watch?v=' . $src_parse['v'] : $src;
		} else {
			$this->src = $src;
		}

		$html = get_transient( 'sow-vid-embed[' . $hash . ']' );
		if ( empty( $html ) ) {
			$html = wp_oembed_get( $this->src, array( 'width' => $video_width ) );

			if ( $autoplay ) {
				$html = preg_replace_callback( '/src=["\'](http[^"\']*)["\']/', array(
					$this,
					'autoplay_callback',
				), $html );
			}

			if ( $loop ) {
				$html = preg_replace_callback( '/src=["\'](http[^"\']*)["\']/', array(
					$this,
					'loop_callback',
				), $html );
			}

			if ( $js_api ) {
				$html = preg_replace_callback( '/src=["\'](http[^"\']*)["\']/', array(
					$this,
					'js_api_callback',
				), $html );
			}

			// Ensure the embed is able to go fullscreen.
			$html = preg_replace( '/<iframe(.*?)>/', '<iframe$1 allowfullscreen mozallowfullscreen webkitallowfullscreen>', $html );

			if ( ! empty( $html ) ) {
				set_transient( 'sow-vid-embed[' . $hash . ']', $html, 30 * 86400 );
			}
		}

		if ( ! empty( $html ) ) {
			// Default directives — the minimum YouTube/Vimeo need for playback.
			$default_sandbox = array( 'allow-scripts', 'allow-same-origin', 'allow-presentation' );

			/**
			 * Filter the sandbox directives applied to the Video Player oEmbed iframe.
			 *
			 * Lets developers adjust the directives for strict internal security policies.
			 * Note: the defaults are the minimum YouTube/Vimeo need; removing them, or
			 * returning an empty value to omit the attribute, may break or relax playback
			 * sandboxing rather than harden it.
			 *
			 * Return an empty array (or empty string) to omit the sandbox attribute.
			 *
			 * @param array  $directives Array of sandbox token strings.
			 * @param string $src        Normalized video source URL ($this->src).
			 * @param array  $context    Playback flags: 'autoplay', 'loop', 'js_api'.
			 */
			$sandbox = apply_filters(
				'siteorigin_widgets_sow-video_oembed_iframe_sandbox',
				$default_sandbox,
				$this->src,
				array(
					'autoplay' => $autoplay,
					'loop'     => $loop,
					'js_api'   => $js_api,
				)
			);

			// Normalize the filter return into a clean array of tokens.
			if ( is_string( $sandbox ) ) {
				$sandbox = preg_split( '/\s+/', $sandbox );
			}
			$sandbox = (array) $sandbox;
			$sandbox = array_map( 'trim', $sandbox );
			$sandbox = array_filter( $sandbox );
			$sandbox = array_unique( $sandbox );

			// Allow-list of valid iframe sandbox tokens used to sanitize the filter return.
			$allowed_sandbox = array(
				'allow-forms',
				'allow-modals',
				'allow-orientation-lock',
				'allow-pointer-lock',
				'allow-popups',
				'allow-popups-to-escape-sandbox',
				'allow-presentation',
				'allow-same-origin',
				'allow-scripts',
				'allow-top-navigation',
				'allow-top-navigation-by-user-activation',
				'allow-downloads',
			);
			$sandbox = array_filter(
				$sandbox,
				function ( $token ) use ( $allowed_sandbox ) {
					return in_array( $token, $allowed_sandbox, true );
				}
			);

			// Strip any pre-existing sandbox attribute (handles legacy-format cached HTML).
			$html = preg_replace( '/(<iframe\b[^>]*?)\s*sandbox=(["\']).*?\2/', '$1', $html );

			// Inject the filtered sandbox attribute, or omit it entirely when empty.
			if ( ! empty( $sandbox ) ) {
				$sandbox_attr = implode( ' ', $sandbox );
				$html = preg_replace( '/<iframe(.*?)>/', '<iframe$1 sandbox="' . esc_attr( $sandbox_attr ) . '">', $html );
			}
		}

		return $html;
	}

	/**
	 * The preg_replace callback that adds autoplay.
	 *
	 * @return mixed
	 */
	public function autoplay_callback( $match ) {
		return str_replace(
			$match[1],
			add_query_arg(
				array(
					'autoplay' => 1,
					'mute' => 1,
				),
				$match[1]
			),
			$match[0]
		);
	}

	/**
	 * The preg_replace callback that adds loop and playlist.
	 *
	 * @return mixed
	 */
	public function loop_callback( $match ) {
		// Extract video id.
		parse_str( parse_url( $this->src, PHP_URL_QUERY ), $vars );

		$new_url = add_query_arg(
			array(
				// Adding the current video in a playlist allows for YouTube to loop the video.
				'playlist' => ! empty( $vars['v'] ) ? $vars['v'] : '',
				'loop' => 1,
			),
			$match[1]
		);

		return str_replace( $match[1], $new_url, $match[0] );
	}

	/**
	 * The preg_replace callback that oEmbed JS API support.
	 *
	 * @return mixed
	 */
	public function js_api_callback( $match ) {
		if ( strpos( $match[0], 'vimeo' ) ) {
			$js_arg = array(
				'api' => 'true',
			);
		} else {
			$js_arg = array(
				'enablejsapi' => 1,
			);
		}

		return str_replace(
			$match[1],
			add_query_arg(
				array(
					$js_arg,
				),
				$match[1]
			),
			$match[0]
		);
	}
}
