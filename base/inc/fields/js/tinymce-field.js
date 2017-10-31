/* global tinymce, switchEditors */

(function ( $ ) {
	var setup = function( $field ) {

		if ( $field.data( 'initialized' ) ) {
			return;
		}

		var $container = $field.find( '.siteorigin-widget-tinymce-container' );
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
				$field.find( '.wp-editor-tabs' ).before( mediaButtons.html );
			}
		} );
		$( document ).one( 'tinymce-editor-setup', function () {
			if ( ! $field.find( '.wp-editor-wrap' ).hasClass( settings.selectedEditor + '-active' ) ) {
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
		
		$field.on( 'click', function ( event ) {
			var $target = $( event.target );
			var mode = $target.hasClass( 'switch-tmce' ) ? 'tmce' : 'html';
			if ( mode === 'tmce' ) {
				var editor = window.tinymce.get( id );
				// Quick bit of sanitization to prevent catastrophic backtracking in TinyMCE HTML parser regex
				if ( $target.hasClass( 'wp-switch-editor' ) && editor !== null ) {
					var content = $textarea.val();
					if ( content.search( '<' ) !== -1 && content.search( '>' ) === -1) {
						content = content.replace( /</g, '' );
						$textarea.val( content );
					}
					editor.setContent(window.switchEditors.wpautop(content));
				}
			}
			$field.find( '.siteorigin-widget-tinymce-selected-editor' ).val( mode );
		} );
		
		$field.data( 'initialized', true );
	};
	
	$( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-tinymce', function () {
		var $field = $( this );
		var $parentRepeaterItem = $field.closest( '.siteorigin-widget-field-repeater-item-form' );
		
		if ( $parentRepeaterItem.length > 0 ) {
			if ( $parentRepeaterItem.is( ':visible' ) ) {
				setup( $field );
			}
			else {
				$parentRepeaterItem.on('slideToggleOpenComplete', function onSlideToggleComplete() {
					if ( $parentRepeaterItem.is( ':visible' ) ) {
						setup( $field );
						$parentRepeaterItem.off( 'slideToggleOpenComplete' );
					}
				});
			}
		}
		else {
			setup( $field );
		}
	});
	
	$( document ).on( 'sortstop', function ( event, ui ) {
		var $form;
		if ( ui.item.is( '.siteorigin-widget-field-repeater-item' ) ) {
			$form = ui.item.find( '> .siteorigin-widget-field-repeater-item-form' );
		}
		else {
			$form = ui.item.find('.siteorigin-widget-form');
		}
		
		$form.find( '.siteorigin-widget-field-type-tinymce' ).each( function () {
			$( this ).data( 'initialized', null );
			setup( $( this ) );
		} );
		
	});

})( jQuery );
