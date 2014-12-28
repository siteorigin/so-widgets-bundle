<?php

/**
 * Class SiteOrigin_Widgets_Bucket
 *
 * Class to handle storage for widgets.
 */
class SiteOrigin_Widgets_Bucket {

	private $bucket;
	private $table_name;
	private $table_exists;

	/**
	 * @param string $bucket The name of the bucket we're dealing with.
	 */
	function __construct( $bucket = 'default' ) {
		global $wpdb;
		$this->bucket       = $bucket;
		$this->table_name   = $wpdb->prefix . 'so_widgets_bucket_entries';
		$this->table_exists = null;
	}

	/**
	 * Get the current bucket
	 *
	 * @return string
	 */
	function current_bucket() {
		return $this->bucket;
	}

	/**
	 * Save content to the current bucket.
	 *
	 * @param $data The object we're storing in the database
	 * @param string $key The key for this item.
	 */
	function save( $data, $key = '' ) {
		global $wpdb;

		if ( is_null( $this->table_exists ) ) {
			$this->table_exists = ( $wpdb->get_var( "SHOW TABLES LIKE '$this->table_name'" ) == $this->table_name );
		}

		if ( !$this->table_exists ) {
			// Create the bucket table
			$wpdb->query( "
				CREATE TABLE IF NOT EXISTS
				$this->table_name (
					entry_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
					entry_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
					entry_bucket VARCHAR(255),
					entry_key VARCHAR(255),
					entry_data TEXT,
					INDEX entry_bucket (entry_bucket),
					INDEX entry_key (entry_bucket, entry_key)
				)
			" );
			$table_exists = ( $wpdb->get_var( "SHOW TABLES LIKE '$this->table_name'" ) == $this->table_name );
		}

		if ( $this->table_exists ) {
			// Lets insert this entry into the bucket
			$wpdb->insert(
				$this->table_name,
				array(
					'entry_bucket' => $this->bucket,
					'entry_key'    => $key,
					'entry_data'   => json_encode( $data ),
				)
			);
		}
	}

	/**
	 * Get a single entry from the bucket
	 *
	 * @param string $value The value of the field
	 * @param string $field The field to get by
	 *
	 * @return array|bool|mixed
	 */
	function get_by( $value, $field = 'id' ) {
		global $wpdb;

		$query = 'SELECT entry_data FROM $this->table_name WHERE ';
		if ( $field == 'id' ) {
			$query .= 'entry_id = %d';
		} elseif ( $field == 'key' ) {
			$query .= 'entry_key = %s';
		}

		// Fetch and return the data
		$data = $wpdb->get_var( $wpdb->prepare( $query, $value ) );

		return empty( $data ) ? false : json_decode( $data, true );
	}

	/**
	 * Delete a single entry from the bucket.
	 *
	 * @param $id
	 */
	function delete( $id ) {
		global $wpdb;

		$wpdb->query( $wpdb->prepare(
			"DELETE FROM $this->table_name WHERE entry_id = %d", $id
		) );
	}

	/**
	 * Clear all entries from the bucket
	 */
	function clear() {
		global $wpdb;

		$wpdb->query( $wpdb->prepare(
			"DELETE FROM $this->table_name WHERE entry_bucket = %s", $this->bucket
		) );
	}
}