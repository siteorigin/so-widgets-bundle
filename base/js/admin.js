
/* globals wp, jQuery, soWidgets, confirm */

var sowEmitters = {

    /**
     * Find the group/state and an extra match part.
     *
     * @param arg
     * @param matchPart
     * @return {*}
     */
    '_match': function(arg, matchPart) {
        if( typeof matchPart === 'undefined' ) { matchPart = '.*'; }

        // Create the regular expression to match the group/state and extra match
        var exp = new RegExp( '^([a-zA-Z0-9_-]+)(\\[([a-zA-Z0-9_-]+)\\])? *: *(' + matchPart + ') *$' );
        var m = exp.exec( arg );

        if( m === null ) { return false; }

        var state = '';
        var group = 'default';

        if( m[3] !== undefined ) {
            group = m[1];
            state = m[3];
        }
        else {
            state = m[1];
        }

        return {
            'match' : m[4].trim(),
            'group' : group,
            'state' : state
        };
    },

    '_checker' : function(val, args, matchPart, callback){
        var returnStates = {};
        if( typeof args.length === 'undefined' ) {
            args = [args];
        }

        var m;
        for( var i = 0; i < args.length; i++ ) {
            m = sowEmitters._match( args[i], matchPart );
            if ( m === false ) { continue; }

            if( m.match === '_true' || callback( val, args, m.match ) ) {
                returnStates[ m.group ] = m.state;
            }
        }

        return returnStates;
    },

    /**
     * A very simple state emitter that simply sets the given group the value
     *
     *
     * @param val
     * @param args
     * @returns {{}}
     */
    'select': function(val, args) {
        if( typeof args.length === 'undefined' ) {
            args = [args];
        }

        var returnGroups = {};
        for( var i = 0; i < args.length; i++ ) {
            if( args[i] === '' ) {
                args[i] = 'default';
            }
            returnGroups[args[i]] = val;
        }

        return returnGroups;
    },

    /**
     * The conditional state emitter uses eval to check a given conditional argument.
     *
     * @param val
     * @param args
     * @return {{}}
     */
    'conditional' : function(val, args){
        return sowEmitters._checker( val, args, '[^;{}]*', function( val, args, match ){
            return eval( match );
        } );
    },

    /**
     * The in state emitter checks if the value is in an array of functions
     *
     * @param val
     * @param args
     * @return {{}}
     */
    'in' :  function(val, args) {
        return sowEmitters._checker( val, args, '[^;{}]*', function( val, args, match ){
            return match.split(',').map( function(s) { return s.trim(); } ).indexOf( val ) !== -1;
        } );
    }
};

