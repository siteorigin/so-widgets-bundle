/* global tinymce, switchEditors */

(function( $ ) {
	$( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-editor', function ( e ) {
		var $$ = $(this);
		var $container = $$.find( '.siteorigin-widget-tinymce-container' );
		var settings = $container.data( 'editorSettings' );
		var $textarea = $container.find( '> textarea' );
		var id = $textarea.attr( 'id' );

		wp.editor.initialize( id, settings );

		editor = window.tinymce.get( id );
	});

})( jQuery );
