( function( $ ) {

    $(document).on( 'sowsetupformfield', '.siteorigin-widget-field-type-media', function(e) {
        var $media = $(this).find('> .media-field-wrapper');
        var $field = $media.closest('.siteorigin-widget-field');

        // Handle the media uploader
        $media.find( '.media-upload-button' ).click(function(e){
            e.preventDefault();
            if( typeof wp.media === 'undefined' ) {
                return;
            }

            var $$ = $(this);
            var $c = $(this ).closest('.siteorigin-widget-field');
            var frame = $(this ).data('frame');

            // If the media frame already exists, reopen it.
            if ( frame ) {
                frame.open();
                return false;
            }

            // Create the media frame.
            frame = wp.media( {
                // Set the title of the modal.
                title: $$.data('choose'),

                // Tell the modal to show only images.
                library: {
                    type: $$.data('library').split(',').map(function(v){ return v.trim(); })
                },

                // Customize the submit button.
                button: {
                    // Set the text of the button.
                    text: $$.data('update'),
                    // Tell the button not to close the modal, since we're
                    // going to refresh the page when the image is selected.
                    close: false
                }
            } );

            // Store the frame
            $$.data('frame', frame);

            // When an image is selected, run a callback.
            frame.on( 'select', function() {
                // Grab the selected attachment.
                var attachment = frame.state().get('selection').first().attributes;

                $c.find('.current .title' ).html(attachment.title);
                var $inputField = $c.find( 'input[type=hidden]' );
                $inputField.val(attachment.id);
                $inputField.trigger('change');

                if(typeof attachment.sizes !== 'undefined'){
                    if(typeof attachment.sizes.thumbnail !== 'undefined'){
                        $c.find('.current .thumbnail' ).attr('src', attachment.sizes.thumbnail.url).fadeIn();
                    }
                    else {
                        $c.find('.current .thumbnail' ).attr('src', attachment.sizes.full.url).fadeIn();
                    }
                }
                else{
                    $c.find('.current .thumbnail' ).attr('src', attachment.icon).fadeIn();
                }

                $field.find('.media-remove-button').removeClass('remove-hide');

                frame.close();
            } );

            // Finally, open the modal.
            frame.open();
        });

        $media.find('.current' )
            .mouseenter(function(){
                var t = $(this ).find('.title' );
                if( t.html() !== ''){
                    t.fadeIn('fast');
                }
            })
            .mouseleave(function(){
                $(this ).find('.title' ).clearQueue().fadeOut('fast');
            });

        $field.find('a.media-remove-button' )
            .click( function( e ){
                e.preventDefault();
                $field.find('.current .title' ).html('');
                $field.find('input[type=hidden]' ).val('');
                $field.find('.current .thumbnail' ).fadeOut('fast');
                $(this).addClass('remove-hide');
            } );

        // Everything for the dialog
        var dialog = false;

        var reflowDialog = function() {
            if( ! dialog ) return;

            var results = dialog.find('.so-widgets-image-results');
            if( results.length === 0 ) return;

            var width = results.width(),
                perRow = Math.floor( width / 276 ),
                spare = ( width - perRow * 276 ),
                resultWidth = spare / perRow + 260;
            
            results.find( '.so-widgets-result-image' ).css( {
                'width' : resultWidth,
                'height' : resultWidth / 1.4
            } );
        };
        $(window).resize( reflowDialog );

        var setupDialog = function(){
            if( ! dialog ) {
                // Create the dialog
                dialog = $( $('#so-widgets-bundle-tpl-image-search-dialog').html().trim() ).appendTo( 'body' );
                dialog.find( '.close' ).click( function(){
                    dialog.hide();
                } );

                var results = dialog.find( '.so-widgets-image-results' );

                var fetchImages = function( query, page ){
                    dialog.find( '.so-widgets-results-loading' ).fadeIn('fast');
                    dialog.find( '.so-widgets-results-loading strong' ).html(
                        dialog.find( '.so-widgets-results-loading strong' ).data( 'loading' )
                    );
                    dialog.find( '.so-widgets-results-more' ).hide();

                    $.get(
                        ajaxurl,
                        {
                            'action' : 'so_widgets_image_search',
                            'q' : query,
                            'page' : page,
                            '_sononce' : dialog.find('input[name="_sononce"]').val()
                        },
                        function( response ){
                            if( response.error ) {
                                alert( response.message );
                                return;
                            }

                            results.removeClass( 'so-loading' );
                            $.each( response.items, function( i, r ){
                                var result = $( $('#so-widgets-bundle-tpl-image-search-result').html().trim() )
                                    .appendTo( results )
                                    .addClass( 'source-' + r.source );
                                var img = result.find('.so-widgets-result-image');

                                // Preload the image
                                img.css( 'background-image', 'url(' + r.thumbnail + ')' );
                                img.data( 'thumbnail', r.thumbnail );
                                img.data( 'preview', r.preview );

                                if( r.url ) {
                                    img.attr( {
                                        'href': r.url,
                                        'target': '_blank'
                                    } );
                                }

                                if( r.full_url ) {
                                    img.data( {
                                        'full_url' : r.full_url,
                                        'import_signature' : r.import_signature
                                    } );
                                    img.attr( 'href', r.full_url );
                                }

                                if( r.source === 'shutterstock' ) {
                                    img.append( $('#so-widgets-bundle-tpl-image-search-result-sponsored').html() );
                                }
                            } );

                            if( page === 1 ) {
                                dialog.find('#so-widgets-image-search-suggestions ul').empty();
                                $.each( response.keywords, function( i, r ){
                                    dialog.find('#so-widgets-image-search-suggestions').show();
                                    dialog.find('#so-widgets-image-search-suggestions ul').append(
                                        $('<li></li>') . append( $('<a href="#"></a>').html( r ).data( 'keyword', r ) )
                                    );
                                } );
                            }

                            dialog.find( '.so-widgets-results-loading' ).fadeOut('fast');

                            reflowDialog();
                            dialog
                                .find( '.so-widgets-results-more' ).show()
                                .find( 'button' ).data( { 'query': query, 'page' : page+1 } );
                        }
                    );

                }

                // Setup the search
                dialog.find('#so-widgets-image-search-form').submit( function( e ){
                    e.preventDefault();

                    // Perform the search
                    var q = dialog.find('.so-widgets-search-input').val();
                    results.empty();

                    if( q !== '' ) {
                        // Send the query to the server
                        fetchImages( q, 1 );
                    }
                } );

                // Clicking on the related search buttons
                dialog.on( 'click', '.so-keywords-list a', function( e ){
                    e.preventDefault();
                    var $$ = $(this).blur();
                    dialog.find('.so-widgets-search-input').val( $$.data( 'keyword' ) );
                    dialog.find('#so-widgets-image-search-form').submit();
                } );

                // Clicking on the more button
                dialog.find('.so-widgets-results-more button').click( function(){
                    var $$ = $(this);
                    fetchImages( $$.data( 'query' ), $$.data( 'page' ) );
                } );

                var hoverTimeout;

                // Clicking on an image to import it
                dialog.on( 'click', '.so-widgets-result-image', function( e ){
                    var $$ = $(this);
                    if( ! $$.data( 'full_url' ) ) {
                        return;
                    }

                    e.preventDefault();

                    if( confirm( dialog.data('confirm-import') ) ) {
                        dialog.addClass( 'so-widgets-importing' );

                        var postId = $( '#post_ID' ).val();
                        if( postId === null ) {
                            postId = '';
                        }

                        // Send the message to import the URL
                        $.get(
                            ajaxurl,
                            {
                                'action' : 'so_widgets_image_import',
                                'full_url' : $$.data( 'full_url' ),
                                'import_signature' : $$.data( 'import_signature' ),
                                'post_id' : postId,
                                '_sononce' : dialog.find('input[name="_sononce"]').val()
                            },
                            function( response ) {
                                dialog.find('#so-widgets-image-search-frame').removeClass( 'so-widgets-importing' );

                                if( response.error === false ) {
                                    // This was a success
                                    dialog.hide();
                                    dialog.find( '.so-widgets-results-loading' ).hide();
                                    $field.find( 'input[type=hidden]' ).val( response.attachment_id ).trigger('change');
                                    $field.find('.current .thumbnail' ).attr('src', response.thumb ).fadeIn();
                                }
                                else {
                                    alert( response.message );
                                    dialog.find( '.so-widgets-results-loading' ).hide();
                                }
                            }
                        );

                        // Clear the dialog
                        dialog.find( '.so-widgets-results-loading' ).fadeIn('fast');
                        dialog.find( '.so-widgets-results-loading strong' ).html(
                            dialog.find( '.so-widgets-results-loading strong' ).data( 'importing' )
                        );
                        dialog.find( '.so-widgets-results-more' ).hide();
                        dialog.find('#so-widgets-image-search-frame').addClass( 'so-widgets-importing' );
                    }
                } );

                // Hovering over an image to preview it
                var previewWindow = dialog.find('.so-widgets-preview-window');
                dialog
                    .on( 'mouseenter', '.so-widgets-result-image', function(){
                        var $$ = $(this),
                            preview = $$.data('preview');

                        clearTimeout( hoverTimeout );

                        hoverTimeout = setTimeout( function(){
                            // Scale the preview sizes
                            var scalePreviewX = 1, scalePreviewY = 1;
                            if( preview[1] > $( window ).outerWidth() *0.33 ) {
                                scalePreviewX = $( window ).outerWidth() *0.33 / preview[1];
                            }
                            if( preview[2] > $( window ).outerHeight() *0.5 ) {
                                scalePreviewY = $( window ).outerHeight() *0.5 / preview[2];
                            }
                            var scalePreview = Math.min( scalePreviewX, scalePreviewY );
                            // Never upscale
                            if( scalePreview > 1 ) {
                                scalePreview = 1;
                            }

                            previewWindow.show()
                                .find('.so-widgets-preview-window-inside')
                                .css( {
                                    'background-image' : 'url(' + $$.data('thumbnail') + ')',
                                    'width' : preview[1] * scalePreview,
                                    'height' : preview[2] * scalePreview
                                } )
                                .append( $( '<img />' ).attr( 'src', preview[0] ) );

                            dialog.trigger('mousemove');
                        }, 1000 );

                    } )
                    .on( 'mouseleave', '.so-widgets-result-image', function(){
                        previewWindow.hide().find('img').remove();
                        clearTimeout( hoverTimeout );
                    } );

                var lastX, lastY;
                dialog.on( 'mousemove', function( e ){
                    if( e.clientX ) lastX = e.clientX;
                    if( e.clientY ) lastY = e.clientY;

                    if( previewWindow.is( ':visible' ) ) {
                        var ph = previewWindow.outerHeight(),
                            pw = previewWindow.outerWidth(),
                            wh = $( window ).outerHeight(),
                            ww = $( window ).outerWidth();


                        // Calculate the top position
                        var top = lastY - ph/2;
                        top = Math.max( top, 10 );
                        top = Math.min( top, wh - 10 - ph );

                        // Calculate the left position
                        var left = (lastX < ww/2) ? lastX + 15 : lastX - 15 - pw;

                        // Figure out where the preview needs to go
                        previewWindow.css({
                            'top': top,
                            'left': left
                        });

                    }
                } );
            }

            dialog.show();
            dialog.find( '.so-widgets-search-input' ).focus();
        };

        // Handle displaying the image search dialog
        $media.find( '.find-image-button' ).click( function(e){
            e.preventDefault();
            setupDialog();
        } );

    });

} )( jQuery );