(function($){

    $.fn.sowSetupForm = function() {

        return $(this).each( function(i, el){
            var $el = $(el), $mainForm, formInitializing = true;

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

                // Listen for a state change event if this is the main form wrapper
                $el.on('sowstatechange', function( e, incomingGroup, incomingState ){

                    // Find all wrappers that have state handlers on them
                    $el.find('[data-state-handler]').each( function(){
                        var $$ = $(this);

                        // Create a copy of the current state handlers. Add in initial handlers if the form is initializing.
                        var handler = $.extend( {}, $$.data( 'state-handler' ), formInitializing ?  $$.data('state-handler-initial' ) : {} ) ;
                        if( Object.keys( handler ).length === 0 ) { return true; }

                        // We need to figure out what the incoming state is
                        var handlerStateParts, handlerState, thisHandler, $$f, runHandler;

                        // Indicates if the handler has run
                        var handlerRun = {};

                        // Go through all the handlers
                        for( var state in handler ) {
                            runHandler = false;

                            // Parse the handler state parts
                            handlerStateParts = state.match(/^([a-zA-Z0-9_-]+)(\[([a-zA-Z0-9_-]+)\])?(\[\])?$/);
                            if( handlerStateParts === null ) {
                                // Skip this if there's a problem with the state parts
                                continue;
                            }

                            handlerState = {
                                'group' : 'default',
                                'name' : '',
                                'multi' : false
                            };

                            // Assign the handlerState attributes based on the parsed state
                            if( handlerStateParts[2] !== undefined ) {
                                handlerState.group = handlerStateParts[1];
                                handlerState.name = handlerStateParts[3];
                            }
                            else {
                                handlerState.name = handlerStateParts[0];
                            }
                            handlerState.multi = (handlerStateParts[4] !== undefined);

                            if( handlerState.group === '_else' ) {
                                // This is the special case of an group else handler
                                // Always run if no handlers from the current group have been run yet
                                handlerState.group = handlerState.name;
                                handlerState.name = '';

                                // We will run this handler because none have run for it yet
                                runHandler = ( typeof handlerRun[ handlerState.group ] === 'undefined' );
                            }
                            else {
                                // Evaluate if we're in the current state
                                runHandler = (handlerState.group === incomingGroup && handlerState.name === incomingState);
                            }


                            // Run the handler if previous checks have determined we should
                            if( runHandler ) {
                                thisHandler = handler[ state ];

                                // Now we can handle the the handler
                                if ( !handlerState.multi ) {
                                    thisHandler = [ thisHandler ];
                                }

                                for (var i = 0; i < thisHandler.length; i++) {
                                    // Choose the item we'll be acting on here
                                    if ( typeof thisHandler[i][1] !== 'undefined' && Boolean( thisHandler[i][1] ) ) {
                                        // thisHandler[i][1] is the sub selector
                                        $$f = $$.find( thisHandler[i][1] );
                                    }
                                    else {
                                        $$f = $$;
                                    }

                                    // Call the function on the wrapper we've selected
                                    $$f[thisHandler[i][0]].apply($$f, typeof thisHandler[i][2] !== 'undefined' ? thisHandler[i][2] : []);

                                }

                                // Store that we've run a handler
                                handlerRun[ handlerState.group ] = true;
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

            var stateEmitterChangeHandler = function(){
                var $$ = $(this);

                // These emitters can either be an array or a
                var emitters = $$.closest('[data-state-emitter]').data('state-emitter');

                if( typeof emitters !== 'undefined' ) {
                    var handleStateEmitter = function(emitter, currentStates){
                        if( typeof sowEmitters[ emitter.callback ] === 'undefined' || emitter.callback.substr(0,1) === '_' ) {
                            // Skip if the function doesn't exist, or it starts with an underscore.
                            return currentStates;
                        }

                        // Return an array that has the new states added to the array
                        return $.extend( currentStates, sowEmitters[emitter.callback]( $$.val(), emitter.args ) );
                    };

                    // Run the states through the state emitters
                    var states = { 'default' : '' };

                    // Go through the array of emitters
                    if( typeof emitters.length === 'undefined' ) { emitters = [emitters]; }
                    for( var i = 0; i < emitters.length; i++ ) {
                        states = handleStateEmitter( emitters[i], states );
                    }

                    // Check which states have changed and trigger appropriate sowstatechange
                    var formStates = $mainForm.data('states');
                    if( typeof formStates === 'undefined' ) {
                        formStates = { 'default' : '' };
                    }
                    for( var k in states ) {
                        if( typeof formStates[k] === 'undefined' || states[k] !== formStates[k] ) {
                            // If the state is different from the original formStates, then trigger a state change
                            formStates[k] = states[k];
                            $mainForm.trigger( 'sowstatechange', [ k, states[k] ] );
                        }
                    }

                    // Store the form states back in the form
                    $mainForm.data('states', formStates);
                }
            };

            $fields.filter('[data-state-emitter]').each( function(){

                // Listen for any change events on an emitter field
                $(this).find('.siteorigin-widget-input').on('keyup change', stateEmitterChangeHandler);

                // Trigger changes on all necessary fields
                $(this).find('.siteorigin-widget-input').each(function(){
                    var $$ = $(this);
                    if( $$.is(':radio') ) {
                        // Only checked radio inputs must have change events
                        if( $$.is(':checked') ) {
                            stateEmitterChangeHandler.call( $$[0] );
                        }
                    }
                    else{
                        stateEmitterChangeHandler.call( $$[0] );
                    }
                });

            } );

            // Give plugins a chance to influence the form
            $el.trigger( 'sowsetupform', $fields ).data('sow-form-setup', true);
            $el.find('.siteorigin-widget-field-repeater-item').trigger('updateFieldPositions');

            /////////////////////////////
            // The end of the form setup.
            /////////////////////////////

            formInitializing = false;
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
                var name = /[a-zA-Z0-9\-]+\[[a-zA-Z0-9]+\]\[(.*)\]/.exec( $$.attr('name') );

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
                        var functionName = ( itemLabel.hasOwnProperty('valueMethod') && itemLabel.valueMethod ) ? itemLabel.valueMethod : 'val';
                        var txt = $el.find(itemLabel.selector)[functionName]();
                        if (txt) {
                            if (txt.length > 80) {
                                txt = txt.substr(0, 79) + '...';
                            }
                            itemTop.find('h4').text(txt);
                        }
                    };
                    updateLabel();
                    var eventName = ( itemLabel.hasOwnProperty('updateEvent') && itemLabel.updateEvent ) ? itemLabel.updateEvent : 'change';
                    $el.bind(eventName, updateLabel);
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

})(jQuery);