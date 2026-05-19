<?php
class WPPQA_Statistics {

	// Invert matrix using Gaussian Elimination with partial pivoting
	public static function invert_matrix( $matrix ) {
		$n = count( $matrix );
		$identity = [];
		for ( $i = 0; $i < $n; $i++ ) {
			$identity[$i] = array_fill( 0, $n, 0.0 );
			$identity[$i][$i] = 1.0;
		}
		
		for ( $i = 0; $i < $n; $i++ ) {
			// Find pivot
			$max_val = abs( $matrix[$i][$i] );
			$max_row = $i;
			for ( $k = $i + 1; $k < $n; $k++ ) {
				if ( abs( $matrix[$k][$i] ) > $max_val ) {
					$max_val = abs( $matrix[$k][$i] );
					$max_row = $k;
				}
			}
			
			if ( $max_val < 1e-12 ) {
				return null; // Singular matrix
			}
			
			// Swap rows if necessary
			if ( $max_row !== $i ) {
				$tmp = $matrix[$i]; $matrix[$i] = $matrix[$max_row]; $matrix[$max_row] = $tmp;
				$tmp = $identity[$i]; $identity[$i] = $identity[$max_row]; $identity[$max_row] = $tmp;
			}
			
			$pivot = $matrix[$i][$i];
			
			// Normalize pivot row
			for ( $j = 0; $j < $n; $j++ ) {
				$matrix[$i][$j] /= $pivot;
				$identity[$i][$j] /= $pivot;
			}
			
			// Eliminate other column values
			for ( $k = 0; $k < $n; $k++ ) {
				if ( $k === $i ) continue;
				$factor = $matrix[$k][$i];
				for ( $j = 0; $j < $n; $j++ ) {
					$matrix[$k][$j] -= $factor * $matrix[$i][$j];
					$identity[$k][$j] -= $factor * $identity[$i][$j];
				}
			}
		}
		return $identity;
	}

	// Normal Cumulative Distribution Function (Abramowitz & Stegun approximation)
	public static function normal_cdf( $z ) {
		$t = 1.0 / ( 1.0 + 0.2316419 * abs( $z ) );
		$d = 0.3989422804 * exp( -$z * $z / 2.0 );
		$prob = $d * $t * ( 0.31938153 + $t * ( -0.356563782 + $t * ( 1.781477937 + $t * ( -1.821255978 + $t * 1.330274429 ) ) ) );
		if ( $z > 0 ) return 1.0 - $prob;
		return $prob;
	}

	// Student's t cumulative distribution function approximation
	public static function student_t_p_value( $t, $df ) {
		if ( $df <= 0 ) return 1.0;
		// Z-score approximation for t-distribution (extremely accurate for df > 10)
		$z = $t * ( 1.0 - 1.0 / ( 4.0 * $df ) );
		return self::normal_cdf( -abs( $z ) ) * 2.0; // Two-tailed p-value
	}

	public static function calculate_p_value( $r, $n ) {
		if ( $n <= 2 ) return 1.0;
		if ( abs( $r ) >= 1.0 ) return 0.0;
		$t = abs( $r ) * sqrt( ( $n - 2 ) / ( 1.0 - $r * $r ) );
		return self::student_t_p_value( $t, $n - 2 );
	}

	// Pearson Correlation Coefficient
	public static function pearson_correlation( $x, $y ) {
		$n = count( $x );
		if ( $n === 0 ) return [ 'r' => 0.0, 'p' => 1.0 ];
		
		$mean_x = array_sum( $x ) / $n;
		$mean_y = array_sum( $y ) / $n;
		
		$num = 0.0;
		$den_x = 0.0;
		$den_y = 0.0;
		
		for ( $i = 0; $i < $n; $i++ ) {
			$dx = $x[$i] - $mean_x;
			$dy = $y[$i] - $mean_y;
			$num += $dx * $dy;
			$den_x += $dx * $dx;
			$den_y += $dy * $dy;
		}
		
		if ( $den_x == 0 || $den_y == 0 ) {
			return [ 'r' => 0.0, 'p' => 1.0 ];
		}
		
		$r = $num / sqrt( $den_x * $den_y );
		$p = self::calculate_p_value( $r, $n );
		
		return [ 'r' => round( $r, 4 ), 'p' => $p ];
	}

