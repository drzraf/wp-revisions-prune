<?php

/**
 * Revisions list pruner
 *
 * @package drzraf/wp-revisions-prune
 *
 * Copyright (c) 2021 Raphaël . Droz + floss @ gmail DOT com
 */

namespace WP_CLI\WpRevisionsPrune;

use WP_CLI;

class RevisionsPruner extends \WP_CLI_Command {


	/**
	 * Output of a list of keep/prune revision IDs from the input of `wp revisions list`
	 *
	 * ## OPTIONS
	 *
	 * [--file=<file>]
	 * : Use input CSV file. (stdin otherwise or if <file> does not exists)
	 *
	 * [--keep-last=<number>]
	 * : Keep at least <number> revisions.
	 *
	 * [--keep-hourly=<number>]
	 * : Number of hourly revisions to keep.
	 *
	 * [--keep-daily=<number>]
	 * : Number of daily revisions to keep.
	 *
	 * [--keep-weekly=<number>]
	 * : Number of weekly revisions to keep.
	 *
	 * [--keep-monthly=<number>]
	 * : Number of monthly revisions to keep.
	 *
	 * [--keep-yearly=<number>]
	 * : Number of yearly revisions to keep.
	 *
	 * [--keep-less-than-n-rev=<number>]
	 * : Disregard pruning revisions of posts having less than <number> revisions.
	 *
	 * [--keep-before=<yyyy-mm-dd>]
	 * : Keep revisions published on or before this date.
	 *
	 * [--keep-after=<yyyy-mm-dd>]
	 * : Keep revisions published on or after this date.
	 *
	 * [--list]
	 * : Output verbose list of revisions it keeps/prunes
	 * With --list=removed only output the flat list of post ID to remove.
	 *
	 * ## EXAMPLES
	 *      wp revisions list --format=csv --fields=ID,post_name,post_date_gmt --yes | wp revisions prune --keep-daily=1 --list
	 *      wp post list --post_type=revision --format=csv --fields=ID,post_name,post_date_gmt | wp revisions prune --keep-hourly=2 --keep-last=5 --list=removed
	 *
	 */
	public function prune( $args, $assoc_args ) {
		$file      = ! empty( $assoc_args['file'] ) ? $assoc_args['file'] : false;
		$show_list = ! empty( $assoc_args['list'] ) ? $assoc_args['list'] : false;
		$content   = is_readable( $file ) ? file( $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ) : array_filter( explode( PHP_EOL, stream_get_contents( STDIN, 1024 * 1024 ) ) );

		$csv = array_map( 'str_getcsv', $content );
		if ( ! $csv ) {
			return;
		}

		// Remove the CSV header
		if ( 'ID' === $csv[0][0] ) {
			array_shift( $csv );
		}

		$policy = [
			'_last'        => isset( $assoc_args['keep-last'] ) && is_numeric( $assoc_args['keep-last'] ) ? intval( $assoc_args['keep-last'] ) : false,
			'_min_rev'     => isset( $assoc_args['keep-less-than-n-rev'] ) && is_numeric( $assoc_args['keep-less-than-n-rev'] ) ? intval( $assoc_args['keep-less-than-n-rev'] ) : false,
			'_keep_before' => isset( $assoc_args['keep-before'] ) ? strtotime( $assoc_args['keep-before'] ) : false,
			'_keep_after'  => isset( $assoc_args['keep-after'] ) ? strtotime( $assoc_args['keep-after'] ) : false,
			'hour'         => isset( $assoc_args['keep-hourly'] ) && is_numeric( $assoc_args['keep-hourly'] ) ? intval( $assoc_args['keep-hourly'] ) : false,
			'day'          => isset( $assoc_args['keep-daily'] ) && is_numeric( $assoc_args['keep-daily'] ) ? intval( $assoc_args['keep-daily'] ) : false,
			'week'         => isset( $assoc_args['keep-weekly'] ) && is_numeric( $assoc_args['keep-weekly'] ) ? intval( $assoc_args['keep-weekly'] ) : false,
			'month'        => isset( $assoc_args['keep-monthly'] ) && is_numeric( $assoc_args['keep-monthly'] ) ? intval( $assoc_args['keep-monthly'] ) : false,
			'year'         => isset( $assoc_args['keep-yearly'] ) && is_numeric( $assoc_args['keep-yearly'] ) ? intval( $assoc_args['keep-yearly'] ) : false,
		];

		$results = self::parse_csv( $csv );
		$remove  = self::blacklist_revisions( $results, $policy );

		if ( 'removed' === $show_list ) {
			asort( $remove, SORT_NUMERIC );
			WP_CLI::log( implode( PHP_EOL, $remove ) );
			return;
		} elseif ( $show_list ) {
			self::show_list( $results, $remove );
		}
		WP_CLI::success( sprintf( 'Prune %d revisions out of %s among %s parent posts', count( $remove ), count( $csv ), count( $results ) ) );
	}

