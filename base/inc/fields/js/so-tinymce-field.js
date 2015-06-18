/* global sowGetWidgetFieldVariable, tinyMCEPreInit, quicktags, QTags, tinymce */

(function( $ ) {
    var setup = function(widgetForm) {
        $(widgetForm).find('> .siteorigin-widget-field-type-tinymce > .siteorigin-widget-tinymce-container').each( function (index, element) {
            var $container = $(element);
            var $textarea = $container.find('textarea');
            var id = $textarea.attr('id');
            if( id.indexOf( '__i__' ) > -1 ) return;
            if( ! QTags.instances[ id ]) {
                var qtSettings = $container.data('qtSettings');
                qtSettings = $.extend({}, tinyMCEPreInit.qtInit['siteorigin-widget-input-tinymce-field'], qtSettings, {id:id});
                tinyMCEPreInit.qtInit[id] = qtSettings;
                $container.find('.quicktags-toolbar').remove();
                quicktags(tinyMCEPreInit.qtInit[id]);
            }
            var mceSettings = $container.data('mceSettings');
            var widgetIdBase = $container.data('widgetIdBase');
            var name = $textarea.attr('name').replace(/\[\d\]/g, '');
            var fieldName = /[a-zA-Z0-9\-]+(?:\[[a-zA-Z0-9]+\])?\[(.*)\]/.exec( name )[1];
            var idPattern = new RegExp( 'widget-' + widgetIdBase + '-.*-' + fieldName.replace( /\]\[/g, '-' ) + '[-\d]*' );
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
                editor.on('change',
                    function() {
                        tinymce.get(id).save();
                        $textarea.trigger('change');
                    }
                );
                editor.on('init',
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
            if( wrapDiv.hasClass('tmce-active') ) {
                // Add a small timeout to make sure everything is ready - mainly for customizer and widgets interface
                var intervalId = setInterval(function(){
                    if($('#' + id + ':visible').length) {
                        tinymce.init(tinyMCEPreInit.mceInit[id]);
                        clearInterval(intervalId);
                    }
                }, 300);
            }
        });
        QTags._buttonsInit();
    };

    $(document).on( 'sowsetupform', function(e) {
        var $f = $(e.target);

        var $repeaters = $f.find('> .siteorigin-widget-field-type-repeater > .siteorigin-widget-field-repeater > .siteorigin-widget-field-repeater-items');
        if( $repeaters.length) {
            var reinitRepeaterItem = function(e, ui) {
                ui.item.find('> .siteorigin-widget-field-repeater-item-form').each(function(){
                    setup( $(this) );
                });
            };
            $repeaters.sortable( "option", "stop", reinitRepeaterItem);
        }

        setup( $(e.target) );
    });

})( jQuery );