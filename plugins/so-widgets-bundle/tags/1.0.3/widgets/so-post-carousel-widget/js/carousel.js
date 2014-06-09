jQuery( function($){
    // The carousel widget
    $('.sow-carousel-wrapper').each(function(){

        var $$ = $(this),
            wrap = $$.closest('.widget'),
            title = wrap.find('.sow-carousel-title');

        var position = 0, page = 1, fetching = false, complete = false;

        var updatePosition = function() {
            if ( position < 0 ) position = 0;
            if ( position >= $$.find('.sow-carousel-item').length - 1 ) {
                position = $$.find('.sow-carousel-item').length - 1;

                // Fetch the next batch
                if( !fetching && !complete) {
                    fetching = true;
                    page++;
                    $$.find('.sow-carousel-items').append('<li class="sow-carousel-item sow-carousel-loading"></li>');

                    $.get(
                        $$.data('ajax-url'),
                        {
                            query : $$.data('query'),
                            action : 'sow_carousel_load',
                            paged : page
                        },
                        function (data, status){
                            var $items = $(data.html);
                            var count = $items.find('.sow-carousel-item').appendTo( $$.find('.sow-carousel-items') ).hide().fadeIn().length;
                            if(count == 0) {
                                complete = true;
                                $$.find('.sow-carousel-loading').fadeOut(function(){$(this).remove()});
                            }
                            else {
                                $$.find('.sow-carousel-loading').remove();
                            }
                            fetching = false;
                        }
                    )
                }
            }
            var entry = $$.find('.sow-carousel-item').eq(0);
            $$.find('.sow-carousel-items').css('margin-left', -( ( entry.width() + parseInt(entry.css('margin-right')) ) * position) + 'px' );
        };

        title.find('a.sow-carousel-previous').click( function(e){
            e.preventDefault();
            position -= 1;
            updatePosition();
        } );

        title.find('a.sow-carousel-next').click( function(e){
            e.preventDefault();
            position += 1;
            updatePosition();
        } );
    } );
} );