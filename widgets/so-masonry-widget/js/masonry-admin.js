(function($){

    $(document).on( 'sowsetupform', '.siteorigin-widget-form[data-class="SiteOrigin_Widget_Masonry_Widget"]', function() {
        var $masonryWidgetForm = $(this);

        if( typeof $masonryWidgetForm.data('sowsetup-masonry-widget') == 'undefined' ) {
            var $valInput = $masonryWidgetForm.find('input[type="hidden"][name*="posts_query"]');
            var updatePostsCount = function() {
                var query = $valInput.val();
                $.post(
                    soWidgets.ajaxurl,
                    { action: 'sow_get_posts', query: query },
                    function(data){
                        var $rptr = $masonryWidgetForm.find('.siteorigin-widget-field-repeater');
                        var $rptrItems = $rptr.find('.siteorigin-widget-field-repeater-item');
                        $.each(data.posts, function (index, post) {
                            var itemExists = false;
                            $rptrItems.each(function(index , rptrItem) {
                                var $rptrItem = $(rptrItem);
                                if( $rptrItem.find('input[id*="post_id"]').val() == post.id) {
                                    $rptrItem.find('input[id*="post_title"]').val(post.title);
                                    itemExists = true;
                                }
                            });
                            if( !itemExists ) {
                                $rptr.sowAddRepeaterItem().find('> .siteorigin-widget-field-repeater-items').slideDown('fast');
                                var $newItem = $rptr.find('.siteorigin-widget-field-repeater-item').last();
                                $newItem.find('.siteorigin-widget-field-repeater-item-top > h4').html(post.title);
                                $newItem.find('input[id*="post_id"]').val(post.id);
                                $newItem.find('input[id*="post_title"]').val(post.title);
                            }
                        });

                        $rptrItems.each(function (index, rptrItem) {
                            var itemFound = false;
                            var $rptrItem = $(rptrItem);
                            $.each(data.posts, function (index, post) {
                                if($rptrItem.find('input[id*="post_id"]').val() == post.id) {
                                    itemFound = true;
                                    return false; //break;
                                }
                            });
                            if(!itemFound) {
                                $rptrItem.sowRemoveRepeaterItem();
                            }
                        });
                    }
                );
            };

            $masonryWidgetForm.on('change', 'input[type="hidden"][name*="posts_query"]',
                function() {
                    var $valInput = $masonryWidgetForm.find('input[type="hidden"][name*="posts_query"]');
                    if(typeof $masonryWidgetForm.data('so-masonry-widget-posts_query') == 'undefined' || $masonryWidgetForm.data('so-masonry-widget-posts_query') != $valInput.val()) {
                        updatePostsCount();
                    }
                }
            );

            updatePostsCount();
            $masonryWidgetForm.data('sowsetup-masonry-widget', true);
        }
    });
})(jQuery);
