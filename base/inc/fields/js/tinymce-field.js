/* global tinymce, switchEditors */

(function ( $ ) {
	$( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-tinymce', function ( e ) {
		var $$ = $( this );

		if ( $$.data( 'initialized' ) ) {
			return;
		}

		var $container = $$.find( '.siteorigin-widget-tinymce-container' );
		var settings = $container.data( 'editorSettings' );
		var $textarea = $container.find( 'textarea' );
		var id = $textarea.attr( 'id' );
		var setupEditor = function ( editor ) {
			editor.on( 'change',
				function () {
					window.tinymce.get( id ).save();
					$textarea.trigger( 'change' );
				}
			);
		};

		settings.tinymce = $.extend( {}, settings.tinymce, { selector: '#' + id, setup: setupEditor } );
		$( document ).one( 'wp-before-tinymce-init', function ( event, init ) {
			if ( init.selector === settings.tinymce.selector ) {
				var mediaButtons = $container.data( 'mediaButtons' );
				$$.find( '.wp-editor-tabs' ).before( mediaButtons.html );
			}
		} );
		$( document ).one( 'tinymce-editor-setup', function () {
			if ( ! $$.find( '.wp-editor-wrap' ).hasClass( settings.selectedEditor + '-active' ) ) {
				setTimeout( function () {
					window.switchEditors.go( id );
				}, 10 );
			}
		} );

		wp.editor.remove( id );

		// Wait for textarea to be visible before initialization.
		if ( $textarea.is( ':visible' ) ) {
			wp.editor.initialize( id, settings );
		}
		else {
			var intervalId = setInterval( function () {
				if ( $textarea.is( ':visible' ) ) {
					wp.editor.initialize( id, settings );
					clearInterval( intervalId );
				}
			}, 500);
		}

		$$.on( 'click', function ( event ) {
			var $target = $( event.target );
			var mode = $target.hasClass( 'wp-switch-editor' ) ? 'tmce' : 'html';
			if ( mode === 'tmce' ) {
				var editor = window.tinymce.get( id );
				// Quick bit of sanitization to prevent catastrophic backtracking in TinyMCE HTML parser regex
				if ( $target.hasClass( 'switch-tmce' ) && editor !== null ) {
					var content = $textarea.val();
					if ( content.search( '<' ) !== -1 && content.search( '>' ) === -1) {
						content = content.replace( /</g, '' );
						$textarea.val( content );
					}
					editor.setContent(window.switchEditors.wpautop(content));
				}

				$$.find( '.siteorigin-widget-tinymce-selected-editor' ).val( mode );
			}
		} );

		$$.data( 'initialized', true );
	} );

})( jQuery );