	// Fractional Ranking helper for Spearman correlation
	public static function ranks( $arr ) {
		$n = count( $arr );
		$pairs = [];
		foreach ( $arr as $i => $val ) {
			$pairs[] = [ 'index' => $i, 'val' => $val ];
		}
		
		usort( $pairs, function( $a, $b ) {
			if ( $a['val'] == $b['val'] ) return 0;
			return ( $a['val'] < $b['val'] ) ? -1 : 1;
		} );
		
		$ranks = array_fill( 0, $n, 0.0 );
		$i = 0;
		while ( $i < $n ) {
			$j = $i + 1;
			while ( $j < $n && $pairs[$j]['val'] == $pairs[$i]['val'] ) {
				$j++;
			}
			
			$rank = ( $i + 1 + $j ) / 2.0;
			for ( $k = $i; $k < $j; $k++ ) {
				$ranks[$pairs[$k]['index']] = $rank;
			}
			$i = $j;
		}
		return $ranks;
	}

	// Spearman Correlation Coefficient
	public static function spearman_correlation( $x, $y ) {
		$rank_x = self::ranks( $x );
		$rank_y = self::ranks( $y );
		return self::pearson_correlation( $rank_x, $rank_y );
	}

	// Multiple OLS Linear Regression solver
	public static function linear_regression( $X, $y ) {
		$n = count( $X );
		if ( $n === 0 ) return null;
		$p = count( $X[0] );
		
		// Compute X^T * X
		$XTX = [];
		for ( $i = 0; $i < $p; $i++ ) {
			$XTX[$i] = array_fill( 0, $p, 0.0 );
			for ( $j = 0; $j < $p; $j++ ) {
				$sum = 0.0;
				for ( $k = 0; $k < $n; $k++ ) {
					$sum += $X[$k][$i] * $X[$k][$j];
				}
				$XTX[$i][$j] = $sum;
			}
		}
		
		// Invert X^T * X
		$invXTX = self::invert_matrix( $XTX );
		if ( !$invXTX ) return null;
		
		// Compute X^T * y
		$XTy = array_fill( 0, $p, 0.0 );
		for ( $i = 0; $i < $p; $i++ ) {
			$sum = 0.0;
			for ( $k = 0; $k < $n; $k++ ) {
				$sum += $X[$k][$i] * $y[$k];
			}
			$XTy[$i] = $sum;
		}
		
		// Compute Beta = (X^T * X)^-1 * X^T * y
		$beta = array_fill( 0, $p, 0.0 );
		for ( $i = 0; $i < $p; $i++ ) {
			$sum = 0.0;
			for ( $j = 0; $j < $p; $j++ ) {
				$sum += $invXTX[$i][$j] * $XTy[$j];
			}
			$beta[$i] = $sum;
		}
		
		// Compute R^2
		$mean_y = array_sum( $y ) / $n;
		$ss_tot = 0.0;
		$ss_res = 0.0;
		for ( $i = 0; $i < $n; $i++ ) {
			$pred = 0.0;
			for ( $j = 0; $j < $p; $j++ ) {
				$pred += $X[$i][$j] * $beta[$j];
			}
			$ss_tot += ( $y[$i] - $mean_y ) * ( $y[$i] - $mean_y );
			$ss_res += ( $y[$i] - $pred ) * ( $y[$i] - $pred );
		}
		
		$r2 = $ss_tot == 0 ? 0.0 : ( 1.0 - ( $ss_res / $ss_tot ) );
		
		// Calculate SEs and p-values for coefficients
		$variance_residual = $n <= $p ? 0.0 : ( $ss_res / ( $n - $p ) );
		$std_errors = [];
		$p_values = [];
		$t_stats = [];
		
		for ( $j = 0; $j < $p; $j++ ) {
			$se = sqrt( max( 0.0, $variance_residual * $invXTX[$j][$j] ) );
			$std_errors[$j] = $se;
			$t = $se == 0.0 ? 0.0 : ( $beta[$j] / $se );
			$t_stats[$j] = $t;
			$p_values[$j] = self::student_t_p_value( abs( $t ), $n - $p );
		}
		
		return [
			'coefficients' => $beta,
			'std_errors'   => $std_errors,
			't_stats'      => $t_stats,
			'p_values'     => $p_values,
			'r2'           => round( $r2, 4 ),
		];
	}

