/* global sowGetWidgetFieldVariable, tinyMCEPreInit, quicktags, QTags, tinymce */

(function( $ ) {
    var setup = function(widgetForm) {
        $(widgetForm).find('.siteorigin-widget-tinymce-container').each( function (index, element) {
            var $container = $(element);
            var formClass = $container.closest('.siteorigin-widget-form-main').data('class');
            var $textarea = $container.find('textarea');
            var id = $textarea.attr('id');
            if( id.indexOf( '__i__' ) > -1 ) return;
            var name = $textarea.attr('name').replace(/\[\d\]/g, '');
            var qtSettings = sowGetWidgetFieldVariable(formClass, name, 'qtSettings');
            qtSettings = $.extend({}, tinyMCEPreInit.qtInit['siteorigin-widget-input-tinymce-field'], qtSettings, {id:id});
            tinyMCEPreInit.qtInit[id] = qtSettings;
            if( QTags.instances[ id ] != null ) {
                delete QTags.instances[ id ];
            }
            $container.find('.quicktags-toolbar').remove();
            quicktags(tinyMCEPreInit.qtInit[id]);
            $textarea.on('input propertychange', function () {
                $container.find('input[type="hidden"]').val($textarea.val());
            });
            var mceSettings = sowGetWidgetFieldVariable(formClass, name, 'mceSettings');
            var fieldName = /[a-zA-Z0-9\-]+(?:\[[a-zA-Z0-9]+\])?\[(.*)\]/.exec( name )[1];
            var idPattern = new RegExp( 'widget-.+-[_a-zA-Z0-9]+-' + fieldName.replace( /\]\[/g, '-' ) + '[-\d]*' );
            for( var initId in tinyMCEPreInit.mceInit) {
                if(initId.match(idPattern)) {
                    mceSettings = $.extend({}, tinyMCEPreInit.mceInit[initId], mceSettings);
                }
            }
            var setupEditor = function(editor) {
                editor.on('change', function() {
                        tinymce.get(id).save();
                        $textarea.trigger('input');
                    }
                );
            };
            mceSettings = $.extend({}, mceSettings, {selector:'#'+id, setup:setupEditor});
            tinyMCEPreInit.mceInit[id] = mceSettings;
            var wrapDiv = $container.find('div#wp-' + id + '-wrap');
            if(tinymce.get(id) != null) {
                tinymce.get(id).remove();
            }
            if(wrapDiv.hasClass('tmce-active')) {

                tinymce.init(tinyMCEPreInit.mceInit[id]);
            }
        });
        QTags._buttonsInit();
    };

    $(document).on( 'sowsetupform', '.siteorigin-widget-form-main', function() {
        var widgetForm = this;
        if(!$(widgetForm).data('setup-complete')) {
            var initializeTinyMCEFields = function() {
                setup(widgetForm);
            };
            $(widgetForm).find('.siteorigin-widget-field-repeater-items').sortable( "option", "stop", initializeTinyMCEFields);
            $(widgetForm).data('setup-complete', true);
            initializeTinyMCEFields();
        }
    });

})( jQuery );