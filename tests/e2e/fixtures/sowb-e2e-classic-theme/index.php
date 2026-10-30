<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
	<?php
	while ( have_posts() ) {
		the_post();
		the_content();
	}

	dynamic_sidebar( 'sowb-e2e-sidebar' );
	wp_footer();
	?>
	<!-- sowb-e2e-stylesheets-deleted:<?php echo (int) get_option( 'sowb_e2e_stylesheets_deleted' ); ?> -->
</body>
</html>
