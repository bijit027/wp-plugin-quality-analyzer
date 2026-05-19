<?php
class WPPQA_API_Fetcher {

	public static function fetch_plugins( $page = 1, $per_page = 100, $browse = 'popular' ) {
		$url = 'https://api.wordpress.org/plugins/info/1.2/';
		$args = [
			'action'  => 'query_plugins',
			'request' => [
				'page'     => $page,
				'per_page' => $per_page,
				'browse'   => $browse,
				'fields'   => [
					'active_installs'          => 1,
					'last_updated'             => 1,
					'rating'                   => 1,
					'num_ratings'              => 1,
					'support_threads'          => 1,
					'support_threads_resolved' => 1,
					'downloaded'               => 1,
					'added'                    => 1,
					'contributors'             => 1,
				]
			]
		];

		$query_string = http_build_query( $args );
		$request_url = $url . '?' . $query_string;

		$response = wp_remote_get( $request_url, [
			'timeout' => 30
		] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! $data || ! isset( $data['plugins'] ) ) {
			return new WP_Error( 'api_error', 'Invalid response from WordPress.org API' );
		}

		return $data;
	}

	public static function process_and_save( $plugins_array ) {
		$saved = 0;

		foreach ( $plugins_array as $plugin_data ) {
			$days_since_update = 0;
			$last_updated_time = strtotime( $plugin_data['last_updated'] );
			if ( $last_updated_time ) {
				$days_since_update = max( 0, (int) floor( ( time() - $last_updated_time ) / DAY_IN_SECONDS ) );
			}

			$resolution_rate = 0;
			if ( ! empty( $plugin_data['support_threads'] ) ) {
				$resolution_rate = ( $plugin_data['support_threads_resolved'] / $plugin_data['support_threads'] ) * 100;
			}

			$record = [
				'slug'                     => $plugin_data['slug'],
				'name'                     => $plugin_data['name'],
				'active_installs'          => isset( $plugin_data['active_installs'] ) ? (int) $plugin_data['active_installs'] : 0,
				'rating'                   => isset( $plugin_data['rating'] ) ? (int) $plugin_data['rating'] : 0,
				'num_ratings'              => isset( $plugin_data['num_ratings'] ) ? (int) $plugin_data['num_ratings'] : 0,
				'last_updated'             => $last_updated_time ? gmdate( 'Y-m-d H:i:s', $last_updated_time ) : null,
				'days_since_update'        => $days_since_update,
				'support_threads'          => isset( $plugin_data['support_threads'] ) ? (int) $plugin_data['support_threads'] : 0,
				'support_threads_resolved' => isset( $plugin_data['support_threads_resolved'] ) ? (int) $plugin_data['support_threads_resolved'] : 0,
				'resolution_rate'          => round( $resolution_rate, 2 ),
				'downloaded'               => isset( $plugin_data['downloaded'] ) ? (int) $plugin_data['downloaded'] : 0,
				'added'                    => isset( $plugin_data['added'] ) ? gmdate( 'Y-m-d', strtotime( $plugin_data['added'] ) ) : null,
			];

			$record['health_score'] = WPPQA_Health_Score::calculate( $record );
			$record['is_abandoned'] = ( $days_since_update > 365 ) ? 1 : 0;
			$record['fetched_at']   = current_time( 'mysql' );

			WPPQA_Database::upsert_plugin( $record );
			$saved++;
		}

		return $saved;
	}

	public static function fetch_all_pages( $total_plugins, $per_page = 25, $browse = 'popular' ) {
		$total_pages = ceil( $total_plugins / $per_page );
		$fetched = 0;
		$saved = 0;
		$errors = [];

		self::update_status( 'running', 0, $total_plugins, 'Starting fetch...' );

		for ( $page = 1; $page <= $total_pages; $page++ ) {
			$response = self::fetch_plugins( $page, $per_page, $browse );

			if ( is_wp_error( $response ) ) {
				$errors[] = $response->get_error_message();
				self::update_status( 'error', $fetched, $total_plugins, 'API Error on page ' . $page . ': ' . $response->get_error_message() );
				break;
			}

			$plugins_on_page = $response['plugins'];
			$fetched += count( $plugins_on_page );
			$saved += self::process_and_save( $plugins_on_page );

			self::update_status( 'running', $fetched, $total_plugins, 'Fetched page ' . $page . ' of ' . $total_pages );

			if ( $fetched >= $total_plugins ) {
				break;
			}

			sleep( 1 );
		}

		if ( empty( $errors ) ) {
			self::update_status( 'complete', $fetched, $total_plugins, 'Fetch completed successfully' );
		}

		return [
			'fetched' => $fetched,
			'saved'   => $saved,
			'errors'  => $errors,
		];
	}

	public static function fetch_single_page( $page = 1, $per_page = 25, $browse = 'popular', $total = 100 ) {
		self::update_status( 'running', ( $page - 1 ) * $per_page, $total, 'Fetching page ' . $page );

		$response = self::fetch_plugins( $page, $per_page, $browse );

		if ( is_wp_error( $response ) ) {
			self::update_status( 'error', ( $page - 1 ) * $per_page, $total, 'API Error on page ' . $page . ': ' . $response->get_error_message() );
			return $response;
		}

		$plugins_on_page = $response['plugins'];
		$saved = self::process_and_save( $plugins_on_page );
		$fetched = ( $page - 1 ) * $per_page + count( $plugins_on_page );

		if ( $fetched >= $total || count( $plugins_on_page ) < $per_page ) {
			self::update_status( 'complete', $fetched, $total, 'Fetch completed successfully' );
		} else {
			self::update_status( 'running', $fetched, $total, 'Fetched page ' . $page );
		}

		return [
			'success' => true,
			'fetched' => $fetched,
			'saved'   => $saved,
			'page'    => $page,
		];
	}

	public static function update_status( $status, $fetched, $total, $message ) {
		set_transient( 'wppqa_fetch_status', [
			'status'  => $status,
			'fetched' => $fetched,
			'total'   => $total,
			'message' => $message,
		], HOUR_IN_SECONDS );
	}

	public static function get_fetch_status() {
		$status = get_transient( 'wppqa_fetch_status' );
		if ( ! $status ) {
			return [
				'status'  => 'idle',
				'fetched' => 0,
				'total'   => 0,
				'message' => 'Ready to fetch plugins',
			];
		}
		return $status;
	}
}
