/* globals jQuery, soWidgetsAdmin */

jQuery( function( $ ){

    $('.so-widget-toggle-active button').click( function(){
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
                $img.css('margin-left', -($img.width()-128)/2 );
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
    };
    $('#sow-widget-search input').on( {
        keyup: widgetSearch,
        search: widgetSearch
    });

    $(window).resize(function() {
        var $descriptions = $('.so-widget-text').css('height', 'auto');
        var largestHeight = 0;

        $descriptions.each(function () {
            largestHeight = Math.max(largestHeight, $(this).height()  );
        });

        $descriptions.each(function () {
            $(this).css('height', largestHeight);
        });

    }).resize();

    // Handle the tabs
    $('#sow-widgets-page .page-nav a').click(function(e){
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

        $(window).resize();
    });

    // Enable css3 animations on the widgets list
    $('#widgets-list').addClass('so-animated');

    // Handle the dialog
    var dialog = $('#sow-settings-dialog');

    $( '#widgets-list .so-widget-settings' ).click( function( e ){
        var $$ = $(this);
        e.preventDefault();

        dialog.find('.so-content')
            .empty()
            .addClass('so-loading')
            .load( $$.data('form-url'), function(){
                $(this).removeClass('so-loading');
            } );

        dialog.show();
    } );

    dialog.find('.so-close').click( function( e ){
        e.preventDefault();
        dialog.hide();
    } );

    dialog.find('.so-save').click( function( e ){
        e.preventDefault();

	    var $$ = $(this);
	    $$.prop('disabled', true);
        $( '#widgets-list .so-widget-settings' ).prop('disabled', true);

	    dialog.find( 'form' ).submit( function( ){
		    $$.prop('disabled', false);
		    dialog.hide();
	    } ).submit();
    } );

    // Enable all widget settings button after the save iframe has loaded.
    $('#so-widget-settings-save').load( function(){
        $( '#widgets-list .so-widget-settings' ).prop('disabled', false);
    } );

    // Automatically open settings modal based on hash
    if( window.location.hash && window.location.hash.substring(0, 10) === '#settings-' ) {
        var openSettingsId = window.location.hash.substring(10);
        $('div[data-id="' + openSettingsId +  '"] button.so-widget-settings').click();
    }

} );
