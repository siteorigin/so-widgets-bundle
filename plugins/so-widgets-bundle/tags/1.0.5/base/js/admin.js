(function($){

    $.fn.sowSetupForm = function() {
        return $(this).each( function(i, el){
            var $el = $(el);

            // Skip this if we've already set up the form
            if( $el.is('.siteorigin-widget-form-main') ) {
                if( $el.data('sow-form-setup') == true ) return true;
                if( $('body').hasClass('widgets-php') && !$el.is(':visible') ) return true;

                // Lets set up the preview
                $el.sowSetupPreview();
            }

            // Find any field or sub widget fields.
            var $fields = $el.find('> .siteorigin-widget-field');

            // Process any sub sections
            $fields.find('> .siteorigin-widget-section').sowSetupForm();

            // Store the field names
            $fields.find('.siteorigin-widget-input').each(function(i, input){
                if( $(input).data( 'original-name') == null ) {
                    $(input).data( 'original-name', $(input).attr('name') );
                }
            });

            // Setup all the repeaters
            $fields.find('> .siteorigin-widget-field-repeater').sowSetupRepeater();

            // For any repeater items currently in existence
            $el.find('.siteorigin-widget-field-repeater-item').sowSetupRepeaterActions();

            // Set up any color fields
            $fields.find('> .siteorigin-widget-input-color').wpColorPicker()
                .closest('.siteorigin-widget-field').find('a').click(function(){
                    if(typeof $.fn.dialog != 'undefined') {
                        $(this).closest('.panel-dialog').dialog("option", "position", "center");
                    }
                });

            // handle the media field. Check that this is working
            $fields.find('> .media-field-wrapper').each(function(){
                var $media = $(this);
                // Handle the media uploader
                $media.find('a.media-upload-button' ).click(function(event){
                    if( typeof wp.media == 'undefined' ) return;

                    var $$ = $(this);
                    var $c = $(this ).closest('.siteorigin-widget-field');
                    var frame = $(this ).data('frame');

                    // If the media frame already exists, reopen it.
                    if ( frame ) {
                        frame.open();
                        return false;
                    }

                    // Create the media frame.
                    frame = wp.media( {
                        // Set the title of the modal.
                        title: $$.data('choose'),

                        // Tell the modal to show only images.
                        library: {
                            type: $$.data('library').split(',').map(function(v){ return v.trim() })
                        },

                        // Customize the submit button.
                        button: {
                            // Set the text of the button.
                            text: $$.data('update'),
                            // Tell the button not to close the modal, since we're
                            // going to refresh the page when the image is selected.
                            close: false
                        }
                    } );

                    // Store the frame
                    $$.data('frame', frame);

                    // When an image is selected, run a callback.
                    frame.on( 'select', function() {
                        // Grab the selected attachment.
                        var attachment = frame.state().get('selection').first().attributes;

                        $c.find('.current .title' ).html(attachment.title);
                        $c.find('input[type=hidden]' ).val(attachment.id);

                        if(typeof attachment.sizes != 'undefined'){
                            if(typeof attachment.sizes.thumbnail != 'undefined')
                                $c.find('.current .thumbnail' ).attr('src', attachment.sizes.thumbnail.url).fadeIn();
                            else
                                $c.find('.current .thumbnail' ).attr('src', attachment.sizes.full.url).fadeIn();
                        }
                        else{
                            $c.find('.current .thumbnail' ).attr('src', attachment.icon).fadeIn();
                        }

                        frame.close();
                    } );

                    // Finally, open the modal.
                    frame.open();

                    return false;
                });

                // Show/hide the remove button when hovering over the media select button.
                $media
                    .mouseenter(function(){
                        if($(this ).closest('.siteorigin-widget-field').find('input[type=hidden]' ).val() != '') $(this ).find('.media-remove-button').fadeIn('fast');
                    })
                    .mouseleave(function(){
                        $(this ).find('.media-remove-button').fadeOut('fast');
                    })

                $media.find('.current' )
                    .mouseenter(function(){
                        var t = $(this ).find('.title' );
                        if( t.html() != ''){
                            t.fadeIn('fast');
                        }
                    })
                    .mouseleave(function(){
                        $(this ).find('.title' ).clearQueue().fadeOut('fast');
                    })

                $media.find('a.media-remove-button' )
                    .click(function(){
                        var $$ = $(this ).closest('.siteorigin-widget-field');

                        $$.find('.current .title' ).html('');
                        $$.find('input[type=hidden]' ).val('');
                        $$.find('.current .thumbnail' ).fadeOut('fast');
                        $(this ).fadeOut('fast');
                    });

            })

            // Handle toggling of the sub widget form
            $fields.filter('.siteorigin-widget-field-type-widget, .siteorigin-widget-field-type-section').find('> label').click(function(){
                var $$ = $(this);
                $(this).toggleClass( 'siteorigin-widget-section-visible' );
                $(this).siblings('.siteorigin-widget-section').slideToggle(function(){

                    // Center the PB dialog
                    if(typeof $.fn.dialog != 'undefined') {
                        $(this).closest('.panel-dialog').dialog("option", "position", "center");
                    }
                });
            });

            // Handle the icon selection
            var iconWidgetCache = {};
            $fields.find('> .siteorigin-widget-icon-selector').each(function(){
                var $is = $(this);
                var $v = $is.find('.siteorigin-widget-icon-icon');

                var rerenderIcons = function(){
                    var family = $is.find('select.siteorigin-widget-icon-family').val();
                    var container = $is.find('.siteorigin-widget-icon-icons');

                    if(typeof iconWidgetCache[family] == 'undefined') return;

                    container.empty();

                    if( $('#'+'siteorigin-widget-font-'+family).length == 0) {

                        $("<link rel='stylesheet' type='text/css'>")
                            .attr('id', 'siteorigin-widget-font-' + family)
                            .attr('href', iconWidgetCache[family]['style_uri'])
                            .appendTo('head');
                    }


                    for ( var i in iconWidgetCache[family]['icons'] ) {

                        var icon = $('<div data-sow-icon="' + iconWidgetCache[family]['icons'][i] +  '"/>')
                            .attr('data-value', family + '-' + i)
                            .addClass( 'sow-icon-' + family )
                            .addClass( 'siteorigin-widget-icon-icons-icon' )
                            .click(function(){
                                var $$ = $(this);
                                if( $$.hasClass('siteorigin-widget-active') ) {
                                    $$.removeClass('siteorigin-widget-active');
                                    $v.val( '' );
                                }
                                else {
                                    container.find('.siteorigin-widget-icon-icons-icon').removeClass('siteorigin-widget-active');
                                    $$.addClass('siteorigin-widget-active');
                                    $v.val( $(this).data('value') );
                                }
                            });

                        if( $v.val() == family + '-' + i ) icon.addClass('siteorigin-widget-active');

                        container.append(icon);
                    }

                    // Move a selcted item to the first position
                    container.prepend( container.find('.siteorigin-widget-active') );
                }

                // Create the function for changing the icon family and call it once
                var changeIconFamily = function(){
                    // Fetch the family icons from the server
                    var family = $is.find('select.siteorigin-widget-icon-family').val();
                    if(typeof family == 'undefined' || family == '') return;

                    if(typeof iconWidgetCache[family] == 'undefined') {
                        $.getJSON(
                            ajaxurl,
                            { 'action' : 'siteorigin_widgets_get_icons', 'family' :  $is.find('select.siteorigin-widget-icon-family').val() },
                            function(data) {
                                iconWidgetCache[family] = data;
                                rerenderIcons()
                            }
                        );
                    }
                    else {
                        rerenderIcons();
                    }
                }
                changeIconFamily();

                $is.find('select.siteorigin-widget-icon-family').change(function(){
                    $is.find('.siteorigin-widget-icon-icons').empty();
                    changeIconFamily();
                });

            });

            // Give plugins a chance to influence the form
            $el.trigger('sowsetupform').data('sow-form-setup', true);
            $el.find('.siteorigin-widget-field-repeater-item').trigger('updateFieldPositions');

            /********
             * The end of the form setup.
             *******/
        } );
    };

    $.fn.sowSetupPreview = function(){
        var $el = $(this);
        var previewButton = $el.siblings('.siteorigin-widget-preview');

        previewButton.find('> a').click(function(e){
            e.preventDefault();
            var data = {};

            $el.find( '*[name]' ).each( function () {
                var $$ = $(this);
                var name = /[a-zA-Z\-]+\[[0-9]+\]\[(.*)\]/.exec( $$.attr('name') );

                name = name[1];
                parts = name.split('][');

                // Make sure we either have numbers or strings
                parts = parts.map(function(e){
                    if( !isNaN(parseFloat(e)) && isFinite(e) ) return parseInt(e);
                    else return e;
                });

                var sub = data;
                for(var i = 0; i < parts.length; i++) {
                    if(i == parts.length - 1) {
                        // This is the end, so we need to store the actual field value here
                        if( $$.attr('type') == 'checkbox' ){
                            if ( $$.is(':checked') ) sub[ parts[i] ] = $$.val() != '' ? $$.val() : true;
                        }
                        else sub[ parts[i] ] = $$.val();
                    }
                    else {
                        if(typeof sub[parts[i]] == 'undefined') {
                            sub[parts[i]] = {};
                        }
                        // Go deeper into the data and continue
                        sub = sub[parts[i]];
                    }
                }
            } );

            // Create the modal
            var overlay = $('<div class="siteorigin-widgets-preview-modal-overlay"></div>').appendTo('body');
            var modal = $('<div class="siteorigin-widgets-preview-modal"></div>').appendTo('body');
            var close = $('<div class="siteorigin-widgets-preview-close dashicons dashicons-no"></div>').appendTo(modal);
            var iframe = $('<iframe class="siteorigin-widgets-preview-iframe" scrolling="no"></iframe>').appendTo(modal);

            $.post(
                ajaxurl,
                {
                    'action' : 'so_widgets_preview',
                    'data' : JSON.stringify(data),
                    'class' : $el.data('class')
                },
                function(html) {
                    iframe.contents().find('body').html( html );
                }
            );

            close.add(overlay).click(function(){
                overlay.remove();
                modal.remove();
            });
        });
    }

    $.fn.sowSetupRepeater = function(){

        return $(this).each( function(i, el){
            var $el = $(el);
            var $items = $el.find('.siteorigin-widget-field-repeater-items');
            var name = $el.data('repeater-name');

            $items.bind('updateFieldPositions', function(){
                var $$ = $(this);

                // Set the position for the repeater items
                $$.find('> .siteorigin-widget-field-repeater-item').each(function(i, el){
                    $(el).find('.siteorigin-widget-input').each(function(j, input){
                        var pos = $(input).data('repeater-positions');
                        if( typeof pos == 'undefined' ) {
                            pos = {};
                        }

                        pos[name] = i;
                        $(input).data('repeater-positions', pos);
                    });
                });

                // Update the field names for all the input items
                $$.find('.siteorigin-widget-input').each(function(i, input){
                    var pos = $(input).data('repeater-positions');
                    var $in = $(input);

                    if(typeof pos != 'undefined') {
                        var newName = $in.data('original-name');

                        if(typeof newName == 'undefined') {
                            $in.data( 'original-name', $in.attr('name') );
                            newName = $in.attr('name');
                        }

                        for(var k in pos) {
                            newName = newName.replace('#' + k + '#', pos[k] );
                        }
                        $(input).attr('name', newName);
                    }
                });

            });

            $items.sortable( {
                handle : '.siteorigin-widget-field-repeater-item-top',
                items : '> .siteorigin-widget-field-repeater-item',
                update: function(){
                    $items.trigger('updateFieldPositions');
                }
            });
            $items.trigger('updateFieldPositions');

            $el.find('> .siteorigin-widget-field-repeater-add').disableSelection().click( function(e){
                e.preventDefault();
                $el.closest('.siteorigin-widget-field-repeater')
                    .sowAddRepeaterItem()
                    .find('> .siteorigin-widget-field-repeater-items').slideDown('fast');

                // Center the PB dialog
                if(typeof $.fn.dialog != 'undefined') {
                    $(this).closest('.panel-dialog').dialog("option", "position", "center");
                }
            } );

            $el.find('> .siteorigin-widget-field-repeater-top > .siteorigin-widget-field-repeater-expend').click( function(e){
                e.preventDefault();
                $el.closest('.siteorigin-widget-field-repeater').find('> .siteorigin-widget-field-repeater-items').slideToggle('fast');
            } );
        } );
    };

    $.fn.sowAddRepeaterItem = function(){
        return $(this).each( function(i, el){

            var $el = $(el);
            var theClass = $el.closest('.siteorigin-widget-form').data('class');

            var formClass = $el.closest('.siteorigin-widget-form').data('class');

            var item = $('<div class="siteorigin-widget-field-repeater-item" />')
                .append(
                    $('<div class="siteorigin-widget-field-repeater-item-top" />')
                        .append(
                            $('<div class="siteorigin-widget-field-expand" />')

                        )
                        .append(
                            $('<div class="siteorigin-widget-field-remove" />')

                        )
                        .append( $('<h4 />').html( $el.data('item-name') ) )
                )
                .append(
                    $('<div class="siteorigin-widget-field-repeater-item-form" />')
                        .html( window.sow_repeater_html[formClass][$el.data('repeater-name')] )
                )
                .sowSetupRepeaterActions();

            // Add the item and refresh
            $el.find('> .siteorigin-widget-field-repeater-items').append(item).sortable( "refresh").trigger('updateFieldPositions');
            item.hide().slideDown('fast');

        } );
    };

    $.fn.sowSetupRepeaterActions = function(){
        return $(this).each( function(i, el){
            var $el = $(el);

            if(typeof $el.data('sowrepeater-actions-setup') == 'undefined') {
                var top = $el.find('> .siteorigin-widget-field-repeater-item-top');

                top.find('.siteorigin-widget-field-expand')
                    .click(function(e){
                        e.preventDefault();
                        $(this).closest('.siteorigin-widget-field-repeater-item').find('.siteorigin-widget-field-repeater-item-form').eq(0).slideToggle('fast', function(){
                            if(typeof $.fn.dialog != 'undefined') {
                                $(this).closest('.panel-dialog').dialog("option", "position", "center");
                            }
                        });
                    });

                top.find('.siteorigin-widget-field-remove')
                    .click(function(e){
                        e.preventDefault();
                        if(confirm(soWidgets.sure)) {
                            var $s = $(this).closest('.siteorigin-widget-field-repeater-items');
                            $(this).closest('.siteorigin-widget-field-repeater-item').slideUp('fast', function(){
                                $(this).remove();
                                $s.sortable( "refresh" ).trigger('updateFieldPositions');
                            });
                        }
                    });

                $el.find('> .siteorigin-widget-field-repeater-item-form').sowSetupForm();

                $el.data('sowrepeater-actions-setup', true);
            }
        });
    }

    // When we click on a widget top
    $('.widgets-holder-wrap').on('click', '.widget:has(.siteorigin-widget-form-main) .widget-top', function(){
        var $$ = $(this).closest('.widget').find('.siteorigin-widget-form-main');
        setTimeout( function(){ $$.sowSetupForm(); }, 200);
    });

    // When we open a Page Builder widget dialog
    $(document).on('dialogopen', function(e){
        $(e.target).find('.siteorigin-widget-form-main').sowSetupForm();
    });

})(jQuery);