/* globals jQuery */

jQuery( function ( $ ) {
	var setupVideoPlayers = function() {
		var $ = jQuery;
		$('video.sow-video-widget').mediaelementplayer();
	};
	setupVideoPlayers();

	$( document ).on( 'sowb_setup_widgets', setupVideoPlayers );
} );