	private static function parse_csv( $csv ) {
		$results = [];
		array_walk(
			$csv,
			function ( &$a ) use ( $csv, &$results ) {
				if ( ! preg_match( '/^\d+-(revision|autosave)-v1$/', $a[1] ) ) {
					return;
				}
				$parent = explode( '-', $a[1] )[0];
				$date   = strtotime( $a[2] );
				// Note: We may have multiple revision for an identical timestamp
				$results[ $parent ][] = [ $date, $a ];
			}
		);

		foreach ( $results as $revisions ) {
			usort(
				$revisions,
				function ( $a, $b ) {
					return $a[0] > $b[0];
				}
			);
		}

		return $results;
	}

	/**
	 * This logic (in particular the "--keep-last=<N>" assume revisions withint $results
	 * are sort in the reverse chronological order (newest first)
	 */
	private static function blacklist_revisions( $results, $policy ) {

		$policy_map = [
			'hour'  => 'Y-m-d-H',
			'day'   => 'Y-m-d',
			'week'  => 'Y-W',
			'month' => 'Y-m',
			'year'  => 'Y',
		];

		$remove = [];
		foreach ( $results as $parent_id => $revisions ) {
			if ( $policy['_min_rev'] && count( $revisions ) <= $policy['_min_rev'] ) {
				WP_CLI::debug( "[min-rev] preserves all revisions of $parent_id" );
				continue;
			}

			if ( $policy['_last'] && count( $revisions ) <= $policy['_last'] ) {
				WP_CLI::debug( "[keep-last] preserves all revisions of $parent_id" );
				continue;
			}

			$found = [
				'hour'  => [],
				'day'   => [],
				'week'  => [],
				'month' => [],
				'year'  => [],
			];

			$last_kept = 0;
			foreach ( $revisions as $revision ) {
				list($date, $data) = $revision;
				$id                = $data[0];
				$preserve          = false;

				if ( $policy['_keep_before'] && $date <= $policy['_keep_before'] ) {
					WP_CLI::debug( "[keep-before] preserves $id" );
					continue;
				}

				if ( $policy['_keep_after'] && $date >= $policy['_keep_after'] ) {
					WP_CLI::debug( "[keep-after] preserves $id" );
					continue;
				}

				if ( $policy['_last'] && $last_kept <= $policy['_last'] ) {
					$last_kept++;
					WP_CLI::debug( "[keep-last] preserves $id" );
					continue;
				}

				foreach ( $policy_map as $policy_name => $policy_time_format ) {
					$up_to = $policy[ $policy_name ];
					// No policy set for this time interval.
					if ( false === $up_to ) {
						continue;
					}

					// No revision to compare to. Store (unless we want 0 revision for that time interval.
					if ( $up_to && ! $found[ $policy_name ] ) {
						$found[ $policy_name ] = [ $date ];
						break;
					}

					// New hour/day/week/month/year. Start a new round and mark this revision at non-removable
					// by other time interval rues.
					if ( $up_to && gmdate( $policy_time_format, end( $found[ $policy_name ] ) ) !== gmdate( $policy_time_format, $date ) ) {
						$found[ $policy_name ] = [ $date ];
						$preserve              = true;
						continue;
					}

					// If no (more) revision wanted. Drop this (and subsequents) revision.
					// (And do not consider wider time intervals)
					if ( ! $preserve && ( ! $up_to || count( $found[ $policy_name ] ) >= $policy[ $policy_name ] ) ) {
						WP_CLI::debug( "[$policy_name] says remove $id" );
						$remove[] = $id;
						break;
					}

					// Otherwise, preserve this one.
					$found[ $policy_name ][] = $date;
				}
			}
		}

		return $remove;
	}

	private static function show_list( $results, $remove ) {
		foreach ( $results as $parent_id => $revisions ) {
			$removed_revisions = 0;
			foreach ( $revisions as $revision ) {
				$data      = $revision[1];
				$id        = $data[0];
				$to_remove = in_array( $id, $remove, true );
				WP_CLI::log( implode( "\t", array_values( $data ) ) . ( $to_remove ? "\t[remove]" : '' ) );
				if ( $to_remove ) {
					$removed_revisions++;
				}
			}
			if ( $removed_revisions ) {
				WP_CLI::debug( sprintf( "[% 5d] => remove %d out of %d revisions\n", $parent_id, $removed_revisions, count( $revisions ) ) );
			}
		}
	}
}
