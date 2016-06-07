<?php
/**
 * @var $post
 * @var $taxonomy
 * @var $display_format
 */
?>

<div id="sow-taxonomy-container" class="sow-taxonomy">

	<?php $terms = get_the_terms( $post->ID, $taxonomy ); ?>

	<?php foreach ( $terms as $term ) : ?>
		<p><?php echo $term->name ?></p>
	<?php endforeach; ?>

</div>
