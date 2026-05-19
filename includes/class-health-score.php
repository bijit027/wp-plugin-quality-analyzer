<?php
class WPPQA_Health_Score {

	public static function calculate( $plugin_data ) {
		$breakdown = self::get_score_breakdown( $plugin_data );
		return $breakdown['final_score'];
	}

	public static function get_risk_label( $score ) {
		if ( $score >= 70 ) {
			return [ 'label' => 'Healthy', 'color' => 'green' ];
		} elseif ( $score >= 50 ) {
			return [ 'label' => 'Moderate', 'color' => 'orange' ];
		} elseif ( $score >= 30 ) {
			return [ 'label' => 'At Risk', 'color' => 'red' ];
		} else {
			return [ 'label' => 'Abandoned', 'color' => 'darkred' ];
		}
	}

	public static function get_score_breakdown( $plugin_data ) {
		$days_since_update = isset( $plugin_data['days_since_update'] ) ? (int) $plugin_data['days_since_update'] : 0;
		if ( ! isset( $plugin_data['days_since_update'] ) && isset( $plugin_data['last_updated'] ) ) {
			$last_updated = strtotime( $plugin_data['last_updated'] );
			if ( $last_updated ) {
				$days_since_update = (int) max( 0, floor( ( time() - $last_updated ) / DAY_IN_SECONDS ) );
			}
		}

		$update_score = max( 0, 1 - ( $days_since_update / 365 ) );
		
		$rating = isset( $plugin_data['rating'] ) ? (float) $plugin_data['rating'] : 0;
		$rating_score = $rating / 100;
		
		$support_threads = isset( $plugin_data['support_threads'] ) ? (int) $plugin_data['support_threads'] : 0;
		$support_threads_resolved = isset( $plugin_data['support_threads_resolved'] ) ? (int) $plugin_data['support_threads_resolved'] : 0;
		$resolution_score = ( $support_threads > 0 ) ? ( $support_threads_resolved / $support_threads ) : 0.5;
		
		$response_score = 1.0;

		$weights = [ 'update' => 0.30, 'rating' => 0.25, 'resolution' => 0.25, 'response' => 0.20 ];

		$phs = ( $update_score * $weights['update'] ) + 
			   ( $rating_score * $weights['rating'] ) + 
			   ( $resolution_score * $weights['resolution'] ) + 
			   ( $response_score * $weights['response'] );

		$final = round( $phs * 100, 2 );

		return [
			'update_score'     => $update_score,
			'rating_score'     => $rating_score,
			'resolution_score' => $resolution_score,
			'response_score'   => $response_score,
			'final_score'      => $final,
			'risk_label'       => self::get_risk_label( $final ),
			'weights'          => $weights
		];
	}

	public static function recalculate_all() {
		$plugins = WPPQA_Database::get_all_plugins( 'id', 'ASC', 'all' );
		$count = 0;

		foreach ( $plugins as $plugin ) {
			$health_score = self::calculate( $plugin );
			$is_abandoned = ( $plugin['days_since_update'] > 365 ) ? 1 : 0;

			$plugin['health_score'] = $health_score;
			$plugin['is_abandoned'] = $is_abandoned;

			WPPQA_Database::upsert_plugin( $plugin );
			$count++;
		}

		return $count;
	}
}
