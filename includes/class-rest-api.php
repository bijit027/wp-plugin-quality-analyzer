<?php
class WPPQA_REST_API {

	private $namespace = 'wppqa/v1';

	public function register_routes() {
		register_rest_route( $this->namespace, '/fetch', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'endpoint_fetch' ],
			'permission_callback' => [ $this, 'check_permission' ],
		] );

		register_rest_route( $this->namespace, '/status', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'endpoint_status' ],
			'permission_callback' => [ $this, 'check_permission' ],
		] );

		register_rest_route( $this->namespace, '/plugins', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'endpoint_plugins' ],
			'permission_callback' => [ $this, 'check_permission' ],
		] );

		register_rest_route( $this->namespace, '/stats', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'endpoint_stats' ],
			'permission_callback' => [ $this, 'check_permission' ],
		] );

		register_rest_route( $this->namespace, '/chart-data', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'endpoint_chart_data' ],
			'permission_callback' => [ $this, 'check_permission' ],
		] );

		register_rest_route( $this->namespace, '/export', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'endpoint_export' ],
			'permission_callback' => [ $this, 'check_permission' ],
		] );

		register_rest_route( $this->namespace, '/clear', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'endpoint_clear' ],
			'permission_callback' => [ $this, 'check_permission' ],
		] );
	}

	public function check_permission() {
		return current_user_can( 'manage_options' );
	}

	public function endpoint_fetch( WP_REST_Request $request ) {
		$total = $request->get_param( 'total' ) ? (int) $request->get_param( 'total' ) : 100;
		$per_page = $request->get_param( 'per_page' ) ? (int) $request->get_param( 'per_page' ) : 25;
		$page = $request->get_param( 'page' ) ? (int) $request->get_param( 'page' ) : 1;
		$browse = $request->get_param( 'browse' ) ? sanitize_text_field( $request->get_param( 'browse' ) ) : 'popular';
		
		$result = WPPQA_API_Fetcher::fetch_single_page( $page, $per_page, $browse, $total );

		if ( is_wp_error( $result ) ) {
			return new WP_Error( 'fetch_failed', $result->get_error_message(), [ 'status' => 500 ] );
		}

		return rest_ensure_response( $result );
	}

	public function endpoint_status() {
		$status = WPPQA_API_Fetcher::get_fetch_status();
		return rest_ensure_response( $status );
	}

	public function endpoint_plugins( WP_REST_Request $request ) {
		$orderby = $request->get_param( 'orderby' ) ? sanitize_text_field( $request->get_param( 'orderby' ) ) : 'id';
		$order = $request->get_param( 'order' ) ? sanitize_text_field( $request->get_param( 'order' ) ) : 'DESC';
		$filter = $request->get_param( 'filter' ) ? sanitize_text_field( $request->get_param( 'filter' ) ) : 'all';
		$page = $request->get_param( 'page' ) ? (int) $request->get_param( 'page' ) : 1;
		$per_page = $request->get_param( 'per_page' ) ? (int) $request->get_param( 'per_page' ) : 25;

		$plugins = WPPQA_Database::get_all_plugins( $orderby, $order, $filter );
		
		$total = count( $plugins );
		$pages = ceil( $total / $per_page );
		
		$offset = ( $page - 1 ) * $per_page;
		$paged_plugins = array_slice( $plugins, $offset, $per_page );

		return rest_ensure_response( [
			'plugins' => $paged_plugins,
			'total'   => $total,
			'pages'   => $pages
		] );
	}

	public function endpoint_stats() {
		$summary = WPPQA_Database::get_stats_summary();
		$abandoned_percent = 0;
		if ( $summary['total'] > 0 ) {
			$abandoned_percent = round( ( $summary['abandoned'] / $summary['total'] ) * 100, 2 );
		}

		$summary['abandoned_percent'] = $abandoned_percent;

		return rest_ensure_response( $summary );
	}

	public function endpoint_chart_data( WP_REST_Request $request ) {
		$type = $request->get_param( 'type' );
		$plugins = WPPQA_Database::get_all_plugins( 'id', 'ASC', 'all' );

		$data = [];

		if ( $type === 'rating' ) {
			$buckets = [ '<2★' => 0, '2-3★' => 0, '3-4★' => 0, '4-4.5★' => 0, '4.5-5★' => 0 ];
			foreach ( $plugins as $plugin ) {
				$r = $plugin['rating'] / 20; // 100 to 5
				if ( $r <= 2 ) $buckets['<2★']++;
				elseif ( $r <= 3 ) $buckets['2-3★']++;
				elseif ( $r <= 4 ) $buckets['3-4★']++;
				elseif ( $r <= 4.5 ) $buckets['4-4.5★']++;
				else $buckets['4.5-5★']++;
			}
			$data = [
				'labels' => [ '<2★', '2–3★', '3–4★', '4–4.5★', '4.5–5★' ],
				'values' => array_values( $buckets )
			];
		} elseif ( $type === 'abandonment' ) {
			$abandoned = 0;
			$active = 0;
			foreach ( $plugins as $plugin ) {
				if ( $plugin['is_abandoned'] ) {
					$abandoned++;
				} else {
					$active++;
				}
			}
			$data = [
				'labels' => [ 'Active', 'Abandoned' ],
				'values' => [ $active, $abandoned ]
			];
		} elseif ( $type === 'update_frequency' ) {
			$buckets = [ '<=30' => 0, '31-90' => 0, '91-180' => 0, '181-365' => 0, '>365' => 0 ];
			foreach ( $plugins as $plugin ) {
				$d = $plugin['days_since_update'];
				if ( $d <= 30 ) $buckets['<=30']++;
				elseif ( $d <= 90 ) $buckets['31-90']++;
				elseif ( $d <= 180 ) $buckets['91-180']++;
				elseif ( $d <= 365 ) $buckets['181-365']++;
				else $buckets['>365']++;
			}
			$data = [
				'labels' => [ '≤30 days', '31–90', '91–180', '181–365', '>365 (Abandoned)' ],
				'values' => array_values( $buckets )
			];
		} elseif ( $type === 'support' ) {
			$buckets = [ '0-25' => 0, '25-50' => 0, '50-75' => 0, '75-100' => 0 ];
			foreach ( $plugins as $plugin ) {
				$r = $plugin['resolution_rate'];
				if ( $r <= 25 ) $buckets['0-25']++;
				elseif ( $r <= 50 ) $buckets['25-50']++;
				elseif ( $r <= 75 ) $buckets['50-75']++;
				else $buckets['75-100']++;
			}
			$data = [
				'labels' => [ '0–25%', '25–50%', '50–75%', '75–100%' ],
				'values' => array_values( $buckets )
			];
		} elseif ( $type === 'health_score' ) {
			$buckets = [ '0-40' => 0, '40-60' => 0, '60-80' => 0, '80-100' => 0 ];
			foreach ( $plugins as $plugin ) {
				$h = $plugin['health_score'];
				if ( $h <= 40 ) $buckets['0-40']++;
				elseif ( $h <= 60 ) $buckets['40-60']++;
				elseif ( $h <= 80 ) $buckets['60-80']++;
				else $buckets['80-100']++;
			}
			$data = [
				'labels' => [ '0–40', '40–60', '60–80', '80–100' ],
				'values' => array_values( $buckets )
			];
		} elseif ( $type === 'scatter' ) {
			$scatter_data = [];
			foreach ( $plugins as $plugin ) {
				$scatter_data[] = [
					'x' => (float) $plugin['resolution_rate'],
					'y' => (float) $plugin['health_score']
				];
			}
			$data = [
				'points' => $scatter_data
			];
		}

		return rest_ensure_response( $data );
	}

	public function endpoint_export( WP_REST_Request $request ) {
		$format = $request->get_param( 'format' ) ? sanitize_text_field( $request->get_param( 'format' ) ) : 'csv';
		$plugins = WPPQA_Database::get_all_plugins( 'id', 'ASC', 'all' );

		$upload_dir = wp_upload_dir();
		$filename = 'wordpress-plugin-quality-dataset-' . date( 'Y-m-d' ) . '.' . $format;
		$file_path = $upload_dir['path'] . '/' . $filename;
		$file_url = $upload_dir['url'] . '/' . $filename;

		if ( $format === 'csv' ) {
			$fp = fopen( $file_path, 'w' );
			if ( ! empty( $plugins ) ) {
				$headers = array_keys( $plugins[0] );
				$headers[] = 'risk_label';
				fputcsv( $fp, $headers );

				foreach ( $plugins as $plugin ) {
					$row = array_values( $plugin );
					$risk = WPPQA_Health_Score::get_risk_label( $plugin['health_score'] );
					$row[] = $risk['label'];
					fputcsv( $fp, $row );
				}
			}
			fclose( $fp );
		} else {
			file_put_contents( $file_path, json_encode( $plugins ) );
		}

		return rest_ensure_response( [
			'success' => true,
			'url'     => $file_url
		] );
	}

	public function endpoint_clear() {
		WPPQA_Database::clear_all();
		return rest_ensure_response( [
			'success' => true,
			'message' => 'All data cleared'
		] );
	}
}
