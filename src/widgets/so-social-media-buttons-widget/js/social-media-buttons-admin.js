(function($){

    // After the form is setup, add some custom stuff.
    $(document).on( 'sowsetupform', '.siteorigin-widget-form[data-class="SiteOrigin_Widget_SocialMediaButtons_Widget"]', function() {
        var $socialMediaForm = $(this);

        var setNetworkDefaults = function($selectNetworkInput) {
            window.sowFetchWidgetVariable('networks', 'SiteOrigin_Widget_SocialMediaButtons_Widget',
                function(networks) {
                    var selectedNetwork = networks[$selectNetworkInput.find(':selected').val()];
                    var $closestForm = $selectNetworkInput.closest('.siteorigin-widget-field-repeater-item-form');

                    var $urlInput = $closestForm.find('[id*="networks-url"]');
                    $urlInput.val(selectedNetwork.base_url);

                    var $iconColorPicker = $closestForm.find('[id*="networks-icon_color"]');
                    $iconColorPicker.wpColorPicker('color', selectedNetwork.icon_color);

                    var $buttonColorPicker = $closestForm.find('[id*="networks-button_color"]');
                    $buttonColorPicker.wpColorPicker('color', selectedNetwork.button_color);
                }
            );
        };

        if ( typeof $socialMediaForm.data('initialised') == 'undefined' ) {
            $socialMediaForm.on('change', '[id*="networks-name"]',
                function(event) {
                    setNetworkDefaults($(event.target));
                }
            );

            $socialMediaForm.data('initialised', true);
        }

    } );

})(jQuery);