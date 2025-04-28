/* globals jQuery, sowb */

var sowb = window.sowb || {};

jQuery( function ( $ ) {
	sowb.setupVideoPlayers = () => {
		const $video = $( 'video.sow-video-widget' );

		if ( $video.data( 'initialized' ) ) {
			return $video;
		}

		$video.each( function () {
			const $this = $( this );

			// Do we need to set up Media Elements?
			if (
				typeof $.fn.mediaelementplayer === 'function' &&
				$this.attr( 'controls' )
			) {
				$this.mediaelementplayer();
				return;
			}

			// Controls are hidden. Add click event to play/pause video.
			$this.on( 'click', ( e ) => {
				if ( e.target.nodeName !== 'VIDEO' ) {
					return;
				}

				const video = e.target;
				video.paused ? video.play() : video.pause();
			} );
		} );

		if ( typeof $.fn.fitVids === 'function' ) {
			$( '.sow-video-wrapper.use-fitvids' ).fitVids();
		}

		$video.data( 'initialized', true );
	};
	sowb.setupVideoPlayers();

	$( sowb ).on( 'setup_widgets', sowb.setupVideoPlayers );

	jQuery( '.sow-video-wrapper.use-fitvids' ).on( 'setupFitVids', function() {
		$( this ).fitVids();
	} );
} );

// It's possible that the video was blocked during initial setup by the SO Embed Blocker.
jQuery( document ).on('siteorigin_embed_blocker_unblock', () => {
	jQuery( '.sow-video-wrapper.use-fitvids' ).trigger( 'setupFitVids' );
} );
