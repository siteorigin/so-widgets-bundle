(function( $ ) {

    $(document).on( 'sowsetupform', '.siteorigin-widget-form-main', function() {
        var $widgetForm = $(this);

        if( typeof $widgetForm.data('sowsetup-tinymce-fields') === 'undefined' ) {
            $widgetForm.find('.siteorigin-widget-input-tinymce').each(function (i, el) {
                var formClass = $widgetForm.data('class');
                var $el = $(el);
                var container = $el.closest('.siteorigin-widget-tinymce-container');
                var elementName = container.data('element-name');
                var mceSettings = window.sowGetWidgetFieldVariable(formClass, elementName, 'mceSettings');
                var qtSettings = window.sowGetWidgetFieldVariable(formClass, elementName, 'qtSettings');
                var id = container.attr('id');
                $.extend(mceSettings, tinyMCEPreInit.mceInit['siteorigin-widget-input-tinymce-field']);
                $.extend(qtSettings, tinyMCEPreInit.qtInit['siteorigin-widget-input-tinymce-field']);
                tinyMCEPreInit.mceInit[id] = mceSettings;
                tinyMCEPreInit.qtInit[id] = qtSettings;
                quicktags(tinyMCEPreInit.qtInit[id]);
                QTags._buttonsInit();
                tinymce.init(tinyMCEPreInit.mceInit[id]);
            });

            $widgetForm.data('sowsetup-tinymce-fields', true);
        }
    });
})( jQuery );