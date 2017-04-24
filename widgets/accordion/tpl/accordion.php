<?php
$items = array();
if(!empty($instance['frames'])) $items[] = $instance['frames'];
if(!empty($instance['controls']['open_first_element'])) $open_first_element = $instance['controls']['open_first_element'];
?>

<div class="ow-accordion">
	<?php foreach($items as $i => $values) { ?>
		<?php $itemcount = 0; ?>
		<?php foreach ($values as $key => $value) { ?>
			<div class="ow-accordion-item <?php if($open_first_element && $itemcount == 0) echo 'active'; ?>">
				<div class="ow-accordion-header"><h3><?php echo $value['title'] ?></h3></div>
				<div class="ow-accordion-content"><?php echo $value['text'] ?></div>
			</div>
			<?php $itemcount++; ?>
		<?php } ?>
	<?php } ?>
</div>
