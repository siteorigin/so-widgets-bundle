/* globals jQuery, soWidgetsAdmin */

jQuery( function( $ ){

	$( '.so-widget-toggle-active button' ).on( 'click', function() {
		var $$ = $(this),
			s = $$.data('status'),
			$w = $$.closest('.so-widget');

		if(s) {
			$w.addClass('so-widget-is-active').removeClass('so-widget-is-inactive');
		}
		else {
			$w.removeClass('so-widget-is-active').addClass('so-widget-is-inactive');
		}

		// Lets send an ajax request.
		$.post(
			soWidgetsAdmin.toggleUrl,
			{
				'widget' : $w.data('id'),
				'active' : s
			},
			function(data){
				// $sw.find('.dashicons-yes').clearQueue().fadeIn('fast').delay(750).fadeOut('fast');
			}
		);

	} );

	//  Fill in the missing header images
	$('.so-widget-banner').each( function(){
		var $$ = $(this),
			$img = $$.find('img');

		if( !$img.length ) {
			// Create an SVG image as a placeholder icon
			var pattern = Trianglify({
				width: 128,
				height: 128,
				variance : 1,
				cell_size: 32,
				seed: $$.data('seed')
			});

			$$.append( pattern.svg() );
		}
		else {
			if( $img.width() > 128 ) {
				// Deal with wide banner images
				$img.css( 'margin-left', - ( $img.width() - 128 ) / 2 + 'px' );
			}
		}
	} );

	// Lets implement the search
	var widgetSearch = function(){
		var q = $(this).val().toLowerCase();

		if( q === '' ) {
			$('.so-widget-wrap').show();
		}
		else {
			$('.so-widget').each( function(){
				var $$ = $(this);

				if( $$.find('h3').html().toLowerCase().indexOf(q) > -1 ) {
					$$.parent().show();
				}
				else {
					$$.parent().hide();
				}
			} );
		}

		$( window ).trigger( 'resize' );
	};
	$('#sow-widget-search input').on( {
		keyup: widgetSearch,
		search: widgetSearch
	});

	$( window ).on( 'resize', function() {
		var $descriptions = $( '.so-widget-text:visible' );
		var largestHeight = 0;
		var largestHeight = [];
		var column = 0;

		$descriptions.css( 'height', 'auto' );

		// Don't size text descriptions on tablet portrait and mobile devices.
		if ( window.matchMedia( '(max-width: 960px)' ).matches ) {
			return;
		}

		// Work out how many columns are visible per row.
		if ( window.matchMedia( '(min-width: 1800px)' ).matches ) {
			columnCount = 4;
		} else if ( window.matchMedia( '(max-width: 1280px)' ).matches ) {
			columnCount = 2;
		} else {
			columnCount = 3;
		}

		$descriptions.each( function( index ) {
			column = index / columnCount;
			// Turnicate column number - IE 11 friendly.
			column = column < 0 ? Math.ceil( column ) : Math.floor( column );
			$( this ).data( 'column', column )

			largestHeight[ column ] = Math.max( typeof largestHeight[ column ] == 'undefined' ? 0 : largestHeight[ column ], $( this ).height() );
		} );

		$descriptions.each( function() {
			$( this ).css( 'height', largestHeight[ $( this ).data( 'column' ) ] + 'px' );
		} );
	} ).trigger( 'resize' );

	// Handle the tabs
	$( '#sow-widgets-page .page-nav a' ).on( 'click', function( e ) {
		e.preventDefault();
		var $$ = $(this);
		var href = $$.attr('href');

		var $li = $$.closest('li');
		$('#sow-widgets-page .page-nav li').not($li).removeClass('active');
		$li.addClass('active');

		switch( href ) {
			case '#all' :
				$('.so-widget-wrap').show();
				break;

			case '#enabled' :
				$('.so-widget-wrap').hide();
				$('.so-widget-wrap .so-widget-is-active').each(function(){ $(this).closest('.so-widget-wrap').show(); });
				$('.so-widget-wrap .so-widget-is-inactive').each(function(){ $(this).closest('.so-widget-wrap').hide(); });
				break;

			case '#disabled' :
				$('.so-widget-wrap .so-widget-is-active').each(function(){ $(this).closest('.so-widget-wrap').hide(); });
				$('.so-widget-wrap .so-widget-is-inactive').each(function(){ $(this).closest('.so-widget-wrap').show(); });
				break;
		}

		$( window ).trigger( 'resize' );
	});

	// Enable css3 animations on the widgets list
	$('#widgets-list').addClass('so-animated');

	// Handle the dialog
	var dialog = $('#sow-settings-dialog');

	$( '#widgets-list .so-widget-settings' ).on( 'click', function( e ) {
		var $$ = $(this);
		e.preventDefault();

		$content = dialog.find( '.so-content' );
		$content
			.empty()
			.addClass('so-loading')

		$.get( $$.data( 'form-url' ), function( form ) {
			$content
				.html( form )
				.removeClass( 'so-loading' );
		} );

		dialog.show();
		$( '#sow-settings-dialog .so-close' ).trigger( 'focus' );

		// Close dialog when escape is pressed.
		$( window ).one( 'keyup', function( e ) {
			if ( e.which === 27 ) {
				dialog.hide();
			}
		} );
	} );

	dialog.find( '.so-close' ).on( 'click keyup', function( e ){
		if ( e.type == 'keyup' && ! window.sowbForms.isEnter( e ) ) {
			return;
		}
		e.preventDefault();
		dialog.hide();
	} );

	dialog.find( '.so-save' ).on( 'click', function( e ) {
		e.preventDefault();
		var $form = dialog.find( 'form' );

		validSave = sowbForms.validateFields( $form )
		if ( typeof validSave == 'boolean' && ! validSave ) {
			return false;
		}

		var $$ = $( this );
		$$.prop( 'disabled', true );

		$form.on( 'submit', function() {
			$$.prop( 'disabled', false );
			dialog.hide();
		} ).trigger( 'submit' );
	} );

	// Enable all widget settings button after the save iframe has loaded.
	$('#so-widget-settings-save').on( 'load', function() {
		$( '#widgets-list .so-widget-settings' ).prop( 'disabled', false );
	} );

	// Automatically open settings modal based on hash
	if( window.location.hash && window.location.hash.substring(0, 10) === '#settings-' ) {
		var openSettingsId = window.location.hash.substring(10);
		$('div[data-id="' + openSettingsId +  '"] button.so-widget-settings').trigger( 'click' );
	}

} );
