/* globals jQuery */
jQuery(function ($) {
    var $grid = $('.sow-masonry-grid');

    var resizeMasonry = function(){
        var columnWidth;
        $grid.each(function(){
            var $gridEl = $(this);
            var settings = $gridEl.data('settings');
            var numColumns = settings.numColumns;
            $gridEl.css('width', 'auto');
            var horizontalGutterSpace = settings.gutter * ( numColumns - 1 );
            columnWidth = Math.floor( ( $gridEl.width() - ( horizontalGutterSpace ) ) / numColumns );
            $gridEl.width( ( columnWidth * numColumns ) + horizontalGutterSpace );

            $gridEl.find('> .sow-masonry-grid-item').each(function(){
                var $$ = $(this);
                var colSpan = $$.data('colSpan');
                $$.width( ( columnWidth * colSpan ) + (settings.gutter * (colSpan-1)));
                var rowSpan = $$.data('rowSpan');
                //Use rowHeight if non-zero else fall back to matching columnWidth.
                var rowHeight = settings.rowHeight || columnWidth;
                $$.css('height', (rowHeight * rowSpan) + (settings.gutter * (rowSpan-1)));

                var $img = $$.find('> img,> a > img');
                var imgAR = $img.attr('height') > 0 ? $img.attr('width')/$img.attr('height') : 1;
                var itemAR = $$.height() > 0 ? $$.width()/$$.height() : 1;
                imgAR = parseFloat(imgAR.toFixed(3));
                itemAR = parseFloat(itemAR.toFixed(3));
                if(imgAR > itemAR) {
                    $img.css('width', 'auto');
                    $img.css('height', '100%');
                    var marginLeft = ($img.width() - $$.width()) * -0.5;
                    $img.css('margin-left', marginLeft+'px');
                }
                else {
                    $img.css('height', 'auto');
                    $img.css('width', '100%');
                    var marginTop = ($img.height() - $$.height()) * -0.5;
                    $img.css('margin-top', marginTop+'px');
                }
            });

            $gridEl.packery({
                itemSelector: '.sow-masonry-grid-item',
                columnWidth: columnWidth,
                gutter: settings.gutter
            });
        });
    };

    $(window).resize(resizeMasonry);
    resizeMasonry();
});