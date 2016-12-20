
var sowb = window.sowb || {};

sowb.setupImageGrid = function() {
	var $ = jQuery;
	
    $('.sow-image-grid-wrapper').each( function(){
        var $$ = $(this);

        var maxWidth = $$.data('max-width'),
            maxHeight =  $$.data('max-height');

        if( maxWidth !== undefined || maxHeight !== undefined ) {
            $$.find('img').each( function(){
                var $img = $(this).css('display', 'block'),
                    ratio = $img.width() / $img.height();

                var width = [];

                // Lets set the widths of the image
                if( maxWidth != undefined && $img.width() > maxWidth ) {
                    width.push( maxWidth );
                }

                if( maxHeight != undefined && $img.height() > maxHeight  ) {
                    width.push( Math.round( maxHeight * ratio ) );
                }

                if( width.length ) {
                    width = Math.min.apply( Math, width );
                    $img.css('max-width', width);
                }

            } );
        }
        else {
            $$.find('img').css('display', 'block');
        }

        var alignImages = function(){

        };
        alignImages();

        $(window).resize(alignImages);

    } );

};

jQuery( function($){
	sowb.setupImageGrid();
} );
