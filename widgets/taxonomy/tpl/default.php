<?php
/**
 * @var $title
 * @var $post
 * @var $taxonomy_name
 * @var $label
 * @var $display_format
 */
?>

<?php if( !empty( $title ) ) echo $args['before_title'] . esc_html( $title ) . $args['after_title']; ?>

<div class="sow-taxonomy">

	<?php if( !empty( $label ) ) : ?>
		<label class="sow-taxonomy-label"><?php echo esc_html( $label ) ?></label>
	<?php endif; ?>

	<?php if ( $display_format == 'text' || $display_format == 'links' ) echo '<p>' ?>

		<?php foreach ( get_the_terms( $post->ID, $taxonomy_name ) as $term ) : ?>
			<?php if ( $display_format == 'text' ) : ?>
				<span class="so-taxonomy-text" ref="tag"><?php echo esc_html( $term->name ) ?></span>
			<?php else: ?>
				<a class="so-taxonomy-<?php echo esc_attr( $display_format )?>" href="<?php echo get_term_link( $term, $taxonomy_name ) ?>" rel="tag"  <?php if ( ! empty( $new_window ) ) echo 'target="_blank" rel="noopener noreferrer"'; ?>><?php echo esc_html( $term->name ) ?></a>
			<?php endif; ?>
		<?php endforeach; ?>

	<?php if ( $display_format == 'text' || $display_format == 'links' ) echo '</p>' ?>

</div>
