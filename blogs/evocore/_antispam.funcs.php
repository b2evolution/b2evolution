<?php
/**
 * This file implements Antispam handling functions.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 * Parts of this file are copyright (c)2004 by Vegar BERG GULDAL - {@link http://funky-m.com/}.
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
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 *
 * Vegar BERG GULDAL grants Francois PLANQUE the right to license
 * Vegar BERG GULDAL's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
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
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


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

	// TODO: 'SELECT COUNT(*) FROM T_antispam WHERE aspm_string LIKE "%'.$url.'%" ?
	// TODO: Check basedomain against T_basedomains (dom_status = 'blacklist')

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

/*
 * $Log$
 * Revision 1.6  2005/02/28 09:06:32  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.5  2005/02/25 02:26:37  blueyed
 * start of hitlog refactoring
 *
 * Revision 1.4  2004/10/17 20:18:37  fplanque
 * minor changes
 *
 * Revision 1.3  2004/10/16 15:28:51  vegarg
 * Added copyright notes to all files I can remember I have edited.
 *
 * Revision 1.2  2004/10/14 18:31:24  blueyed
 * granting copyright
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.35  2004/10/12 17:22:29  fplanque
 * Edited code documentation.
 *
 */
?>
