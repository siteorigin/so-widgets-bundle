jQuery( function( $ ) {

    $(document).on( 'sowsetupformfield', '.siteorigin-widget-field-type-media', function(e) {
        var $media = $(this).find('> .media-field-wrapper');
        var $field = $media.closest('.siteorigin-widget-field');

        // Handle the media uploader
        $media.find('a.media-upload-button' ).click(function(e){
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

            return false;
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
            })

        $field.find('a.media-remove-button' )
            .click(function(e){
                e.preventDefault();
                $field.find('.current .title' ).html('');
                $field.find('input[type=hidden]' ).val('');
                $field.find('.current .thumbnail' ).fadeOut('fast');
                $(this).addClass('remove-hide');
            });

    });

} );