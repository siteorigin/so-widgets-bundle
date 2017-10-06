<?php

/**
 * From this SO answer by Halil Özgür: https://stackoverflow.com/a/18781630/3710600
 *
 * Works with both integer and string positions.
 *
 * @param array $array
 * @param int|string $position
 * @param mixed $insert
 */
function siteorigin_widgets_array_insert( &$array, $position, $insert ) {
	if ( is_int( $position ) ) {
		array_splice( $array, $position, 0, $insert );
	} else {
		$pos   = array_search( $position, array_keys( $array ) );
		$array = array_merge(
			array_slice( $array, 0, $pos ),
			$insert,
			array_slice( $array, $pos )
		);
	}
}
