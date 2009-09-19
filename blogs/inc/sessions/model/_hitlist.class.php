<?php
/**
 * This file implements the Hitlist class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
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
		/**
		 * @var DB
		 */
		global $DB;
		global $Debuglog, $Settings, $localtimenow;
		global $Plugins;

		// Prune when $localtime is a NEW day (which will be the 1st request after midnight):
		$last_prune = $Settings->get( 'auto_prune_stats_done' );
		if( $last_prune >= date('Y-m-d', $localtimenow) )
		{ // Already pruned today
			return T_('Pruning has already been done today');
		}

		$time_prune_before = ($localtimenow - ($Settings->get('auto_prune_stats') * 86400)); // 1 day = 86400 seconds

		$rows_affected = $DB->query( "
			DELETE FROM T_hitlog
			WHERE hit_datetime < '".date('Y-m-d', $time_prune_before)."'", 'Autopruning hit log' );
		$Debuglog->add( 'Hitlist::dbprune(): autopruned '.$rows_affected.' rows from T_hitlog.', 'hit' );

		// Prune sessions that have timed out and are older than auto_prune_stats
		$sess_prune_before = ($localtimenow - $Settings->get( 'timeout_sessions' ));
		$smaller_time = min( $sess_prune_before, $time_prune_before );
		// allow plugins to prune session based data
		$Plugins->trigger_event( 'BeforeSessionsDelete', $temp_array = array( 'cutoff_timestamp' => $smaller_time ) );

		$rows_affected = $DB->query( 'DELETE FROM T_sessions WHERE sess_lastseen < '.$DB->quote(date('Y-m-d H:i:s', $smaller_time)), 'Autoprune sessions' );
		$Debuglog->add( 'Hitlist::dbprune(): autopruned '.$rows_affected.' rows from T_sessions.', 'hit' );

		// Prune non-referrered basedomains (where the according hits got deleted)
		// BUT only those with unknown dom_type/dom_status, because otherwise this
		//     info is useful when we get hit again.
		// Note: MySQL server version >= 4 is required for multi-table deletes, but v 4.1 is now a requirement for b2evolution:
		$rows_affected = $DB->query( "
			DELETE T_basedomains
			  FROM T_basedomains LEFT JOIN T_hitlog ON hit_referer_dom_ID = dom_ID
			 WHERE hit_referer_dom_ID IS NULL
			 AND dom_type = 'unknown'
			 AND dom_status = 'unknown'" );
		$Debuglog->add( 'Hitlist::dbprune(): autopruned '.$rows_affected.' rows from T_basedomains.', 'hit' );

		// Prune entries from T_useragents which are not referenced in T_hitlog anymore.
		$rows_affected = $DB->query('
			DELETE T_useragents
			  FROM T_useragents LEFT JOIN T_hitlog ON agnt_ID = hit_agnt_ID
			 WHERE hit_agnt_ID IS NULL');
		$Debuglog->add( 'Hitlist::dbprune(): autopruned '.$rows_affected.' rows from T_useragents.', 'hit' );

		$Settings->set( 'auto_prune_stats_done', date('Y-m-d H:i:s', $localtimenow) ); // save exact datetime
		$Settings->dbupdate();

		return ''; /* ok */
	}
}

/*
 * $Log$
 * Revision 1.16  2009/09/19 12:52:54  blueyed
 * Remove obsolete entries from T_useragents when pruning hitlog info.
 *
 * Revision 1.15  2009/09/14 18:37:07  fplanque
 * doc/cleanup/minor
 *
 * Revision 1.14  2009/09/13 21:26:50  blueyed
 * SQL_NO_CACHE for SELECT queries using T_hitlog
 *
 * Revision 1.13  2009/07/06 22:55:11  fplanque
 * minor
 *
 * Revision 1.12  2009/07/06 12:44:59  yabs
 * Modifying BeforeSessionsDelete behaviour
 *
 * Revision 1.11  2009/07/05 00:50:29  fplanque
 * no message
 *
 * Revision 1.10  2009/07/04 08:01:49  yabs
 * doc
 *
 * Revision 1.9  2009/07/04 01:52:51  fplanque
 * doc
 *
 * Revision 1.8  2009/07/02 22:01:15  blueyed
 * Fix BeforeSessionsDelete: add it to base plugin class, test plugin and implement FPs requirements.
 *
 * Revision 1.7  2009/07/02 00:01:50  fplanque
 * needs optimization.
 *
 * Revision 1.6  2009/06/15 18:50:50  blueyed
 * Fix SQL in session pruning: s/FOM/FROM/
 *
 * Revision 1.5  2009/06/14 14:58:09  blueyed
 * Use get_row to select sess_IDs for session pruning
 *
 * Revision 1.4  2009/06/14 12:17:32  yabs
 * adding BeforeSessionsDelete trigger to enable plugins to perform housekeeping based on the sessions that are about to be pruned
 *
 * Revision 1.3  2009/03/08 23:57:45  fplanque
 * 2009
 *
 * Revision 1.2  2008/01/21 09:35:33  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/06/25 11:00:58  fplanque
 * MODULES (refactored MVC)
 *
 */
?>
