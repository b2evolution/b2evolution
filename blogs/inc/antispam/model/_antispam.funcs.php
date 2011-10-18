<?php
/**
 * This file implements Antispam handling functions.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}.
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
	if( empty( $abuse_string ) )
	{
		return false;
	}

	// Check if the string already is in the blacklist:
	if( antispam_check($abuse_string) )
	{
		return false;
	}

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
 *           - validate_url
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
		'SELECT aspm_string
		   FROM  T_antispam
		  WHERE '.$DB->quote($haystack).' LIKE CONCAT("%",aspm_string,"%")
		  LIMIT 0, 1', 0, 0, 'Check URL against antispam blacklist' );
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
	global $baseurl, $Messages, $Settings;

	if( ! $Settings->get('antispam_report_to_central') )
	{
		$Messages->add( 'Reporting is disabled.', 'error' );  // NO TRANS necessary
		return;
	}

	if( preg_match( '#^http://localhost[/:]#', $baseurl) && ( $antispamsrv_host != 'localhost' ) && empty( $antispam_test_for_real )  )
	{ // Local install can only report to local test server
		$Messages->add( T_('Reporting abuse to b2evolution aborted (Running on localhost).'), 'error' );
		return(false);
	}

	// Construct XML-RPC client:
	load_funcs('xmlrpc/model/_xmlrpc.funcs.php');
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
	load_funcs('xmlrpc/model/_xmlrpc.funcs.php');
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

	if( evo_strlen( $base_domain ) < evo_strlen( $domain ) )
	{	// The guy is spamming with subdomains (or www):
		return '.'.$base_domain;
	}

	// The guy is spamming with the base domain:
	return '//'.$base_domain;
}


/**
 * Get the blog restricted condition
 *
 * Creates an sql command part, which is a condition, that restrict to show comments from those blogs,
 * where current user has no edit permission for comments.
 * It is used by the antispam.ctrl, when current_User wants to delete the affected comments.
 * 
 * @param boolean delete draft comments
 * @param boolean delete published comments
 * @param boolean delete deprecated comments
 * @return string sql WHERE condition part, corresponding the user permissions
 */
function blog_restrict( $deldraft, $delpubl, $deldepr )
{
	global $current_User;
	$BlogCache = & get_BlogCache();
	$draft = '';
	$published = '';
	$deprecated = '';
	for( $l_Blog = & $BlogCache->get_first(); !is_null($l_Blog); $l_Blog = & $BlogCache->get_next() )
	{ // check all blogs permission
		if( $deldraft && $current_User->check_perm( 'blog_draft_comments', 'edit', false, $l_Blog->ID ) )
		{
			$draft .= $l_Blog->ID.','; 
		}
		if( $delpubl != null && $current_User->check_perm( 'blog_published_comments', 'edit', false, $l_Blog->ID ) )
		{
			$published .= $l_Blog->ID.','; 
		}
		if( $deldepr && $current_User->check_perm( 'blog_deprecated_comments', 'edit', false, $l_Blog->ID ) )
		{
			$deprecated .= $l_Blog->ID.','; 
		}
	}
	// blog and comment status restriction condition
	$restriction = '( comment_status = "%s" AND comment_post_ID IN 
					(SELECT post_ID from T_items__item INNER JOIN T_categories ON post_main_cat_ID = cat_ID
						WHERE cat_blog_ID IN (%s) ) )';
	$or = '';
	if( $draft != '' )
	{ // there is at least one blog on which current user has edit draft comments permission
		$draft = substr( $draft, 0, strlen($draft) - 1 );
		$draft = sprintf( $restriction, 'draft', $draft );
		$or = ' OR ';
	}
	if( $published != '' )
	{ // there is at least one blog on which current user has edit published comments permission
		$published = substr( $published, 0, strlen($published) - 1 );
		$published = $or.sprintf( $restriction, 'published', $published );
		$or = ' OR ';
	}
	if( $deprecated != '' )
	{ // there is at least one blog on which current user has edit deprecated comments permission
		$deprecated = substr( $deprecated, 0, strlen($deprecated) - 1 );
		$deprecated = $or.sprintf( $restriction, 'deprecated', $deprecated );
	}

	return ' AND ( '.$draft.$published.$deprecated.' )';
}


/**
 * Show affected comments
 * 
 * @param array affected Comment list, all comments in this list must have the same status
 * @param string Comment visibility status in this list
 * @param string ban keyword
 * @param integer The number of corresponding comments on which current user has no permission
 */
