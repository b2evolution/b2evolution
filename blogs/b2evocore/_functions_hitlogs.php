<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * This file built upon code by N C Young (nathan@ncyoung.com) (http://ncyoung.com/entry/57)
 */
require_once (dirname(__FILE__)."/$core_dirout/$conf_subdir/_stats.php");

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

function dbg($string)
{
	global $debug;
	if( $debug ) print " ".$string." \n";
}


/*
 * log_hit(-)
 *
 * Log a hit on a blog page / rss feed
 */
function log_hit()
{
	global $querycount, $localtimenow, $blog, $tablehitlog, $blackList, $search_engines, $user_agents;
	global $doubleCheckReferers, $comments_allowed_uri_scheme, $HTTP_REFERER, $page;
	
	$ReqURI = $_SERVER['REQUEST_URI'];
	// dbg( "current url: ".$ReqURI);

	$fullCurrentURL = "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
	// dbg( "full current url: ".$fullCurrentURL);

	$ref = $HTTP_REFERER;
	// dbg( "referer: ".$ref);

	$RemoteAddr = $_SERVER['REMOTE_ADDR'];
	// dbg( "Remote Addr: ".$RemoteAddr);
	//$RemoteHost = $_SERVER['REMOTE_HOST'];
	//dbg( "Remote Host: ".$RemoteHost);

	$UserAgent = $_SERVER['HTTP_USER_AGENT'];
	// dbg( "User Agent: ".$UserAgent);
	if ($UserAgent != strip_tags($UserAgent))
	{ //then they have tried something funny,
		//putting HTML or PHP into the HTTP_REFERER
		dbg(T_("bad char in User Agent"));
		$UserAgent = "";
	}

	// dbg("Languages: ".$_SERVER['HTTP_ACCEPT_LANGUAGE']);

	
	$ignore = "no";		// So far so good
	
	if( $ref != strip_tags($ref) )
	{ //then they have tried something funny,
		//putting HTML or PHP into the HTTP_REFERER
		//$ignore = 'badchar';
		dbg('bad char in referer');
		return;		// Hazardous
	}
	elseif( $error = validate_url( $ref, $comments_allowed_uri_scheme ) )
	{	//if they are trying to inject javascript or a blocked (spam) URL
		dbg($error);
		return;		// Hazardous
	}
	
	// SEARCH BLACKLIST	
	foreach ($blackList as $site)
	{
		if (stristr($ref, $site))
		{
			// $ignore = 'blacklist';
			dbg( T_('referer ignored'). " (". T_('BlackList'). ")");
			return;
		}
	}
			
	if( stristr($ReqURI, 'rss') || stristr($ReqURI, 'rdf') )
	{
		$ignore = "rss";
		// don't mess up the XML!! dbg("referer ignored (RSS)");
	}
	else
	{	// Lookup robots
		foreach ($user_agents as $user_agent)
		{
			if( ($user_agent[0]=='robot') && (strstr($UserAgent, $user_agent[1])) )
			{
				$ignore = "robot";
				dbg( T_('referer ignored'). " (". T_('robot'). ")");
				break;
			}
		}
	}
	
	if( $ignore == 'no' )
	{
		if( strlen($ref) < 13 )
		{	// minimum http://az.fr/ , this will be considered direct access (although it could be https:)
			$ignore = 'invalid';
			dbg( T_('referer ignored'). " (". T_('invalid'). ")");
		}
	}


	if( $ignore == 'no' )
	{	// identify search engines
		foreach($search_engines as $engine)
		{
			// dbg("engine: ".$engine);
			if(stristr($ref, $engine))
			{
				$ignore = 'search';
				dbg( T_('referer ignored'). " (". T_('search engine'). ")");
				break;
			}
		}
	}	
		

	if ($doubleCheckReferers)
	{
		dbg(T_('loading referering page'));

		//this is so that the page up until the call to
		//logReferer will get shown before it tries to check
		//back against the refering URL.
		flush();

		$goodReferer = 0;
		if ( strlen($ref) > 0 )
		{
			$fp = @fopen ($ref, 'r');
			if ($fp)
			{
				//timeout after 5 seconds
				socket_set_timeout($fp, 5);
				while (!feof ($fp))
				{
					$page .= trim(fgets($fp));
				}
				if (strstr($page,$fullCurrentURL))
				{
					dbg(T_('found current url in page'));
					$goodReferer = 1;
				}
			}
		} else {
			// Direct accesses are always good hits
			$goodReferer = 1;
		}

		if(!$goodReferer)
		{	// This was probably spam!
			dbg( sprintf('did not find %s in %s', $fullCurrentURL, $page ) );
			$ref="";
			return;
		}

	}


	$baseDomain = preg_replace("/http:\/\//i", "", $ref);
	$baseDomain = preg_replace("/^www\./i", "", $baseDomain);
	$baseDomain = preg_replace("/\/.*/i", "", $baseDomain);

	$sql ="insert into $tablehitlog( visitTime, visitURL, hit_ignore, referingURL, baseDomain, hit_blog_ID, hit_remote_addr, hit_user_agent ) ";
	$sql .= "values( FROM_UNIXTIME(".$localtimenow."), '".addslashes($ReqURI)."', '$ignore', '".addslashes($ref)."', '".addslashes($baseDomain)."', $blog, '$RemoteAddr', '".addslashes($UserAgent)."')";


	// print $sql;

	mysql_query($sql) or mysql_oops( $sql );
	$querycount++;

}


/*
 * hit_delete(-)
 *
 * Delete a hit
 */
function hit_delete( $hit_ID )
{
	global $tablehitlog, $querycount;

	$sql ="DELETE FROM $tablehitlog WHERE visitID = $hit_ID";
	$querycount++;
	mysql_query($sql) or mysql_oops( $sql );

}

/*
 * hit_prune(-)
 *
 * Delete all hits from a certain date
 */
function hit_prune( $date )
{
	global $tablehitlog, $querycount;

	$iso_date = date ('Y-m-d', $date);
	$sql ="DELETE FROM $tablehitlog WHERE DATE_FORMAT(visitTime,'%Y-%m-%d') = '$iso_date'";
	$querycount++;
	mysql_query($sql) or mysql_oops( $sql );

}

/*
 * hit_change_type(-)
 *
 * Change type for a hit
 */
function hit_change_type( $hit_ID, $type )
{
	global $tablehitlog, $querycount;

	$sql ="UPDATE $tablehitlog ".
				"SET hit_ignore = '$type', ".
				"    visitTime = visitTime ".	// prevent mySQL from updating timestamp
				"WHERE visitID = $hit_ID";
	$querycount++;
	mysql_query($sql) or mysql_oops( $sql );

}


/*
 * list_antiSpam(-)
 *
 * Extract anti-spam
 */
function list_antiSpam()
{
	global 	$querycount, $tableantispam, $res_stats;

	$sql = "SELECT * FROM $tableantispam ORDER BY domain ASC";
	$res_stats = mysql_query( $sql ) or mysql_oops( $sql );
	$querycount++;
}

/*
 * antiSpam_ID(-)
 */
function antiSpam_ID()
{
	global $row_stats;
	echo $row_stats['ID'];
}

/*
 * antiSpam_domain(-)
 */
function antiSpam_domain()
{
	global $row_stats;
	echo $row_stats['domain'];
}

/*
 * get_domain_from_hit_ID(-)
 *
 * Gets the baseDomain for a certain hit ID. (duh)
 */
function get_domain_from_hit_ID( $hit_ID )
{
	global $tablehitlog, $querycount;

	$sql ="SELECT baseDomain FROM $tablehitlog WHERE visitID = '$hit_ID' LIMIT 1";
	$querycount++;
	$q = mysql_query($sql) or mysql_oops( $sql );
	while( list($domain) = mysql_fetch_row($q) )
	{
		return $domain;
	}
}

/*
 * domain_ban(-)
 *
 * Ban a domain
 */
function domain_ban( $hit_ID )
{
	global $tablehitlog, $tablecomments, $tableantispam, $querycount, $deluxe_ban;

	$domain = get_domain_from_hit_ID($hit_ID);
	$sql ="INSERT INTO $tableantispam VALUES ('', '$domain')";
	$querycount++;
	mysql_query($sql) or mysql_oops( $sql );
	
	if ( $deluxe_ban )
	{
		// Delete all banned comments and stats entries
		// Stats entries first
		$sql ="DELETE FROM $tablehitlog WHERE baseDomain = '$domain'";	// This is quite drastic!
		$querycount++;
		mysql_query($sql) or mysql_oops( $sql );
		
		// Then comments
		$sql ="DELETE FROM $tablecomments WHERE comment_author_url LIKE '%$domain%'";	// This is quite drastic!
		$querycount++;
		mysql_query($sql) or mysql_oops( $sql );
	}

}

/*
 * keyword_ban(-)
 *
 * Ban any URL containing a certain keyword
 */
function keyword_ban( $keyword )
{
	global $tableantispam, $tablehitlog, $tablecomments, $querycount, $deluxe_ban;

	$sql ="INSERT INTO $tableantispam VALUES ('', '$keyword')";
	$querycount++;
	mysql_query($sql) or mysql_oops( $sql );
	
	if ( $deluxe_ban )
	{
		// Delete all banned comments and stats entries
		// Stats entries first
		$sql ="DELETE FROM $tablehitlog WHERE baseDomain LIKE '%$keyword%'";	// This is quite drastic!
		$querycount++;
		mysql_query($sql) or mysql_oops( $sql );
		
		// Then comments
		$sql ="DELETE FROM $tablecomments WHERE comment_author_url LIKE '%$keyword%'";	// This is quite drastic!
		$querycount++;
		mysql_query($sql) or mysql_oops( $sql );
	}
}

/*
 * remove_ban(-)
 *
 * Remove a domain from the ban list
 */
function remove_ban( $hit_ID )
{
	global $tableantispam, $querycount;

	$sql ="DELETE FROM $tableantispam WHERE ID = '$hit_ID'";
	$querycount++;
	mysql_query($sql) or mysql_oops( $sql );
}

/*
 * ban_affected_hits(-)
 */
function ban_affected_hits($banned, $type)
{
	global  $querycount, $tablehitlog, $res_affected_hits;

	switch( $type )
	{
		case "hit_ID":
			$domain = get_domain_from_hit_ID($banned);
			$sql = "SELECT * FROM $tablehitlog WHERE baseDomain = '$domain' ORDER BY baseDomain ASC";
			break;
		case "keyword":
		default:
			// Assume it's a keyword
			$sql = "SELECT * FROM $tablehitlog WHERE baseDomain LIKE '%$banned%' ORDER BY baseDomain ASC";
			break;
	}
	$res_affected_hits = mysql_query( $sql ) or mysql_oops( $sql );
	$querycount++;
}

/*
 * ban_affected_comments(-)
 */
function ban_affected_comments($banned, $type)
{
	global  $querycount, $tablecomments, $res_affected_comments;

	switch( $type )
	{
		case "hit_ID":
			$domain = get_domain_from_hit_ID($banned);
			$sql = "SELECT comment_author, comment_author_url, comment_date, comment_content FROM $tablecomments WHERE comment_author_url LIKE '%$domain%' ORDER BY comment_date ASC";
			break;
		case "keyword":
		default:
			// Assume it's a keyword
			$sql = "SELECT comment_author, comment_author_url, comment_date, comment_content FROM $tablecomments WHERE comment_author_url LIKE '%$banned%' ORDER BY comment_date ASC";
			break;
	}
	$res_affected_comments = mysql_query( $sql ) or mysql_oops( $sql );
	$querycount++;
}



/*
 * refererList(-)
 *
 * Extract stats
 */
function refererList(
	$howMany=5,
	$visitURL="", 
	$disp_blog=0, 
	$disp_uri=0, 
	$type = "'no'",		// 'no' normal refer, 'invalid', 'badchar', 'blacklist', 'rss', 'robot', 'search'
	$groupby = '', 	// baseDomain
	$blog_ID ='',
	$get_total_hits = false, // Get total number of hits (needed for percentages)
	$get_user_agent = false ) // Get the user agent
{
	global 	$querycount, $tablehitlog, $res_stats, $stats_total_hits;
	$i=2;

	autoquote( $type );		// In case quotes are missing

	$ret = Array();

	//if no visitURL, will show links to current page.
	//if url given, will show links to that page.
	//if url="global" will show links to all pages
	if (!$visitURL){
		$visitURL = $_SERVER['REQUEST_URI'];
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

	//echo $sql;
	$res_stats = mysql_query( $sql ) or mysql_oops( $sql );
	$querycount++;

	if( $get_total_hits )
	{	// we need to get total hits
		$sql = "SELECT COUNT(*) AS total_hits ".$sql_from_where;
		$res_total_hits = mysql_query( $sql ) or mysql_oops( $sql );
		$querycount++;
		$row_total_hits = mysql_fetch_array($res_total_hits);
		$stats_total_hits = $row_total_hits['total_hits'];
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
	$ref = trim(stripslashes($row_stats['referingURL']));
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
function stats_basedomain()
{
	global $row_stats;
	echo htmlentities( stripslashes($row_stats['baseDomain']));
}

/*
 * stats_search_keywords(-)
 *
 * Displays keywords used for search leading to this page
 */
function stats_search_keywords()
{
	global $row_stats;
	$kwout = '';
	$ref = stripslashes($row_stats['referingURL']);
	if( ($pos_question = strpos( $ref, '?' )) == false )
	{
		echo '[', T_('not a query - no params!'), ']';
		return;
	}
	$ref_params = explode( '&', substr( $ref, $pos_question+1 ) );
	foreach( $ref_params as $ref_param )
	{
		$param_parts = explode( '=', $ref_param );
		if( $param_parts[0] == 'q' or $param_parts[0] == 'query' or $param_parts[0] == 'p' or $param_parts[0] == 'kw')
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
	echo htmlentities(stripslashes($row_stats['visitURL']));
}

/*
 * stats_user_agent(-)
 */
function stats_user_agent( $translate = false )
{
	global $row_stats, $user_agents;
	$UserAgent = stripslashes($row_stats['hit_user_agent']);
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



/* select count(*) as nb, hit_ignore
from b2hitlog
group by hit_ignore
order by nb desc 


update b2hitlog
set hit_ignore ='robot' 
where `hit_ignore` LIKE 'invalid' AND `hit_user_agent` LIKE 'FAST-WebCrawler/%'  



*/

?>
