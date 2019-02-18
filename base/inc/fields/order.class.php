<?php

/**
 * Class SiteOrigin_Widget_Field_Order
 */
class SiteOrigin_Widget_Field_Order extends SiteOrigin_Widget_Field_Base {

    protected $options;

    protected function render_field( $value, $instance ) {
    
		$value = $this->sanitize_field_input( $value, $instance );
		
        if( ! empty( $this->options ) && ! empty( $value ) ) {
            ?><div class="siteorigin-widget-order-items"><?php
            foreach( $value as $key ) {
                ?>
                <div class="siteorigin-widget-order-item" data-value="<?php echo esc_attr( $key ) ?>">
                    <?php echo esc_html( $this->options[ $key ] ) ?>
                </div>
                <?php
            }
            ?></div><?php
        }

        ?>
        <input
            type="hidden"
            name="<?php echo esc_attr( $this->element_name ) ?>"
            id="<?php echo esc_attr( $this->element_id ) ?>"
            class="siteorigin-widget-input"
            value="<?php echo esc_attr( implode( ',', $value ) ) ?>">
        <?php

    }

    protected function sanitize_field_input( $value, $instance ) {
		if( is_string( $value ) ) {
			$value = explode(',', $value);
			$value = array_map( 'trim', $value );
		}
		
		if ( ! is_array( $value ) ) {
			$value = array();
		}

        foreach( $value as $i => $k ) {
            if( empty( $this->options[$k] ) ) {
                unset( $value[ $i ] );
            }
        }

        return $value;
    }

    public function enqueue_scripts() {
        wp_enqueue_script( 'so-order-field', plugin_dir_url(__FILE__) . 'js/order-field' . SOW_BUNDLE_JS_SUFFIX . '.js', array( 'jquery', 'jquery-ui-sortable' ), SOW_BUNDLE_VERSION );
        wp_enqueue_style( 'so-order-field', plugin_dir_url(__FILE__) . 'css/order-field.css', array( ), SOW_BUNDLE_VERSION );
    }

}
