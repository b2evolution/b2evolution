<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 */


/*
 * antispam_create(-)
 *
 * Insert a new abuse string into DB
 */
function antispam_create( $abuse_string, $aspm_source = 'local' )
{
	global $tableantispam, $querycount, $cache_antispam;
	
	// Cut the crap if the string is empty:
	$abuse_string = trim( $abuse_string );
	if( empty( $abuse_string ) ) return false;
	
	// Check if the string already is in the blacklist:
	if( antispam_url($abuse_string) ) return false;
	
	// Insert new string into DB:
	$sql ="INSERT INTO $tableantispam( aspm_string, aspm_source ) VALUES( '$abuse_string', '$aspm_source' )";
	$querycount++;
	mysql_query($sql) or mysql_oops( $sql );

	// Insert into cache:
	$cache_antispam[] = $abuse_string;

	return true;
}


/*
 * antispam_update_source(-)
 *
 * Note: We search by string because we sometimes don't know the ID 
 * (e-g when download already in list/cache)
 */
function antispam_update_source( $aspm_string, $aspm_source )
{
	global $tableantispam, $querycount;
	
	$sql ="UPDATE $tableantispam SET aspm_source = '$aspm_source' WHERE aspm_string = '$aspm_string'";
	$querycount++;
	mysql_query($sql) or mysql_oops( $sql );
}

/*
 * antispam_delete(-)
 *
 * Remove an entry from the ban list
 */
function antispam_delete( $string_ID )
{
	global $tableantispam, $querycount;

	$sql ="DELETE FROM $tableantispam WHERE aspm_ID = $string_ID";
	$querycount++;
	mysql_query($sql) or mysql_oops( $sql );
}


/*
 * antispam_url(-)
 *
 * Check if an URL contains abusive substrings
 */
function antispam_url( $url )
{
	global $tableantispam, $querycount, $cache_antispam;

	if( !isset($cache_antispam)) 
	{	// Cache not loaded, load now:
		$query = "SELECT aspm_string FROM $tableantispam";
		$querycount++;
		$q = mysql_query( $query ) or mysql_oops( $query );
		$cache_antispam = array();
		while( list($tmp) = mysql_fetch_row($q) )
		{
			$cache_antispam[] = $tmp;
		}
	}
	
	// Check URL for abuse:
	foreach ($cache_antispam as $block)
	{
		if( strpos($url, $block) !== false)
		{
			return true;	// SPAM detected!
		}
	}

	return false;	// no problem.

}


/*
 * list_antiSpam(-)
 *
 * Extract anti-spam
 */
function list_antiSpam()
{
	global 	$querycount, $tableantispam, $res_stats;

	$sql = "SELECT aspm_ID, aspm_string, aspm_source FROM $tableantispam ORDER BY aspm_string ASC";
	$res_stats = mysql_query( $sql ) or mysql_oops( $sql );
	$querycount++;
}

/*
 * antiSpam_ID(-)
 */
function antiSpam_ID()
{
	global $row_stats;
	echo $row_stats['aspm_ID'];
}

/*
 * antiSpam_domain(-)
 */
function antiSpam_domain()
{
	global $row_stats;
	echo $row_stats['aspm_string'];
}


/*
 * antiSpam_domain(-)
 */
function antispam_source( $disp = true, $raw = false )
{
	global $row_stats, $aspm_sources;
	$asp_source = $row_stats['aspm_source'];
	if( ! $raw )
		$asp_source = T_(	$aspm_sources[$asp_source] );
	if( $disp )
		echo $asp_source;
	else
		return $asp_source;
}


/*
 * keyword_ban(-)
 *
 * Ban any URL containing a certain keyword
 */
