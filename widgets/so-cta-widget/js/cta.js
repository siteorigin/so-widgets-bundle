jQuery(function($){
    $('.sow-cta-base').each(function(){
        var $$ = $(this);
        var
            $b = $$.find('.so-widget-sow-button'),
            $t = $$.find('.sow-cta-text');

        if($t.outerHeight() > $b.outerHeight()) {
            $b.css('margin-top', ( $t.outerHeight() - $b.outerHeight() )/2  );
        }
    });
});