<?php

class SiteOrigin_Widget_Field_Editor extends SiteOrigin_Widget_Field_Text_Input_Base {
	/**
	 * The number of visible rows in the textarea.
	 *
	 * @access protected
	 * @var int
	 */
	protected $rows = 10;
	/**
	 * The editor initial height. Overrides rows if it is set.
	 *
	 * @access protected
	 * @var int
	 */
	protected $editor_height;
	/**
	 * The editor to be displayed initially.
	 *
	 * @access protected
	 * @var string
	 */
	protected $default_editor = 'tinymce';
	/**
	 * The last editor selected by the user.
	 *
	 * @access protected
	 * @var string
	 */
	protected $selected_editor;

	protected function initialize() {
		if ( ! is_admin() ) {
			return;
		}
	}
	
	protected function get_input_classes() {
		$classes = parent::get_input_classes();
		$classes[] = 'wp-editor-area';
		return $classes;
	}
	
	
	protected function render_before_field( $value, $instance ) {
		$selected_editor_name = $this->get_selected_editor_field_name( $this->base_name );
		if( ! empty( $instance[ $selected_editor_name ] ) ) {
			$this->selected_editor = $instance[ $selected_editor_name ];
		}
		else {
			$this->selected_editor = $this->default_editor;
		}
		parent::render_before_field( $value, $instance );
	}
	
	protected function render_field( $value, $instance ) {
		
		$selected_editor = in_array( $this->selected_editor, array( 'tinymce', 'tmce' ) ) ? 'tmce' : 'html';
		
		$settings = array(
			'selectedEditor' => $selected_editor,
			'tinymce' => array(
				'wp_skip_init' => strpos( $this->element_id, '__i__' ) != false ||
				                  strpos( $this->element_id, '_id_' ) != false,
				'toolbar1' => 'formatselect,bold,italic,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,unlink,wp_more,wp_adv',
				'toolbar2' => 'strikethrough,hr,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help',
				'wpautop' => true,
			),
			'quicktags' => array(
				'buttons' => 'strong,em,link,block,del,ins,img,ul,ol,li,code,more,close',
			),
		);
		
		$value = apply_filters( 'the_editor_content', $value, $this->selected_editor );
		
		if ( false !== stripos( $value, 'textarea' ) ) {
			$value = preg_replace( '%</textarea%i', '&lt;/textarea', $value );
		}
		
		?><div class="siteorigin-widget-tinymce-container"
		       data-editor-settings="<?php echo esc_attr( json_encode( $settings ) ) ?>">
			<textarea id="<?php echo esc_attr( $this->element_id ) ?>"
			          name="<?php echo esc_attr( $this->element_name ) ?>"
			          <?php if ( isset( $this->editor_height ) ) : ?>
				          style="height: <?php echo intval( $this->editor_height ) ?>px"
			          <?php else : ?>
				          rows="<?php echo esc_attr( $this->rows ) ?>"
			          <?php endif; ?>
				<?php $this->render_data_attributes( $this->get_input_data_attributes() ) ?>
				<?php $this->render_CSS_classes( $this->get_input_classes() ) ?>
				<?php if ( ! empty( $this->placeholder ) ) echo 'placeholder="' . esc_attr( $this->placeholder ) . '"' ?>
				<?php if( ! empty( $this->readonly ) ) echo 'readonly' ?>><?php echo $value ?></textarea>
		</div>
		<input type="hidden"
		       name="<?php echo esc_attr( $this->for_widget->so_get_field_name( $this->base_name . '_selected_editor', $this->parent_container) ) ?>"
		       class="siteorigin-widget-input siteorigin-widget-tinymce-selected-editor"
		       value="<?php echo esc_attr( $this->selected_editor ) ?>"/>
		<?php

	}

	public function enqueue_scripts() {
		wp_enqueue_editor();
		wp_enqueue_script( 'so-editor-field', plugin_dir_url(__FILE__) . 'js/editor-field' . SOW_BUNDLE_JS_SUFFIX . '.js', array( 'jquery' ), SOW_BUNDLE_VERSION );
	}

	protected function sanitize_field_input( $value, $instance ) {
		return $value;
	}

	public function sanitize_instance( $instance ) {
		$selected_editor_name = $this->get_selected_editor_field_name( $this->base_name );
		if( ! empty( $instance[ $selected_editor_name ] ) ) {
			$selected_editor = $instance[ $selected_editor_name ];
			$instance[ $selected_editor_name ] = in_array( $selected_editor, array( 'tinymce', 'tmce', 'quicktags', 'html' ) ) ? $selected_editor : $this->default_editor;
		}
		return $instance;
	}
	
	public function get_selected_editor_field_name( $base_name ) {
		$v_name = $base_name;
		if( strpos($v_name, '][') !== false ) {
			// Remove this splitter
			$v_name = substr( $v_name, strrpos($v_name, '][') + 2 );
		}
		return $v_name . '_selected_editor';
	}
}
