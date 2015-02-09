jQuery( function($){
    // The carousel widget
    $('.sow-carousel-wrapper').each(function(){

        var $$ = $(this),
            $container = $$.closest('.sow-carousel-container').parent(),
            $itemsContainer = $$.find('.sow-carousel-items');

        var position = 0, page = 1, fetching = false, complete = false;

        var updatePosition = function() {
            if ( position < 0 ) position = 0;
            if ( position >= $$.find('.sow-carousel-item').length - 1 ) {
                position = $$.find('.sow-carousel-item').length - 1;

                // Fetch the next batch
                if( !fetching && !complete) {
                    fetching = true;
                    page++;
                    $itemsContainer.append('<li class="sow-carousel-item sow-carousel-loading"></li>');

                    $.get(
                        $$.data('ajax-url'),
                        {
                            query : $$.data('query'),
                            action : 'sow_carousel_load',
                            paged : page
                        },
                        function (data, status){
                            var $items = $(data.html);
                            var count = $items.appendTo( $itemsContainer ).hide().fadeIn().length;
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
            $itemsContainer.css('transition-duration', "0.45s");
            $itemsContainer.css('margin-left', -( ( entry.width() + parseInt(entry.css('margin-right')) ) * position) + 'px' );
        };

        $container.on( 'click', 'a.sow-carousel-previous',
            function(e){
                e.preventDefault();
                position -= 1;
                updatePosition();
            }
        );

        $container.on( 'click', 'a.sow-carousel-next',
            function(e){
                e.preventDefault();
                position += 1;
                updatePosition();
            }
        );
        var validSwipe = false;
        $$.swipe( {
            excludedElements: "",
            triggerOnTouchEnd: true,
            threshold: 75,
            swipeStatus: function (event, phase, direction, distance) {
                var $item = $$.find('.sow-carousel-item');
                var itemWidth = $item.eq(0).width() + parseInt($item.css('margin-right'));
               if ( phase == "move" ) {
                    var curPos = -( itemWidth * position);
                    if (direction == "left") {
                        curPos -= distance;
                    } else if( direction == "right") {
                        curPos += distance;
                    }
                    $itemsContainer.css('transition-duration', "0s");
                    $itemsContainer.css('margin-left', curPos + 'px' );
                }
                else if ( phase == "end" ) {
                    var swipeFinalPos = parseInt( $itemsContainer.css('margin-left') );
                    position = Math.abs( Math.round( swipeFinalPos / itemWidth ) );
                    updatePosition();
                    validSwipe = true;
                }
                else if( phase == "cancel") {
                    updatePosition();
                }
            }
        } );

        $$.on('click', '.sow-carousel-item a',
            function (event) {
                if(validSwipe) {
                    event.preventDefault();
                    validSwipe = false;
                }
            }
        )
    } );
} );