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

    // Lets fill in the extra header images
    $('.so-widget-banner').each( function(){
        var $$ = $(this);

        var pattern = Trianglify({
            width: 420,
            height: 210,
            seed: $$.data('seed')
        });

        var background = pattern.png();
        $$.css('background-image', 'url(' + background.replace(/(\r\n|\n|\r)/gm, "") + ')');
    } );

    $(window).resize(function() {
        var $descriptions = $('.so-widget-text').css('height', 'auto');
        var largestHeight = 0;

        $descriptions.each(function () {
            largestHeight = Math.max(largestHeight, $(this).height()  );
        });

        $descriptions.each(function () {
            $(this).css('height', largestHeight);
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