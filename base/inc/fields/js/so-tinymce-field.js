(function( $ ) {

    $(document).on( 'sowsetupform', '.siteorigin-widget-form-main', function() {
        var $widgetForm = $(this);

        if( typeof $widgetForm.data('sowsetup-tinymce-fields') === 'undefined' ) {
            $widgetForm.find('.siteorigin-widget-input-tinymce').each(function (i, el) {
                var formClass = $widgetForm.data('class');
                var $el = $(el);
                var container = $el.closest('.siteorigin-widget-tinymce-container');
                var elementName = container.data('element-name');
                var id = container.data('element-id');
                var html = container.html();
                var repped = html.replace(/siteorigin-widget-input-tinymce-field/g, id);
                container.html(repped);
                var mceSettings = window.sowGetWidgetFieldVariable(formClass, elementName, 'mceSettings');
                var qtSettings = window.sowGetWidgetFieldVariable(formClass, elementName, 'qtSettings');
                $.extend(mceSettings, tinyMCEPreInit.mceInit['siteorigin-widget-input-tinymce-field'], {selector:'#'+id});
                $.extend(qtSettings, tinyMCEPreInit.qtInit['siteorigin-widget-input-tinymce-field'], {id:id});
                tinyMCEPreInit.mceInit[id] = mceSettings;
                tinyMCEPreInit.qtInit[id] = qtSettings;
                quicktags(tinyMCEPreInit.qtInit[id]);
                tinymce.init(tinyMCEPreInit.mceInit[id]);
            });
            QTags._buttonsInit();

            $widgetForm.data('sowsetup-tinymce-fields', true);
        }
    });
})( jQuery );