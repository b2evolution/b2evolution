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
 * Parts of this file are copyright (c)2005 by The University of North Carolina at Charlotte as
 * contributed by Jason Edgecombe {@link http://tst.uncc.edu/}.
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
 * @return string blacklisted keyword found or false if no spam detected
 */
function antispam_check( $haystack )
{
	global $DB, $Debuglog, $Timer;

	// TODO: 'SELECT COUNT(*) FROM T_antispam WHERE aspm_string LIKE "%'.$url.'%" ?
	// TODO: Check basedomain against T_basedomains (dom_status = 'blacklist')

	$Timer->start( 'antispam_url' );
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
	$Timer->stop( 'antispam_url' );

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
	global $debug, $evonetsrv_host, $evonetsrv_port, $evonetsrv_uri;
	global $baseurl, $Messages;

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
		if( $ret = xmlrpc_logresult( $result, $Messages ) )
		{ // Remote operation successful:
			antispam_update_source( $abuse_string, 'reported' );

			$Messages->add( T_('Reported abuse to b2evolution.net.'), 'success' );
		}
		else
		{
			$Messages->add( T_('Failed to report abuse to b2evolution.net.'), 'error' );
		}

		return($ret);
	}
	else
	{
		$Messages->add( T_('Reporting abuse to b2evolution aborted (Running on localhost).'), 'error' );
		return(false);
	}
}


/**
 * Request abuse list from central blacklist.
 *
 * @param boolean Display while fetching it?
 */
function antispam_poll_abuse( $display = true )
{
	global $Messages, $Settings, $baseurl, $debug, $evonetsrv_host, $evonetsrv_port, $evonetsrv_uri;

	// Construct XML-RPC client:
	$client = new xmlrpc_client( $evonetsrv_uri, $evonetsrv_host, $evonetsrv_port);
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


	$Messages->add( T_('Requesting abuse list from b2evolution.net...'), 'note' );

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

/*
 * $Log$
 * Revision 1.20  2005/12/12 19:21:21  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.19  2005/11/18 22:05:41  fplanque
 * no message
 *
 * Revision 1.18  2005/11/18 18:32:42  fplanque
 * Fixed xmlrpc logging insanity
 * (object should have been passed by reference but you can't pass NULL by ref)
 * And the code was geeky/unreadable anyway.
 *
 * Revision 1.17  2005/11/16 22:33:46  blueyed
 * removed rudimentary dnsrbl feature
 *
 * Revision 1.16  2005/10/28 20:26:43  blueyed
 * Handle failed update of antispam strings correctly.
 *
 * Revision 1.15  2005/10/28 02:37:37  blueyed
 * Normalized AbstractSettings API
 *
 * Revision 1.14  2005/09/19 14:13:03  fplanque
 * no message
 *
 * Revision 1.13  2005/09/17 18:13:21  blueyed
 * Typo, whitespace.
 *
 * Revision 1.12  2005/09/07 17:40:22  fplanque
 * enhanced antispam
 *
 * Revision 1.11  2005/09/06 17:13:54  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.10  2005/08/08 22:54:41  blueyed
 * Re-activated /admin/antispam, with slight improvements. Still needs a lot more love.
 *
 * Revision 1.9  2005/04/20 18:37:59  fplanque
 * Relocation of javascripts and CSS files to their proper places...
 *
 * Revision 1.8  2005/04/19 20:39:37  jwedgeco
 * I forgot to add the copyright and license text. I added it.
 *
 * Revision 1.7  2005/04/19 20:34:11  jwedgeco
 * Added Real-time DNS blacklist support.
 * Configure in conf/advanced.php.
 *
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