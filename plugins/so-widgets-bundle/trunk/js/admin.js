jQuery(function($){
    $('.so-widget .switch .switch-input').change(function(e){
        var $$ = $(this);
        var s = $$.is(':checked');

        if(s) $$.closest('.so-widget').addClass('so-widget-is-active').removeClass('so-widget-is-inactive');
        else $$.closest('.so-widget').removeClass('so-widget-is-active').addClass('so-widget-is-inactive');

        // Lets send an ajax request.
        $.post(
            $$.data('url'),
            { 'active' : s },
            function(data){

            }
        );
    });
});