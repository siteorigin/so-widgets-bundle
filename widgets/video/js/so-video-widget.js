/* globals jQuery , sowb */

var sowb = window.sowb || {};

jQuery( function ( $ ) {
	sowb.setupVideoPlayers = function () {
		var $ = jQuery;
		var $video = $( 'video.sow-video-widget' );
		
		if ( !$video.is( ':visible' ) || $video.data( 'initialized' ) ) {
			return $video;
		}
		
		$video.mediaelementplayer();
		
		$video.data( 'initialized', true );
	};
	sowb.setupVideoPlayers();
	
	$( sowb ).on( 'setup_widgets', sowb.setupVideoPlayers );
} );

window.sowb = sowb;
