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

			if ( ! empty( $html ) ) {
				set_transient( 'sow-vid-embed[' . $hash . ']', $html, 30 * 86400 );
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
