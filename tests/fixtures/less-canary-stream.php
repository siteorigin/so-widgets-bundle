<?php

/**
 * A test-only stream wrapper that serves fixed canary bytes at
 * sowcanary://<any path> and counts every open and stat.
 *
 * A LESS check that reads no file leaves both counters at zero. The tests
 * that assert zero also run a positive control, so a counter that never
 * moves cannot pass for a check that works.
 */
class SiteOrigin_Less_Canary_Stream {
	const SCHEME = 'sowcanary';
	const CANARY = 'SOW-CANARY-7f3a91c2';

	public static $opens = 0;
	public static $stats = 0;

	public $context;

	private $position = 0;

	public static function register() {
		if ( ! in_array( self::SCHEME, stream_get_wrappers(), true ) ) {
			stream_wrapper_register( self::SCHEME, self::class );
		}

		self::reset();
	}

	public static function unregister() {
		if ( in_array( self::SCHEME, stream_get_wrappers(), true ) ) {
			stream_wrapper_unregister( self::SCHEME );
		}
	}

	public static function reset() {
		self::$opens = 0;
		self::$stats = 0;
		clearstatcache();
	}

	public static function url( $path = 'canary' ) {
		return self::SCHEME . '://' . $path;
	}

	public function stream_open( $path, $mode, $options, &$opened_path ) {
		self::$opens++;
		$this->position = 0;

		return true;
	}

	public function stream_read( $count ) {
		$chunk = substr( self::CANARY, $this->position, $count );
		$this->position += strlen( $chunk );

		return $chunk;
	}

	public function stream_eof() {
		return $this->position >= strlen( self::CANARY );
	}

	public function stream_stat() {
		return self::file_stat();
	}

	public function url_stat( $path, $flags ) {
		self::$stats++;

		return self::file_stat();
	}

	public function stream_close() {
	}

	private static function file_stat() {
		return array(
			'mode' => 0100644,
			'size' => strlen( self::CANARY ),
		);
	}
}
