<?php
/**
 * @var $taxonomy
 */
?>

<div id="sow-taxonomy-container" class="sow-taxonomy">

	<?php $terms = the_terms( $post->ID, array( 'taxonomy' => $taxonomy ) ); ?>

	<?php foreach ( $terms as $term ) : ?>
		<p><?php echo $term->name ?></p>
	<?php endforeach; ?>

</div>
