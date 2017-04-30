<?php

/**
 * Class SiteOrigin_Widget_Field_Posts
 */
class SiteOrigin_Widget_Field_Posts extends SiteOrigin_Widget_Field_Container_Base {

	public function __construct( $base_name, $element_id, $element_name, $field_options, SiteOrigin_Widget $for_widget, $parent_container = array() ) {
		parent::__construct( $base_name, $element_id, $element_name, $field_options, $for_widget, $parent_container );

		$types        = get_post_types( array( 'public' => true ), 'objects' );
		$type_options = array( '_all' => __( 'All', 'so-widgets-bundle' ) );

		foreach ( $types as $id => $type ) {
			$type_options[ $id ] = $type->labels->name;
		}

		$this->fields = array(

			'post_type' => array(
				'type'     => 'select',
				'label'    => __( 'Post type', 'so-widgets-bundle' ),
				'multiple' => true,
				'options'  => $type_options,
			),

			'post__in' => array(
				'type'  => 'autocomplete',
				'label' => __( 'Post in', 'so-widgets-bundle' ),
				'source' => 'posts',
			),

			'tax_query' => array(
				'type'  => 'autocomplete',
				'label' => __( 'Taxonomies', 'so-widgets-bundle' ),
				'source' => 'terms',
			),

			'date_query' => array(
				'type'  => 'date-range',
				'label' => __( 'Date range', 'so-widgets-bundle' ),
			),

			'orderby' => array(
				'type'    => 'select',
				'label'   => __( 'Order by', 'so-widgets-bundle' ),
				'options' => array(
					'none'           => __( 'No order', 'so-widgets-bundle' ),
					'ID'             => __( 'Post ID', 'so-widgets-bundle' ),
					'author'         => __( 'Author', 'so-widgets-bundle' ),
					'title'          => __( 'Title', 'so-widgets-bundle' ),
					'date'           => __( 'Published date', 'so-widgets-bundle' ),
					'modified'       => __( 'Modified date', 'so-widgets-bundle' ),
					'parent'         => __( 'By parent', 'so-widgets-bundle' ),
					'rand'           => __( 'Random order', 'so-widgets-bundle' ),
					'comment_count'  => __( 'Comment count', 'so-widgets-bundle' ),
					'menu_order'     => __( 'Menu order', 'so-widgets-bundle' ),
					'meta_value'     => __( 'By meta value', 'so-widgets-bundle' ),
					'meta_value_num' => __( 'By numeric meta value', 'so-widgets-bundle' ),
					'post__in'       => __( 'By include order', 'so-widgets-bundle' ),
				),
			),

			'order' => array(
				'type'    => 'radio',
				'label'   => __( 'Order direction', 'so-widgets-bundle' ),
				'options' => array(
					'ASC'  => __( 'Ascending', 'so-widgets-bundle' ),
					'DESC' => __( 'Descending', 'so-widgets-bundle' ),
				),
				'default' => 'DESC',
			),

			'posts_per_page' => array(
				'type'  => 'number',
				'label' => __( 'Posts per page', 'so-widgets-bundle' ),
			),

			'sticky' => array(
				'type'    => 'select',
				'label'   => __( 'Sticky posts', 'so-widgets-bundle' ),
				'options' => array(
					''        => __( 'Default', 'so-widgets-bundle' ),
					'ignore'  => __( 'Ignore sticky', 'so-widgets-bundle' ),
					'exclude' => __( 'Exclude sticky', 'so-widgets-bundle' ),
					'only'    => __( 'Only sticky', 'so-widgets-bundle' ),
				),
			),

			'additional' => array(
				'type'        => 'text',
				'label'       => __( 'Additional', 'so-widgets-bundle' ),
				'description' => __( 'Additional query arguments. See <a href="http://codex.wordpress.org/Function_Reference/query_posts" target="_blank">query_posts</a>.', 'so-widgets-bundle' )
			),
		);
	}

	protected function render_field_label( $value, $instance ) {
		?><div class="posts-container-label-wrapper<?php if ( $this->state == 'open' ) {
			echo ' siteorigin-widget-section-visible';
		} ?>"><?php
		parent::render_field_label( $value, $instance );
		?><span class="sow-current-count"><?php echo esc_html( siteorigin_widget_post_selector_count_posts( $value ) )?></span>
		</div><?php
	}

	protected function render_field( $value, $instance ) {
		$value = wp_parse_args( $value );
		if ( $this->collapsible ) {
			?><div class="siteorigin-widget-section <?php if ( $this->state == 'closed' ) {
				echo 'siteorigin-widget-section-hide';
			} ?>"><?php
		}

		$this->create_and_render_sub_fields( $value, array( 'name' => $this->base_name, 'type' => 'composite' ) );

		if ( $this->collapsible ) {
			?></div><?php
		}
	}

	public function enqueue_scripts() {
	    wp_enqueue_script( 'so-posts-selector-field', plugin_dir_url( __FILE__ ) . 'js/posts-field' . SOW_BUNDLE_JS_SUFFIX . '.js', array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-autocomplete', 'underscore', 'backbone' ), SOW_BUNDLE_VERSION, true );
	}

	protected function sanitize_field_input( $value, $instance ) {
		$value = parent::sanitize_field_input( $value, $instance );
		$result = '';
		foreach ( $value as $key => $item ) {
			if ( ! empty( $item ) ) {
				$result .= ( empty( $result ) ? '' : '&' ) . $key . '=' . $item;
			}
		}

		return $result;
	}

}
