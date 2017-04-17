jQuery( function ( $ ) {
	var ajaxData = vc.EditElementPanelView.prototype.ajaxData;
	vc.EditElementPanelView.prototype.ajaxData = function() {
		if ( this.model.get( 'shortcode' ) === 'siteorigin_widget_vc' && this.model.get( 'from_content' ) ) {
			var widgetData = this.model.get( 'params' ).so_widget_data;
			// Need to add slashes for frontend the first time after data is parsed from content, due to shortcode
			// processing removing slashes.
			widgetData = widgetData.replace( /\\/g, '\\\\' );
			this.model.set( 'params', { so_widget_data: widgetData } );
			this.model.unset( 'from_content' );
		}
		return ajaxData.apply( this );
	};

} );
