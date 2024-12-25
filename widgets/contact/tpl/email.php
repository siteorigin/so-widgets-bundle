<!DOCTYPE html>
<html lang="<?php echo esc_attr( get_bloginfo( 'language' ) ); ?>">
<head>
	<meta charset="<?php esc_attr( get_bloginfo( 'charset' ) ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>
		<?php echo esc_html( $subject ); ?>
	</title>
</head>
<body>
	<?php echo wp_kses_post( $body ); ?>
</body>
</html>
