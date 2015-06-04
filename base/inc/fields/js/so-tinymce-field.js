/* global sowGetWidgetFieldVariable, tinyMCEPreInit, quicktags, QTags, tinymce */

(function( $ ) {

    $(document).on( 'sowsetupform', '.siteorigin-widget-form-main', function() {
        var $widgetForm = $(this);

        if( typeof $widgetForm.data('sowsetup-tinymce-fields') === 'undefined' ) {
            $widgetForm.find('.siteorigin-widget-tinymce-container').each(function (i, el) {
                var formClass = $widgetForm.data('class');
                var container = $(el);
                var id = container.find('textarea').attr('id');
                var name = container.find('textarea').attr('name');
                var fieldName = /[a-zA-Z0-9\-]+\[[a-zA-Z0-9]+\]\[(.*)\]/.exec( name )[1];
                var mceSettings = sowGetWidgetFieldVariable(formClass, name, 'mceSettings');
                var qtSettings = sowGetWidgetFieldVariable(formClass, name, 'qtSettings');
                var idPattern = new RegExp( 'widget-' + formClass.replace(/_/g, '-').toLowerCase() + '-[a-zA-Z0-9]+-' + fieldName );
                for( var initId in tinyMCEPreInit.mceInit) {
                    if(initId.match(idPattern)) {
                        mceSettings = $.extend({}, tinyMCEPreInit.mceInit[initId], mceSettings);
                    }
                }
                mceSettings = $.extend({}, mceSettings, {selector:'#'+id});
                qtSettings = $.extend({}, tinyMCEPreInit.qtInit['siteorigin-widget-input-tinymce-field'], qtSettings, {id:id});
                tinyMCEPreInit.mceInit[id] = mceSettings;
                tinyMCEPreInit.qtInit[id] = qtSettings;
                quicktags(tinyMCEPreInit.qtInit[id]);
                var wrapDiv = container.find('div#wp-' + id + '-wrap');
                if(wrapDiv.hasClass('tmce-active')) {
                    tinymce.init(tinyMCEPreInit.mceInit[id]);
                }
            });
            QTags._buttonsInit();

            $widgetForm.data('sowsetup-tinymce-fields', true);
        }
    });
})( jQuery );