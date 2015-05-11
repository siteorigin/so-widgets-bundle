/* globals jQuery */

//Catch Update button click and transform widgets post meta data before event is propagated.
(function($){
    $('#publish').click(
        function(event) {
            var data = {};
            var $el = $('#siteorigin-widgets-meta-box');
            // This uses the exact same code to extract the model from the view as in the admin.js so could do with some refactoring.
            $el.find( '*[name]' ).each( function () {
                var $$ = $(this);
                var name = /[a-zA-Z0-9\-]+\[[a-zA-Z0-9]+\]\[(.*)\]/.exec( $$.attr('name') );

                if( ! name ) {
                    return;
                }

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
            $el.find('input[name="widget_post_meta"]').val( JSON.stringify( data ));
        }
    );
})(jQuery);