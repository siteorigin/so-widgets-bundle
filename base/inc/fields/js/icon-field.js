( function( $ ) {

    var iconWidgetCache = {};

    $(document).on( 'sowsetupformfield', '.siteorigin-widget-field-type-icon', function(e) {
        var $$ = $(this),
            $is = $$.find('.siteorigin-widget-icon-selector'),
            $v = $is.find('.siteorigin-widget-icon-icon'),
            $b = $$.find('.siteorigin-widget-icon-selector-current');

        // Clicking on the button should display the icon selector
        $b.click(function(){
            $is.slideToggle();
        });

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
                            // This is being unselected
                            $$.removeClass('siteorigin-widget-active');
                            $v.val( '' );

                            // Hide the button icon
                            $b.find('span').hide();
                        }
                        else {
                            // This is being selected
                            container.find('.siteorigin-widget-icon-icons-icon').removeClass('siteorigin-widget-active');
                            $$.addClass('siteorigin-widget-active');
                            $v.val( $$.data('value') );

                            // Also add this to the button
                            $b.find('span')
                                .show()
                                .attr( 'data-sow-icon', $$.attr('data-sow-icon') )
                                .attr( 'class', '' )
                                .addClass( 'sow-icon-' + family );
                        }
                        $v.trigger('change');

                        // Hide the icon selector
                        $is.slideUp();
                    });

                container.append(icon);

                if( $v.val() === family + '-' + i ) {
                    // Add selected icon to the button.
                    $b.find('span')
                        .show()
                        .attr( 'data-sow-icon', icon.attr('data-sow-icon') )
                        .attr( 'class', '' )
                        .addClass( 'sow-icon-' + family );
                    icon.addClass('siteorigin-widget-active');
                }
            }

            // Move a selcted item to the first position
            container.prepend( container.find('.siteorigin-widget-active') );
        };

        // Create the function for changing the icon family and call it once
        var changeIconFamily = function(){
            // Fetch the family icons from the server
            var family = $is.find('select.siteorigin-widget-icon-family').val();

            var dataIcons = $is.find('select.siteorigin-widget-icon-family option:selected' ).data('icons');
            if( dataIcons !== null ) {
                iconWidgetCache[family] = dataIcons;
            }


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
    } );

} )( jQuery );