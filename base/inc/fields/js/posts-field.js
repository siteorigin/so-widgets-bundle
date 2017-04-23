/* global jQuery, _,  */

(function( $ ) {

	$( document ).on( 'sowsetupform', '.siteorigin-widget-field-type-posts', function ( e ) {
		var $postsField = $( this );
		$postsField.change( function ( event ) {
			console.log( 'query changed' );
		} );
	} );

})( jQuery );
