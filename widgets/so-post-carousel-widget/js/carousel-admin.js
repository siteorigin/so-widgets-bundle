(function($){

    // After the form is setup, add some custom stuff.
    $(document).on('sowsetupform', '.siteorigin-widget-form[data-class="SiteOrigin_Widget_PostCarousel_Widget"]', function () {
        var $carouselForm = $(this);
        var $valInput = $carouselForm.find('input[type="hidden"][name*="posts"]');
        var updatePostsCount = function() {
            var query = $valInput.val();
            $.post(
                ajaxurl,
                { action: 'sow_get_posts', query: query, 'ignore_pagination' : true },
                function(data){
                    $carouselForm.find('.sow-current-count').text(data.found_posts);
                }
            );
        };

        $carouselForm.on('change', 'input[type="hidden"][name*="posts"]',
            function() {
                updatePostsCount();
            }
        );

        updatePostsCount();
    });

})(jQuery);