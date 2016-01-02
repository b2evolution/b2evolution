<?php
/**
 * This file implements the Hitlist class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package evocore
 *
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * A list of hits. Provides functions for maintaining and extraction of Hits.
 *
 * @package evocore
 */
class Hitlist
{
	/**
	 * Delete all hits for a specific date
	 *
	 * @param int unix timestamp to delete hits for
	 * @return mixed Return value of {@link DB::query()}
	 */
	static function prune( $date )
	{
		global $DB;

		$sql = 'DELETE FROM T_hitlog
			WHERE DATE_FORMAT( hit_datetime, "%Y-%m-%d" ) = '.$DB->quote( date( 'Y-m-d', $date ) );

		return $DB->query( $sql, 'Prune hits for a specific date' );
	}


	/**
	 * Change type for a hit
	 *
	 * @param int ID to change
	 * @param string new type, must be valid ENUM for hit_referer_type field
	 * @return mixed Return value of {@link DB::query()}
	 */
	static function change_type( $hit_ID, $type )
	{
		global $DB;

		$sql = '
				UPDATE T_hitlog
				   SET hit_referer_type = '.$DB->quote( $type ).',
				       hit_datetime = hit_datetime ' /* prevent mySQL from updating timestamp */ .'
				 WHERE hit_ID = '.$DB->quote( $hit_ID );
		return $DB->query( $sql, 'Change type for a specific hit' );
	}


