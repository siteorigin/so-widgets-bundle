<?php

/**
 * Class SiteOrigin_Widget_Field_Section
 */
class SiteOrigin_Widget_Field_Section extends SiteOrigin_Widget_Field_Container_Base {

	protected function render_field( $value, $instance ) {
		?><div class="siteorigin-widget-section <?php if( !empty( $this->hide ) ) echo 'siteorigin-widget-section-hide'; ?>"><?php
		if ( !isset( $this->sub_field_options ) || empty($this->sub_field_options ) ) return;
		$this->create_and_render_sub_fields( $value );
		?></div><?php
	}

}