function keyword_ban( $keyword )
{
	global $tablehitlog, $tablecomments, $querycount, $deluxe_ban, $auto_report_abuse;

	// Cut the crap if the string is empty:
	$keyword = trim( $keyword );
	if( empty( $keyword ) ) return false;

	echo '<div class="panelinfo">';
	printf( '<p>'.T_('Banning the keyword %s...').'</p>', $keyword);

	// Insert into DB:
	antispam_create( $keyword );
		
	if ( $deluxe_ban )
	{ // Delete all banned comments and stats entries
		echo '<p>'.T_('Removing all related comments and hits...').'</p>';
		// Stats entries first
		$sql ="DELETE FROM $tablehitlog WHERE baseDomain LIKE '%$keyword%'";	// This is quite drastic!
		$querycount++;
		mysql_query($sql) or mysql_oops( $sql );
		
		// Then comments
		$sql ="DELETE FROM $tablecomments WHERE comment_author_url LIKE '%$keyword%'";	// This is quite drastic!
		$querycount++;
		mysql_query($sql) or mysql_oops( $sql );
	}
	
	echo '</div>';
	
	// Report this keyword as abuse:
	if( $auto_report_abuse )
	{
		b2evonet_report_abuse( $keyword );
	}
}


/*
 * ban_affected_hits(-)
 *
 * find log hits that would be affected by a ban
 */
function ban_affected_hits($banned)
{
	global  $querycount, $tablehitlog, $res_affected_hits;

	$sql = "SELECT visitID, UNIX_TIMESTAMP(visitTime) AS visitTime, referingURL, baseDomain, hit_blog_ID, visitURL FROM $tablehitlog WHERE baseDomain LIKE '%$banned%' ORDER BY baseDomain ASC";
	$res_affected_hits = mysql_query( $sql ) or mysql_oops( $sql );
	$querycount++;
}

/*
 * ban_affected_comments(-)
 *
 * find comments that would be affected by a ban
 */
function ban_affected_comments($banned)
{
	global  $querycount, $tablecomments, $res_affected_comments;

	$sql = "SELECT comment_author, comment_author_url, comment_date, comment_content FROM $tablecomments WHERE comment_author_url LIKE '%$banned%' ORDER BY comment_date ASC";
	$res_affected_comments = mysql_query( $sql ) or mysql_oops( $sql );
	$querycount++;
}


// -------------------- XML-RPC callers ---------------------------

/*
 * b2evonet_report_abuse(-)
 *
 * pings b2evolution.net to report abuse from a particular domain
 */
function b2evonet_report_abuse( $abuse_string, $display = true ) 
{
	$test = 1;

	global $baseurl;
	if( $display )
	{	
		echo "<div class=\"panelinfo\">\n";
		echo '<h3>', T_('Reporting abuse to b2evolution.net...'), "</h3>\n";
	}
	if( !preg_match( '#^http://localhost[/:]#', $baseurl) || $test ) 
	{
		// Construct XML-RPC client:
		if( $test == 2 )
		{
		 	$client = new xmlrpc_client('/b2evolution/blogs/evonetsrv/xmlrpc.php', 'localhost', 8088);
			// $client->debug = 1;
		}
		else
		{
			$client = new xmlrpc_client('/evonetsrv/xmlrpc.php', 'b2evolution.net', 80);
			// $client->debug = 1;
		}
		
		// Construct XML-RPC message:
		$message = new xmlrpcmsg( 
									'b2evo.reportabuse',	 											// Function to be called
									array( 
										new xmlrpcval(0,'int'),										// Reserved
										new xmlrpcval('annonymous','string'),			// Reserved
										new xmlrpcval('nopassrequired','string'),	// Reserved
										new xmlrpcval($abuse_string,'string'),		// The abusive string to report
										new xmlrpcval($baseurl,'string'),					// The base URL of this b2evo
									)  
								);
		$result = $client->send($message);
		if( $ret = xmlrpc_displayresult( $result ) )
		{	// Remote operation successful:
			antispam_update_source( $abuse_string, 'reported' );
		}

		if( $display ) echo '<p>', T_('Done.'), "</p>\n</div>\n";
		return($ret);
	} 
	else 
	{
		if( $display ) echo "<p>", T_('Aborted (Running on localhost).'), "</p>\n</div>\n";
		return(false);
	}
}


