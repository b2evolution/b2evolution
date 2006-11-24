<?php
/**
 * This file implements functions for logging of hits and extracting stats.
 *
 * NOTE: the refererList() and stats_* functions are not fully functional ATM. I'll transform them into the Hitlog object during the next days. blueyed.
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
 * {@internal Origin:
 * This file was inspired by N C Young's Referer Script released in
 * the public domain on 07/19/2002. {@link http://ncyoung.com/entry/57}.
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
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

//get most linked to pages on site
//select count(hit_uri) as count, hit_uri from T_hitlog group by hit_uri order by count desc

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
 * @todo Transform to make this a stub for {@link $Hitlist}
 *
 * Extract stats
 */
function refererList(
	$howMany = 5,
	$visitURL = '',
	$disp_blog = 0,
	$disp_uri = 0,
	$type = "'referer'",		// was: 'referer' normal refer, 'invalid', 'badchar', 'blacklist', 'rss', 'robot', 'search'
													// new: 'search', 'blacklist', 'referer', 'direct', ('spam' but spam is not logged)
	$groupby = '', 	// dom_name
	$blog_ID = '',
	$get_total_hits = false, // Get total number of hits (needed for percentages)
	$get_user_agent = false ) // Get the user agent
{
	global $DB, $res_stats, $stats_total_hits, $ReqURI;

	if( strpos( $type, "'" ) !== 0 )
	{ // no quote at position 0
		$type = "'".$type."'";
	}

	//if no visitURL, will show links to current page.
	//if url given, will show links to that page.
	//if url="global" will show links to all pages
	if (!$visitURL)
	{
		$visitURL = $ReqURI;
	}

	if( $groupby == '' )
	{ // No grouping:
		$sql = "SELECT hit_ID, UNIX_TIMESTAMP(hit_datetime) AS hit_datetime, hit_referer, dom_name";
	}
	else
	{ // group by
		if( $groupby == 'baseDomain' )
		{ // compatibility HACK!
			$groupby = 'dom_name';
		}
		$sql = "SELECT COUNT(*) AS totalHits, hit_referer, dom_name";
	}
	if( $disp_blog )
	{
		$sql .= ", hit_blog_ID";
	}
	if( $disp_uri )
	{
		$sql .= ", hit_uri";
	}
	if( $get_user_agent )
	{
		$sql .= ", agnt_signature";
	}

	$sql_from_where = '
			  FROM T_hitlog
			 INNER JOIN T_useragents ON hit_agnt_ID = agnt_ID
			  LEFT JOIN T_basedomains ON dom_ID = hit_referer_dom_ID
			 WHERE hit_referer_type IN ('.$type.')
			   AND agnt_type = "browser"';
	if( !empty($blog_ID) )
	{
		$sql_from_where .= " AND hit_blog_ID = '$blog_ID'";
	}
	if ($visitURL != "global")
	{
		$sql_from_where .= " AND hit_uri = '".$DB->escape($visitURL, 0, 250)."'";
	}

	$sql .= $sql_from_where;

	if( $groupby == '' )
	{ // No grouping:
		$sql .= " ORDER BY hit_ID DESC";
	}
	else
	{ // group by
		$sql .= " GROUP BY $groupby ORDER BY totalHits DESC";
	}
	$sql .= " LIMIT $howMany";

	$res_stats = $DB->get_results( $sql, ARRAY_A );

	if( $get_total_hits )
	{ // we need to get total hits
		$sql = "SELECT COUNT(*) ".$sql_from_where;
		$stats_total_hits = $DB->get_var( $sql );
	}
	else
	{ // we're not getting total hits
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
	echo date_i18n( $format, $row_stats['hit_datetime'] );
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
	$dec_point = '.' )
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

	$BlogCache = & get_Cache('BlogCache');
	$Blog = & $BlogCache->get_by_ID($row_stats['hit_blog_ID']);

	$Blog->disp('name');
}


/*
 * stats_referer(-)
 */
function stats_referer( $before='', $after='', $disp_ref = true )
{
	global $row_stats;
	$ref = trim($row_stats['hit_referer']);
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
		echo htmlentities( $row_stats['dom_name'] );
	else
		return $row_stats['dom_name'];
}


/**
 * Displays keywords used for search leading to this page
 *
 * @todo link keyword param to search engine ()
 */
