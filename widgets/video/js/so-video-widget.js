/* globals jQuery , sowb */

var sowb = window.sowb || {};

jQuery( function ( $ ) {
	sowb.setupVideoPlayers = function () {
		var $ = jQuery;
		var $video = $( 'video.sow-video-widget' );

		if ( $video.data( 'initialized' ) ) {
			return $video;
		}

		if ( $video.is( ':visible' ) ) {
			$video.mediaelementplayer();
		}

		if ( typeof $.fn.fitVids == 'function' ) {
			$( '.sow-video-wrapper.use-fitvids' ).fitVids();
		}
		
		$video.data( 'initialized', true );
	};
	sowb.setupVideoPlayers();
	
	$( sowb ).on( 'setup_widgets', sowb.setupVideoPlayers );
} );

window.sowb = sowb;
