/* globals jQuery */
jQuery(function ($) {
    var $grid = $('.sow-masonry-grid');
    $grid.masonry({
        itemSelector: '.sow-masonry-grid-item',
        columnWidth: '.sow-masonry-grid-sizer',
        percentPosition: true
    });
});