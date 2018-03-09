<?php

/**
 * Class SiteOrigin_Widget_Field_Date_Range
 */
class SiteOrigin_Widget_Field_Date_Range extends SiteOrigin_Widget_Field_Base {

	/**
	 * Either 'relative' or 'specific'. Whether to allow relative or specific date selection.
	 *
	 * @access protected
	 * @var array
	 */
	protected $date_type;

	protected function render_field( $value, $instance ) {

		if ( $this->date_type == 'specific' ) {
			$this->render_specific_date_selector();
		} else {
			$this->render_relative_date_selector( $value );
		}
		?><input type="hidden"
		         class="siteorigin-widget-input"
		         value="<?php echo esc_attr( $value ) ?>"
		         name="<?php echo esc_attr( $this->element_name ) ?>" /><?php
	}

	private function render_specific_date_selector() {
		?><div class="sowb-specific-date-after"><span><?php
		_ex( 'From', 'From this date', 'so-widgets-bundle' );
		?></span><input type="text" class="datepicker after-picker"/></div><?php
		?><div class="sowb-specific-date-before"><span><?php
		_e( 'to', 'so-widgets-bundle' );
		?></span><input type="text" class="datepicker before-picker"/></div><?php
	}

	private function render_relative_date_selector( $value ) {
		$value = json_decode(
			$value,
			true
		);

		$from = ! empty( $value['from'] ) ? $value['from'] : array();
		$this->render_relative_date_selector_part( 'from', __( 'From', 'so-widgets-bundle' ), $from );

		$to = ! empty( $value['to'] ) ? $value['to'] : array();
		$this->render_relative_date_selector_part( 'to', __( 'to', 'so-widgets-bundle' ), $to );
	}

	private function render_relative_date_selector_part( $name, $label, $value ) {
		$units = $this->get_units();

		$val = ! empty( $value['value'] ) ? $value['value'] : 0;
		$unit = ! empty( $value['unit'] ) ? $value['unit'] : 'days';

		?><div class="sowb-relative-date" data-name="<?php echo esc_attr( $name ) ?>"><span><?php
			echo esc_html( $label );
			?></span><input type="number" min="0" step="1" class="sowb-relative-date-value" value="<?php echo esc_attr( $val ) ?>"/>
		<select class="sowb-relative-date-unit">
			<?php foreach( $units as $value => $label) : ?>
				<option value="<?php echo esc_attr( $value ) ?>" <?php selected( $value, $unit ) ?>><?php echo $label ?></option>
			<?php endforeach; ?>
		</select><span><?php _e( 'ago', 'so-widgets-bundle' ); ?></span></div><?php
	}

	private function get_units() {
		return array(
			'days' => __( 'days', 'so-widgets-bundle' ),
			'weeks' => __( 'weeks', 'so-widgets-bundle' ),
			'months' => __( 'months', 'so-widgets-bundle' ),
			'years' => __( 'years', 'so-widgets-bundle' ),
		);
	}

	public function enqueue_scripts() {
		wp_enqueue_style(
			'so-date-range-field',
			plugin_dir_url(__FILE__) . 'css/date-range-field.css',
			array( 'sowb-pikaday' ),
			SOW_BUNDLE_VERSION
		);
		wp_enqueue_script(
			'so-date-range-field',
			plugin_dir_url(__FILE__) . 'js/date-range-field' . SOW_BUNDLE_JS_SUFFIX . '.js',
			array( 'jquery', 'sowb-pikaday' ),
			SOW_BUNDLE_VERSION
		);
	}

	protected function sanitize_field_input( $value, $instance ) {
		if ( $this->date_type == 'specific' ) {
			if ( ! empty( $value ) ) {
				$value = json_decode(
					$value,
					true
				);
				if ( ! empty( $value['after'] ) ) {
					$value_after    = new DateTime( $value['after'] );
					$value['after'] = $value_after->format( 'Y-m-d' );
				}
				if ( ! empty( $value['before'] ) ) {
					$value_before    = new DateTime( $value['before'] );
					$value['before'] = $value_before->format( 'Y-m-d' );
				}
			} else {
				$value = array( 'after' => '', 'before' => '' );
			}
		} else if ( $this->date_type == 'relative' ) {
			if ( ! empty( $value ) ) {
				$value = json_decode(
					$value,
					true
				);
				$unit_keys = array_keys( $this->get_units() );
				foreach( array( 'from', 'to' ) as $key ) {
					if ( empty( $value[$key] ) ) {
						$value[$key] = array();
					}
					$item = $value[$key];
					$val = empty( $item['value'] ) ? 0 : intval( $item['value'] );
					$unit = ( ! empty( $item['unit'] ) && in_array( $item['unit'], $unit_keys ) ) ? $item['unit'] : $unit_keys[0];
					$value[$key] = array( 'value' => $val, 'unit' => $unit );
				}
			} else {
				$value = array( 'from' => array(), 'to' => array() );
			}
		}
		return json_encode( $value );
	}
}
