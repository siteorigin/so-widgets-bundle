/* global tinymce, switchEditors */

(function( $ ) {
	$( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-editor', function ( e ) {
		var $$ = $(this);
		var $container = $$.find( '.siteorigin-widget-tinymce-container' );
		var settings = $container.data( 'editorSettings' );
		var $textarea = $container.find( '> textarea' );
		var id = $textarea.attr( 'id' );

		$( document ).on( 'tinymce-editor-setup', function () {

			if ( ! $$.find( '.wp-editor-wrap' ).hasClass( settings.selectedEditor + '-active' ) ) {
				setTimeout( function () {
					window.switchEditors.go( id );
				}, 10 );
			}
		} );
		wp.editor.initialize( id, settings );

		$$.on( 'click', function( event ) {

			var $target = $( event.target );
			if ( $target.hasClass( 'wp-switch-editor' ) ) {
				var mode = $target.hasClass( 'switch-tmce' ) ? 'tmce' : 'html';
				if ( mode === 'tmce' ) {
					// Quick bit of sanitization to prevent catastrophic backtracking in TinyMCE HTML parser regex
					var editor = tinymce.get( id );
					if (editor !== null) {
						var content = $textarea.val();
						if (content.search( '<' ) !== -1) {
							if (content.search( '>' ) === -1) {
								content = content.replace( /</g, '' );
								$textarea.val( content );
							}
						}
						// editor.setContent(window.switchEditors.wpautop(content));
					}
				}

				$$.find( '.siteorigin-widget-tinymce-selected-editor' ).val( mode );
			}
		});
	});

})( jQuery );
