<?php
/**
 * This file implements Antispam handling functions.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2007 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 * Parts of this file are copyright (c)2004 by Vegar BERG GULDAL - {@link http://funky-m.com/}.
 * Parts of this file are copyright (c)2005 by The University of North Carolina at Charlotte as
 * contributed by Jason Edgecombe {@link http://tst.uncc.edu/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 *
 * Vegar BERG GULDAL grants Francois PLANQUE the right to license
 * Vegar BERG GULDAL's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 *
 * The University of North Carolina at Charlotte grants Francois PLANQUE the right to license
 * Jason EDGECOMBE's contributions to this file and the b2evolution project
 * under the GNU General Public License (http://www.opensource.org/licenses/gpl-license.php)
 * and the Mozilla Public License (http://www.opensource.org/licenses/mozilla1.1.php).
 *  }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 * @author vegarg: Vegar BERG GULDAL.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * antispam_create(-)
 *
 * Insert a new abuse string into DB
 */
function antispam_create( $abuse_string, $aspm_source = 'local' )
{
	global $DB;

	// Cut the crap if the string is empty:
	$abuse_string = trim( $abuse_string );
	if( empty( $abuse_string ) ) return false;

	// Check if the string already is in the blacklist:
	if( antispam_check($abuse_string) ) return false;

	// Insert new string into DB:
	$sql = "INSERT INTO T_antispam( aspm_string, aspm_source )
					VALUES( '".$DB->escape($abuse_string)."', '$aspm_source' )";
	$DB->query( $sql );

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
 * Check if a string contains abusive substrings
 *
 * Note: Letting the database do the LIKE %% match is a little faster than doing in it PHP,
 * not to mention the incredibly long overhead of preloading the list into PHP
 *
 * @todo dh> IMHO this method is too generic used! It gets used for:
 *           - comment author name
 *           - comment/message author email
 *           - comment content
 *           - message (email) content
 *           - validate_url()
 *           ..and validates all this against the antispam blacklist!
 *           We should rather differentiate here more and make it pluggable!
 *
 * @return string blacklisted keyword found or false if no spam detected
 */
function antispam_check( $haystack )
{
	global $DB, $Debuglog, $Timer;

	// TODO: 'SELECT COUNT(*) FROM T_antispam WHERE aspm_string LIKE "%'.$url.'%" ?

	$Timer->resume( 'antispam_url' ); // resuming to get the total number..
	$block = $DB->get_var(
		"SELECT aspm_string
		   FROM  T_antispam
		  WHERE ".$DB->quote($haystack)." LIKE CONCAT('%',aspm_string,'%')
		  LIMIT 0, 1", 0, 0, 'Check URL against antispam blacklist' );
	if( $block )
	{
			$Debuglog->add( 'Spam block: '.$block );
			return $block;	// SPAM detected!
	}
	$Timer->pause( 'antispam_url' );

	return false;	// no problem.
}


// -------------------- XML-RPC callers ---------------------------

/**
 * Pings b2evolution.net to report abuse from a particular domain.
 *
 * @param string The keyword to report as abuse.
 * @return boolean True on success, false on failure.
 */
function antispam_report_abuse( $abuse_string )
{
	global $debug, $antispamsrv_host, $antispamsrv_port, $antispamsrv_uri, $antispam_test_for_real;
	global $baseurl, $Messages;

	if( preg_match( '#^http://localhost[/:]#', $baseurl) && ( $antispamsrv_host != 'localhost' ) && empty( $antispam_test_for_real )  )
	{ // Local install can only report to local test server
		$Messages->add( T_('Reporting abuse to b2evolution aborted (Running on localhost).'), 'error' );
		return(false);
	}

	// Construct XML-RPC client:
	load_funcs('_ext/xmlrpc/_xmlrpc.php' );
	$client = new xmlrpc_client( $antispamsrv_uri, $antispamsrv_host, $antispamsrv_port);
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
	if( $ret = xmlrpc_logresult( $result, $Messages ) )
	{ // Remote operation successful:
		antispam_update_source( $abuse_string, 'reported' );

		$Messages->add( sprintf( T_('Reported abuse to %s.'), $antispamsrv_host ), 'success' );
	}
	else
	{
		$Messages->add( T_('Failed to report abuse to b2evolution.net.'), 'error' );
	}

	return($ret);
}


/**
 * Request abuse list from central blacklist.
 *
 * @return boolean true = success, false = error
 */
function antispam_poll_abuse()
{
	global $Messages, $Settings, $baseurl, $debug, $antispamsrv_host, $antispamsrv_port, $antispamsrv_uri;

	// Construct XML-RPC client:
	load_funcs('_ext/xmlrpc/_xmlrpc.php' );
	$client = new xmlrpc_client( $antispamsrv_uri, $antispamsrv_host, $antispamsrv_port);
	$client->debug = $debug;

	// Get datetime from last update, because we only want newer stuff...
	$last_update = $Settings->get( 'antispam_last_update' );
	// Encode it in the XML-RPC format
	$Messages->add( T_('Latest update timestamp').': '.$last_update, 'note' );
	$startat = mysql2date( 'Ymd\TH:i:s', $last_update );
	//$startat = iso8601_encode( mktime(substr($m,11,2),substr($m,14,2),substr($m,17,2),substr($m,5,2),substr($m,8,2),substr($m,0,4)) );

	// Construct XML-RPC message:
	$message = new xmlrpcmsg(
								'b2evo.pollabuse',                            // Function to be called
								array(
									new xmlrpcval(0,'int'),                     // Reserved
									new xmlrpcval('annonymous','string'),       // Reserved
									new xmlrpcval('nopassrequired','string'),   // Reserved
									new xmlrpcval($startat,'dateTime.iso8601'), // Datetime to start at
									new xmlrpcval(0,'int')                      // Reserved
								)
							);

	$Messages->add( sprintf( T_('Requesting abuse list from %s...'), $antispamsrv_host ), 'note' );

	$result = $client->send($message);

	if( $ret = xmlrpc_logresult( $result, $Messages ) )
	{ // Response is not an error, let's process it:
		$response = $result->value();
		if( $response->kindOf() == 'struct' )
		{ // Decode struct:
			$response = xmlrpc_decode_recurse($response);
			if( !isset( $response['strings'] ) || !isset( $response['lasttimestamp'] ) )
			{
				$Messages->add( T_('Incomplete reponse.'), 'error' );
				$ret = false;
			}
			else
			{ // Start registering strings:
				$value = $response['strings'];
				if( count($value) == 0 )
				{
					$Messages->add( T_('No new blacklisted strings are available.'), 'note' );
				}
				else
				{ // We got an array of strings:
					$Messages->add( T_('Adding strings to local blacklist:'), 'note' );
					foreach($value as $banned_string)
					{
						if( antispam_create( $banned_string, 'central' ) )
						{ // Creation successed
							$Messages->add( T_('Adding:').' &laquo;'.$banned_string.'&raquo;: '
								.T_('OK.'), 'note' );
						}
						else
						{ // Was already handled
							$Messages->add( T_('Adding:').' &laquo;'.$banned_string.'&raquo;: '
								.T_('Not necessary! (Already handled)'), 'note' );
							antispam_update_source( $banned_string, 'central' );
						}
					}
					// Store latest timestamp:
					$endedat = date('Y-m-d H:i:s', iso8601_decode($response['lasttimestamp']) );
					$Messages->add( T_('New latest update timestamp').': '.$endedat, 'note' );

					$Settings->set( 'antispam_last_update', $endedat );
					$Settings->dbupdate();
				}
				$Messages->add( T_('Done.'), 'success' );
			}
		}
		else
		{
			$Messages->add( T_('Invalid reponse.'), 'error' );
			$ret = false;
		}
	}

	return($ret);
}


/**
 * Get the base domain that could be blacklisted from an URL.
 *
 * We want to concentrate on the main domain and we want to prefix it with either . or // in order not
 * to blacklist too large.
 *
 * {@internal This function gets tested in _misc.funcs.simpletest.php}}
 *
 * @param string URL or domain
 * @return string|false the pattern to match this domain in the blacklist; false if we could not extract the base domain
 */
function get_ban_domain( $url )
{
	// echo '<p>'.$url;

	// Remove http:// part + everything after the last path element ( '/' alone is ignored on purpose )
	$domain = preg_replace( '~^ ([a-z]+://)? ([^/#]+) (/ ([^/]*/)+ )? .* ~xi', '\\2\\3', $url );

	// echo '<br>'.$domain;

	if( preg_match( '~^[0-9.]+$~', $domain ) )
	{	// All numeric = IP address, don't try to cut it any further
		return '//'.$domain;
	}

	// Remove any www*. prefix:
	$base_domain = preg_replace( '~^(www \w* \. )~xi', '', $domain );

	if( empty($base_domain) )
	{
		return false;
	}

	if( strlen( $base_domain ) < strlen( $domain ) )
	{	// The guy is spamming with subdomains (or www):
		return '.'.$base_domain;
	}

	// The guy is spamming with the base domain:
	return '//'.$base_domain;
}


/*
 * $Log$
 * Revision 1.3  2007/11/28 16:38:21  fplanque
 * minor
 *
 * Revision 1.2  2007/09/22 19:23:56  fplanque
 * various fixes & enhancements
 *
 * Revision 1.1  2007/09/04 14:56:18  fplanque
 * antispam cleanup
 *
 * Revision 1.1  2007/06/25 10:59:18  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.20  2007/04/26 00:11:03  fplanque
 * (c) 2007
 *
 * Revision 1.19  2007/03/19 21:22:38  blueyed
 * TODO antispam_check()
 *
 * Revision 1.18  2006/12/19 17:21:54  blueyed
 * Fixed domain extraction if anchor (#) follows domain name directly. See http://forums.b2evolution.net/viewtopic.php?p=48672#48672
 *
 * Revision 1.17  2006/11/21 19:18:39  fplanque
 * get_base_domain()  / get_ban_domain() may need more unit tests, especially about what to do when invalid URLs are passed.
 *
 * Revision 1.16  2006/11/16 22:43:17  blueyed
 * resume/pause antispam_url timer instead of start/stopping, because it may get called more than once
 */
?>