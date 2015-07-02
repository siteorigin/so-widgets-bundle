jQuery( function($){
    $('.ow-pt-icon[data-icon]').each(function(){
        var $$ = $(this);
        var icon = $$.data('icon');

        if($('#so-pt-icon-' + icon).length) {
            var svg = $('#so-pt-icon-' + icon + ' svg').clone().css({
                'max-width' : 24,
                'max-height' : 24
            });

            if($$.data('icon-color') != '') {
                svg.find('path').css( 'fill', $$.data('icon-color') );
            }
            else {
                svg.find('path').css( 'fill', '#333333' );
            }

            $$.append(svg);
        }
    })
} );