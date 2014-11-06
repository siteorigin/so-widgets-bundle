<?php
//TODO: figure out how to display default params in widget editor
$width = empty($instance['width']) ? 600 : $instance['width'];
$height = empty($instance['height']) ? 450 : $instance['height'];

//TODO: figure out how to handle required params
$urlParams = array();
switch ( $instance['mode'] ) {
	case 'place':
		$place = $instance['place'];
		$urlParams['q'] = $place['query'];
		if(!empty($place['attribution_source'])) $urlParams['attribution_source'] = $place['attribution_source'];
		if(!empty($place['attribution_web_url'])) $urlParams['attribution_web_url'] = $place['attribution_web_url'];
		break;
	case 'directions':
		$directions = $instance['directions'];
		$urlParams['origin'] = $directions['origin'];
		$urlParams['destination'] = $directions['destination'];
		if(!empty($directions['waypoints'])) $urlParams['waypoints'] = $directions['waypoints'];
		if(!empty($directions['travel_mode'])) $urlParams['mode'] = $directions['travel_mode'];

		if ( !empty( $directions['avoid_tolls'] ) || !empty( $directions['avoid_ferries'] ) || !empty( $directions['avoid_highways'] ) ) {
			$avoid = '';
			if(!empty($directions['avoid_tolls'])) $avoid .= (empty($avoid) ? '' : '|') . 'tolls';
			if(!empty($directions['avoid_ferries'])) $avoid .= (empty($avoid) ? '' : '|') . 'ferries';
			if(!empty($directions['avoid_highways'])) $avoid .= (empty($avoid) ? '' : '|') . 'highways';
			$urlParams['avoid'] = $avoid;
		}
		if (!empty($directions['units'])) $urlParams['units'] = $directions['units'];
		break;
	case 'search':
		$urlParams['q'] = $instance['search']['query'];
		break;
	case 'view':
		$urlParams['center'] = $instance['view']['center'];
		break;
}

if(!empty($instance['center']) && empty($urlParams['center'])) $urlParams['center'] = $instance['center'];
if(!empty($instance['zoom'])) $urlParams['zoom'] = $instance['zoom'];
if(!empty($instance['maptype'])) $urlParams['maptype'] = $instance['maptype'];
if(!empty($instance['language'])) $urlParams['language'] = $instance['language'];
if(!empty($instance['region'])) $urlParams['region'] = $instance['region'];

$urlVars = '';
foreach ( $urlParams as $name => $val ) {
	$urlVars .= '&' . $name . '=' . urlencode($val);
}

?>
<iframe
  width="<?= $width ?>"
  height="<?= $height ?>"
  src="https://www.google.com/maps/embed/v1/<?= $instance['mode'] ?>?key=<?= $instance['api_key'] . $urlVars ?>">
</iframe>