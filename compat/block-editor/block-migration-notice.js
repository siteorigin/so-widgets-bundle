jQuery( function( $ ) {
	const sendRequest = ( action ) => {
		$.post( ajaxurl, {
			action: action,
			nonce: sowbBlockMigration.nonce
		} );
	}

	$( document ).on( 'click', '.so-widgets-block-migration-notice .notice-consent', function() {
		$( this ).closest( '.so-widgets-block-migration-notice' ).slideUp( 200 );

		sendRequest( 'so_widgets_block_migration_notice_consent' );
	} );

	$( document ).on( 'click', '.so-widgets-block-migration-notice .notice-dismiss', function() {
		$( this ).closest( '.so-widgets-block-migration-notice' ).slideUp( 200 );

		sendRequest( 'so_widgets_block_migration_notice_dismiss' );
	} );
} );