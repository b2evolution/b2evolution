<?php
/**
 * This file implements functions for logging of hits and extracting stats.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}.
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
 * This file was inspired by N C Young's Referer Script released in
 * the public domain on 07/19/2002. {@link (http://ncyoung.com/entry/57).
 * See also {@link http://ncyoung.com/demo/referer/}.
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author N C Young (nathan@ncyoung.com).
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 * @author vegarg: Vegar BERG GULDAL.
 *
 * @version $Id$
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

//get most linked to pages on site
//select count(visitURL) as count, visitURL from b2hitlog group by visitURL order by count desc

/*if ($refererList)
{
	print "referers:<br />";
	$ar = refererList($refererList,"global");
	print join("<br />",$ar);
}

if ($topRefererList)
{
	print join("<br />",topRefererList($topRefererList,"global"));
}
*/

/**
 * Detetect when a hit should be discarded
 *
 * There are several situations in which a hit can be discarded:
 * - it's a reload of the same page
 * - it's a spam
 * - it's blacklisted...
 *
 * TODO: we might want to kill the connection on some occasions here... (?)
 *
 * @return boolean true if hit is loggable...
 */
function filter_hit()
{
 	global $Debuglog, $ReqURI, $ReqPath, $DB, $Settings, $localtimenow, $comments_allowed_uri_scheme;
 	global $blackList, $search_engines, $user_agents, $HTTP_REFERER, $HTTP_USER_AGENT;

	$Debuglog->add( 'filter_hit: REMOTE_ADDR: '.$_SERVER['REMOTE_ADDR'], 'hit' );
	$Debuglog->add( 'filter_hit: HTTP_REFERER: '.$HTTP_REFERER, 'hit' );
	// $Debuglog->add( 'Hit Log: '. "Remote Host: ".$_SERVER['REMOTE_HOST'], 'hit' );
	$Debuglog->add( 'filter_hit: HTTP_USER_AGENT: '.$HTTP_USER_AGENT, 'hit' );

	/*
	 * Check if the referer is clean:
	 */
	if( $HTTP_REFERER != strip_tags($HTTP_REFERER) )
	{ // then they have tried something funny,
		// putting HTML or PHP into the HTTP_REFERER
		// $ignore = 'badchar';
		$Debuglog->add( 'filter_hit: bad char in referer', 'hit');
		return 'badchar';		// Hazardous
	}
	elseif( $error = validate_url( $HTTP_REFERER, $comments_allowed_uri_scheme ) )
	{	// if they are trying to inject javascript or a blocked (spam) URL
		$Debuglog->add( 'filter_hit: '. $error, 'hit');
		return 'badchar';		// Hazardous
	}


	/*
	 * Check for reloads (if the URI has been requested from same IP/useragent
	 * in past reloadpage_timeout seconds.)
	 */
	if( $DB->get_var(
				'SELECT visitID FROM T_hitlog
					WHERE	visitURL = '.$DB->quote($ReqURI).'
						AND UNIX_TIMESTAMP(visitTime)-'.$localtimenow.' < '.$Settings->get('reloadpage_timeout').'
						AND hit_remote_addr = '.$DB->quote($_SERVER['REMOTE_ADDR']).'
						AND hit_user_agent = '.$DB->quote($HTTP_USER_AGENT) ) )
	{
	 	$Debuglog->add( 'filter_hit: URI-reload!', 'hit' );
	 	return 'reload'; 		// We don't want to log this hit
	}


	/*
	 * Lookup robots
	 */
	foreach( $user_agents as $user_agent )
	{
		if( ($user_agent[0] == 'robot') && (strstr($HTTP_USER_AGENT, $user_agent[1])) )
		{
			$Debuglog->add( 'filter_hit: robot', 'hit' );
			return 'robot';
		}
	}


 	/*
	 * Check blacklist, see {@link $blackList}
	 * fplanque: we log these again, because if we didn't we woudln't detect
	 * reloads on these... and that would be a problem!
	 */
	foreach( $blackList as $site )
	{
		if( strpos( $HTTP_REFERER, $site ) !== false )
		{
			$Debuglog->add( 'filter_hit: referer will be hidden (BlackList)', 'hit' );
			return 'blacklist';
		}
	}


	/*
	 * Check for XML feeds
	 */
	if( stristr($ReqPath, 'rss')
			|| stristr($ReqPath, 'rdf')
			|| stristr($ReqPath, 'atom') )
	{
		$Debuglog->add( 'filter_hit: RSS', 'hit' );
		return 'rss';
	}


	/*
	 * Check if we have a valid referer:
	 * minimum length: http://az.fr/
	 */
	if( strlen($HTTP_REFERER) < 13 )
	{	// this will be considered direct access (although it could be https: ??)
		$Debuglog->add( 'filter_hit: invalid referer / direct access?', 'hit' );
		return 'invalid';
	}


	/*
	 * Is the referer a search engine?
	 */
	foreach($search_engines as $engine)
	{
		if( stristr($HTTP_REFERER, $engine) )
		{
			$Debuglog->add( 'filter_hit: search engine ('.$engine.')', 'hit' );
			return 'search';
		}
	}


 	/*
 	 * We have a valid referer
 	 */
 	return 'no';		// Hit type: normal (previous meaning: no ignore)
}


/**
 * Log a hit on a blog page / rss feed
 *
 * This function should be called at the end of the page, otherwise if the page
 * is displaying previous hits, it may display the current one too.
 * The hit will not be logged in special occasions, see {@link $hit_type}
 */
function log_hit()
{
	global $DB, $localtimenow, $blog;
	global $doubleCheckReferers, $HTTP_REFERER, $page, $ReqURI;
	global $HTTP_USER_AGENT, $hit_type, $Debuglog;
	global $stats_autoprune;

	/**
	 * Make sure we want to log this hit, see {@link $hit_type}
	 */
	if( in_array( $hit_type, array( 'badchar', 'reload', 'preview', 'already_logged' ) ) )
	{	// We don't want to log this hit!
  	$Debuglog->add( 'log_hit: Hit NOT Logged ('.$hit_type.')', 'hit' );
		return false;
	}

	if( $doubleCheckReferers )
	{
		$Debuglog->add( 'log_hit: double check: loading referering page', 'hit' );

		// flush now, so that the meat of the page will get shown before it tries to check
		// back against the refering URL.
		flush();

		$goodReferer = 0;
		if( strlen($HTTP_REFERER) > 0 )
		{
			$fullCurrentURL = 'http://'. $_SERVER['SERVER_NAME']. $ReqURI;
			// $Debuglog->add( 'Hit Log: '. "full current url: ".$fullCurrentURL, 'hit');

			$fp = @fopen( $HTTP_REFERER, 'r' );
			if( $fp )
			{
				// timeout after 5 seconds
				socket_set_timeout($fp, 5);
				while( !feof($fp) )
				{
					$page .= trim(fgets($fp));
				}
				if (strstr($page,$fullCurrentURL))
				{
					$Debuglog->add( 'log_hit: found current url in page', 'hit' );
					$goodReferer = 1;
				}
			}

			if( !$goodReferer )
			{	// This was probably spam!
				$Debuglog->add( 'log_hit: '. sprintf('did not find %s in %s', $fullCurrentURL, $page ), 'hit' );
				return false;
			}
		}
		else
		{ // Direct accesses are always good hits
			$goodReferer = 1;
		}
	}

	/*
	 * Record the hit:
	 */
	$baseDomain = preg_replace("/http:\/\//i", '', $HTTP_REFERER);
	$baseDomain = preg_replace("/^www\./i", '', $baseDomain);
	$baseDomain = preg_replace("/\/.*/i", '', $baseDomain);
	// insert hit into DB table:
	$sql = "INSERT INTO T_hitlog( visitTime, visitURL, hit_ignore, referingURL, baseDomain,
																		hit_blog_ID, hit_remote_addr, hit_user_agent )
					VALUES( FROM_UNIXTIME(".$localtimenow."), '".$DB->escape($ReqURI)."', '$hit_type',
									'".$DB->escape($HTTP_REFERER)."', '".$DB->escape($baseDomain)."', $blog,
									'".$DB->escape($_SERVER['REMOTE_ADDR'])."', '".$DB->escape($HTTP_USER_AGENT)."')";

	$DB->query( $sql );

	// Remember we have logged already:
	$hit_type = 'already_logged';

	/*
	 * Auto pruning of old stats
	 */
	if( isset($stats_autoprune) && ($stats_autoprune > 0) )
	{	// Autopruning is requested
		$sql = "DELETE FROM T_hitlog
						 WHERE visitTime < '".date( 'Y-m-d', $localtimenow - ($stats_autoprune * 86400) )."'";
																														// 1 day = 86400 seconds
		$rows_affected = $DB->query( $sql );
		$Debuglog->add( 'log_hit: autopruned '.$rows_affected.' rows.', 'hit' );
	}

	return true;
}


/**
 * Delete a hit
 *
 * {@internal hit_delete(-) }}
 *
 * @param int ID to delete
 */
function hit_delete( $hit_ID )
{
	global $DB;

	$sql = "DELETE FROM T_hitlog WHERE visitID = $hit_ID";

	return $DB->query( $sql );
}


/**
 * Delete all hits from a certain date
 *
 * {@internal hit_prune(-) }}
 *
 * @param int unix timestamp to delete hits for
 */
function hit_prune( $date )
{
	global $DB;

	$iso_date = date ('Y-m-d', $date);
	$sql = "DELETE FROM T_hitlog
					WHERE DATE_FORMAT(visitTime,'%Y-%m-%d') = '$iso_date'";

	return $DB->query( $sql );
}


/**
 * Change type for a hit
 *
 * {@internal hit_change_type(-) }}
 *
 * @param int ID to change
 * @param string new type, must be valid ENUM for hit_ignore field
 */
function hit_change_type( $hit_ID, $type )
{
	global $DB;

	$sql = "UPDATE T_hitlog
					SET hit_ignore = '$type',
							visitTime = visitTime "	// prevent mySQL from updating timestamp
					." WHERE visitID = $hit_ID";
	return $DB->query( $sql );
}


/**
 *
 * {@internal refererList(-) }}
 *
 * Extract stats
 */
function refererList(
	$howMany = 5,
	$visitURL = '',
	$disp_blog = 0,
	$disp_uri = 0,
	$type = "'no'",		// 'no' normal refer, 'invalid', 'badchar', 'blacklist', 'rss', 'robot', 'search'
	$groupby = '', 	// baseDomain
	$blog_ID = '',
	$get_total_hits = false, // Get total number of hits (needed for percentages)
	$get_user_agent = false ) // Get the user agent
{
	global $DB, $res_stats, $stats_total_hits, $ReqURI;

	autoquote( $type );		// In case quotes are missing

	$ret = array();

	//if no visitURL, will show links to current page.
	//if url given, will show links to that page.
	//if url="global" will show links to all pages
	if (!$visitURL)
	{
		$visitURL = $ReqURI;
	}

	if( $groupby == '' )
	{	// No grouping:
		$sql = "SELECT visitID, UNIX_TIMESTAMP(visitTime) AS visitTime, referingURL, baseDomain";
	}
	else
	{	// group by
		$sql = "SELECT COUNT(*) AS totalHits, referingURL, baseDomain";
	}
	if( $disp_blog )
	{
		$sql .= ", hit_blog_ID";
	}
	if( $disp_uri )
	{
		$sql .= ", visitURL";
	}
	if( $get_user_agent )
	{
		$sql .= ", hit_user_agent";
	}

	$sql_from_where = " FROM T_hitlog WHERE hit_ignore IN ($type)";
	if( !empty($blog_ID) )
	{
		$sql_from_where .= " AND hit_blog_ID = '$blog_ID'";
	}
	if ($visitURL != "global")
	{
		$sql_from_where .= " AND visitURL = '$visitURL'";
	}

	$sql .= $sql_from_where;

	if( $groupby == '' )
	{	// No grouping:
		$sql .= " ORDER BY visitID DESC";
	}
	else
	{	// group by
		$sql .= "	GROUP BY $groupby ORDER BY totalHits DESC";
	}
	$sql .= " LIMIT $howMany";

	$res_stats = $DB->get_results( $sql, ARRAY_A );

	if( $get_total_hits )
	{	// we need to get total hits
		$sql = "SELECT COUNT(*) ".$sql_from_where;
		$stats_total_hits = $DB->get_var( $sql );
	}
	else
	{	// we're not getting total hits
		$stats_total_hits = 1;		// just in case some tries a percentage anyway (avoid div by 0)
	}

}


/*
 * stats_hit_ID(-)
 */
function stats_hit_ID()
{
	global $row_stats;
	echo $row_stats['visitID'];
}

/*
 * stats_hit_remote_addr(-)
 */
function stats_hit_remote_addr()
{
	global $row_stats;
	echo $row_stats['hit_remote_addr'];
}

/*
 * stats_time(-)
 */
function stats_time( $format = '' )
{
	global $row_stats;
	if( $format == '' )
		$format = locale_datefmt().' '.locale_timefmt();
	echo date_i18n( $format, $row_stats['visitTime'] );
}


/*
 * stats_total_hit_count(-)
 */
function stats_total_hit_count()
{
	global $stats_total_hits;
	echo $stats_total_hits;
}


/*
 * stats_hit_count(-)
 */
function stats_hit_count( $disp = true )
{
	global $row_stats;
	if( $disp )
		echo $row_stats['totalHits'];
	else
		return $row_stats['totalHits'];
}


/*
 * stats_hit_percent(-)
 */
function stats_hit_percent(
	$decimals = 1,
	$dec_point = ',' )
{
	global $row_stats, $stats_total_hits;
	$percent = $row_stats['totalHits'] * 100 / $stats_total_hits;
	echo number_format( $percent, $decimals, $dec_point, '' ).'&nbsp;%';
}


/*
 * stats_blog_ID(-)
 */
function stats_blog_ID()
{
	global $row_stats;
	echo $row_stats['hit_blog_ID'];
}


/*
 * stats_blog_name(-)
 */
function stats_blog_name()
{
	global $row_stats;
	$stats_blogparams = get_blogparams_by_ID( $row_stats['hit_blog_ID'] );
	echo format_to_output( $stats_blogparams->blog_name, 'htmlbody' );
}


/*
 * stats_referer(-)
 */
function stats_referer( $before='', $after='', $disp_ref = true )
{
	global $row_stats;
	$ref = trim($row_stats['referingURL']);
	if( strlen($ref) > 0 )
	{
		echo $before;
		if( $disp_ref ) echo htmlentities( $ref );
		echo $after;
	}
}


/*
 * stats_basedomain(-)
 */
function stats_basedomain( $disp = true )
{
	global $row_stats;
	if( $disp )
		echo htmlentities( $row_stats['baseDomain'] );
	else
		return $row_stats['baseDomain'];
}


/**
 * stats_search_keywords(-)
 *
 * Displays keywords used for search leading to this page
 */
function stats_search_keywords()
{
	global $row_stats;
	$kwout = '';
	$ref = $row_stats['referingURL'];
	if( ($pos_question = strpos( $ref, '?' )) == false )
	{
		echo '[', T_('not a query - no params!'), ']';
		return;
	}
	$ref_params = explode( '&', substr( $ref, $pos_question+1 ) );
	foreach( $ref_params as $ref_param )
	{
		$param_parts = explode( '=', $ref_param );
		if( $param_parts[0] == 'q' or $param_parts[0] == 'query' or $param_parts[0] == 'p' or $param_parts[0] == 'kw' or $param_parts[0] == 'qs' )
		{ // found "q" query parameter
			$q = urldecode($param_parts[1]);
			if( strpos( $q, 'Ã' ) !== false )
			{	// Probability that the string is UTF-8 encoded is very high, that'll do for now...
				//echo "[UTF-8 decoding]";
				$q = utf8_decode( $q );
			}
			$qwords = explode( ' ', $q );
			foreach( $qwords as $qw )
			{
				if( strlen( $qw ) > 30 ) $qw = substr( $qw, 0, 30 )."...";	// word too long, crop it
				$kwout .= $qw.' ';
			}
			echo htmlentities($kwout);
			return;
		}
	}
	echo '[', T_('no query string found'), ']';
}


/*
 * stats_req_URI(-)
 */
function stats_req_URI()
{
	global $row_stats;
	echo htmlentities($row_stats['visitURL']);
}


/**
 * stats_user_agent(-)
 *
 * @param boolean
 */
function stats_user_agent( $translate = false )
{
	global $row_stats, $user_agents;
	$UserAgent = $row_stats[ 'hit_user_agent' ];
	if( $translate )
	{
		foreach ($user_agents as $curr_user_agent)
		{
			if (stristr($UserAgent, $curr_user_agent[1]))
			{
				$UserAgent = $curr_user_agent[2];
				break;
			}
		}
	}
	echo htmlentities( $UserAgent );
}


/**
 * Display "Statistics" title if these have been requested
 *
 * {@internal stats_title(-) }}
 *
 * @param string Prefix to be displayed if something is going to be displayed
 * @param mixed Output format, see {@link format_to_output()} or false to
 *								return value instead of displaying it
 */
function stats_title( $prefix = ' ', $display = 'htmlbody' )
{
	global $disp;

	if( $disp == 'stats' )
	{
		$info = $prefix. T_('Statistics');
		if ($display)
			echo format_to_output( $info, $display );
		else
			return $info;
	}
}



/*
 * $Log$
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.55  2004/10/12 17:22:30  fplanque
 * Edited code documentation.
 *
 * Revision 1.14  2004/2/8 16:7:1  vegarg
 * Bugfix in log_hit(). (thanks to mg)
 *
 * Revision 1.13  2004/2/1 20:8:27  vegarg
 * Direct hits are now logged when doubleCheckReferers is enabled. (contrib by kiesow)
 *
 * Revision 1.11  2004/1/16 16:49:49  vegarg
 * Added (incomplete) prune date option, which for some reason doesn't work yet.
 *
 * Revision 1.9  2004/1/16 14:12:16  vegarg
 * Added functions for b2antispam.php.
 *
 * Revision 1.1.1.1.2.1  2003/9/1 17:34:21  sakichan
 * SECURITY: SQL injection fix
 */
?>