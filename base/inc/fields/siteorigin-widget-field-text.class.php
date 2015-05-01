<?php

/**
 * Class SiteOrigin_Widget_Field_Text
 */
class SiteOrigin_Widget_Field_Text extends SiteOrigin_Widget_Field_Text_Input_Base {

	protected function render_field( $value ) {
		$this->render_text_input( $value );
	}
}