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
            var mceSettings = sowGetWidgetFieldVariable(formClass, name, 'mceSettings');
            var fieldName = /[a-zA-Z0-9\-]+(?:\[[a-zA-Z0-9]+\])?\[(.*)\]/.exec( name )[1];
            var idPattern = new RegExp( 'widget-.+-[_a-zA-Z0-9]+-' + fieldName.replace( /\]\[/g, '-' ) + '[-\d]*' );
            for( var initId in tinyMCEPreInit.mceInit) {
                if(initId.match(idPattern)) {
                    mceSettings = $.extend({}, tinyMCEPreInit.mceInit[initId], mceSettings);
                }
            }
            var content;
            var curEd = tinymce.get(id);
            if(curEd != null) {
                content = curEd.getContent();
                curEd.remove();
            }
            var setupEditor = function(editor) {
                editor.onChange.add(
                    function() {
                        tinymce.get(id).save();
                    }
                );
                editor.onInit.add(
                    function () {
                        if(content) {
                            editor.setContent(content);
                        }
                    }
                );
            };
            mceSettings = $.extend({}, mceSettings, {selector:'#'+id, setup:setupEditor});
            tinyMCEPreInit.mceInit[id] = mceSettings;
            var wrapDiv = $container.find('div#wp-' + id + '-wrap');
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
            var $repeaters = $(widgetForm).find('.siteorigin-widget-field-repeater-items');
            if( $repeaters.length) {
                $repeaters.on('updateFieldPositions', initializeTinyMCEFields);
                $repeaters.sortable( "option", "stop", initializeTinyMCEFields);
            }
            else {
                initializeTinyMCEFields();
            }
            $(widgetForm).data('setup-complete', true);
        }
    });

})( jQuery );