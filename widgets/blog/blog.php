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
				'instance_storage' => true,
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
		add_action( 'wp_enqueue_scripts', array( $this, 'register_template_assets' ) );
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
							'label' => __( 'Column Count', 'so-widgets-bundle' ),
						),
						'featured_image' => array(
							'type' => 'checkbox',
							'label' => __( 'Featured Image', 'so-widgets-bundle' ),
							'default' => true,
						),
						'content' => array(
							'type' => 'select',
							'label' => __( 'Post Content ', 'so-widgets-bundle' ),
							'description' => __( 'Choose how to display your post content. Select Full Post Content if using the "more" quicktag.', 'so-widgets-bundle' ),
							'default' => 'full',
							'options' => array(
								'excerpt' => __( 'Post Excerpt', 'so-widgets-bundle' ),
								'full' => __( 'Full Post Content', 'so-widgets-bundle' ),
							),
						),
						'read_more' => array(
							'type' => 'checkbox',
							'label' => __( 'Post Excerpt Read More Link', 'so-widgets-bundle' ),
							'description' => __( 'Display the Read More link below the post excerpt.', 'so-widgets-bundle' ),
						),
						'date' => array(
							'type' => 'checkbox',
							'label' => __( 'Post Date', 'so-widgets-bundle' ),
							'default' => true,
						),
						'author' => array(
							'type' => 'checkbox',
							'label' => __( 'Post Author', 'so-widgets-bundle' ),
							'default' => true,
						),
						'categories' => array(
							'type' => 'checkbox',
							'label' => __( 'Post Categories', 'so-widgets-bundle' ),
							'default' => true,
						),
						'comment_count' => array(
							'type' => 'checkbox',
							'label' => __( 'Post Comment Count', 'so-widgets-bundle' ),
							'default' => true,
						),
					),
				),

				'posts' => array(
					'type' => 'posts',
					'label' => __( 'Posts Query', 'so-widgets-bundle' ),
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
				'default'     => '780px',
				'description' => __( 'Device width, in pixels, to collapse into a mobile view.', 'so-widgets-bundle' )
			)
		);
	}

	function register_template_assets() {
		wp_register_script( 'sow-blog-template-masonry', plugin_dir_url( __FILE__ ) . 'js/masonry' . SOW_BUNDLE_JS_SUFFIX . '.js', array( 'jquery', 'jquery-isotope' ) );
		wp_register_script( 'sow-blog-template-portfolio', plugin_dir_url( __FILE__ ) . 'js/portfolio' . SOW_BUNDLE_JS_SUFFIX . '.js', array( 'jquery', 'jquery-isotope' ) );

		wp_register_script( 'jquery-isotope', plugin_dir_url( SOW_BUNDLE_BASE_FILE ) . 'js/lib/isotope.pkgd' . SOW_BUNDLE_JS_SUFFIX . '.js', array( 'jquery' ), '3.0.4', true );

		do_action( 'siteorigin_widgets_blog_template_stylesheets' );
	}

	function get_template_name( $instance ) {
		return 'base';
	}

	function get_style_name( $instance ) {
		$template = empty( $instance['template'] ) ? 'standard' : $instance['template'];

		// If this template has any assets, load them.
		if ( wp_style_is( 'sow-blog-template-' . $template, 'registered' ) ) {
			wp_enqueue_style( 'sow-blog-template-' . $template );
		}

		if ( wp_script_is( 'sow-blog-template-' . $template, 'registered' ) ) {
			wp_enqueue_script( 'sow-blog-template-' . $template );
		}

		return $template;
	}

	function get_less_variables( $instance ) {
		if ( empty( $instance ) ) {
			return array();
		}

		$columns = (int) $instance['settings']['columns'] > 0 ? (int) $instance['settings']['columns'] : 1;
		return array(
			'responsive_breakpoint' => $this->get_global_settings( 'responsive_breakpoint' ),
			'column_width' => 100 / $columns - ( $columns * 0.5 )  . '%',
			'categories' => ! empty( $instance['settings']['categories'] ) ? $instance['settings']['categories'] : false,
			'author' => ! empty( $instance['settings']['author'] ) ? $instance['settings']['author'] : false,
		);
	}

	static public function portfolio_get_terms( $instance, $post_id = 0 ) {
		$terms = array();
		if ( post_type_exists( 'jetpack-portfolio' ) ) {
			if ( $post_id ) {
				$terms = get_the_terms( (int) $post_id, 'jetpack-portfolio-type' );
			} else {
				$terms = get_terms( 'jetpack-portfolio-type' );
			}
		}

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			$fallback = apply_filters( 'siteorigin_widgets_blog_portfolio_fallback_term', 'category', $instance );
			// Unable to find posts with portfolio type. Try using fallback term..
			if ( $post_id ) {
				return get_the_terms( (int) $post_id, $fallback );
			} else {
				return get_terms( $fallback );
			}
		} else {
			return $terms;
		}

	}

	function modify_instance( $instance ) {
		if ( empty( $instance ) ) {
			return array();
		}

		if ( empty( $instance['template'] ) ) {
			$instance['template'] = 'standard';
		} else {
			// Ensure selected template is valid.
			switch ( $instance['template'] ) {
				case 'alternate':
				case 'grid':
				case 'masonry':
				case 'offset':
				case 'portfolio':
				case 'standard':
					break;
				default:
					$instance['template'] = 'standard';
					break;
			}
		}

		$instance['paged_id'] = ! empty( $instance['_sow_form_id'] ) ? (int) substr( $instance['_sow_form_id'], 0, 5 ) : null;

		return $instance;
	}

	public function get_template_variables( $instance, $args ) {
		if ( ! isset( $instance['paged'] ) ) {
			$instance['paged'] = ! empty( $_GET['sow-' . $instance['paged_id'] ] ) ? (int) $_GET['sow-' . $instance['paged_id'] ] : 1;
		}
		$query = wp_parse_args(
			array(
				'paged' => $instance['paged'],
			),
			siteorigin_widget_post_selector_process_query( $instance['posts'] )
		);

		if ( $instance['template'] == 'portfolio' ) {
			// This post type relies on each post having an image so exclude any posts that don't.
			$query['meta_query'] = array(
				array(
					'key' => '_thumbnail_id',
					'compare' => 'EXISTS'
				),
			);
		}

		// Add template specific settings.
		$template_settings = array(
			'date_format' => isset( $instance['settings']['date_format'] ) ? $instance['settings']['date_format'] : null,
		);
		if ( $instance['template'] == 'offset' ) {
			if ( $instance['settings']['date'] ) {
				if ( get_the_time( 'U' ) !== get_the_modified_time( 'U' ) ) {
					$template_settings['time_string']  = '<time class="entry-date published" datetime="%1$s">%2$s</time><time class="updated" datetime="%3$s">%4$s</time>';
				} else {
					$template_settings['time_string']  = '<time class="entry-date published updated" datetime="%1$s">%2$s</time>';
				}
			}
		}

		if ( $instance['template'] == 'portfolio' ) {
			$template_settings['terms'] = $this->portfolio_get_terms( $instance );
		}

		return array(
			'title' => $instance['title'],
			'settings' => $instance['settings'],
			'template_settings' => $template_settings,
			'posts' => new WP_Query( apply_filters( 'siteorigin_widgets_blog_query', $query, $instance ) ),
		);
	}

	static public function post_meta( $settings ) {
		if ( $settings['date'] ) :	
			$date_format = isset( $settings['date_format'] ) ? $settings['date_format'] : null;
			?>
			<span class="sow-entry-date">
				<a href="<?php echo esc_url( get_permalink() ); ?>" rel="bookmark">
					<time class="published" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
						<?php echo esc_html( get_the_date( $date_format ) ); ?>
					</time>
					<time class="updated" datetime="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>">
						<?php echo esc_html( get_the_modified_date() ); ?>
					</time>
				</a>
			</span>
		<?php endif; ?>

		<?php if ( $settings['author'] ) : ?>
			<span class="sow-entry-author-link byline">
				<?php if ( function_exists( 'coauthors_posts_links' ) ) : ?>
					<?php coauthors_posts_links(); ?>
				<?php else: ?>
					<span class="sow-author author vcard">
						<a class="url fn n" href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>" rel="author">
							<?php echo esc_html( get_the_author() ); ?>
						</a>
					</span>
				<?php endif; ?>
			</span>
		<?php endif; ?>

		<?php if ( $settings['categories'] && has_category() ) : ?>
			<span class="sow-entry-categories">
				<?php
				/* translators: used between list items, there is a space after the comma */
				the_category( esc_html__( ', ', 'so-widgets-bundle' ) );
				?>
			</span>
		<?php endif; ?>

		<?php if ( comments_open() && $settings['comment_count'] ) : ?>
			<span class="sow-entry-comments">
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

	static public function post_featured_image( $settings, $categories = false, $size = 'post-thumbnail' ) {
		if ( $settings['featured_image'] && has_post_thumbnail() ) : ?>
			<div class="sow-entry-thumbnail">
				<?php if ( $categories && $settings['categories'] && has_category() ) : ?>
					<div class="sow-thumbnail-meta">
						<?php
						echo get_the_category_list();
						?>
					</div>
				<?php endif; ?>
				<a href="<?php the_permalink(); ?>">
					<?php the_post_thumbnail( $size ); ?>
				</a>
			</div>
			<?php
		endif;
	}

	static public function generate_excerpt( $settings ) {
		if ( $settings['read_more'] ) {
			$read_more_text = ! empty( $settings['read_more_text'] ) ?  $settings['read_more_text'] : __( 'Continue reading', 'so-widgets-bundle' );
			$read_more_text = '<a class="sow-more-link more-link excerpt" href="' . esc_url( get_permalink() ) . '">
			' . esc_html( $read_more_text ) . '</a>';
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

	function paginate_links( $settings, $posts, $instance ) {
		$pagination_markup = defined( 'SITEORIGIN_PREMIUM_VERSION' ) ? apply_filters( 'siteorigin_widgets_blog_pagination_markup', false, $settings, $posts, $instance ) : false;

		if ( empty( $pagination_markup ) ) {
			if ( isset( $settings['pagination_reload'] ) && $settings['pagination_reload'] == 'ajax' ) {
				$current = 99999;
				$show_all_prev_next = true;
			} else {
				$current = max( 1, $posts->query['paged'] );
				$show_all_prev_next = false;
			}

			$pagination_markup = paginate_links( array(
				'format' => '?sow-' . $instance['paged_id'] . '=%#%',
				'total' => $posts->max_num_pages,
				'current' => $current,
				'show_all' => $show_all_prev_next,
				'prev_next' => ! $show_all_prev_next,
			) );
		}

		if ( ! empty( $pagination_markup ) ) {
			?>
			<nav class="sow-post-navigation">
				<h2 class="screen-reader-text"><?php esc_html_e( 'Post navigation', 'so-widgets-bundle' ); ?></h2>
				<div class="sow-nav-links">
					<?php echo $pagination_markup; ?>
				</div>
			</nav>
			<?php
		}
	}
}

siteorigin_widget_register( 'sow-blog', __FILE__, 'SiteOrigin_Widget_Blog_Widget' );
