/* global sowGetWidgetFieldVariable, tinyMCEPreInit, quicktags, QTags, tinymce */

(function( $ ) {
    var setup = function(widgetForm) {

        $(widgetForm).find('> .siteorigin-widget-field-type-tinymce > .siteorigin-widget-tinymce-container').each( function (index, element) {
            var $container = $(element);
            var formClass = $container.closest('.siteorigin-widget-form-main').data('class');
            var $textarea = $container.find('textarea');
            var id = $textarea.attr('id');
            if( id.indexOf( '__i__' ) > -1 ) return;
            var name = $textarea.attr('name').replace(/\[\d\]/g, '');
            var qtSettings = $container.data('qtSettings');
            qtSettings = $.extend({}, tinyMCEPreInit.qtInit['siteorigin-widget-input-tinymce-field'], qtSettings, {id:id});
            tinyMCEPreInit.qtInit[id] = qtSettings;
            if( QTags.instances[ id ] != null ) {
                delete QTags.instances[ id ];
            }
            $container.find('.quicktags-toolbar').remove();
            quicktags(tinyMCEPreInit.qtInit[id]);
            var mceSettings = $container.data('mceSettings');
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
                setTimeout(function(){
                    tinymce.init(tinyMCEPreInit.mceInit[id]);
                }, 250);
            }
        });
        QTags._buttonsInit();
    };

    $(document).on( 'sowsetupform', function(e) {
        var $f = $(e.target);

        var $repeaters = $f.find('> .siteorigin-widget-field-type-repeater > .siteorigin-widget-field-repeater > .siteorigin-widget-field-repeater-items');
        if( $repeaters.length) {
            var reinitRepeaterItems = function(e) {
                var $$ = $(e.target);
                $$.find('> .siteorigin-widget-field-repeater-item > .siteorigin-widget-field-repeater-item-form').each(function(){
                    setup( $(this) );
                });
            };
            $repeaters.on('updateFieldPositions', reinitRepeaterItems);
            $repeaters.sortable( "option", "stop", reinitRepeaterItems);
        }

        setup( $(e.target) );
    });

})( jQuery );