<?php

/**
 * Class siteorigin_lessc
 *
 * An extension to the lessc class that adds a few custom functions
 */
class SiteOrigin_Widgets_Less_Functions {

	/**
	 * @param lessc $c
	 *
	 * Register less functions in a lessc object
	 */
	static function registerFunctions(&$c){
		$c->registerFunction( 'length', array('SiteOrigin_Widgets_Less_Functions', 'lengthFunction') );
	}

	/**
	 * Very basic length function that checks the length of a list. Might need some more checks for other types.
	 *
	 * @param $arg
	 *
	 * @return int
	 */
	static function lengthFunction($arg){
		if(empty($arg[0]) || empty($arg[2]) || $arg[0] != 'list') return 1;
		return count($arg[2]);
	}

}