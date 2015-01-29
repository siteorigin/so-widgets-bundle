/**
 * Main theme Javascript - (c) Greg Priday, freely distributable under the terms of the GPL 2.0 license.
 */

jQuery(function($){
    if($('body').has('.so-masonry-container')){

        var resizeMasonry = function(){
            // Make sure the width of the masonry container is always divisible by 4
            $('.so-masonry-container').each(function(){
                var $$ = $(this);
                $$.css('width', 'auto');
                $$.width(Math.floor($$.width()/4)*4);
            });

            $('.so-masonry-container > .masonry-brick').each(function(){
                // First start by simulating the size
                var $$ = $(this);

                if($$.hasClass('size-11') || $$.hasClass('size-22')){
                    $$.css('height', $$.width());
                }
                else if($$.hasClass('size-21')){
                    $$.css('height', $$.width()/2);
                }
                else if($$.hasClass('size-12')){
                    $$.css('height', $$.width()*2);
                }
            });

            $('.so-masonry-container .post-information').each(function(){
                $(this).css('margin-top', -$(this).height()/2);
            })
        }

        $(window).resize(resizeMasonry);
        resizeMasonry();

        $('.so-masonry-container').masonry({
            itemSelector: '.masonry-brick',
            // set columnWidth a fraction of the container width
            columnWidth: function( containerWidth ) {
                if(!$('body').hasClass('responsive')) return containerWidth/4;

                if($(window).width() <= 640){
                    return containerWidth/2;
                }
                else if($(window).width() <= 480){
                    return containerWidth;
                }
                else{
                    return containerWidth/4;
                }
            }
        });

        var openSpeed = 450;
        var closeSpeed = 250;

        // Set up the hover effect
        $('.so-masonry-container .masonry-brick').not('.no-thumbnail')
            .mouseenter(function(){
                var $$ = $(this);
                if($$.find('.thumbnail-link').hasClass('loading')) return;

                var img = $$.find('.thumbnail-link img').css('visibility','visible').eq(0);

                if($$.has('.splitter').length){
                    // Just animate the existing splitters

                    $$.find('.top').clearQueue().animate({'bottom': img.height(), 'opacity' : 0}, openSpeed );
                    $$.find('.bottom').clearQueue().animate({'top': img.height(), 'opacity' : 0}, openSpeed );

                    return;
                }

                $$.find('.splitter').remove();

                var spBase = $('<div/>').addClass('splitter').css({
                    'height' : img.height()/2
                }).hide();

                var spTop = spBase.clone().addClass('top').css('bottom', img.height()/2).append(img.clone().removeClass('wp-post-image').width(img.width()).height(img.height()));
                var spBottom = spBase.clone().addClass('bottom').css('top', img.height()/2).append(img.clone().removeClass('wp-post-image').width(img.width()).height(img.height()));

                img.hide();
                $$.find('a.thumbnail-link').append([spBottom, spTop]);

                spTop.show().animate({'bottom': img.height(), 'opacity' : 0}, openSpeed );
                spBottom.show().animate({'top': img.height(), 'opacity' : 0}, openSpeed );
                $$.find('.post-information').clearQueue().css('margin-top', -$$.find('.post-information').height()/2).fadeIn(closeSpeed, function(){
                    $(this).css('opacity', 1);
                });
            })
            .mouseleave(function(){
                var $$ = $(this);
                if($$.find('.thumbnail-link').hasClass('loading')) return;

                var img = $$.find('.wp-post-image');

                $$.find('.post-information').clearQueue().fadeOut(closeSpeed);

                $$.find('.top').clearQueue().animate({'bottom': img.height()/2, 'opacity' : 1}, closeSpeed, function(){
                    $(this).remove();
                });
                $$.find('.bottom').clearQueue().animate({'top': img.height()/2, 'opacity' : 1}, closeSpeed, function(){
                    img.show();
                    $(this).remove();
                });
            });
    }

    // Fade in loader for the thumbnail images
    if($('.so-masonry-container .thumbnail-link img').length){

        // Resize the thumbnails
        var resizeThumbs = function(){
            $('.so-masonry-container .thumbnail-link img').each(function(){
                var $$ = $(this);
                var $p = $$.parent();

                $p.css('height', $$.width() / Number($$.attr('width')) * Number($$.attr('height')));
            });
        }
        $(window).resize(resizeThumbs);
        resizeThumbs();

        $('.so-masonry-container .thumbnail-link img').hide().each(function(){
            var $$ = $(this);
            if($$.get(0).complete) {
                // Ignore this if it's already complete
                $$.show();
                return;
            }

            // Fade in all the images as they load
            $$.closest('.masonry-brick').addClass('loading');
            var temp = $('<img />').attr('src', $$.attr('src')).load(function(){
                $$.css('visibility', 'visible').hide().fadeIn('slow');
                $$.closest('.masonry-brick').removeClass('loading');
            });
        });
    }
});