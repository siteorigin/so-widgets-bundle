<html>
<head>
	<title><?php _e('Widget Preview', 'siteorigin-widgets') ?></title>
	<meta id="Viewport" name="viewport" width="width=960, initial-scale=0.25">
</head>
<body>
	<?php
	the_widget( $class, $instance, array(
		'before_widget' => '<div class="widget-preview-wrapper">',
		'after_widget' => '</div>',
	) );
	siteorigin_widget_print_styles();
	?>
</body>
</html>