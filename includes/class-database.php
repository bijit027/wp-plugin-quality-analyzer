<?php
class WPPQA_Database {

	public static function create_tables() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'pqa_plugins';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			slug varchar(200) NOT NULL,
			name varchar(500) NOT NULL,
			active_installs bigint(20) DEFAULT 0,
			rating int(11) DEFAULT 0,
			num_ratings int(11) DEFAULT 0,
			last_updated datetime DEFAULT NULL,
			days_since_update int(11) DEFAULT 0,
			support_threads int(11) DEFAULT 0,
			support_threads_resolved int(11) DEFAULT 0,
			resolution_rate decimal(5,2) DEFAULT 0,
			downloaded bigint(20) DEFAULT 0,
			added date DEFAULT NULL,
			health_score decimal(5,2) DEFAULT 0,
			is_abandoned tinyint(1) DEFAULT 0,
			fetched_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY slug (slug),
			KEY health_score (health_score),
			KEY is_abandoned (is_abandoned)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	public static function get_all_plugins( $orderby = 'id', $order = 'DESC', $filter = 'all' ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'pqa_plugins';

		$where = '1=1';
		if ( $filter !== 'all' ) {
			if ( $filter === 'healthy' ) {
				$where .= ' AND health_score >= 70';
			} elseif ( $filter === 'moderate' ) {
				$where .= ' AND health_score >= 50 AND health_score < 70';
			} elseif ( $filter === 'at_risk' ) {
				$where .= ' AND health_score >= 30 AND health_score < 50';
			} elseif ( $filter === 'abandoned' ) {
				$where .= ' AND health_score < 30';
			}
		}

		$orderby = esc_sql( $orderby );
		$order   = esc_sql( $order );

		$sql = "SELECT * FROM $table_name WHERE $where ORDER BY $orderby $order";
		return $wpdb->get_results( $sql, ARRAY_A );
	}

	public static function get_plugin_count() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'pqa_plugins';
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );
	}

	public static function get_abandoned_count() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'pqa_plugins';
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE is_abandoned = 1" );
	}

	public static function get_average_health_score() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'pqa_plugins';
		return (float) $wpdb->get_var( "SELECT AVG(health_score) FROM $table_name" );
	}

	public static function upsert_plugin( $data ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'pqa_plugins';

		$format = array();
		foreach ( $data as $key => $value ) {
			if ( is_int( $value ) ) {
				$format[] = '%d';
			} elseif ( is_float( $value ) ) {
				$format[] = '%f';
			} else {
				$format[] = '%s';
			}
		}

		$wpdb->replace( $table_name, $data, $format );
		return $wpdb->insert_id;
	}

	public static function clear_all() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'pqa_plugins';
		return $wpdb->query( "TRUNCATE TABLE $table_name" );
	}

	public static function get_stats_summary() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'pqa_plugins';

		$row = $wpdb->get_row( "SELECT 
			COUNT(*) as total, 
			SUM(is_abandoned) as abandoned, 
			AVG(rating) as avg_rating, 
			AVG(health_score) as avg_health_score, 
			AVG(resolution_rate) as avg_resolution_rate, 
			AVG(active_installs) as avg_installs 
			FROM $table_name", ARRAY_A );
			
		return array(
			'total'               => (int) $row['total'],
			'abandoned'           => (int) $row['abandoned'],
			'avg_rating'          => (float) $row['avg_rating'],
			'avg_health_score'    => (float) $row['avg_health_score'],
			'avg_resolution_rate' => (float) $row['avg_resolution_rate'],
			'avg_installs'        => (float) $row['avg_installs'],
		);
	}
}
