/* global jQuery, soWidgets */

( function( $ ) {

	$( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-multiple_media', function( e ) {
		var $field = $( this ),
			$data = $field.find( '.siteorigin-widget-input' ),
			selectedMedia = $data.val().split( ',' );

		if ( $field.data( 'initialized' ) ) {
			return;
		}

		// Handle the media uploader
		$field.find( '.button' ).on( 'click', function( e ) {
			e.preventDefault();
			if ( typeof wp.media === 'undefined' ) {
				return;
			}

			var $$ = $( this );
			var frame = $( this ).data( 'frame' );

			// If the media frame already exists, reopen it.
			if ( frame ) {
				frame.open();
				return false;
			}

			// Create the media frame.
			frame = wp.media( {
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

			// If there's a selected image, highlight it. 
			frame.on( 'open', function() {
				if ( selectedMedia.length ) {
					var selection = frame.state().get( 'selection' );

					$.each( selectedMedia, function() {
					    selection.add( wp.media.attachment( this ) );
					} );
				}
			} );

			// Store the frame
			$$.data( 'frame', frame );

			// When an image is selected, run a callback.
			frame.on( 'select', function() {
				var attachmentIds = [],
					attachment,
					attachmentUrl,
					$currentItem,
					$thumbnail;

				$.each( frame.state().get( 'selection' ).models, function() {
					attachment = this.attributes;

					// Don't process images that already exist.
					if ( selectedMedia.indexOf( attachment.id.toString() ) == -1 ) {
						$field.find( '.multiple-media-field-template .multiple-media-field-item' ).clone().appendTo( $field.find( '.multiple-media-field-items' ) );
						$currentItem = $field.find( '.multiple-media-field-items .multiple-media-field-item' ).last();

						$thumbnail = $currentItem.find( '.thumbnail' );
						$thumbnail.attr( 'title', attachment.title );
						$currentItem.find( '.title' ).text( attachment.title );

						$currentItem.attr( 'data-id', attachment.id );

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
					}

					attachmentIds.push( attachment.id );
				} );

				// Remove any no longer selected images
				$field.find( '.multiple-media-field-items .multiple-media-field-item' ).each( function() {
					if ( attachmentIds.indexOf( $( this ).data( 'id' ) ) == -1 ) {
						$( this ).remove();
					}
				} )

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
		});

		$( document ).on( 'click','.siteorigin-widget-field-type-multiple_media a.media-remove-button', function( e ) {
			e.preventDefault();
			var $currentItem = $( this ).parent();

			selectedMedia.splice( selectedMedia.indexOf( $currentItem.data( 'id' ) ) );
			$data.val( selectedMedia.join( ',' ) );

			$currentItem.remove();
		} );

		$field.data( 'initialized', true );
	} );

} )( jQuery );
