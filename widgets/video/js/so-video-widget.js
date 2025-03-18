/* globals jQuery, sowb */

let sowb = window.sowb || {};

jQuery( function ( $ ) {
	sowb.setupVideoPlayers = () => {
		const $video = $( 'video.sow-video-widget' );

		if ( $video.data( 'initialized' ) ) {
			return $video;
		}

		$video.each( function () {
			const $this = $( this );
			const $container = $this.closest( '.mejs-container' );
			const $controls = $container.find( '.mejs-controls' );

			// Do we need to set up Media Elements?
			if ( $controls.css( 'display' ) === 'none' ) {
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
} );

window.sowb = sowb;