function stats_search_keywords( $ref )
{
	$kwout = '';
	if( ($pos_question = strpos( $ref, '?' )) == false )
	{
		return '['.T_('not a query - no params!').']';
	}
	$ref_params = explode( '&', substr( $ref, $pos_question+1 ) );
	foreach( $ref_params as $ref_param )
	{
		$param_parts = explode( '=', $ref_param );
		if( $param_parts[0] == 'q'
				or $param_parts[0] == 'as_q' 		// Google Advanced Search Query
				or $param_parts[0] == 'query'
				or $param_parts[0] == 'search'
				or $param_parts[0] == 'p'
				or $param_parts[0] == 'kw'
				or $param_parts[0] == 'qs'
				or $param_parts[0] == 'r'
				or $param_parts[0] == 'rdata'				// search.ke.voila.fr
				or $param_parts[0] == 'su'				// suche.web.de
			)
		{ // found "q" query parameter
			$q = urldecode($param_parts[1]);
			if( strpos( $q, 'Ã' ) !== false )
			{ // Probability that the string is UTF-8 encoded is very high, that'll do for now...
				//echo "[UTF-8 decoding]";
				$q = utf8_decode( $q );
			}
			$qwords = explode( ' ', $q );
			foreach( $qwords as $qw )
			{
				if( strlen( $qw ) > 30 ) $qw = substr( $qw, 0, 30 )."...";	// word too long, crop it
				$kwout .= $qw.' ';
			}
			return htmlentities($kwout);
		}
	}
	return '['.T_('no query string found').']';
}


/*
 * stats_req_URI(-)
 */
function stats_req_URI()
{
	global $row_stats;
	echo htmlentities($row_stats['hit_uri']);
}


/**
 * stats_user_agent(-)
 *
 * @param boolean
 */
function stats_user_agent( $translate = false )
{
	global $row_stats, $user_agents;
	$UserAgent = $row_stats[ 'agnt_signature' ];
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


/*
 * stats_title(-)
 *
 * @movedTo _obsolete092.php
 */


/*
 * $Log$
 * Revision 1.10  2006/11/24 18:27:24  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.9  2006/10/10 19:26:24  blueyed
 * Use BlogCache instead "blogparams"
 *
 * Revision 1.8  2006/10/06 21:54:16  blueyed
 * Fixed hit_uri handling, especially in strict mode
 *
 * Revision 1.7  2006/08/20 13:47:25  fplanque
 * extracted param funcs from misc
 *
 * Revision 1.6  2006/04/19 20:13:50  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.5  2006/04/19 17:20:07  blueyed
 * Prefix "ban" domains with "://"; do only count browser type hits as referer (not "rss"!); Whitespace!
 *
 * Revision 1.4  2006/04/13 13:43:10  blueyed
 * Use "://suche." generally as search referer; added "su" search param part
 *
 * Revision 1.3  2006/03/12 23:08:59  fplanque
 * doc cleanup
 *
 * Revision 1.2  2006/03/02 20:05:29  blueyed
 * Fixed/polished stats (linking T_useragents to T_hitlog, not T_sessions again). I've done this the other way around before, but it wasn't my idea.. :p
 *
 * Revision 1.1  2006/02/23 21:11:58  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.14  2005/12/12 19:21:22  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.13  2005/11/05 01:53:54  blueyed
 * Linked useragent to a session rather than a hit;
 * SQL: moved T_hitlog.hit_agnt_ID to T_sessions.sess_agnt_ID
 *
 * Revision 1.12  2005/10/14 21:00:08  fplanque
 * Stats & antispam have obviously been modified with ZERO testing.
 * Fixed a sh**load of bugs...
 *
 * Revision 1.11  2005/10/11 20:35:58  fplanque
 * Oh man! This is SO full of crap, it's pathetic!! :-(((
 *
 * Revision 1.10  2005/09/06 17:13:55  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.9  2005/05/27 19:00:56  fplanque
 * no message
 *
 * Revision 1.8  2005/03/09 14:54:26  fplanque
 * refactored *_title() galore to requested_title()
 *
 * Revision 1.7  2005/02/28 09:06:33  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.6  2005/02/28 01:32:32  blueyed
 * Hitlog refactoring, part uno.
 *
 * Revision 1.5  2005/02/09 21:43:32  blueyed
 * introduced getIpList()
 *
 * Revision 1.4  2004/11/15 18:57:05  fplanque
 * cosmetics
 *
 * Revision 1.3  2004/11/09 00:25:12  blueyed
 * minor translation changes (+MySQL spelling :/)
 *
 * Revision 1.2  2004/10/14 18:31:25  blueyed
 * granting copyright
 *
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