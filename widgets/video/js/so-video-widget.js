/* globals jQuery, sowb */

var sowb = window.sowb || {};

jQuery( function ( $ ) {
	sowb.setupVideoPlayers = () => {
		const $video = $( 'video.sow-video-widget' ).filter( function () {
			return ! $( this ).data( 'initialized' );
		} );

		if ( ! $video.length ) {
			return $video;
		}

		$video.each( function () {
			const $this = $( this );
			const showCoverOnEnd = Boolean( $this.data( 'show-cover-on-end' ) );

			// Do we need to set up Media Elements?
			if (
				typeof $.fn.mediaelementplayer === 'function' &&
				$this.attr( 'controls' )
			) {
				$this.mediaelementplayer( {
					showPosterWhenEnded: showCoverOnEnd,
				} );
				$this.data( 'initialized', true );
				return;
			}

			// Reset to the native poster image when MediaElement isn't in use.
			if ( showCoverOnEnd ) {
				this.addEventListener( 'ended', function () {
					this.load();
				} );
			}

			// Controls are hidden. Add click event to play/pause video.
			$this.on( 'click', ( e ) => {
				if ( e.target.nodeName !== 'VIDEO' ) {
					return;
				}

				const video = e.target;
				video.paused ? video.play() : video.pause();
			} );

			$this.data( 'initialized', true );
		} );

		if ( typeof $.fn.fitVids === 'function' ) {
			$( '.sow-video-wrapper.use-fitvids' ).fitVids();
		}
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
