<?php

/**
 * Class SiteOrigin_Widgets_Bucket
 *
 * Class to handle storage for widgets.
 */
class SiteOrigin_Widgets_Bucket {

	private $widget;
	private $bucket;
	private $table_name;
	private $table_exists;

	/**
	 * @param string $bucket The name of the bucket we're dealing with.
	 */
	function __construct( $widget, $bucket = 'default' ) {
		global $wpdb;

		$this->widget       = $widget;
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
	 * @param mixed $data The object we're storing in the database
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
					entry_widget VARCHAR(255),
					entry_bucket VARCHAR(255),
					entry_key VARCHAR(255),
					entry_data TEXT,
					INDEX entry_bucket (entry_bucket),
					INDEX entry_key (entry_bucket, entry_key)
				)
			" );
			$this->table_exists = ( $wpdb->get_var( "SHOW TABLES LIKE '$this->table_name'" ) == $this->table_name );
		}

		if ( $this->table_exists ) {
			// Lets insert this entry into the bucket
			$wpdb->insert(
				$this->table_name,
				array(
					'entry_widget' => get_class($this->widget),
					'entry_bucket' => $this->bucket,
					'entry_key'    => $key,
					'entry_data'   => json_encode( $data ),
				)
			);
		}
	}

	/**
	 * Update a instance by either key or ID
	 */
	function update_by($value, $field = 'id', $data){

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
		$row = $wpdb->get_row( $wpdb->prepare( $query, $value ) );
		if(empty($row)) return false;

		$data = empty( $row->data ) ? array() : json_decode( $row->data, true );
		$data['entry_id'] = $row->entry_id;
		return $data;
	}

	/**
	 * Delete a single entry from the bucket.
	 *
	 * @param $value
	 * @param string $field
	 */
	function delete_by( $value, $field = 'id' ) {
		global $wpdb;

		$query = 'DELETE FROM $this->table_name WHERE ';
		if ( $field == 'id' ) {
			$query .= 'entry_id = %d';
		} elseif ( $field == 'key' ) {
			$query .= 'entry_key = %s';
		}

		// Run the delete query
		$wpdb->query( $wpdb->prepare( $query, $value ) );
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

	/**
	 * Get a paginated list of entries.
	 * @param $page
	 */
	function get_entries($per_page = 20, $page){

	}

}