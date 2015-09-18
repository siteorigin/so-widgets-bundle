/* globals jQuery */
jQuery(function ($) {
    var $grid = $('.sow-masonry-grid');

    var resizeMasonry = function(){
        var columnWidth;
        $grid.each(function(){
            var $$ = $(this);
            var numColumns = $$.data('numColumns');
            $$.css('width', 'auto');
            columnWidth = $$.width()/numColumns;
            $$.width(Math.floor(columnWidth)*numColumns);
        });

        $('.sow-masonry-grid > .sow-masonry-grid-item').each(function(){
            var $$ = $(this);
            var rowSpan = $$.data('rowSpan');
            $$.css('height', columnWidth * rowSpan);
        });
    };

    $(window).resize(resizeMasonry);
    resizeMasonry();

    $grid.masonry({
        itemSelector: '.sow-masonry-grid-item',
        columnWidth: '.sow-masonry-grid-sizer',
        percentPosition: true
    });
});