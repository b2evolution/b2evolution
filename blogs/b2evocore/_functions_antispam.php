<?php
/**
 * This file implements Antispam handling functions.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evocore
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );


/**
 * antispam_create(-)
 *
 * Insert a new abuse string into DB
 */
function antispam_create( $abuse_string, $aspm_source = 'local' )
{
	global $DB, $cache_antispam;

	// Cut the crap if the string is empty:
	$abuse_string = trim( $abuse_string );
	if( empty( $abuse_string ) ) return false;

	// Check if the string already is in the blacklist:
	if( antispam_url($abuse_string) ) return false;

	// Insert new string into DB:
	$sql = "INSERT INTO T_antispam( aspm_string, aspm_source )
					VALUES( '".$DB->escape($abuse_string)."', '$aspm_source' )";
	$DB->query( $sql );

	// Insert into cache:
	$cache_antispam[] = $abuse_string;

	return true;
}


/**
 * antispam_update_source(-)
 *
 * Note: We search by string because we sometimes don't know the ID
 * (e-g when download already in list/cache)
 */
function antispam_update_source( $aspm_string, $aspm_source )
{
	global $DB;

	$sql = "UPDATE T_antispam
					SET aspm_source = '$aspm_source'
					WHERE aspm_string = '".$DB->escape($aspm_string)."'";
	$DB->query( $sql );
}

/*
 * antispam_delete(-)
 *
 * Remove an entry from the ban list
 */
function antispam_delete( $string_ID )
{
	global $DB;

	$sql = "DELETE FROM T_antispam
					WHERE aspm_ID = $string_ID";
	$DB->query( $sql );
}


/**
 * Check if an URL contains abusive substrings
 *
 * antispam_url(-)
 */
function antispam_url( $url )
{
	global $DB, $cache_antispam, $Debuglog;

	if( !isset($cache_antispam))
	{ // Cache not loaded, load now:
		$cache_antispam = $DB->get_col( 'SELECT aspm_string FROM T_antispam' );
	}

	// Check URL for abuse:
	foreach( $cache_antispam as $block )
	{
		if( stristr($url, $block) !== false)
		{
			$Debuglog->add( 'Spam block: '.$block );
			return $block;	// SPAM detected!
		}
	}

	return false;	// no problem.

}



// -------------------- XML-RPC callers ---------------------------

/*
 * b2evonet_report_abuse(-)
 *
 * pings b2evolution.net to report abuse from a particular domain
 */
function b2evonet_report_abuse( $abuse_string, $display = true )
{
	global $debug, $evonetsrv_host, $evonetsrv_port, $evonetsrv_uri;

	global $baseurl;
	if( $display )
	{
		echo "<div class=\"panelinfo\">\n";
		echo '<h3>', T_('Reporting abuse to b2evolution.net...'), "</h3>\n";
	}
	if( !preg_match( '#^http://localhost[/:]#', $baseurl) || ( $evonetsrv_host == 'localhost' ) )
	{ // Local install can only report to local test server
		// Construct XML-RPC client:
		$client = new xmlrpc_client( $evonetsrv_uri, $evonetsrv_host, $evonetsrv_port);
		$client->debug = $debug;

		// Construct XML-RPC message:
		$message = new xmlrpcmsg(
									'b2evo.reportabuse',                        // Function to be called
									array(
										new xmlrpcval(0,'int'),                   // Reserved
										new xmlrpcval('annonymous','string'),     // Reserved
										new xmlrpcval('nopassrequired','string'), // Reserved
										new xmlrpcval($abuse_string,'string'),    // The abusive string to report
										new xmlrpcval($baseurl,'string'),         // The base URL of this b2evo
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
	global $Settings, $baseurl, $debug, $evonetsrv_host, $evonetsrv_port, $evonetsrv_uri;

	if( $display )
	{
		echo "<div class=\"panelinfo\">\n";
		echo '<h3>', T_('Requesting abuse list from b2evolution.net...'), "</h3>\n";
	}

	// Construct XML-RPC client:
	$client = new xmlrpc_client( $evonetsrv_uri, $evonetsrv_host, $evonetsrv_port);
	$client->debug = $debug;

	// Get datetime from last update, because we only want newer stuff...
	$m = $Settings->get( 'antispam_last_update' );
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
						{	// Creation successed
							echo T_('OK.');
						}
						else
						{ // Was already handled
							echo T_('Not necessary! (Already handled)');
							antispam_update_source( $banned_string, 'central' );
						}
						echo '</li>';
					}
					echo '</ul>';
					// Store latest timestamp:
					$endedat = date('Y-m-d H:i:s', iso8601_decode($response['lasttimestamp']) );
					echo '<p>', T_('New latest update timestamp'), ': ', $endedat, '</p>';

					$Settings->set( 'antispam_last_update', $endedat );
					$Settings->updateDB();
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