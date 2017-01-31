( function($){

    $(document).on( 'sowsetupform', function(e) {
        var $form = $(e.target);


        $form.find( '.siteorigin-widget-field-type-order' ).each( function(){
            var $$ = $( this );

            $$.find( '.siteorigin-widget-order-items' ).sortable( {
                stop: function(){
                    var val = [];
                    $$.find( '.siteorigin-widget-order-item' ).each( function( i, el ){
                        val.push( $(el).data('value') );
                    } );
                    $$.find('.siteorigin-widget-input').val( val.join(',') );
                }
            } );
        } );
    });

}( jQuery ) );