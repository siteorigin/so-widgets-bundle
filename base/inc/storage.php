<?php

/**
 * Save an entry into the bucket
 *
 * @param $bucket
 * @param $data
 */
function siteorigin_widgets_save_to_bucket($bucket, $data){
	global $wpdb;

	static $table_exists = null;
	static $table_name = $wpdb->prefix . 'so_widgets_bucket_entries';

	if( is_null($table_exists) ) {
		$table_exists = ( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name );
	}

	if(!$table_exists) {
		// Create the bucket table
		$wpdb->query("
				CREATE TABLE IF NOT EXISTS
				$table_name (
					entry_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
					entry_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
					entry_bucket VARCHAR(255),
					entry_data TEXT,
					INDEX bucket (bucket)
				)
			");
		$table_exists = ( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name );
	}

	if( $table_exists ) {
		// Lets insert this entry into the bucket
		$wpdb->insert(
			$table_name,
			array(
				'entry_bucket' => $bucket,
				'entry_data' => json_encode($data),
			)
		);
	}
}