function echo_affected_comments( $affected_comments, $status, $keyword, $noperms_count )
{
	$num_comments = count( $affected_comments );
	if( $num_comments == 0 )
	{
		if( $noperms_count == 0 )
		{ // There isn't any affected comment witch corresponding status
			printf( '<p>'.T_('No %s comments match the keyword [%s].').'</p>', '<strong>'.$status.'</strong>', htmlspecialchars($keyword) );
		}
		else
		{ // There are affected comment witch corresponding status, but current user has no permission
			printf( '<p>'.T_('There are %d matching %s comments, but you have no permission to edit them.').'</p>', $noperms_count, '<strong>'.$status.'</strong>' );
		}
		return;
	}

	echo '<p>';
	echo '<input type="checkbox" name="del'.$status.'" id="del'.$status.'_cb" value="1" checked="checked"/>';
	echo '<label for="del'.$status.'_cb"> ';
	echo sprintf ( T_('Delete the following %s %s comments:'), $num_comments == 500 ? '500+' : $num_comments, '<strong>'.$status.'</strong>' );
	echo '</label>';
	echo '</p>';

	echo '<table class="grouped" cellspacing="0">';
	echo '<thead><tr>';
	echo '<th class="firstcol">'.T_('Date').'</th>';
	echo '<th>'.T_('Author').'</th>';
	echo '<th>'.T_('Auth. URL').'</th>';
	echo '<th class="center">'.T_('Auth. IP').'</th>';
	echo '<th>'.T_('Content starts with...').'</th>';
	echo '<th class="shrinkwrap">'.T_('Action').'</th>';
	echo '</tr></thead>';
	$count = 0;
	foreach( $affected_comments as $Comment )
	{
		echo '<tr class="'.(($count%2 == 1) ? 'odd' : 'even').'">';
		echo '<td class="firstcol">'.mysql2date(locale_datefmt().' '.locale_timefmt(), $Comment->get( 'date' ) ).'</td>';
		echo '<td>'.$Comment->get_author_name().'</td>';
		echo '<td>';
		disp_url( $Comment->get_author_url(), 50 );
		echo '</td>';
		echo '<td class="center">'.$Comment->get( 'author_IP' ).'</td>';
		echo '<td>'.strmaxlen(strip_tags( $Comment->get_content() ), 71).'</td>';
		// no permission check, because affected_comments contains current user editable comments
		echo '<td class="shrinkwrap">'.action_icon( T_('Edit...'), 'edit', '?ctrl=comments&amp;action=edit&amp;comment_ID='.$Comment->ID ).'</td>';
		echo '</tr>';
		$count++;
	}
	echo "</tbody></table>";
}


/*
 * $Log$
 * Revision 1.21  2011/10/18 06:45:10  sam2kb
 * Fixing PHP warnings "Converting from  to : not supported..."
 *
 * Revision 1.20  2011/09/05 14:17:26  sam2kb
 * Refactor antispam controller
 *
 * Revision 1.19  2011/09/04 22:13:13  fplanque
 * copyright 2011
 *
 * Revision 1.18  2011/08/01 04:31:14  sam2kb
 * Fixed warning on cron "Converting from  to : not supported..."
 *
 * Revision 1.17  2011/02/15 06:13:49  sam2kb
 * strlen replaced with evo_strlen to support utf-8 logins and domain names
 *
 * Revision 1.16  2010/10/19 02:00:53  fplanque
 * MFB
 *
 * Revision 1.15  2010/07/19 06:13:35  efy-asimo
 * use Comment->get( 'author_IP') instead of get_author_ip() function
 *
 * Revision 1.14  2010/07/13 08:51:17  efy-asimo
 * doc - reply to todo fp>asimo
 *
 * Revision 1.13  2010/06/17 08:54:52  efy-asimo
 * antispam screen, antispam tool dispplay fix
 *
 * Revision 1.12  2010/06/01 11:33:19  efy-asimo
 * Split blog_comments advanced permission (published, deprecated, draft)
 * Use this new permissions (Antispam tool,when edit/delete comments)
 *
 * Revision 1.11  2010/05/14 08:16:04  efy-asimo
 * antispam tool ban form - create seperate table for different comments
 *
 * Revision 1.10  2010/02/08 17:52:06  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.9  2009/03/08 23:57:41  fplanque
 * 2009
 *
 * Revision 1.8  2008/04/04 17:02:21  fplanque
 * cleanup of global settings
 *
 * Revision 1.7  2008/01/21 09:35:25  fplanque
 * (c) 2008
 *
 * Revision 1.6  2008/01/20 18:20:22  fplanque
 * Antispam per group setting
 *
 * Revision 1.5  2008/01/19 15:45:28  fplanque
 * refactoring
 *
 * Revision 1.4  2008/01/14 07:22:06  fplanque
 * Refactoring
 *
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