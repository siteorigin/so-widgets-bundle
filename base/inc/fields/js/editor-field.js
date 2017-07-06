/* global tinymce, switchEditors */

(function( $ ) {
	$( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-editor', function ( e ) {
		var $$ = $(this);
		var $textarea = $$.find( '> textarea' );
		var id = $textarea.attr( 'id' );

		wp.editor.initialize( id, {
			tinymce: {
				wpautop: true
			},
			quicktags: true
		} );

		editor = window.tinymce.get( id );
	});

})( jQuery );
