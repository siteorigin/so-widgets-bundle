/* globals jQuery */

jQuery(function($){

    $('.so-widget .switch .switch-input').change(function(e){
        var $$ = $(this);
        var s = $$.is(':checked');
        var $sw = $$.closest('.switch');

        if(s) {
            $$.closest('.so-widget').addClass('so-widget-is-active').removeClass('so-widget-is-inactive');
        }
        else {
            $$.closest('.so-widget').removeClass('so-widget-is-active').addClass('so-widget-is-inactive');
        }

        // Lets send an ajax request.
        $.post(
            $$.data('url'),
            { 'active' : s },
            function(data){
                $sw.find('.dashicons-yes').clearQueue().fadeIn('fast').delay(750).fadeOut('fast');
            }
        );
    });

    $(window).resize(function() {
        var $descriptions = $('div.so-widget-text');
        var largestHeight = 0;
        $descriptions.each(function () {
            var headerHeight = $(this).find('h4').height();
            var bodyHeight = $(this).find('p.so-widget-description').height();
            var headerMarginBottom = parseFloat($(this).find('h4').css('margin-bottom'));
            var bodyMarginTop = parseFloat($(this).find('p.so-widget-description').css('margin-top'));
            var innerMargin = Math.max(headerMarginBottom, bodyMarginTop);
            var divHeight = headerHeight + bodyHeight + innerMargin;
            largestHeight = Math.max(largestHeight, divHeight);
        });
        $descriptions.each(function () {
            var minHeight = parseInt($(this).css('min-height'));
            var divHeight = Math.max(largestHeight, minHeight);
            $(this).height(divHeight);
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
});