	public static function calculate_research_metrics() {
		$plugins = WPPQA_Database::get_all_plugins( 'id', 'ASC', 'all' );
		if ( empty( $plugins ) ) return null;
		
		$n = count( $plugins );
		
		$ratings = [];
		$days_since_update = [];
		$resolution_rates = [];
		$health_scores = [];
		$active_installs_log = [];
		$downloads_log = [];
		$support_threads_log = [];
		
		$recency_scores = [];
		$support_efficiencies = [];
		$engagement_scores = [];
		
		foreach ( $plugins as $p ) {
			$r = (float) $p['rating'];
			$ratings[] = $r / 100.0;
			
			$days = (float) $p['days_since_update'];
			$days_since_update[] = $days;
			
			$res = (float) $p['resolution_rate'];
			$resolution_rates[] = $res;
			
			$h = (float) $p['health_score'];
			$health_scores[] = $h;
			
			$inst = (float) $p['active_installs'];
			$inst_log = log( 1.0 + $inst );
			$active_installs_log[] = $inst_log;
			
			$down = isset( $p['downloaded'] ) ? (float) $p['downloaded'] : 0.0;
			$down_log = log( 1.0 + $down );
			$downloads_log[] = $down_log;
			
			$supp = isset( $p['support_threads'] ) ? (float) $p['support_threads'] : 0.0;
			$supp_log = log( 1.0 + $supp );
			$support_threads_log[] = $supp_log;
			
			// Engineered features
			$recency_scores[] = 1.0 / ( 1.0 + $days );
			$supp_res = isset( $p['support_threads_resolved'] ) ? (float) $p['support_threads_resolved'] : 0.0;
			$support_efficiencies[] = $supp_res / ( $supp + 1.0 );
			$engagement_scores[] = $inst_log + $down_log;
		}
		
		// 1. Calculations - Correlations
		$corr_rating_days = self::pearson_correlation( $ratings, $days_since_update );
		$spearman_rating_days = self::spearman_correlation( $ratings, $days_since_update );
		
		$corr_rating_res = self::pearson_correlation( $ratings, $resolution_rates );
		$spearman_rating_res = self::spearman_correlation( $ratings, $resolution_rates );
		
		$corr_health_res = self::pearson_correlation( $health_scores, $resolution_rates );
		$spearman_health_res = self::spearman_correlation( $health_scores, $resolution_rates );
		
		$corr_health_recency = self::pearson_correlation( $health_scores, $recency_scores );
		$spearman_health_recency = self::spearman_correlation( $health_scores, $recency_scores );
		
		$corr_installs_health = self::pearson_correlation( $active_installs_log, $health_scores );
		$spearman_installs_health = self::spearman_correlation( $active_installs_log, $health_scores );
		
		$corr_rating_installs = self::pearson_correlation( $ratings, $active_installs_log );
		$spearman_rating_installs = self::spearman_correlation( $ratings, $active_installs_log );
		
		// 2. Calculations - Regression
		$X = [];
		for ( $i = 0; $i < $n; $i++ ) {
			$X[] = [
				1.0, // Intercept
				$ratings[$i],
				$resolution_rates[$i],
				$recency_scores[$i],
				$active_installs_log[$i]
			];
		}
		$regression = self::linear_regression( $X, $health_scores );
		
		return [
			'total_sample' => $n,
			'correlations' => [
				'rating_vs_days' => [
					'feature_x' => 'User Rating',
					'feature_y' => 'Days Since Update',
					'pearson'   => $corr_rating_days['r'],
					'spearman'  => $spearman_rating_days['r'],
					'p_value'   => $corr_rating_days['p']
				],
				'rating_vs_res' => [
					'feature_x' => 'User Rating',
					'feature_y' => 'Support Resolution Rate',
					'pearson'   => $corr_rating_res['r'],
					'spearman'  => $spearman_rating_res['r'],
					'p_value'   => $corr_rating_res['p']
				],
				'health_vs_res' => [
					'feature_x' => 'Plugin Health Score',
					'feature_y' => 'Support Resolution Rate',
					'pearson'   => $corr_health_res['r'],
					'spearman'  => $spearman_health_res['r'],
					'p_value'   => $corr_health_res['p']
				],
				'health_vs_recency' => [
					'feature_x' => 'Plugin Health Score',
					'feature_y' => 'Recency Score',
					'pearson'   => $corr_health_recency['r'],
					'spearman'  => $spearman_health_recency['r'],
					'p_value'   => $corr_health_recency['p']
				],
				'installs_vs_health' => [
					'feature_x' => 'Log Active Installs',
					'feature_y' => 'Plugin Health Score',
					'pearson'   => $corr_installs_health['r'],
					'spearman'  => $spearman_installs_health['r'],
					'p_value'   => $spearman_installs_health['p']
				],
				'rating_vs_installs' => [
					'feature_x' => 'User Rating',
					'feature_y' => 'Log Active Installs',
					'pearson'   => $corr_rating_installs['r'],
					'spearman'  => $spearman_rating_installs['r'],
					'p_value'   => $spearman_rating_installs['p']
				]
			],
			'regression' => $regression ? [
				'r2' => $regression['r2'],
				'coefficients' => [
					[
						'variable' => 'Intercept (Constant)',
						'val'      => round( $regression['coefficients'][0], 4 ),
						'se'       => round( $regression['std_errors'][0], 4 ),
						't'        => round( $regression['t_stats'][0], 4 ),
						'p'        => $regression['p_values'][0]
					],
					[
						'variable' => 'User Rating (Normalized)',
						'val'      => round( $regression['coefficients'][1], 4 ),
						'se'       => round( $regression['std_errors'][1], 4 ),
						't'        => round( $regression['t_stats'][1], 4 ),
						'p'        => $regression['p_values'][1]
					],
					[
						'variable' => 'Support Resolution Rate (%)',
						'val'      => round( $regression['coefficients'][2], 4 ),
						'se'       => round( $regression['std_errors'][2], 4 ),
						't'        => round( $regression['t_stats'][2], 4 ),
						'p'        => $regression['p_values'][2]
					],
					[
						'variable' => 'Recency Score (1/(1+Days))',
						'val'      => round( $regression['coefficients'][3], 4 ),
						'se'       => round( $regression['std_errors'][3], 4 ),
						't'        => round( $regression['t_stats'][3], 4 ),
						'p'        => $regression['p_values'][3]
					],
					[
						'variable' => 'Log Active Installs',
						'val'      => round( $regression['coefficients'][4], 4 ),
						'se'       => round( $regression['std_errors'][4], 4 ),
						't'        => round( $regression['t_stats'][4], 4 ),
						'p'        => $regression['p_values'][4]
					]
				]
			] : null,
			'hypothesis_tests' => [
				[
					'id'          => 'H1',
					'hypothesis'  => 'Update frequency (recency_score) significantly correlates with plugin user rating.',
					'p_value'     => $corr_rating_days['p'],
					'significant' => $corr_rating_days['p'] < 0.05,
					'r_value'     => $corr_rating_days['r'],
					'result'      => $corr_rating_days['p'] < 0.05 ? 'SUPPORTED' : 'REJECTED'
				],
				[
					'id'          => 'H2',
					'hypothesis'  => 'Support responsiveness (resolution_rate) is a strong positive predictor of plugin health score.',
					'p_value'     => $corr_health_res['p'],
					'significant' => $corr_health_res['p'] < 0.05,
					'r_value'     => $corr_health_res['r'],
					'result'      => ( $corr_health_res['p'] < 0.05 && $corr_health_res['r'] > 0.3 ) ? 'STRONG SUPPORT' : ( $corr_health_res['p'] < 0.05 ? 'SUPPORTED' : 'REJECTED' )
				],
				[
					'id'          => 'H3',
					'hypothesis'  => 'User ratings show ceiling effects, exhibiting weak predictive value for overall plugin health.',
					'p_value'     => self::pearson_correlation( $ratings, $health_scores )['p'],
					'significant' => self::pearson_correlation( $ratings, $health_scores )['p'] < 0.05,
					'r_value'     => self::pearson_correlation( $ratings, $health_scores )['r'],
					'result'      => abs( self::pearson_correlation( $ratings, $health_scores )['r'] ) < 0.3 ? 'SUPPORTED (Ceiling Confirmed)' : 'REJECTED'
				]
			]
		];
	}
}
