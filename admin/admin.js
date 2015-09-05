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
            palette: {
                YlGn: ["#ffffe5","#f7fcb9","#d9f0a3","#addd8e","#78c679","#41ab5d","#238443","#006837","#004529"],
                BuGn: ["#f7fcfd","#e5f5f9","#ccece6","#99d8c9","#66c2a4","#41ae76","#238b45","#006d2c","#00441b"],
                PuBu: ["#fff7fb","#ece7f2","#d0d1e6","#a6bddb","#74a9cf","#3690c0","#0570b0","#045a8d","#023858"],
                BuPu: ["#f7fcfd","#e0ecf4","#bfd3e6","#9ebcda","#8c96c6","#8c6bb1","#88419d","#810f7c","#4d004b"],
                RdPu: ["#fff7f3","#fde0dd","#fcc5c0","#fa9fb5","#f768a1","#dd3497","#ae017e","#7a0177","#49006a"],
                PuRd: ["#f7f4f9","#e7e1ef","#d4b9da","#c994c7","#df65b0","#e7298a","#ce1256","#980043","#67001f"],
                OrRd: ["#fff7ec","#fee8c8","#fdd49e","#fdbb84","#fc8d59","#ef6548","#d7301f","#b30000","#7f0000"],
                Purples: ["#fcfbfd","#efedf5","#dadaeb","#bcbddc","#9e9ac8","#807dba","#6a51a3","#54278f","#3f007d"],
                Blues: ["#f7fbff","#deebf7","#c6dbef","#9ecae1","#6baed6","#4292c6","#2171b5","#08519c","#08306b"],
                Greens: ["#f7fcf5","#e5f5e0","#c7e9c0","#a1d99b","#74c476","#41ab5d","#238b45","#006d2c","#00441b"],
                Oranges: ["#fff5eb","#fee6ce","#fdd0a2","#fdae6b","#fd8d3c","#f16913","#d94801","#a63603","#7f2704"],
                Reds: ["#fff5f0","#fee0d2","#fcbba1","#fc9272","#fb6a4a","#ef3b2c","#cb181d","#a50f15","#67000d"],
                Greys: ["#ffffff","#f0f0f0","#d9d9d9","#bdbdbd","#969696","#737373","#525252","#252525","#000000"],
            },
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