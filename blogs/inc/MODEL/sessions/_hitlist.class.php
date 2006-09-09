<?php
/**
 * This file implements the Hitlist class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
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
	 * Delete a hit.
	 *
	 * @static
	 * @param int ID to delete
	 * @return mixed Return value of {@link DB::query()}
	 */
	function delete( $hit_ID )
	{
		global $DB;

		return $DB->query( "DELETE FROM T_hitlog WHERE hit_ID = $hit_ID", 'Delete a hit' );
	}


	/**
	 * Delete all hits for a specific date
	 *
	 * @static
	 * @param int unix timestamp to delete hits for
	 * @return mixed Return value of {@link DB::query()}
	 */
	function prune( $date )
	{
		global $DB;

		$iso_date = date ('Y-m-d', $date);
		$sql = "
			DELETE FROM T_hitlog
			 WHERE DATE_FORMAT(hit_datetime,'%Y-%m-%d') = '$iso_date'";

		return $DB->query( $sql, 'Prune hits for a specific date' );
	}


	/**
	 * Change type for a hit
	 *
	 * @static
	 * @param int ID to change
	 * @param string new type, must be valid ENUM for hit_referer_type field
	 * @return mixed Return value of {@link DB::query()}
	 */
	function change_type( $hit_ID, $type )
	{
		global $DB;

		$sql = '
				UPDATE T_hitlog
				   SET hit_referer_type = '.$DB->quote($type).",
				       hit_datetime = hit_datetime " /* prevent mySQL from updating timestamp */ ."
				 WHERE hit_ID = $hit_ID";
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
	 * @static
	 * @return string Empty, if ok.
	 */
	function dbprune()
	{
		global $DB, $Debuglog, $Settings, $localtimenow;

		// Prune when $localtime is a NEW day (which will be the 1st request after midnight):
		$last_prune = $Settings->get( 'auto_prune_stats_done' );
		if( $last_prune >= date('Y-m-d', $localtimenow) )
		{ // Already pruned today
			return T_('Pruning has already been done today');
		}

		$time_prune_before = ($localtimenow - ($Settings->get('auto_prune_stats') * 86400)); // 1 day = 86400 seconds

		$rows_affected = $DB->query( '
			DELETE FROM T_hitlog
			WHERE hit_datetime < "'.date('Y-m-d', $time_prune_before).'"', 'Autopruning hit log' );
		$Debuglog->add( 'Hitlist::dbprune(): autopruned '.$rows_affected.' rows from T_hitlog.', 'hit' );

		// Prune sessions that have timed out and are older than auto_prune_stats
		$sess_prune_before = ($localtimenow - $Settings->get( 'timeout_sessions' ));
		$smaller_time = min( $sess_prune_before, $time_prune_before );
		$sess_prune_YMD = date( 'Y-m-d H:i:s', $smaller_time );

		$rows_affected = $DB->query( '
			DELETE FROM T_sessions
			WHERE sess_lastseen < "'.$sess_prune_YMD.'"', 'Autoprune sessions' );
		$Debuglog->add( 'Hitlist::dbprune(): autopruned '.$rows_affected.' rows from T_sessions.', 'hit' );

		// Prune non-referrered basedomains (where the according hits got deleted)
		// BUT only those with unknown dom_type/dom_status, because otherwise this
		//     info is useful when we get hit again.
		$mysql_ver = mysql_get_server_info();
		if( ($pos = strpos($mysql_ver, '.')) && substr( $mysql_ver, 0, $pos ) >= '4' )
		{ // MySQL server version >= 4 (required for multi-table deletes):
			$rows_affected = $DB->query( '
				DELETE T_basedomains
				  FROM T_basedomains LEFT JOIN T_hitlog ON hit_referer_dom_ID = dom_ID
				 WHERE hit_referer_dom_ID IS NULL
				 AND dom_type = "unknown"
				 AND dom_status = "unknown"' );
			$Debuglog->add( 'Hitlist::dbprune(): autopruned '.$rows_affected.' rows from T_basedomains.', 'hit' );
		}
		else
		{ // two queries for MySQL < 4
			$ids = $DB->get_row( '
				SELECT dom_ID
				  FROM T_basedomains LEFT JOIN T_hitlog ON hit_referer_dom_ID = dom_ID
				 WHERE hit_referer_dom_ID IS NULL
				 AND dom_type = "unknown"
				 AND dom_status = "unknown"' );

			$rows_affected = $DB->query( '
				DELETE FROM T_basedomains
				 WHERE dom_ID IN ( '.implode( ', ', $ids ).' )' );

			$Debuglog->add( 'Hitlist::dbprune(): autopruned '.$rows_affected.' rows from T_basedomains (MySQL<4).', 'hit' );
		}

		$Settings->set( 'auto_prune_stats_done', date('Y-m-d H:i:s', $localtimenow) ); // save exact datetime
		$Settings->dbupdate();

		return ''; /* ok */
	}
}