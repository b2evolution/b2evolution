<?php
/**
 * This file implements Antispam handling functions.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}.
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
	global $debug, $antispamsrv_host, $antispamsrv_port, $antispamsrv_uri;
	global $baseurl, $Messages;

	if( preg_match( '#^http://localhost[/:]#', $baseurl) && ( $antispamsrv_host != 'localhost' ) )
	{ // Local install can only report to local test server
		$Messages->add( T_('Reporting abuse to b2evolution aborted (Running on localhost).'), 'error' );
		return(false);
	}

	// Construct XML-RPC client:
	load_funcs( '_misc/ext/_xmlrpc.php' );
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
 * @param boolean Display while fetching it?
 * @return boolean true = success, false = error
 */
function antispam_poll_abuse()
{
	global $Messages, $Settings, $baseurl, $debug, $antispamsrv_host, $antispamsrv_port, $antispamsrv_uri;

	// Construct XML-RPC client:
	load_funcs( '_misc/ext/_xmlrpc.php' );
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
	$domain = preg_replace( '~^ ([a-z]+://)? ([^/]+) (/ ([^/]*/)+ )? .* ~xi', '\\2\\3', $url );

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
 * Revision 1.17  2006/11/21 19:18:39  fplanque
 * get_base_domain()  / get_ban_domain() may need more unit tests, especially about what to do when invalid URLs are passed.
 *
 * Revision 1.16  2006/11/16 22:43:17  blueyed
 * resume/pause antispam_url timer instead of start/stopping, because it may get called more than once
 *
 * Revision 1.15  2006/08/19 07:56:30  fplanque
 * Moved a lot of stuff out of the automatic instanciation in _main.inc
 *
 * Revision 1.14  2006/07/05 21:57:20  blueyed
 * trans
 *
 * Revision 1.13  2006/07/05 21:56:01  blueyed
 * trans
 *
 * Revision 1.12  2006/07/04 17:32:29  fplanque
 * no message
 *
 * Revision 1.11  2006/07/01 23:27:49  fplanque
 * better base domain isolation
 *
 * Revision 1.10  2006/06/26 23:09:34  fplanque
 * Really working cronjob environment :)
 *
 * Revision 1.9  2006/05/30 21:59:46  blueyed
 * Fixed E_FATAL with polling of blacklist..
 *
 * Revision 1.8  2006/05/19 18:15:05  blueyed
 * Merged from v-1-8 branch
 *
 * Revision 1.7.2.1  2006/05/19 15:06:24  fplanque
 * dirty sync
 *
 * Revision 1.7  2006/05/12 21:53:37  blueyed
 * Fixes, cleanup, translation for plugins
 *
 * Revision 1.6  2006/04/28 11:16:17  blueyed
 * test, doc
 *
 * Revision 1.5  2006/04/27 20:10:34  fplanque
 * changed banning of domains. Suggest a prefix by default.
 *
 * Revision 1.4  2006/04/19 22:26:24  blueyed
 * cleanup/polish
 *
 * Revision 1.3  2006/04/07 22:43:20  blueyed
 * removed wrong todo
 *
 * Revision 1.2  2006/03/12 23:08:58  fplanque
 * doc cleanup
 *
 * Revision 1.1  2006/02/23 21:11:57  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
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