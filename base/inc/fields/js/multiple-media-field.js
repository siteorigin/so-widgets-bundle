/* global jQuery, soWidgets */

( function( $ ) {
	const setupMultipleMediaField = function() {
		const $field = $( this );

		if ( $field.data( 'initialized' ) ) {
			return;
		}

		const $data = $field.find( '.siteorigin-widget-input' );
		let selectedMedia = $data.val().split( ',' ),
			repeater = $data.data( 'repeater' );

		if ( repeater ) {
			// This field is used to bulk add repeater items.
			repeater.field = $field.siblings( '.siteorigin-widget-field-' + repeater.field ).find( ' > .siteorigin-widget-field-repeater' );
			repeater.addBtn = repeater.field.find( '> .siteorigin-widget-field-repeater-add' );
		}

		// Handle the media uploader
		$field.find( '.button-secondary' ).on( 'click', function( e ) {
			e.preventDefault();

			if ( typeof window.top.wp.media === 'undefined' ) {
				return;
			}

			const $$ = $( this );
			let frame = $( this ).data( 'frame' );

			// If the media frame already exists, reopen it.
			if ( frame ) {
				frame.open();
				return false;
			}

			// Create the media frame.
			frame = window.top.wp.media( {
				title: $$.data( 'choose' ),
				library: {
					type: $$.data( 'library' ).split( ',' ).map( function( v ) { return v.trim(); } )
				},
				multiple: true,
				button: {
					text: $$.data( 'update' ),
					close: false
				}
			} );

			// If there's images selected, highlight them.
			if ( ! repeater ) {
				frame.on( 'open', function() {
					if ( selectedMedia.length ) {
						const selection = frame.state().get( 'selection' );

						$.each( selectedMedia, function() {
							selection.add( window.top.wp.media.attachment( this ) );
						} );
					}
				} );
			}

			// Store the frame
			$$.data( 'frame', frame );

			// When an image is selected, run a callback.
			frame.on( 'select', function() {
				const attachmentIds = [];
				let attachment,
					attachmentUrl,
					$currentItem,
					$thumbnail,
					$inputField;

				$.each( frame.state().get( 'selection' ).models, function() {
					attachment = this.attributes;
					if ( repeater ) {
						// Add new item
						repeater.addBtn.trigger( 'click' );
						// Find image setting and set it.
						$currentItem = repeater.field.find( '> .siteorigin-widget-field-repeater-items > .siteorigin-widget-field-repeater-item:last-of-type > .siteorigin-widget-field-repeater-item-form > .siteorigin-widget-field-' + repeater.setting );
						$inputField = $currentItem.find( '.siteorigin-widget-input' ).not( '.media-fallback-external' );
						$inputField.val( attachment.id );

						// We need to manually set the thumbnail to show as the field won't.
						$currentItem.find( '.thumbnail' ).show();
					} else {
						attachmentIds.push( attachment.id );
						// Don't process images that already exist.
						if ( selectedMedia.indexOf( attachment.id.toString() ) == -1 ) {
							$field.find( '.multiple-media-field-template .multiple-media-field-item' ).clone().appendTo( $field.find( '.multiple-media-field-items' ) );
							$currentItem = $field.find( '.multiple-media-field-items .multiple-media-field-item' ).last();
							$currentItem.attr( 'data-id', attachment.id );
						} else {
							return;
						}
					}

					// Display thumbnail.
					$thumbnail = $currentItem.find( '.thumbnail' );
					$thumbnail.attr( 'title', attachment.title );
					$currentItem.find( '.title' ).html( attachment.title );
					if ( typeof attachment.sizes !== 'undefined' ) {
						if ( typeof attachment.sizes.thumbnail !== 'undefined' ) {
							attachmentUrl = attachment.sizes.thumbnail.url;
						} else {
							attachmentUrl = attachment.sizes.full.url;
						}
					} else{
						attachmentUrl = attachment.icon;
					}
					$thumbnail.attr( 'src', attachmentUrl );

					if ( repeater ) {
						// This is required to ensure state emitter titles are updated.
						$inputField.trigger( 'change', { silent: true } );
					}
				} );

				// Remove any no longer selected images
				$field.find( '.multiple-media-field-items .multiple-media-field-item' ).each( function() {
					if ( attachmentIds.indexOf( $( this ).data( 'id' ) ) == -1 ) {
						$( this ).remove();
					}
				} );

				// Store image data.
				if ( attachmentIds.length ) {
					selectedMedia = attachmentIds;
					$data.val( attachmentIds.join( ',' ) );
				} else {
					selectedMedia = [];
					$data.val( '' );
				}

				frame.close();
			} );

			// Finally, open the modal.
			frame.open();
		} );

		if ( ! repeater ) {
			$field.on( 'click', 'a.media-remove-button', function( e ) {
				e.preventDefault();
				const $currentItem = $( this ).parent();

				selectedMedia.splice( selectedMedia.indexOf( $currentItem.data( 'id' ) ), 1 );
				$data.val( selectedMedia.join( ',' ) );

				$currentItem.remove();
			} );
		}

		$field.data( 'initialized', true );
	};

	 // If the current page isn't the site editor, set up the Multiple Media field now.
	 if (
		 window.top === window.self &&
		 (
			 typeof pagenow === 'string' &&
			 pagenow !== 'site-editor'
		 )
	 ) {
		 $( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-multiple_media', setupMultipleMediaField );
	 }

	// Add support for the Site Editor.
	window.addEventListener( 'message', function( e ) {
		if ( e.data && e.data.action === 'sowbBlockFormInit' ) {
			$( '.siteorigin-widget-field-type-multiple_media' ).each( function() {
				setupMultipleMediaField.call( this );
			} );
		}
	} );
} )( jQuery );
