<?php

/**
 * This class handles video related functionality.
 *
 * Class SiteOrigin_Video
 */

class SiteOrigin_Video {
	var $src;
	
	/**
	 * Check whether it's possible to oEmbed by testing if a provider URL can be obtained.
	 *
	 * @param string $url The URL of the video to be embedded.
	 *
	 * @return bool Whether it's possible to embed this video.
	 */
	function can_oembed( $url ) {
		$wp_oembed = new WP_oEmbed();
		$provider = $wp_oembed->get_provider( $url, array( 'discover' => false ) );
		
		return ! empty( $provider );
	}
	
	/**
	 * Gets a video source embed
	 *
	 * @param string $src The URL of the video.
	 * @param bool $autoplay Whether to start playing the video automatically once loaded. ( YouTube only )
	 * @param bool $related_videos Deprecated.
	 *
	 * @return false|mixed|null|string|string[]
	 */
	function get_video_oembed( $src, $autoplay = false, $related_videos = false, $loop = false ) {
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
		
		// Convert embed format to standard format to be compatible with wp_oembed_get
		$this->src = preg_replace( '/https?:\/\/www.youtube.com\/embed\/([^\/]+)/', 'https://www.youtube.com/watch?v=$1', $src );

		$html = get_transient( 'sow-vid-embed[' . $hash . ']' );
		if ( empty( $html ) ) {
			$html = wp_oembed_get( $this->src, array( 'width' => $video_width ) );

			if ( $autoplay ) {
				$html = preg_replace_callback( '/src=["\'](http[^"\']*)["\']/', array(
					$this,
					'autoplay_callback'
				), $html );
			}

			if ( $loop ) {
				$html = preg_replace_callback( '/src=["\'](http[^"\']*)["\']/', array(
					$this,
					'loop_callback'
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
	 * @param $match
	 *
	 * @return mixed
	 */
	function autoplay_callback( $match ) {
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
	 * @param $match
	 *
	 * @return mixed
	 */
	function loop_callback( $match ) {
		// Extract video id.
		parse_str( parse_url( $this->src, PHP_URL_QUERY ), $vars );

		$new_url = add_query_arg(
			array(
				'loop' => 1,
				// Adding the current video in a playlist allows for YouTube to loop the video.
				'playlist' => ! empty( $vars['v'] ) ? $vars['v'] : '',
			),
			$match[1]
		);						
		return str_replace( $match[1], $new_url, $match[0] );
	}
}
