<?php
/**
 * This file implements the Hitlist class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * {@internal
 * Daniel HAHLER grants François PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: François PLANQUE.
 *
 * @version $Id$
 *
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


/**
 * A list of hits. Provides functions for maintaining and extraction of Hits.
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

		$sql = "DELETE FROM T_hitlog WHERE hit_ID = $hit_ID";

		return $DB->query( $sql );
	}


	/**
	 * Delete all hits from a certain date
	 *
	 * @static
	 *
	 * @param int unix timestamp to delete hits for
	 * @return mixed Return value of {@link DB::query()}
	 */
	function prune( $date )
	{
		global $DB;

		$iso_date = date ('Y-m-d', $date);
		$sql = "DELETE FROM T_hitlog
						WHERE DATE_FORMAT(hit_datetime,'%Y-%m-%d') = '$iso_date'";

		return $DB->query( $sql );
	}


	/**
	 * Change type for a hit
	 *
	 * @static
	 *
	 * @param int ID to change
	 * @param string new type, must be valid ENUM for hit_referer_type field
	 * @return mixed Return value of {@link DB::query()}
	 */
	function change_type( $hit_ID, $type )
	{
		global $DB;

		$sql = "UPDATE T_hitlog
						SET hit_referer_type = '$type',
								hit_datetime = hit_datetime " // prevent mySQL from updating timestamp
						." WHERE hit_ID = $hit_ID";
		return $DB->query( $sql );
	}



	/**
	 * Auto pruning of old stats.
	 *
	 * It uses a general setting to store the day of the last prune.
	 */
	function autoPrune()
	{
		/*if( $Settings->get( 'auto_prune' ) )
		{ // Autopruning is requested
			if( $
			$sql = "DELETE FROM T_hitlog
							WHERE hit_datetime < '".date( 'Y-m-d', $localtimenow - ($stats_autoprune * 86400) )."'";
																															// 1 day = 86400 seconds
			$rows_affected = $DB->query( $sql );
			$Debuglog->add( 'log_hit: autopruned '.$rows_affected.' rows.', 'hit' );
		}*/
	}
}
