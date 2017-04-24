jQuery(document).ready(function() {

	/** Faltbarer Text in der Leistungsübersicht
		bei Click auf die Zeile werden alle Zeilen hochgeschoben und dann die geklickt
		entweder hoch oder runter gefahren.
		Die geklickte Zeile bekommt die KLasse aktiv. Falls die Klasse schon aktiv war wird das aktiv entfernt.
	***/
	jQuery('.ow-accordion .ow-accordion-item').click(function(e) {
		jQuery('.ow-accordion-item .ow-accordion-content').stop().slideUp('slow');
		var isactiv = jQuery(this).hasClass('active');
		jQuery('.ow-accordion-item.active').removeClass('active');
		jQuery(this).children('.ow-accordion-content').stop().slideToggle('slow');
		if (!isactiv) {
			jQuery(this).stop().toggleClass('active');
		}
	});
	jQuery('.ow-accordion .ow-accordion-item.active .ow-accordion-content').css('display', 'block'); 
});
