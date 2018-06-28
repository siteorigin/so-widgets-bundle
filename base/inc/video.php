<?php

/**
 * This class handles video related functionality.
 *
 * Class SiteOrigin_Video
 */

class SiteOrigin_Video {
	
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
	 * @param bool $related_videos Whether to show related videos after the video has finished playing. ( YouTube only )
	 *
	 * @return false|mixed|null|string|string[]
	 */
	function get_video_oembed( $src, $autoplay = false, $related_videos = true ) {
		if ( empty( $src ) ) {
			return '';
		}
		
		global $content_width;
		
		$video_width = ! empty( $content_width ) ? $content_width : 640;
		
		$hash = md5( serialize( array(
			'src'      => $src,
			'width'    => $video_width,
			'autoplay' => $autoplay,
		) ) );
		
		$html = get_transient( 'sow-vid-embed[' . $hash . ']' );
		if ( empty( $html ) ) {
			$html = wp_oembed_get( $src, array( 'width' => $video_width ) );
			
			if ( $autoplay ) {
				$html = preg_replace_callback( '/src=["\'](http[^"\']*)["\']/', array(
					$this,
					'autoplay_callback'
				), $html );
			}
			
			if ( empty( $related_videos ) ) {
				$html = preg_replace_callback( '/src=["\'](http[^"\']*)["\']/', array(
					$this,
					'remove_related_videos'
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
		return str_replace( $match[1], add_query_arg( 'autoplay', 1, $match[1] ), $match[0] );
	}
	
	/**
	 * The preg_replace callback that adds the rel param for YouTube videos.
	 *
	 * @param $match
	 *
	 * @return mixed
	 */
	function remove_related_videos( $match ) {
		return str_replace( $match[1], add_query_arg( 'rel', 0, $match[1] ), $match[0] );
	}
}