	/**
	 * Auto pruning of old stats.
	 *
	 * It uses a general setting to store the day of the last prune, avoiding multiple prunes per day.
	 * fplanque>> Check: How much faster is this than DELETING right away with an INDEX on the date field?
	 *
	 * Note: we're using {@link $localtimenow} to log hits, so use this for pruning, too.
	 *
	 * NOTE: do not call this directly, but only in conjuction with auto_prune_stats_mode.
	 *
	 * @return array array(
	 *   'result'  => 'error' | 'ok'
	 *   'message' => Message of the error or result data
	 * )
	 */
	static function dbprune()
	{
		/**
		 * @var DB
		 */
		global $DB;
		global $Debuglog, $Settings, $localtimenow;
		global $Plugins, $Messages;

		// Prune when $localtime is a NEW day (which will be the 1st request after midnight):
		$last_prune = $Settings->get( 'auto_prune_stats_done' );
		if( $last_prune >= date( 'Y-m-d', $localtimenow ) && $last_prune <= date( 'Y-m-d', $localtimenow + 86400 ) )
		{ // Already pruned today (and not more than one day in the future -- which typically never happens)
			$message = T_('Pruning has already been done today');
			$Messages->add( $message, 'error' );
			return array(
					'result'  => 'error',
					'message' => $message
				);
		}

		// Get tables info
		global $db_config;
		$tables = array();
		$tables_info = $DB->get_results( 'SHOW TABLE STATUS WHERE Name IN ( '.$DB->quote( array( 'T_hitlog', 'T_sessions', 'T_basedomains' ) ).' )' );
		foreach( $tables_info as $table_info )
		{
			$tables[ $table_info->Name ] = array(
					'type' => $table_info->Engine,
					'rows' => $table_info->Rows
				);
		}

		// Init Timer for hitlist
		// Note: Don't use global $Timer because it works only in debug mode
		load_class( '_core/model/_timer.class.php', 'Timer' );
		$hitlist_Timer = new Timer( 'prune_hits' );

		$time_prune_before = ( $localtimenow - ( $Settings->get( 'auto_prune_stats' ) * 86400 ) ); // 1 day = 86400 seconds


		// PRUNE HITLOG:
		$hitlist_Timer->start( 'hitlog' );
		$hitlog_rows_affected = $DB->query( "
			DELETE FROM T_hitlog
			WHERE hit_datetime < '".date( 'Y-m-d', $time_prune_before )."'", 'Autopruning hit log' );
		$hitlist_Timer->stop( 'hitlog' );
		$Debuglog->add( 'Hitlist::dbprune(): autopruned '.$hitlog_rows_affected.' rows from T_hitlog.', 'request' );


		// PREPARE PRUNING SESSIONS: 
		// Prune sessions that have timed out and are older than auto_prune_stats
		$sess_prune_before = ($localtimenow - $Settings->get( 'timeout_sessions' ));
		// IMPORTANT: we cut off at the oldest date between session timeout and sessions pruning.
		// So if session timeout is really long (2 years for example), the sessions table won't be pruned as small as expected from the pruning delay.
		$smaller_time = min( $sess_prune_before, $time_prune_before );

		// allow plugins to prune session based data
		$Plugins->trigger_event( 'BeforeSessionsDelete', $temp_array = array( 'cutoff_timestamp' => $smaller_time ) );

		// PRUNE SESSIONS:
		$hitlist_Timer->start( 'sessions' );
		$sessions_rows_affected = $DB->query( 'DELETE FROM T_sessions WHERE sess_lastseen_ts < '.$DB->quote( date( 'Y-m-d H:i:s', $smaller_time ) ), 'Autoprune sessions' );
		$hitlist_Timer->stop( 'sessions' );
		$Debuglog->add( 'Hitlist::dbprune(): autopruned '.$sessions_rows_affected.' rows from T_sessions.', 'request' );


		// PRUNE BASEDOMAINS:
		// Prune non-referrered basedomains (where the according hits got deleted)
		// BUT only those with unknown dom_type/dom_status, because otherwise this
		//     info is useful when we get hit again.
		$hitlist_Timer->start( 'basedomains' );
		$basedomains_rows_affected = $DB->query( "
			DELETE T_basedomains
			  FROM T_basedomains LEFT JOIN T_hitlog ON hit_referer_dom_ID = dom_ID
			 WHERE hit_referer_dom_ID IS NULL
			 AND dom_type = 'unknown'
			 AND dom_status = 'unknown'" );
		$hitlist_Timer->stop( 'basedomains' );
		$Debuglog->add( 'Hitlist::dbprune(): autopruned '.$basedomains_rows_affected.' rows from T_basedomains.', 'request' );


		// OPTIMIZE TABLES:
		$hitlist_Timer->start( 'optimize_hitlog' );
		$DB->query('OPTIMIZE TABLE T_hitlog');
		$hitlist_Timer->stop( 'optimize_hitlog' );

		$hitlist_Timer->start( 'optimize_sessions' );
		$DB->query('OPTIMIZE TABLE T_sessions');
		$hitlist_Timer->stop( 'optimize_sessions' );

		$hitlist_Timer->start( 'optimize_basedomains' );
		$DB->query('OPTIMIZE TABLE T_basedomains');
		$hitlist_Timer->stop( 'optimize_basedomains' );


		// Stop total hitlist timer
		$hitlist_Timer->stop( 'prune_hits' );

		$Settings->set( 'auto_prune_stats_done', date( 'Y-m-d H:i:s', $localtimenow ) ); // save exact datetime
		$Settings->dbupdate();

		$Messages->add( T_('The old hits & sessions have been pruned.'), 'success' );
		return array(
				'result'  => 'ok',
				// DO NOT TRANSLATE! (This is sysadmin level info -- we assume they can read English)
				'message' =>
					'STATUS:'."\n"
					.sprintf( 'T_hitlog: %s - %s rows',
						$tables[ $db_config['aliases']['T_hitlog'] ]['type'],
						$tables[ $db_config['aliases']['T_hitlog'] ]['rows'] )."\n"
					.sprintf( 'T_sessions: %s - %s rows',
						$tables[ $db_config['aliases']['T_sessions'] ]['type'],
						$tables[ $db_config['aliases']['T_sessions'] ]['rows'] )."\n"
					.sprintf( 'T_basedomains: %s - %s rows',
						$tables[ $db_config['aliases']['T_basedomains'] ]['type'],
						$tables[ $db_config['aliases']['T_basedomains'] ]['rows'] )."\n"
					."\n"
					.'PRUNING:'."\n"
					.sprintf( '%s rows from T_hitlog, Execution time: %s seconds', $hitlog_rows_affected, $hitlist_Timer->get_duration( 'hitlog' ) )."\n"
					.sprintf( '%s rows from T_sessions, Execution time: %s seconds', $sessions_rows_affected, $hitlist_Timer->get_duration( 'sessions' ) )."\n"
					.sprintf( '%s rows from T_basedomains, Execution time: %s seconds', $basedomains_rows_affected, $hitlist_Timer->get_duration( 'basedomains' ) )."\n"
					."\n"
					.'OPTIMIZING:'."\n"
					.sprintf( 'T_hitlog: %s seconds', $hitlist_Timer->get_duration( 'optimize_hitlog' ) )."\n"
					.sprintf( 'T_sessions: %s seconds', $hitlist_Timer->get_duration( 'optimize_sessions' ) )."\n"
					.sprintf( 'T_basedomains: %s seconds', $hitlist_Timer->get_duration( 'optimize_basedomains' ) )."\n"
					."\n"
					.sprintf( 'Total execution time: %s seconds', $hitlist_Timer->get_duration( 'prune_hits' ) )
			);
	}
}

?>