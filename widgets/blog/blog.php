<?php
/*
Widget Name: Blog
Description: Display blog posts in a list or grid. Choose a design that suits your content.
Author: SiteOrigin
Author URI: https://siteorigin.com
Documentation: https://siteorigin.com/widgets-bundle/blog-widget/
*/

class SiteOrigin_Widget_Blog_Widget extends SiteOrigin_Widget {
	function __construct() {
		parent::__construct(
			'sow-blog',
			__( 'SiteOrigin Blog', 'so-widgets-bundle' ),
			array(
				'description' => __( 'Display blog posts in a list or grid. Choose a design that suits your content.', 'so-widgets-bundle' ),
				'help' => 'https://siteorigin.com/widgets-bundle/blog-widget/',
				'panels_title' => false,
			),
			array(),
			false,
			plugin_dir_path( __FILE__ )
		);
	}

	function initialize() {
		$this->register_frontend_styles(
			array(
				array(
					'sow-blog',
					plugin_dir_url( __FILE__ ) . 'css/style.css',
				),
			)
		);
	}

	function get_widget_form() {
		$templates = apply_filters( 'siteorigin_widgets_blog_templates', json_decode( file_get_contents( plugin_dir_path( __FILE__ ) . 'data/templates.json' ), true ) );

		return $this->dynamic_preset_state_handler(
			'active_template',
			$templates,
			array(
				'title' => array(
					'type' => 'text',
					'label' => __( 'Title', 'so-widgets-bundle' ),
				),
				'template' => array(
					'type' => 'presets',
					'label' => __( 'Template', 'so-widgets-bundle'),
					'default_preset' => 'standard',
					'options' => $templates,
					'state_emitter' => array(
						'callback' => 'select',
						'args' => array( 'active_template' ),
					),
				),
				'settings' => array(
					'type' => 'section',
					'label' => __( 'Settings', 'so-widgets-bundle' ),
					'hide' => true,
					'fields' => array(
						'columns' => array(
							'type' => 'number',
							'label' => __( 'Column count', 'so-widgets-bundle' ),
						),
						'featured_image' => array(
							'type' => 'checkbox',
							'label' => __( 'Featured image', 'so-widgets-bundle' ),
						),
						'content' => array(
							'type' => 'select',
							'label' => __( 'Post content ', 'so-widgets-bundle' ),
							'description' => __( 'Choose how to display your post content. Select Full Post Content if using the "more" quicktag.', 'so-widgets-bundle' ),
							'options' => array(
								'excerpt' => __( 'Post Excerpt', 'so-widgets-bundle' ),
								'full' => __( 'Full Post Content', 'so-widgets-bundle' ),
							),
						),
						'read_more' => array(
							'type' => 'checkbox',
							'label' => __( 'Post excerpt read more link', 'so-widgets-bundle' ),
							'description' => __( 'Display the Read More link below the post excerpt.', 'so-widgets-bundle' ),
						),
						'date' => array(
							'type' => 'checkbox',
							'label' => __( 'Post date', 'so-widgets-bundle' ),
						),
						'author' => array(
							'type' => 'checkbox',
							'label' => __( 'Post author', 'so-widgets-bundle' ),
						),
						'categories' => array(
							'type' => 'checkbox',
							'label' => __( 'Post categories', 'so-widgets-bundle' ),
						),
						'comment_count' => array(
							'type' => 'checkbox',
							'label' => __( 'Post comment count', 'so-widgets-bundle' ),
						),
					),
				),

				'posts' => array(
					'type' => 'posts',
					'label' => __( 'Posts query', 'so-widgets-bundle' ),
					'hide' => true,
				),
			)
		);
	}

	function get_settings_form() {
		return array(
			'responsive_breakpoint' => array(
				'type'        => 'measurement',
				'label'       => __( 'Responsive Breakpoint', 'so-widgets-bundle' ),
				'default'     => '480px',
				'description' => __( 'This setting controls when the columns will collapse.', 'so-widgets-bundle' )
			)
		);
	}

	function get_template_name( $instance ) {
		return $this->get_style_name( $instance );
	}

	function get_style_name( $instance ) {
		return empty( $instance['template'] ) ? 'standard' : $instance['template'];
	}

