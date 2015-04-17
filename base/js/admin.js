
/* globals wp, jQuery, soWidgets, confirm */

var sowEmitters = {};

(function($){

    $.fn.sowSetupForm = function() {
        return $(this).each( function(i, el){
            var $el = $(el), $mainForm;

            // Skip this if the widget has any fields with an __i__
            var $inputs = $el.find('input');
            if( $inputs.length && $inputs.attr('name').indexOf('__i__') !== -1 ) {
                return this;
            }

            // Skip this if we've already set up the form
            if( $el.is('.siteorigin-widget-form-main') ) {
                
                if( $('body').hasClass('wp-customizer') &&  $el.closest('.panel-dialog').length === 0) {
                    // If in the customizer, we only want to set up admin form for a specific widget when it has been added.
                    if( !$el.closest('.widget').data('sow-widget-added-form-setup') ) {
                        // Setup new widgets when they're added in the customizer interface
                        $(document).on('widget-added', function (e, widget) {
                            widget.data('sow-widget-added-form-setup', true);
                            widget.find('.siteorigin-widget-form').sowSetupForm();
                            widget.removeData('sow-widget-added-form-setup');
                        });
                        return true;
                    }
                }
                if( $el.data('sow-form-setup') === true ) {
                    return true;
                }
                // If we're in the main widgets interface and the form isn't visible and it isn't contained in a
                // panels dialog (when using the Layout Builder widget), don't worry about setting it up.
                if( $('body').hasClass('widgets-php') && !$el.is(':visible') && $el.closest('.panel-dialog').length === 0 ) {
                    return true;
                }

                // Listen for a state change event on the
                $el.on('sowstatechange', function(e, incomingGroup, incomingState){

                    $el.find('[data-state-handler]').each( function(){
                        var $$ = $(this);
                        var handler = $$.data('state-handler');

                        // We need to figure out what the incoming state is
                        var handlerStateParts, thisState, thisHandler, $$f;

                        if( Object.keys( handler ).length > 0 ) {
                            for( var state in handler ) {
                                handlerStateParts = state.match(/^([a-z]+)(\[([a-z]+)\])?(\[\])?$/);
                                thisState = {
                                    'group' : 'default',
                                    'name' : '',
                                    'multi' : false
                                };

                                if( handlerStateParts[2] !== undefined ) {
                                    thisState.group = handlerStateParts[1];
                                    thisState.name = handlerStateParts[3];
                                }
                                else {
                                    thisState.name = handlerStateParts[0];
                                }

                                // Check that the current state
                                if( thisState.group === incomingGroup && thisState.name === incomingState ) {
                                    thisHandler = handler[state];

                                    // Now we can handle the the handler

                                    if (!thisState.multi) {
                                        thisHandler = [thisHandler];
                                    }

                                    for (var i = 0; i < thisHandler.length; i++) {
                                        // Choose the item we'll be acting on here
                                        if (typeof thisHandler[i][1] !== 'undefined' && thisHandler[i][1] !== '') {
                                            $$f = $$.find(thisHandler[i][1]);
                                        }
                                        else {
                                            $$f = $$;
                                        }

                                        // Call the function
                                        $$f[thisHandler[i][0]].apply($$f, typeof thisHandler[i][2] !== 'undefined' ? thisHandler[i][1] : []);

                                    }
                                }
                            }
                        }

                    } );
                } );

                // Lets set up the preview
                $el.sowSetupPreview();
                $mainForm = $el;
            }
            else {
                $mainForm = $el.closest('.siteorigin-widget-form-main');
            }

            // Find any field or sub widget fields.
            var $fields = $el.find('> .siteorigin-widget-field');

            // Process any sub sections
            $fields.find('> .siteorigin-widget-section').sowSetupForm();

            // Store the field names
            $fields.find('.siteorigin-widget-input').each(function(i, input){
                if( $(input).data( 'original-name') === null ) {
                    $(input).data( 'original-name', $(input).attr('name') );
                }
            });

            // Setup all the repeaters
            $fields.find('> .siteorigin-widget-field-repeater').sowSetupRepeater();

            // For any repeater items currently in existence
            $el.find('.siteorigin-widget-field-repeater-item').sowSetupRepeaterItems();

            // Set up any color fields
            $fields.find('> .siteorigin-widget-input-color').wpColorPicker()
                .closest('.siteorigin-widget-field').find('a').click(function(){
                    if(typeof $.fn.dialog !== 'undefined') {
                        $(this).closest('.panel-dialog').dialog("option", "position", "center");
                    }
                });

            // handle the media field. Check that this is working
            $fields.find('> .media-field-wrapper').each(function(){
                var $media = $(this);
                var $field = $media.closest('.siteorigin-widget-field');

                // Handle the media uploader
                $media.find('a.media-upload-button' ).click(function(e){
                    if( typeof wp.media === 'undefined' ) {
                        return;
                    }

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
                        var $inputField = $c.find( 'input[type=hidden]' );
                        $inputField.val(attachment.id);
                        $inputField.trigger('change');

                        if(typeof attachment.sizes !== 'undefined'){
                            if(typeof attachment.sizes.thumbnail !== 'undefined'){
                                $c.find('.current .thumbnail' ).attr('src', attachment.sizes.thumbnail.url).fadeIn();
                            }
                            else {
                                $c.find('.current .thumbnail' ).attr('src', attachment.sizes.full.url).fadeIn();
                            }
                        }
                        else{
                            $c.find('.current .thumbnail' ).attr('src', attachment.icon).fadeIn();
                        }

                        $field.find('.media-remove-button').removeClass('remove-hide');

                        frame.close();
                    } );

                    // Finally, open the modal.
                    frame.open();

                    return false;
                });

                $media.find('.current' )
                    .mouseenter(function(){
                        var t = $(this ).find('.title' );
                        if( t.html() !== ''){
                            t.fadeIn('fast');
                        }
                    })
                    .mouseleave(function(){
                        $(this ).find('.title' ).clearQueue().fadeOut('fast');
                    })

                $field.find('a.media-remove-button' )
                    .click(function(e){
                        e.preventDefault();
                        $field.find('.current .title' ).html('');
                        $field.find('input[type=hidden]' ).val('');
                        $field.find('.current .thumbnail' ).fadeOut('fast');
                        $(this).addClass('remove-hide');
                    });

            });

            // Handle toggling of the sub widget form
            $fields.filter('.siteorigin-widget-field-type-widget, .siteorigin-widget-field-type-section').find('> label').click(function(){
                var $$ = $(this);
                $(this).toggleClass( 'siteorigin-widget-section-visible' );
                $(this).siblings('.siteorigin-widget-section').slideToggle(function(){
                    // Center the PB dialog
                    if(typeof $.fn.dialog !== 'undefined') {
                        $(this).closest('.panel-dialog').dialog( "option", "position", "center" );
                    }

                    $(window).resize();
                });
            });

            ///////////////////////////////////////
            // Handle the icon selection

            var iconWidgetCache = {};
            $fields.find('> .siteorigin-widget-icon-selector').each(function(){
                var $is = $(this);
                var $v = $is.find('.siteorigin-widget-icon-icon');

                var rerenderIcons = function(){
                    var family = $is.find('select.siteorigin-widget-icon-family').val();
                    var container = $is.find('.siteorigin-widget-icon-icons');

                    if(typeof iconWidgetCache[family] === 'undefined') {
                        return;
                    }

                    container.empty();

                    if( $('#'+'siteorigin-widget-font-'+family).length === 0) {

                        $("<link rel='stylesheet' type='text/css'>")
                            .attr('id', 'siteorigin-widget-font-' + family)
                            .attr('href', iconWidgetCache[family].style_uri)
                            .appendTo('head');
                    }


                    for ( var i in iconWidgetCache[family].icons ) {

                        var icon = $('<div data-sow-icon="' + iconWidgetCache[family].icons[i] +  '"/>')
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

                        if( $v.val() === family + '-' + i ) {
                            icon.addClass('siteorigin-widget-active');
                        }

                        container.append(icon);
                    }

                    // Move a selcted item to the first position
                    container.prepend( container.find('.siteorigin-widget-active') );
                };

                // Create the function for changing the icon family and call it once
                var changeIconFamily = function(){
                    // Fetch the family icons from the server
                    var family = $is.find('select.siteorigin-widget-icon-family').val();
                    if(typeof family === 'undefined' || family === '') {
                        return;
                    }

                    if(typeof iconWidgetCache[family] === 'undefined') {
                        $.getJSON(
                            soWidgets.ajaxurl,
                            { 'action' : 'siteorigin_widgets_get_icons', 'family' :  $is.find('select.siteorigin-widget-icon-family').val() },
                            function(data) {
                                iconWidgetCache[family] = data;
                                rerenderIcons();
                            }
                        );
                    }
                    else {
                        rerenderIcons();
                    }
                };

                changeIconFamily();

                $is.find('select.siteorigin-widget-icon-family').change(function(){
                    $is.find('.siteorigin-widget-icon-icons').empty();
                    changeIconFamily();
                });

            });

            ///////////////////////////////////////
            // Handle the slider sections

            $fields.filter('.siteorigin-widget-field-type-slider').each(function(){
                var $$ = $(this);
                var $input = $$.find('input[type="number"]');
                var $c = $$.find('.siteorigin-widget-value-slider');

                $c.slider({
                    max: parseInt( $input.attr('max') ),
                    min: parseInt( $input.attr('min') ),
                    value: parseInt( $input.val() ),
                    step: 1,
                    slide: function( event, ui ) {
                        $input.val( parseInt(ui.value) );
                        $$.find('.siteorigin-widget-slider-value').html( ui.value );
                    }
                });
            });

            ///////////////////////////////////////
            // Setup the URL fields

            $fields.filter('.siteorigin-widget-field-type-link').each( function(){
                var $$ = $(this);

                // Function that refreshes the list of
                var request = null;
                var refreshList = function(){
                    if( request !== null ) {
                        request.abort();
                    }

                    var query = $$.find('.content-text-search').val();

                    var $ul = $$.find('ul.posts').empty().addClass('loading');
                    $.get(
                        soWidgets.ajaxurl,
                        { action: 'so_widgets_search_posts', query: query },
                        function(data){
                            for( var i = 0; i < data.length; i++ ) {
                                // Add all the post items
                                $ul.append(
                                    $('<li>')
                                        .addClass('post')
                                        .html( data[i].post_title + '<span>(' + data[i].post_type + ')</span>' )
                                        .data( data[i] )
                                );
                            }
                            $ul.removeClass('loading');
                        }
                    );
                };

                // Toggle display of the existing content
                $$.find('.select-content-button, .button-close').click( function(e) {
                    e.preventDefault();
                    
                    $(this).blur();
                    var $s = $$.find('.existing-content-selector');
                    $s.toggle();

                    if( $s.is(':visible') && $s.find('ul.posts li').length === 0 ) {
                        refreshList();
                    }

                } );

                // Clicking on one of the url items
                $$.on( 'click', '.posts li', function(e){
                    e.preventDefault();
                    var $li = $(this);
                    $$.find('input.siteorigin-widget-input').val( 'post: ' + $li.data('ID') );

                    $$.find('.existing-content-selector').toggle();
                } );

                var interval = null;
                $$.find('.content-text-search').keyup( function(){
                    if( interval !== null ) {
                        clearTimeout(interval);
                    }

                    interval = setTimeout(function(){
                        refreshList();
                    }, 500);
                } );
            } );

            ///////////////////////////////////////
            // Now lets handle the state emitters

            $fields.filter('[data-state-emitter]').each( function(){

                // Listen for any change events on an emitter field
                $(this).find('.siteorigin-widget-input').on('keyup change', function(){
                    var $$ = $(this);

                    // These emitters can either be an array or a
                    var emitters = $$.closest('[data-state-emitter]').data('state-emitter');

                    var handleStateEmitter = function(emitter, currentStates){
                        if( typeof sowEmitters[ emitter.callback ] === 'undefined' ) {
                            // The function does not exist, so just return the current states
                            return currentStates;
                        }

                        // Return an array that has the new states added to the array
                        return $.extend( currentStates, sowEmitters[emitter.callback]( $$.val(), emitter.args ) );
                    };

                    // Run the states through the state emitters
                    var states = { 'default' : '' };

                    if( typeof emitters.length === 'undefined' ) {
                        // This is an emitter with a single
                        states = handleStateEmitter( emitters, states );
                    }
                    else {
                        // Go through the array of emitters
                        for( var i = 0; i < emitters.length; i++ ) {
                            states = handleStateEmitter( emitters[i], states );
                        }
                    }


                    // TODO handle an emitters value with multiple emitters

                    // Check which states have changed and trigger appropriate sowstatechange
                    var formStates = $mainForm.data('states');
                    if( typeof formStates === 'undefined' ) {
                        formStates = {
                            'default' : ''
                        };
                    }
                    for( var k in states ) {
                        if( typeof formStates[k] === 'undefined' || states[k] !== formStates[k] ) {
                            formStates[k] = states[k];
                            $mainForm.trigger( 'sowstatechange', [ k, states[k] ] );
                        }
                    }

                    // Store the form states back in the form
                    $mainForm.data('states', formStates);
                });

            } );

            // Give plugins a chance to influence the form
            $el.trigger( 'sowsetupform', $fields ).data('sow-form-setup', true);
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

            // Lets build the data from the widget
            var data = {};
            $el.find( '*[name]' ).each( function () {
                var $$ = $(this);
                var name = /[a-zA-Z\-]+\[[a-z0-9]+\]\[(.*)\]/.exec( $$.attr('name') );

                name = name[1];
                var parts = name.split('][');

                // Make sure we either have numbers or strings
                parts = parts.map(function(e){
                    if( !isNaN(parseFloat(e)) && isFinite(e) ) {
                        return parseInt(e);
                    }
                    else {
                        return e;
                    }
                });

                var sub = data;
                for(var i = 0; i < parts.length; i++) {
                    if(i === parts.length - 1) {
                        // This is the end, so we need to store the actual field value here
                        if( $$.attr('type') === 'checkbox' ){
                            if ( $$.is(':checked') ) {
                                sub[ parts[i] ] = $$.val() !== '' ? $$.val() : true;
                            } else {
                                sub[ parts[i] ] = false;
                            }
                        }
                        else if( $$.attr('type') === 'radio' ){
                            if ( $$.is(':checked') ) {
                                sub[ parts[i] ] = $$.val() !== '' ? $$.val() : true;
                            }
                        }
                        else {
                            sub[ parts[i] ] = $$.val();
                        }
                    }
                    else {
                        if(typeof sub[parts[i]] === 'undefined') {
                            sub[parts[i]] = {};
                        }
                        // Go deeper into the data and continue
                        sub = sub[parts[i]];
                    }
                }
            } );

            // Create a new modal window
            var modal = $( $('#so-widgets-bundle-tpl-preview-dialog').html()).appendTo('body');
            modal.find('input[name="data"]').val( JSON.stringify(data) );
            modal.find('input[name="class"]').val( $el.data('class') );
            modal.find('iframe').on('load', function(){
                $(this).css('visibility', 'visible');
            });
            modal.find('form').submit();

            modal.find('.close').click(function(){
                modal.remove();
            });
        });
    };

    $.fn.sowSetupRepeater = function(){

        return $(this).each( function(i, el){
            var $el = $(el);
            var $items = $el.find('.siteorigin-widget-field-repeater-items');
            var name = $el.data('repeater-name');

            $items.bind('updateFieldPositions', function(){
                var $$ = $(this);
                var $rptrItems = $$.find('> .siteorigin-widget-field-repeater-item');
                // Set the position for the repeater items
                $rptrItems.each(function(i, el){
                    $(el).find('.siteorigin-widget-input').each(function(j, input){
                        var pos = $(input).data('repeater-positions');
                        if( typeof pos === 'undefined' ) {
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

                    if(typeof pos !== 'undefined') {
                        var newName = $in.data('original-name');

                        if(typeof newName === 'undefined') {
                            $in.data( 'original-name', $in.attr('name') );
                            newName = $in.attr('name');
                        }

                        for(var k in pos) {
                            newName = newName.replace('#' + k + '#', pos[k] );
                        }
                        $(input).attr('name', newName);
                    }
                });

                //Setup scrolling.
                var scrollCount = $el.data('scroll-count') ? parseInt($el.data('scroll-count')) : 0;
                if( scrollCount > 0 && $rptrItems.length > scrollCount) {
                    var itemHeight = $rptrItems.first().outerHeight();
                    $$.css('max-height', itemHeight * scrollCount).css('overflow', 'auto');
                }
                else {
                    //TODO: Check whether there was a value before overriding and set it back to that.
                    $$.css('max-height', '').css('overflow', '');
                }
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
                    .find('> .siteorigin-widget-field-repeater-items').slideDown('fast', function(){
                        $(window).resize();
                    });

                // Center the PB dialog
                if(typeof $.fn.dialog !== 'undefined') {
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
            var formClass = $el.closest('.siteorigin-widget-form').data('class');
            var $nextIndex = $el.find('> .siteorigin-widget-field-repeater-items').children().length+1;
            var repeaterHtml = window.sow_repeater_html[formClass][$el.data('repeater-name')].replace(/\{id\}/g, $nextIndex);
            var readonly = typeof $el.attr('readonly') != 'undefined';
            var item = $('<div class="siteorigin-widget-field-repeater-item ui-draggable" />')
                .append(
                    $('<div class="siteorigin-widget-field-repeater-item-top" />')
                        .append(
                            $('<div class="siteorigin-widget-field-expand" />')

                        )
                        .append(
                            readonly ? '' : $('<div class="siteorigin-widget-field-remove" />')

                        )
                        .append( $('<h4 />').html( $el.data('item-name') ) )
                )
                .append(
                    $('<div class="siteorigin-widget-field-repeater-item-form" />')
                        .html( repeaterHtml )
                );

            // Add the item and refresh
            $el.find('> .siteorigin-widget-field-repeater-items').append(item).sortable( "refresh").trigger('updateFieldPositions');
            item.sowSetupRepeaterItems();
            item.hide().slideDown('fast', function(){
                $(window).resize();
            });

        } );
    };

    $.fn.sowRemoveRepeaterItem = function () {
        return $(this).each( function(i, el){
            var $itemsContainer = $(this).closest('.siteorigin-widget-field-repeater-items');
            $(this).remove();
            $itemsContainer.sortable("refresh").trigger('updateFieldPositions');
        });
    };

    $.fn.sowSetupRepeaterItems = function () {
        return $(this).each(function (i, el) {
            var $el = $(el);

            if (typeof $el.data('sowrepeater-actions-setup') === 'undefined') {
                var $parentRepeater = $el.closest('.siteorigin-widget-field-repeater');
                var itemTop = $el.find('> .siteorigin-widget-field-repeater-item-top');
                var itemLabel = $parentRepeater.data('item-label');
                if (itemLabel && itemLabel.selector) {
                    var updateLabel = function () {
                        var txt = $el.find(itemLabel.selector)[itemLabel.valueMethod]();
                        if (txt) {
                            if (txt.length > 80) {
                                txt = txt.substr(0, 79) + '...';
                            }
                            itemTop.find('h4').text(txt);
                        }
                    };
                    updateLabel();
                    if (itemLabel.updateEvent) {
                        $el.bind(itemLabel.updateEvent, updateLabel);
                    }
                }

                itemTop.click(function (e) {
                    if (e.target.className === "siteorigin-widget-field-remove") {
                        return;
                    }
                    e.preventDefault();
                    $(this).closest('.siteorigin-widget-field-repeater-item').find('.siteorigin-widget-field-repeater-item-form').eq(0).slideToggle('fast', function () {
                        if (typeof $.fn.dialog !== 'undefined') {
                            $(this).closest('.panel-dialog').dialog("option", "position", "center");
                        }
                    });
                });

                itemTop.find('.siteorigin-widget-field-remove')
                    .click(function (e) {
                        e.preventDefault();
                        if ( confirm( soWidgets.sure ) ) {
                            var $s = $(this).closest('.siteorigin-widget-field-repeater-items');
                            $(this).closest('.siteorigin-widget-field-repeater-item').slideUp('fast', function () {
                                $(this).remove();
                                $s.sortable("refresh").trigger('updateFieldPositions');
                                $(window).resize();
                            });
                        }
                    });

                $el.find('> .siteorigin-widget-field-repeater-item-form').sowSetupForm();

                $el.data('sowrepeater-actions-setup', true);
            }
        });
    };

    window.sowFetchWidgetVariable = function (key, widget, callback) {
        window.sowVars = window.sowVars || {};

        if (typeof window.sowVars[widget] === 'undefined') {
            $.post(
                soWidgets.ajaxurl,
                { 'action': 'sow_get_javascript_variables', 'widget': widget, 'key': key },
                function (result) {
                    window.sowVars[widget] = result;
                    callback(window.sowVars[widget][key]);
                }
            );
        }
        else {
            callback(window.sowVars[widget][key]);
        }
    };

    // When we click on a widget top
    $('.widgets-holder-wrap').on('click', '.widget:has(.siteorigin-widget-form-main) .widget-top', function(){
        var $$ = $(this).closest('.widget').find('.siteorigin-widget-form-main');
        setTimeout( function(){ $$.sowSetupForm(); }, 200);
    });

    // When we open a Page Builder widget dialog
    $(document).on('dialogopen', function(e){
        $(e.target).find('.siteorigin-widget-form-main').sowSetupForm();
    });

    $(document).trigger('sowadminloaded');

    // The state emitter for standard comparisons
    sowEmitters.conditional = function(val, args){
        var returnStates = {};
        if( typeof args.length === 'undefined' ) {
            args = [args];
        }

        var m, cState, cGroup;
        for( var i = 0; i < args.length; i++ ) {
            m = args[i].match(/^([a-z]+)(\[([a-z]+)\])? *: *([^;{}]*)$/);
            if( m === null ) { continue; }

            if( eval( m[4] ) ) {
                cGroup = 'default';
                if( m[3] !== undefined ) {
                    cGroup = m[1];
                    cState = m[3];
                }
                else {
                    cState = m[1];
                }

                returnStates[cGroup] = cState;
            }
        }

        return returnStates;
    };

    // The state emitter for checking if the value is in a list of another values
    sowEmitters.in = function(val, args) {
        var returnStates = {};

        if( typeof args.length === 'undefined' ) {
            args = [args];
        }

        var m, cState, cGroup, inParts;
        for( var i = 0; i < args.length; i++ ) {
            m = args[i].match(/^([a-z]+)(\[([a-z]+)\])? *: *(.*)$/);
            if( m === null ) { continue; }

            inParts = m[4].split(',').map(function(s) { return s.trim(); });
            if( inParts.indexOf( val ) !== -1 ) {
                cGroup = 'default';
                if( m[3] !== undefined ) {
                    cGroup = m[1];
                    cState = m[3];
                }
                else {
                    cState = m[1];
                }

                returnStates[cGroup] = cState;
            }
        }

        return returnStates;
    };

})(jQuery);