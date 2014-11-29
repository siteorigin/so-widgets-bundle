(function($){

    // After the form is setup, add some custom stuff.
    $(document).on( 'sowsetupform', '.siteorigin-widget-form[data-class="SiteOrigin_Widget_GoogleMap_Widget"]', function(){
        var $mapWidgetForm = $(this);

        if( typeof $mapWidgetForm.data('sowsetup-map-widget') == 'undefined' ) {

            var $mapTypeField = $mapWidgetForm.find('.siteorigin-widget-field-settingsmap_type');
            var updateFieldsForSelectedMapType = function () {
                var selectedType = $mapTypeField.find('input[type="radio"][name*="map_type"]:checked').val();
                $mapWidgetForm.data('selected-type', selectedType);
                if (selectedType == 'static') {
                    $mapWidgetForm.find('.siteorigin-widget-field-state-static').show();
                    $mapWidgetForm.find('.siteorigin-widget-field-state-interactive').hide();
                } else {
                    $mapWidgetForm.find('.siteorigin-widget-field-state-interactive').show();
                    $mapWidgetForm.find('.siteorigin-widget-field-state-static').hide();
                }
            };
            $mapTypeField.change(updateFieldsForSelectedMapType);
            updateFieldsForSelectedMapType();

            var $styleMethodField = $mapWidgetForm.find('.siteorigin-widget-field-stylesstyle_method');

            var updateFieldsForSelectedStyleMethod = function () {
                var selectedMethod = $styleMethodField.find('input[type="radio"][name*="style_method"]:checked').val();
                $mapWidgetForm.find('[class*="map_styles"]').hide();
                $mapWidgetForm.find('.siteorigin-widget-field-styles' + selectedMethod + '_map_styles').show();

                var $fieldMapName = $mapWidgetForm.find('.siteorigin-widget-field-stylesstyled_map_name');
                if ( selectedMethod != 'normal' && $mapWidgetForm.data('selected-type') == 'interactive') {
                    $fieldMapName.show();
                } else {
                    $fieldMapName.hide();
                }
            };
            $styleMethodField.change(updateFieldsForSelectedStyleMethod);
            updateFieldsForSelectedStyleMethod();

            $mapWidgetForm.data('sowsetup-map-widget', true);
        }
    } );

})(jQuery);