	function get_less_variables( $instance ) {
		if ( empty( $instance ) ) {
			return array();
		}

		$columns = (int) $instance['settings']['columns'] > 0 ? (int) $instance['settings']['columns'] : 1;
		return array(
			'responsive_breakpoint' => $this->get_global_settings( 'responsive_breakpoint' ),
			'column_width' => 100 / $columns . '%',
		);
	}

	public function get_template_variables( $instance, $args ) {
		$posts = new WP_Query(
			wp_parse_args(
				array(
					'paged' => (int) get_query_var( 'paged' )
				),
				siteorigin_widget_post_selector_process_query( $instance['posts'] )
			)
		);

		return array(
			'title' => $instance['title'],
			'settings' => $instance['settings'],
			'posts' => $posts,
		);
	}

	// Used for generating the post entry meta.
	function post_meta( $settings ) {
		if ( $settings['date'] ) : ?>
			<span class="entry-date">
				<a href="<?php echo esc_url( get_permalink() ); ?>" rel="bookmark">
					<time class="published" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
						<?php echo esc_html( get_the_date() ); ?>
					</time>
					<time class="updated" datetime="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>">
						<?php echo esc_html( get_the_modified_date() ); ?>
					</time>
				</a>
			</span>
		<?php endif; ?>

		<?php if ( $settings['author'] ) : ?>
			<span class="entry-author-link byline">
				<span class="author vcard">
					<a class="url fn n" href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>" rel="author">
						<?php echo esc_html( get_the_author() ); ?>
					</a>
				</span>
			</span>
		<?php endif; ?>

		<?php if ( $settings['categories'] ) : ?>
			<span class="entry-categories">
				<span class="meta-text"><?php esc_html_e( 'Posted in', 'so-widgets-bundle' ); ?></span>
				<?php
				/* translators: used between list items, there is a space after the comma */
				the_category( esc_html__( ', ', 'so-widgets-bundle' ) );
				?>
			</span>
		<?php endif; ?>

		<?php if ( comments_open() && $settings['comment_count'] ) : ?>
			<span class="entry-comments">
				<?php
				comments_popup_link(
					esc_html__( 'Leave a comment', 'so-widgets-bundle' ),
					esc_html__( 'One Comment', 'so-widgets-bundle' ),
					esc_html__( '% Comments', 'so-widgets-bundle' )
				);
				?>
			</span>
		<?php endif;
	}

	// Used for outputting the post featured image.
	function post_featured_image( $settings, $size = 'post-thumbnail' ) {
		if ( $settings['featured_image'] && has_post_thumbnail() ) : ?>
			<div class="entry-thumbnail">
				<a href="<?php the_permalink(); ?>">
					<?php the_post_thumbnail( $size ); ?>
				</a>
			</div>
			<?php
		endif;
	}

	// Used for generating a custom excerpt with optional read more.
	function generate_excerpt( $settings ) {
		if ( $settings['read_more'] ) {
			$read_more_text = '<a class="more-link excerpt" href="' . esc_url( get_permalink() ) . '">' . esc_html__( 'Continue reading', 'so-widgets-bundle' ) . '</a>';
		}
		$length = apply_filters( 'siteorigin_widgets_blog_excerpt_length', 55 );
		$excerpt = get_the_excerpt();
		$excerpt_add_read_more = str_word_count( $excerpt ) >= $length;

		if ( ! has_excerpt() ) {
			$excerpt = wp_trim_words( $excerpt, $length, '...' );
		}

		if ( $settings['read_more'] && ( has_excerpt() || $excerpt_add_read_more ) ) {
			$excerpt .= $read_more_text;
		}

		echo '<p>' . wp_kses_post( $excerpt ) . '</p>';
	}

	function paginate_links( $settings, $posts ) {
		echo paginate_links( array(
			'base' => str_replace( PHP_INT_MAX, '%#%', esc_url( get_pagenum_link( PHP_INT_MAX ) ) ),
			'format' => '?paged=%#%',
			'current' => max( 1, get_query_var( 'paged' ) ),
			'total' => $posts->max_num_pages,
		) );
	}
}

siteorigin_widget_register( 'sow-blog', __FILE__, 'SiteOrigin_Widget_Blog_Widget' );
