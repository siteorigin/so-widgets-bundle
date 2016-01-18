/* global tinyMCEPreInit, quicktags, QTags, tinymce */

(function( $ ) {
    var setup = function(widgetForm) {
        if(typeof tinyMCEPreInit !== 'undefined') {
            //BS TinyMCE widget sometimes adds 'undefined' as an id when opening PB, which causes a JS error when using repeaters.
            if (tinyMCEPreInit.mceInit.hasOwnProperty('undefined')) {
                delete tinyMCEPreInit.mceInit['undefined'];
            }
            if (tinyMCEPreInit.qtInit.hasOwnProperty('undefined')) {
                delete tinyMCEPreInit.qtInit['undefined'];
            }
            if (QTags.instances.hasOwnProperty('undefined')) {
                delete QTags.instances['undefined'];
            }
            $(widgetForm).find('> .siteorigin-widget-field-type-tinymce > .siteorigin-widget-tinymce-container').each(function (index, element) {
                var $container = $(element);
                var $textarea = $container.find('textarea');
                var id = $textarea.attr('id');
                if( typeof tinymce != 'undefined') {
                    if (id.indexOf('__i__') > -1) return;
                    var mceSettings = $container.data('mceSettings');
                    var widgetIdBase = $container.data('widgetIdBase');
                    var name = $textarea.attr('name').replace(/\[\d+\]/g, '');
                    var fieldName = /[a-zA-Z0-9\-]+(?:\[[a-zA-Z0-9]+\])?\[(.*)\]/.exec(name)[1];
                    var idPattern = new RegExp('widget-' + widgetIdBase + '-.*-' + fieldName.replace(/\]\[/g, '-') + '[-\d]*');
                    for (var initId in tinyMCEPreInit.mceInit) {
                        if (initId.match(idPattern)) {
                            mceSettings = $.extend({}, tinyMCEPreInit.mceInit[initId], mceSettings);
                        }
                    }
                    var content;
                    var curEd = tinymce.get(id);
                    if (curEd != null) {
                        content = curEd.getContent();
                        curEd.remove();
                    }
                    var setupEditor = function (editor) {
                        editor.on('change',
                            function () {
                                tinymce.get(id).save();
                                $textarea.trigger('change');
                                $textarea.val(window.switchEditors.pre_wpautop(editor.getContent()));
                            }
                        );
                        editor.on('init',
                            function () {
                                if (content) {
                                    editor.setContent(content);
                                }
                            }
                        );
                        $textarea.on('keyup',
                            function () {
                                editor.setContent(window.switchEditors.wpautop($textarea.val()));
                            }
                        );
                    };
                    mceSettings = $.extend({}, mceSettings, {selector: '#' + id, setup: setupEditor});
                    tinyMCEPreInit.mceInit[id] = mceSettings;
                    var wrapDiv = $container.find('div#wp-' + id + '-wrap');
                    if (wrapDiv.hasClass('tmce-active')) {
                        // Add a small timeout to make sure everything is ready - mainly for customizer and widgets interface
                        if ($('#' + id).is(':visible')) {
                            tinymce.init(tinyMCEPreInit.mceInit[id]);
                        }
                        else {
                            var intervalId = setInterval(function () {
                                if ($('#' + id).is(':visible')) {
                                    tinymce.init(tinyMCEPreInit.mceInit[id]);
                                    clearInterval(intervalId);
                                }
                            }, 500);
                        }
                    }
                }
                var qtSettings = $container.data('qtSettings');
                qtSettings = $.extend({}, tinyMCEPreInit.qtInit['siteorigin-widget-input-tinymce-field'], qtSettings, {id: id});
                tinyMCEPreInit.qtInit[id] = qtSettings;
                $container.find('.quicktags-toolbar').remove();
                quicktags(tinyMCEPreInit.qtInit[id]);

                $(this).on( 'click', function(event) {

                    var $target = $(event.target);
                    if ( $target.hasClass( 'wp-switch-editor' ) ) {
                        mode = $target.hasClass( 'switch-tmce' ) ? 'tmce' : 'html';
                        $(this).find('+ .siteorigin-widget-tinymce-selected-editor').val(mode);
                    }
                });
            });
            QTags._buttonsInit();
        }
        else {
            setTimeout(function(){
                setup(widgetForm);
            }, 500);
        }
    };

    $(document).on( 'sowsetupform', function(e) {
        var $f = $(e.target);

        if($f.is('.siteorigin-widget-field-repeater-item-form')){
            if($f.is(':visible')) {
                setup( $f );
            }
            else {
                $f.on('slideToggleOpenComplete', function onSlideToggleComplete() {
                    if( $f.is(':visible') ){
                        setup($f);
                        $f.off('slideToggleOpenComplete');
                    }
                })
            }
        }
        else {
            setup($f);
        }
    });
    $(document).on('sortstop', function (event, ui) {
        if(ui.item.is('.siteorigin-widget-field-repeater-item')) {
            ui.item.find('> .siteorigin-widget-field-repeater-item-form').each(function(){
                setup( $(this) );
            });
        }
        else {
            setup(ui.item.find('.siteorigin-widget-form'));
        }
    });

})( jQuery );
