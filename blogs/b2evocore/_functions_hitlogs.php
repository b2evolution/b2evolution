<?php
/**
 * Logging of hits, extraction of stats
 * 
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evocore
 * @author This file built upon code by N C Young (nathan@ncyoung.com) (http://ncyoung.com/entry/57)
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
 * Log a hit on a blog page / rss feed
 *
 */
function log_hit()
{
	global $DB, $localtimenow, $blog, $tablehitlog, $blackList, $search_engines, $user_agents;
	global $doubleCheckReferers, $comments_allowed_uri_scheme, $HTTP_REFERER, $page, $ReqURI, $ReqPath;
	global $HTTP_USER_AGENT;
	global $uri_reloaded, $Debuglog;
	
	# TODO: check for already logged?
	
	$Debuglog->add( 'Hit Log: '. "ReqURI: ".$ReqURI );
	$Debuglog->add( 'Hit Log: '. "Remote Addr: ".$_SERVER['REMOTE_ADDR'] );
	$Debuglog->add( 'Hit Log: '. "referer: ".$HTTP_REFERER );
	#$Debuglog->add( 'Hit Log: '. "Remote Host: ".$_SERVER['REMOTE_HOST'] );
	$Debuglog->add( 'Hit Log: '. "User Agent: ".$HTTP_USER_AGENT );

	// $Debuglog->add( 'Hit Log: '."Languages: ".$_SERVER['HTTP_ACCEPT_LANGUAGE']);
	
	if( $uri_reloaded )
	{ // the Request is considered as reload
		return false;
	}
	
	$ignore = 'no';  // So far so good
	
	if( $HTTP_REFERER != strip_tags($HTTP_REFERER) )
	{ // then they have tried something funny,
		// putting HTML or PHP into the HTTP_REFERER
		// $ignore = 'badchar';
		$Debuglog->add( 'Hit Log: bad char in referer');
		return;		// Hazardous
	}
	elseif( $error = validate_url( $HTTP_REFERER, $comments_allowed_uri_scheme ) )
	{	// if they are trying to inject javascript or a blocked (spam) URL
		$Debuglog->add( 'Hit Log: '. $error);
		return;		// Hazardous
	}
	
	// SEARCH BLACKLIST	
	foreach( $blackList as $site )
	{
		if( strpos( $HTTP_REFERER, $site ) !== false )
		{
			// $ignore = 'blacklist';
			$Debuglog->add( 'Hit Log: '. T_('referer ignored'). ' ('. T_('BlackList'). ')');
			return;
		}
	}
			
	if( stristr($ReqPath, 'rss')
			|| stristr($ReqPath, 'rdf')
			|| stristr($ReqPath, 'atom')  )
	{
		$ignore = 'rss';
		// don't mess up the XML!! $Debuglog->add( 'Hit Log: referer ignored (RSS));
	}
	else
	{	// Lookup robots
		foreach ($user_agents as $user_agent)
		{
			if( ($user_agent[0] == 'robot') && (strstr($HTTP_USER_AGENT, $user_agent[1])) )
			{
				$ignore = "robot";
				$Debuglog->add( 'Hit Log: '. T_('referer ignored'). ' ('. T_('robot'). ')');
				break;
			}
		}
	}
	
	if( $ignore == 'no' )
	{
		if( strlen($HTTP_REFERER) < 13 )
		{	// minimum http://az.fr/ , this will be considered direct access (although it could be https:)
			$ignore = 'invalid';
			$Debuglog->add( 'Hit Log: '. T_('referer ignored'). ' ('. T_('invalid'). ')' );
		}
	}

	if( $ignore == 'no' )
	{	// identify search engines
		foreach($search_engines as $engine)
		{
			// $Debuglog->add( 'Hit Log: '."engine: ".$engine);
			if( stristr($HTTP_REFERER, $engine) )
			{
				$ignore = 'search';
				$Debuglog->add( 'Hit Log: '. T_('referer ignored'). " (". T_('search engine'). ")");
				break;
			}
		}
	}	

	if( $doubleCheckReferers )
	{
		$Debuglog->add( 'Hit Log: '. T_('loading referering page') );

		// this is so that the page up until the call to
		// logReferer will get shown before it tries to check
		// back against the refering URL.
		flush();

		$goodReferer = 0;
		if( strlen($HTTP_REFERER) > 0 )
		{
			$fullCurrentURL = 'http://'. $_SERVER['SERVER_NAME']. $ReqURI;
			// $Debuglog->add( 'Hit Log: '. "full current url: ".$fullCurrentURL);

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
					$Debuglog->add( 'Hit Log: '. T_('found current url in page') );
					$goodReferer = 1;
				}
			}
			
			if( !$goodReferer )
			{	// This was probably spam!
				$Debuglog->add( 'Hit Log: '. sprintf('did not find %s in %s', $fullCurrentURL, $page ) );
				return;
			}
		}
		else
		{ // Direct accesses are always good hits
			$goodReferer = 1;
		}
	}

	$baseDomain = preg_replace("/http:\/\//i", '', $HTTP_REFERER);
	$baseDomain = preg_replace("/^www\./i", '', $baseDomain);
	$baseDomain = preg_replace("/\/.*/i", '', $baseDomain);

	// insert hit into DB table
	$sql = "INSERT INTO $tablehitlog( visitTime, visitURL, hit_ignore, referingURL, baseDomain, 
																		hit_blog_ID, hit_remote_addr, hit_user_agent ) 
					VALUES( FROM_UNIXTIME(".$localtimenow."), '".$DB->escape($ReqURI)."', '$ignore', 
									'".$DB->escape($HTTP_REFERER)."', '".$DB->escape($baseDomain)."', $blog, 
									'".$DB->escape($_SERVER['REMOTE_ADDR'])."', '".$DB->escape($HTTP_USER_AGENT)."')";
	
	return $DB->query( $sql );
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
	global $DB, $tablehitlog;

	$sql = "DELETE FROM $tablehitlog WHERE visitID = $hit_ID";
	
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
	global $DB, $tablehitlog;

	$iso_date = date ('Y-m-d', $date);
	$sql = "DELETE FROM $tablehitlog
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
	global $DB, $tablehitlog;

	$sql = "UPDATE $tablehitlog
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
	global $DB, $tablehitlog, $res_stats, $stats_total_hits, $ReqURI;

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
	
	$sql_from_where = " FROM $tablehitlog WHERE hit_ignore IN ($type)";
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
function stats_hit_count()
{
	global $row_stats;
	echo $row_stats['totalHits'];
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



/* select count(*) as nb, hit_ignore
from b2hitlog
group by hit_ignore
order by nb desc 


update b2hitlog
set hit_ignore ='robot' 
where `hit_ignore` LIKE 'invalid' AND `hit_user_agent` LIKE 'FAST-WebCrawler/%'  



*/
?>