/*
 * b2evonet_poll_abuse(-)
 *
 * request abuse list from central blacklist
 */
function b2evonet_poll_abuse( $display = true ) 
{
	$test = 1;

	global $baseurl, $tablesettings, $querycount;
	
	if( $display )
	{	
		echo "<div class=\"panelinfo\">\n";
		echo '<h3>', T_('Requesting abuse list from b2evolution.net...'), "</h3>\n";
	}

	// Construct XML-RPC client:
	if( $test == 2 )
	{
		$client = new xmlrpc_client('/b2evolution/blogs/evonetsrv/xmlrpc.php', 'localhost', 8088);
		// $client->debug = 1;
	}
	else
	{
		$client = new xmlrpc_client('/evonetsrv/xmlrpc.php', 'b2evolution.net', 80);
		// $client->debug = 1;
	}
	
	// Get datetime from last update, because we only want newer stuff...
	$m = get_settings( 'last_antispam_update' );
	// Encode it in the XML-RPC format
	echo '<p>', T_('Latest update timestamp'), ': ', $m, '</p>';
	$startat = mysql2date( 'Ymd\TH:i:s', $m );
	//$startat = iso8601_encode( mktime(substr($m,11,2),substr($m,14,2),substr($m,17,2),substr($m,5,2),substr($m,8,2),substr($m,0,4)) );
	
	// Construct XML-RPC message:
	$message = new xmlrpcmsg( 
								'b2evo.pollabuse',	 													// Function to be called
								array( 
									new xmlrpcval(0,'int'),											// Reserved
									new xmlrpcval('annonymous','string'),				// Reserved
									new xmlrpcval('nopassrequired','string'),		// Reserved
									new xmlrpcval($startat,'dateTime.iso8601'),	// Datetime to start at
									new xmlrpcval(0,'int')											// Reserved
								)  
							);
	$result = $client->send($message);
	
	if( $ret = xmlrpc_displayresult( $result ) )
	{	// Response is not an error, let's process it:
		$response = $result->value();
		if( $response->kindOf() == 'struct' )
		{	// Decode struct:
			$response = xmlrpc_decode_recurse($response);
			if( !isset( $response['strings'] ) || !isset( $response['lasttimestamp'] ) )
			{	
				echo T_('Incomplete reponse.')."\n";
				$ret = false;
			}
			else
			{	// Start registering strings: 
				$value = $response['strings'];
				if( count($value) == 0 )
				{
					echo '<p>', T_('No new blacklisted strings are available.'), '</p>';
				}
				else
				{	// We got an array of strings:
					echo '<p>', T_('Adding strings to local blacklist'), ':</p><ul>';
					foreach($value as $banned_string)
					{
						echo '<li>', T_('Adding:'), ' [', $banned_string, '] : ';
						if( antispam_create( $banned_string, 'central' ) )
						{
							echo T_('OK.');
						}
						else
						{
							echo T_('Not necessary! (Already handled)');
							antispam_update_source( $banned_string, 'central' );
						}
						echo '</li>';
					}
					echo '</ul>';
					// Store latest timestamp:
					$endedat = date('Y-m-d H:i:s', iso8601_decode($response['lasttimestamp']) );
					echo '<p>', T_('New latest update timestamp'), ': ', $endedat, '</p>';
					
					$sql ="UPDATE $tablesettings SET last_antispam_update = '$endedat'";
					$querycount++;
					mysql_query($sql) or mysql_oops( $sql );
				}				
			}
		}
		else
		{
			echo T_('Invalid reponse.')."\n";
			$ret = false;
		}
	}

	if( $display ) echo '<p>', T_('Done.'), "</p>\n</div>\n";
	return($ret);
}


?>