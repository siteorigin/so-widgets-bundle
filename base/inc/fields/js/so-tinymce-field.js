/* global sowGetWidgetFieldVariable, tinyMCEPreInit, quicktags, QTags, tinymce */

(function( $ ) {

    $(document).on( 'sowsetupform', '.siteorigin-widget-form-main', function() {
        var $widgetForm = $(this);
        $widgetForm.find('.siteorigin-widget-tinymce-container').each(function (i, el) {
            var container = $(el);
            if( container.data('tinymce-setup-complete') ) return;
            var formClass = $widgetForm.data('class');
            var id = container.find('textarea').attr('id');
            if( id.indexOf( '__i__' ) > -1 ) return;
            var name = container.find('textarea').attr('name');
            var fieldName = /[a-zA-Z0-9\-]+\[[a-zA-Z0-9]+\]\[(.*)\]/.exec( name )[1];
            var mceSettings = sowGetWidgetFieldVariable(formClass, name, 'mceSettings');
            var qtSettings = sowGetWidgetFieldVariable(formClass, name, 'qtSettings');
            var idPattern = new RegExp( 'widget-.+-[_a-zA-Z0-9]+-' + fieldName.replace( '][', '-', 'g' ) + '[-\d]*' );
            for( var initId in tinyMCEPreInit.mceInit) {
                if(initId.match(idPattern)) {
                    mceSettings = $.extend({}, tinyMCEPreInit.mceInit[initId], mceSettings);
                }
            }
            mceSettings = $.extend({}, mceSettings, {selector:'#'+id});
            qtSettings = $.extend({}, tinyMCEPreInit.qtInit['siteorigin-widget-input-tinymce-field'], qtSettings, {id:id});
            tinyMCEPreInit.mceInit[id] = mceSettings;
            tinyMCEPreInit.qtInit[id] = qtSettings;
            if( QTags.instances[ id ] == null ) {
                quicktags(tinyMCEPreInit.qtInit[id]);
            }
            var wrapDiv = container.find('div#wp-' + id + '-wrap');
            if(tinymce.get(id) == null && wrapDiv.hasClass('tmce-active')) {
                tinymce.init(tinyMCEPreInit.mceInit[id]);
            }
            container.find('#'+id).change(function() {
                    var content = tinymce.get( id ).save();
                    container.find('input[type="hidden"]').val(content);
                }
            );
            container.data('tinymce-setup-complete', true);
        });
        QTags._buttonsInit();
    });
})( jQuery );