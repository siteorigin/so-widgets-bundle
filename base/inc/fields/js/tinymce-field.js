/* global tinymce, switchEditors */

(function ( $ ) {
	var setup = function( $field ) {

		if ( $field.data( 'initialized' ) ) {
			return;
		}
		
		var wpEditor = wp.oldEditor ? wp.oldEditor : wp.editor;
		if ( wpEditor && wpEditor.hasOwnProperty( 'autop' ) ) {
			wp.editor.autop = wpEditor.autop;
			wp.editor.removep = wpEditor.removep;
			wp.editor.initialize = wpEditor.initialize
		}
		
		var $container = $field.find( '.siteorigin-widget-tinymce-container' );
		var settings = $container.data( 'editorSettings' );
		var $wpautopToggleField;
		if ( settings.wpautopToggleField ) {
			var $widgetForm = $container.closest( '.siteorigin-widget-form' );
			$wpautopToggleField = $widgetForm.find( settings.wpautopToggleField );
			settings.tinymce.wpautop = $wpautopToggleField.is( ':checked' );
		}
		var $textarea = $container.find( 'textarea' );
		var id = $textarea.attr( 'id' );
		var setupEditor = function ( editor ) {
			editor.on( 'change',
				function () {
					var ed = window.tinymce.get( id );
					ed.save();
					$textarea.trigger( 'change' );
				}
			);
			if ( $wpautopToggleField ) {
				$wpautopToggleField.off( 'change' );
				$wpautopToggleField.on( 'change', function () {
					wp.editor.remove( id );
					settings.tinymce.wpautop = $wpautopToggleField.is( ':checked' );
					wp.editor.initialize( id, settings );
				} );
			}
		};
		
		if ( settings.tinymce ) {
			settings.tinymce = $.extend( {}, settings.tinymce, { selector: '#' + id, setup: setupEditor } );
		}
		$( document ).on( 'wp-before-tinymce-init', function ( event, init ) {
			if ( init.selector === settings.tinymce.selector ) {
				var mediaButtons = $container.data( 'mediaButtons' );
				if ( $field.find( '.wp-media-buttons' ).length === 0 ) {
					$field.find( '.wp-editor-tabs' ).before( mediaButtons.html );
				}
			}
		} );
		$( document ).on( 'tinymce-editor-setup', function () {
			if ( ! $field.find( '.wp-editor-wrap' ).hasClass( settings.selectedEditor + '-active' ) ) {
				setTimeout( function () {
					window.switchEditors.go( id );
				}, 10 );
			}
		} );

		wpEditor.remove( id );
		if ( window.tinymce ) {
			window.tinymce.EditorManager.overrideDefaults( { base_url: settings.baseURL, suffix: settings.suffix } );
		}
		// Wait for textarea to be visible before initialization.
		if ( $textarea.is( ':visible' ) ) {
			wpEditor.initialize( id, settings );
		}
		else {
			var intervalId = setInterval( function () {
				if ( $textarea.is( ':visible' ) ) {
					wpEditor.initialize( id, settings );
					clearInterval( intervalId );
				}
			}, 500);
		}
		
		$field.on( 'click', function ( event ) {
			var $target = $( event.target );
			if ( ! $target.is( '.wp-switch-editor' ) ) {
				return;
			}
			var mode = $target.hasClass( 'switch-tmce' ) ? 'tmce' : 'html';
			if ( mode === 'tmce' ) {
				var editor = window.tinymce.get( id );
				// Quick bit of sanitization to prevent catastrophic backtracking in TinyMCE HTML parser regex
				if ( editor !== null ) {
					var content = $textarea.val();
					if ( content.search( '<' ) !== -1 && content.search( '>' ) === -1) {
						content = content.replace( /</g, '' );
						$textarea.val( content );
					}
					editor.setContent(window.switchEditors.wpautop(content));
				}
			}
			settings.selectedEditor = mode;
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
