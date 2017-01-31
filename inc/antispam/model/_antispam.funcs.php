<?php
/**
 * This file implements Antispam handling functions.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 * Parts of this file are copyright (c)2004 by Vegar BERG GULDAL - {@link http://funky-m.com/}.
 * Parts of this file are copyright (c)2005 by The University of North Carolina at Charlotte as
 * contributed by Jason Edgecombe {@link http://tst.uncc.edu/}.
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
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * antispam_create(-)
 *
 * Insert a new abuse string into DB
 *
 * @param string Abuse string
 * @param string Keyword source
 * @return boolean TRUE if antispam keyword was inserted, FALSE if abuse string is empty or keyword is already in DB
 */
function antispam_create( $abuse_string, $keyword_source = 'local' )
{
	global $DB;

	// Cut the crap if the string is empty:
	$abuse_string = trim( $abuse_string );
	if( empty( $abuse_string ) )
	{
		return false;
	}

	// Check if the string already is in the blacklist:
	if( antispam_check( $abuse_string ) )
	{
		return false;
	}

	// Insert new string into DB:
	$DB->query( 'INSERT INTO T_antispam__keyword ( askw_string, askw_source )
		VALUES ( '.$DB->quote( $abuse_string ).', '.$DB->quote( $keyword_source ).' )' );

	return true;
}


/**
 * antispam_update_source(-)
 *
 * Note: We search by string because we sometimes don't know the ID
 * (e-g when download already in list/cache)
 *
 * @param string Abuse string
 * @param string Keyword source
 */
function antispam_update_source( $abuse_string, $keyword_source )
{
	global $DB;

	$DB->query( 'UPDATE T_antispam__keyword
		SET askw_source = '.$DB->quote( $keyword_source ).'
		WHERE askw_string = '.$DB->quote( $abuse_string ) );
}

/*
 * antispam_delete(-)
 *
 * Remove an entry from the ban list
 *
 * @param integer antispam keyword ID
 */
function antispam_delete( $keyword_ID )
{
	global $DB;

	$DB->query( 'DELETE FROM T_antispam__keyword
		WHERE askw_ID = '.intval( $keyword_ID ) );
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

	// TODO: 'SELECT COUNT(*) FROM T_antispam__keyword WHERE askw_string LIKE "%'.$url.'%" ?

	$Timer->resume( 'antispam_url' ); // resuming to get the total number..
	$block = $DB->get_var(
		'SELECT askw_string
		   FROM  T_antispam__keyword
		  WHERE '.$DB->quote( $haystack ).' LIKE CONCAT("%",askw_string,"%")
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
	global $outgoing_proxy_hostname, $outgoing_proxy_port, $outgoing_proxy_username, $outgoing_proxy_password;

	if( ! $Settings->get('antispam_report_to_central') )
	{
		$Messages->add( 'Reporting is disabled.', 'error' );  // NO TRANS necessary
		return false;
	}

	if( preg_match( '#^http://localhost[/:]#', $baseurl) && ( $antispamsrv_host != 'localhost' ) && empty( $antispam_test_for_real )  )
	{ // Local install can only report to local test server
		$Messages->add( T_('Reporting abuse to b2evolution aborted (Running on localhost).'), 'error' );
		return false;
	}

	// Construct XML-RPC client:
	load_funcs( 'xmlrpc/model/_xmlrpc.funcs.php' );
	$client = new xmlrpc_client( $antispamsrv_uri, $antispamsrv_host, $antispamsrv_port );
	// yura: I commented this because xmlrpc_client prints the debug info on screen and it breaks header_redirect()
	// $client->debug = $debug;

	// Set proxy for outgoing connections:
	if( ! empty( $outgoing_proxy_hostname ) )
	{
		$client->setProxy( $outgoing_proxy_hostname, $outgoing_proxy_port, $outgoing_proxy_username, $outgoing_proxy_password );
	}

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
	$result = $client->send( $message );
	if( $ret = xmlrpc_logresult( $result, $Messages, false ) )
	{ // Remote operation successful:
		antispam_update_source( $abuse_string, 'reported' );

		$Messages->add( sprintf( T_('Reported abuse to %s.'), $antispamsrv_host ), 'success' );
	}
	else
	{
		$Messages->add( sprintf( T_('Failed to report abuse to %s.'), $antispamsrv_host ), 'error' );
	}

	return $ret;
}


/**
 * Request abuse list from central blacklist.
 *
 * @return boolean true = success, false = error
 */
function antispam_poll_abuse()
{
	global $Messages, $Settings, $baseurl, $debug, $antispamsrv_host, $antispamsrv_port, $antispamsrv_uri;
	global $outgoing_proxy_hostname, $outgoing_proxy_port, $outgoing_proxy_username, $outgoing_proxy_password;

	// Construct XML-RPC client:
	load_funcs('xmlrpc/model/_xmlrpc.funcs.php');
	$client = new xmlrpc_client( $antispamsrv_uri, $antispamsrv_host, $antispamsrv_port );
	// yura: I commented this because xmlrpc_client prints the debug info on screen and it breaks header_redirect()
	// $client->debug = $debug;

	// Set proxy for outgoing connections:
	if( ! empty( $outgoing_proxy_hostname ) )
	{
		$client->setProxy( $outgoing_proxy_hostname, $outgoing_proxy_port, $outgoing_proxy_username, $outgoing_proxy_password );
	}

	// Get datetime from last update, because we only want newer stuff...
	$last_update = $Settings->get( 'antispam_last_update' );
	// Encode it in the XML-RPC format
	$Messages->add_to_group( T_('Latest update timestamp').': '.$last_update, 'note', T_('Updating antispam:') );
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

	$Messages->add_to_group( sprintf( T_('Requesting abuse list from %s...'), $antispamsrv_host ), 'note', T_('Updating antispam:') );

	$result = $client->send( $message );

	if( $ret = xmlrpc_logresult( $result, $Messages, false ) )
	{ // Response is not an error, let's process it:
		$response = $result->value();
		if( $response->kindOf() == 'struct' )
		{ // Decode struct:
			$response = xmlrpc_decode_recurse( $response );
			if( !isset( $response['strings'] ) || !isset( $response['lasttimestamp'] ) )
			{
				$Messages->add( T_('Incomplete response.'), 'error' );
				$ret = false;
			}
			else
			{ // Start registering strings:
				$value = $response['strings'];
				if( count( $value ) == 0 )
				{
					$Messages->add_to_group( T_('No new blacklisted strings are available.'), 'note', T_('Updating antispam:') );
				}
				else
				{ // We got an array of strings:
					foreach( $value as $banned_string )
					{
						if( antispam_create( $banned_string, 'central' ) )
						{ // Creation successed
							$Messages->add_to_group( T_('Adding:').' &laquo;'.$banned_string.'&raquo;: '
								.T_('OK.'), 'note', T_('Adding strings to local blacklist:') );
						}
						else
						{ // Was already handled
							$Messages->add_to_group( T_('Adding:').' &laquo;'.$banned_string.'&raquo;: '
								.T_('Not necessary! (Already handled)'), 'note', T_('Adding strings to local blacklist:') );
							antispam_update_source( $banned_string, 'central' );
						}
					}
					// Store latest timestamp:
					$endedat = date('Y-m-d H:i:s', iso8601_decode( $response['lasttimestamp'] ) );
					$Messages->add( T_('New latest update timestamp').': '.$endedat, 'note', T_('Adding strings to local blacklist:') );

					$Settings->set( 'antispam_last_update', $endedat );
					$Settings->dbupdate();
				}
				$Messages->add( T_('Done.'), 'success' );
			}
		}
		else
		{
			$Messages->add( T_('Invalid response.'), 'error' );
			$ret = false;
		}
	}

	return $ret ;
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
	$domain = preg_replace( '~^ ([a-z]+://)? ([^/#\?]+) (/ ([^/]*/)+ )? .* ~xi', '\\2\\3', $url );

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

	if( utf8_strlen( $base_domain ) < utf8_strlen( $domain ) )
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
 * asimo> It was changed so it doesn't restrict to blogs now, but it restricts to comment statuses.
 * When we will have per blog permanently delete comments permission then this function must be changed.
 *
 * @param array with key => value pairs, where the keys are the comment statuses and values are the boolean values to delete comments with the given statuses or not
 * @return string sql WHERE condition part, corresponding the user permissions
 */
function blog_restrict( $delstatuses )
{
	global $current_User;

	if( empty( $delstatuses ) )
	{ // none of the statuses should be deleted
		return ' AND false';
	}

	// asimo> Currently only global blogs editall permission gives rights to permanently delete comments
	// Probably this function must be changed when the advanced collection perms will be finished
	if( !$current_User->check_perm( 'blogs', 'editall', false ) )
	{ // User has permission to permanently delete comments on this blog
		return ' AND false';
	}

	$restriction = '( comment_status = "%s" )';
	$or = '';
	$condition = '';
	foreach( $delstatuses as $status )
	{
		$condition = $condition.$or.sprintf( $restriction, $status/*, $blog_ids */);
		$or = ' OR ';
	}

	return ' AND ( '.$condition.' )';
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
	global $current_User;

	$num_comments = count( $affected_comments );
	if( $num_comments == 0 )
	{
		if( $noperms_count == 0 )
		{ // There isn't any affected comment witch corresponding status
			printf( '<p>'.T_('No %s comments match the keyword %s.').'</p>', '<strong>'.$status.'</strong>', '<code>'.htmlspecialchars($keyword).'</code>' );
		}
		else
		{ // There are affected comment witch corresponding status, but current user has no permission
			printf( '<p>'.T_('There are %d matching %s comments, but you have no permission to edit them.').'</p>', $noperms_count, '<strong>'.$status.'</strong>' );
		}
		return;
	}

	echo '<p>';
	if( $current_User->check_perm( 'blogs', 'editall', false ) )
	{ // current User has rights to permanently delete comments
		$checkbox_status = 'checked="checked"';
	}
	else
	{ // current User doesn't have rights to permanently delete comments, so disable delete checkbox
		$checkbox_status = 'disabled="disabled"';
	}
	echo '<input type="checkbox" name="del'.$status.'" id="del'.$status.'_cb" value="1" '.$checkbox_status.'/>';
	echo '<label for="del'.$status.'_cb"> ';
	echo sprintf ( T_('Delete the following %s %s comments:'), $num_comments == 500 ? '500+' : $num_comments, '<strong>'.$status.'</strong>' );
	echo '</label>';
	echo '</p>';

	echo '<table class="grouped table-striped table-bordered table-hover table-condensed" cellspacing="0">';
	echo '<thead><tr>';
	echo '<th class="firstcol">'.T_('Date').'</th>';
	echo '<th class="center">'.T_('Auth. IP').'</th>';
	echo '<th>'.T_('Author').'</th>';
	echo '<th>'.T_('Auth. URL').'</th>';
	echo '<th>'.T_('Content starts with...').'</th>';
	echo '<th class="shrinkwrap">'.T_('Action').'</th>';
	echo '</tr></thead>';
	$count = 0;
	foreach( $affected_comments as $Comment )
	{
		echo '<tr class="'.(($count%2 == 1) ? 'odd' : 'even').'">';
		echo '<td class="firstcol timestamp">'.mysql2localedatetime_spans( $Comment->get( 'date' ) ).'</td>';
		echo '<td class="center">'.$Comment->get( 'author_IP' ).'</td>';
		echo '<td>'.$Comment->get_author_name().'</td>';
		echo '<td>';
		disp_url( $Comment->get_author_url(), 50 );
		echo '</td>';
		echo '<td>'.excerpt( $Comment->get_content( 'raw_text' ), 71 ).'</td>';
		// no permission check, because affected_comments contains current user editable comments
		echo '<td class="shrinkwrap">'.action_icon( T_('Edit...'), 'edit', '?ctrl=comments&amp;action=edit&amp;comment_ID='.$Comment->ID ).'</td>';
		echo '</tr>';
		$count++;
	}
	echo "</tbody></table>";
}


/**
 * Get IP range from DB
 *
 * @param integer IP start of range
 * @param integer IP end of range
 * @param integer ID of existing IP range
 * @return object Row of the table T_antispam__iprange (NULL - if IP range doesn't exist in DB yet)
*/
function get_ip_range( $ip_start, $ip_end, $aipr_ID = 0 )
{
	global $DB;

	$SQL = new SQL();
	$SQL->SELECT( '*' );
	$SQL->FROM( 'T_antispam__iprange' );
	$SQL->WHERE( ' (
		( '.$DB->quote( $ip_start ).' >= aipr_IPv4start AND '.$DB->quote( $ip_start ).' <= aipr_IPv4end ) OR
		( '.$DB->quote( $ip_end ).' >= aipr_IPv4start AND '.$DB->quote( $ip_end ).' <= aipr_IPv4end ) OR
		( '.$DB->quote( $ip_start ).' <= aipr_IPv4start AND '.$DB->quote( $ip_end ).' >= aipr_IPv4end )
	)' );
	if( !empty( $aipr_ID ) )
	{
		$SQL->WHERE_and( 'aipr_ID != '.$aipr_ID );
	}

	return $DB->get_row( $SQL->get() );
}


/**
 * Block request by IP address, Domain of current user or block because of a Plugin
 * Bock by Plugin: e.g. GeoIP plugin can block the request if it comes from a blocked country
 */
function antispam_block_request()
{
	global $Plugins;

	// Check to block by current IP addresses:
	antispam_block_by_ip();

	// Check to block by current domain:
	antispam_block_by_domain();

	// Check to block by initial referer:
	antispam_block_by_initial_referer();

	// Check if plugins may block the request:
	$Plugins->trigger_event( 'BeforeBlockableAction' );
}


/**
 * Block request by current IP addresses
 */
function antispam_block_by_ip()
{
	global $DB;

	// Detect request IP adresses
	$request_ip_list = get_ip_list();

	if( empty( $request_ip_list ) )
	{ // Could not get any IP address, so can't check anything
		return;
	}

	$condition = '';
	foreach( $request_ip_list as $ip_address )
	{ // create condition for each detected IP address
		$numeric_ip_address = ip2int( $ip_address );
		$condition .= ' OR ( aipr_IPv4start <= '.$DB->quote( $numeric_ip_address ).' AND aipr_IPv4end >= '.$DB->quote( $numeric_ip_address ).' )';
	}
	$condition = '( '.substr( $condition, 4 ).' )';

	$SQL = new SQL();
	$SQL->SELECT( 'aipr_ID' );
	$SQL->FROM( 'T_antispam__iprange' );
	$SQL->WHERE( $condition );
	$SQL->WHERE_and( 'aipr_status = \'blocked\'' );
	$SQL->LIMIT( 1 );
	$ip_range_ID = $DB->get_var( $SQL->get() );

	if( !is_null( $ip_range_ID ) )
	{ // The request from this IP address must be blocked
		$DB->query( 'UPDATE T_antispam__iprange
			SET aipr_block_count = aipr_block_count + 1
			WHERE aipr_ID = '.$DB->quote( $ip_range_ID ) );

		$log_message = sprintf( 'A request with ( %s ) ip addresses was blocked because of a blocked IP range ID#%s.', implode( ', ', $request_ip_list ), $ip_range_ID );
		exit_blocked_request( 'IP', $log_message ); // WILL exit();
	}
}


/**
 * Block request by current domain
 */
function antispam_block_by_domain()
{
	// Detect current IP adresses:
	$current_ip_addreses = get_ip_list();

	if( empty( $current_ip_addreses ) )
	{	// Could not get any IP address, so can't check anything:
		return;
	}

	load_funcs( 'sessions/model/_hitlog.funcs.php' );

	foreach( $current_ip_addreses as $ip_address )
	{
		// Get domain name by current IP adress:
		$ip_domain = gethostbyaddr( $ip_address );

		if( ! empty( $ip_domain ) &&
		    $Domain = & get_Domain_by_subdomain( $ip_domain ) &&
		    $Domain->get( 'status' ) == 'blocked' )
		{	// The request from this domain must be blocked:
			$log_message = sprintf( 'A request from \'%s\' domain was blocked because of the domain \'%s\' is blocked.', $ip_domain, $Domain->get( 'name' ) );
			exit_blocked_request( 'Domain', $log_message ); // WILL exit();
		}
	}
}


/**
 * Block request by initial referer of current session
 */
function antispam_block_by_initial_referer()
{
	global $Session;

	load_funcs( 'sessions/model/_hitlog.funcs.php' );

	// Get first hit params of current session:
	$first_hit_params = $Session->get_first_hit_params();

	if( $first_hit_params && ! empty( $first_hit_params->hit_referer ) &&
			$Domain = & get_Domain_by_url( $first_hit_params->hit_referer ) &&
			$Domain->get( 'status' ) == 'blocked' )
	{	// The request from this initial referer must be blocked:
		$log_message = sprintf( 'A request from \'%s\' initial referer was blocked because of the domain \'%s\' is blocked.', $first_hit_params->hit_referer, $Domain->get( 'name' ) );
		exit_blocked_request( 'Domain of initial referer', $log_message ); // WILL exit();
	}
}


/**
 * Block request by country
 *
 * @param integer country ID
 * @param boolean set true to block the requet here, or false to handle outside the function
 * @return boolean true if blocked, false otherwise
 */
function antispam_block_by_country( $country_ID, $assert = true )
{
	global $DB;

	$CountryCache = & get_CountryCache();
	$Country = $CountryCache->get_by_ID( $country_ID, false );

	if( $Country && $Country->get( 'status' ) == 'blocked' )
	{ // The country exists in the database and has blocked status
		if( $assert )
		{ // block the request
			$log_message = sprintf( 'A request from \'%s\' was blocked because of this country is blocked.', $Country->get_name() );
			exit_blocked_request( 'Country', $log_message ); // WILL exit();
		}
		// Update the number of requests from blocked countries
		$DB->query( 'UPDATE T_regional__country
			SET ctry_block_count = ctry_block_count + 1
			WHERE ctry_ID = '.$Country->ID );
		return true;
	}

	return false;
}


/**
 * Block request by email address and its domain
 */
function antispam_block_by_email( $email_address )
{
	if( mail_is_blocked( $email_address ) )
	{	// Email address is blocked completely
		$log_message = sprintf( 'A request was blocked because of the email address \'%s\' is blocked.', $email_address );
		exit_blocked_request( 'Email address', $log_message );
		// WILL exit();
	}

	// Extract a domain from the email address:
	$email_domain = preg_replace( '#^[^@]+@#', '', $email_address );

	if( ! empty( $email_domain ) &&
			$Domain = & get_Domain_by_subdomain( $email_domain ) &&
			$Domain->get( 'status' ) == 'blocked' )
	{	// The request from domain of the email address must be blocked:
		$log_message = sprintf( 'A request was blocked because of the domain \'%s\' of the email address \'%s\' is blocked.', $Domain->get( 'name' ), $email_address );
		exit_blocked_request( 'Domain of email address', $log_message );
		// WILL exit();
	}
}


/**
 * Check if we can move current user to suspect group
 *
 * @param integer|NULL User ID, NULL = $current_User
 * @param boolean TRUE to check if user is in trust group
 * @return boolean TRUE if current user can be moved
 */
function antispam_suspect_check( $user_ID = NULL, $check_trust_group = true )
{
	global $Settings;

	$suspicious_group_ID = $Settings->get('antispam_suspicious_group');

	if( empty( $suspicious_group_ID ) )
	{ // We don't need to move users to suspicious group
		return false;
	}

	if( is_null( $user_ID ) )
	{ // current User
		global $current_User;
		$User = $current_User;
	}
	else
	{ // Get User by ID
		$UserCache = & get_UserCache();
		$User = $UserCache->get_by_ID( $user_ID, false, false );
	}

	if( empty( $User ) )
	{ // User must be logged in for this action
		return false;
	}

	if( $User->grp_ID == $suspicious_group_ID )
	{ // Current User already is in suspicious group
		return false;
	}

	if( $check_trust_group )
	{ // Check if user is in trust group
		$antispam_trust_groups = $Settings->get('antispam_trust_groups');
		if( !empty( $antispam_trust_groups ) )
		{
			$antispam_trust_groups = explode( ',', $antispam_trust_groups );
			if( in_array( $User->grp_ID, $antispam_trust_groups ) )
			{ // Current User has group which cannot be moved to suspicious users
				return false;
			}
		}
	}

	// We can move current user to suspect group
	return true;
}


/**
 * Move user to suspect group by IP address
 *
 * @param string IP address, Empty value to use current IP address
 * @param integer|NULL User ID, NULL = $current_User
 * @param boolean TRUE to check if user is in trust group
 */
function antispam_suspect_user_by_IP( $IP_address = '', $user_ID = NULL, $check_trust_group = true )
{
	global $DB, $Settings, $Timer;

	$Timer->start( 'suspect_user_by_IP' );

	if( empty( $user_ID ) )
	{	// If user_ID was not set, use the current_User:
		global $current_User;
		$User = $current_User;
	}
	else
	{	// Get User by given ID:
		$UserCache = & get_UserCache();
		$User = $UserCache->get_by_ID( $user_ID, false, false );
	}

	if( !antispam_suspect_check( $user_ID, $check_trust_group ) )
	{	// Current user cannot be moved to suspect group
		$Timer->stop( 'suspect_user_by_IP' );
		return;
	}

	if( empty( $IP_address ) && array_key_exists( 'REMOTE_ADDR', $_SERVER ) )
	{
		$IP_address = $_SERVER['REMOTE_ADDR'];
	}

	// Check by IP address:
	$IP_address_int = ip2int( $IP_address );

	$SQL = new SQL( 'Get IP range by address "'.$IP_address.'" to check if user #'.$user_ID.' must be suspected.' );
	$SQL->SELECT( 'aipr_ID' );
	$SQL->FROM( 'T_antispam__iprange' );
	$SQL->WHERE( 'aipr_IPv4start <= '.$DB->quote( $IP_address_int ) );
	$SQL->WHERE_and( 'aipr_IPv4end >= '.$DB->quote( $IP_address_int ) );
	$SQL->WHERE_and( 'aipr_status = \'suspect\'' );
	$ip_range_ID = $DB->get_row( $SQL->get(), OBJECT, NULL, $SQL->title );

	if( ! is_null( $ip_range_ID ) )
	{	// Move the user to suspicious group because current IP address is suspected:
		$GroupCache = & get_GroupCache();
		if( $suspicious_Group = & $GroupCache->get_by_ID( intval( $Settings->get( 'antispam_suspicious_group' ) ), false, false ) )
		{	// Group exists in DB and we can change user's group:
			$User->set_Group( $suspicious_Group );
			$User->dbupdate();
		}
	}

	$Timer->stop( 'suspect_user_by_IP' );
}


/**
 * Move user to suspect group by reverse DNS domain(that is generated from IP address on user's registration)
 *
 * @param integer|NULL User ID, NULL = $current_User
 * @param boolean TRUE to check if user is in trust group
 */
function antispam_suspect_user_by_reverse_dns_domain( $user_ID = NULL, $check_trust_group = true )
{
	global $Settings, $UserSettings, $Timer;

	$Timer->start( 'suspect_user_by_reverse_dns_domain' );

	if( empty( $user_ID ) )
	{	// If user_ID was not set, use the current_User:
		global $current_User;
		$User = $current_User;
	}
	else
	{	// Get User by given ID:
		$UserCache = & get_UserCache();
		$User = $UserCache->get_by_ID( $user_ID, false, false );
	}

	if( ! antispam_suspect_check( $user_ID, $check_trust_group ) )
	{	// Current user cannot be moved to suspect group:
		$Timer->stop( 'suspect_user_by_reverse_dns_domain' );
		return;
	}

	// Get user's reverse DNS domain that was generated from IP address on registration by function gethostbyaddr()
	$reverse_dns_domain = $UserSettings->get( 'user_registered_from_domain', $User->ID );

	if( empty( $reverse_dns_domain ) )
	{	// Domain must be not empty:
		$Timer->stop( 'suspect_user_by_reverse_dns_domain' );
		return;
	}

	// Try to get a top existing domain of reverse DNS subdomain from DB:
	load_funcs( 'sessions/model/_hitlog.funcs.php' );
	$Domain = & get_Domain_by_subdomain( $reverse_dns_domain );

	if( $Domain && $Domain->get( 'status' ) == 'suspect' )
	{	// Move the user to suspicious group because the reverse DNS has a suspect status:
		$GroupCache = & get_GroupCache();
		if( $suspicious_Group = & $GroupCache->get_by_ID( intval( $Settings->get( 'antispam_suspicious_group' ) ), false, false ) )
		{	// Group exists in DB and we can change user's group:
			$User->set_Group( $suspicious_Group );
			$User->dbupdate();
		}
	}

	$Timer->stop( 'suspect_user_by_reverse_dns_domain' );
}


/**
 * Move user to suspect group by Country ID
 *
 * @param integer Country ID
 * @param integer|NULL User ID, NULL = $current_User
 * @param boolean TRUE to check if user is in trust group
 */
function antispam_suspect_user_by_country( $country_ID, $user_ID = NULL, $check_trust_group = true )
{
	global $DB, $Settings;

	if( !antispam_suspect_check( $user_ID, $check_trust_group ) )
	{ // Current user cannot be moved to suspect group
		return;
	}

	if( is_null( $user_ID ) )
	{ // current User
		global $current_User;
		$User = $current_User;
	}
	else
	{ // Get User by ID
		$UserCache = & get_UserCache();
		$User = $UserCache->get_by_ID( $user_ID, false, false );
	}

	$SQL = new SQL();
	$SQL->SELECT( 'ctry_ID' );
	$SQL->FROM( 'T_regional__country' );
	$SQL->WHERE( 'ctry_ID = '.$DB->quote( $country_ID ) );
	$SQL->WHERE_and( 'ctry_status = \'suspect\'' );
	$country_ID = $DB->get_var( $SQL->get() );

	if( !is_null( $country_ID ) )
	{ // Move current user to suspicious group because country is suspected
		$GroupCache = & get_GroupCache();
		if( $suspicious_Group = & $GroupCache->get_by_ID( (int)$Settings->get('antispam_suspicious_group'), false, false ) )
		{ // Group exists in DB and we can change user's group
			$User->set_Group( $suspicious_Group );
			$User->dbupdate();
		}
	}
}


/**
 * Get status titles of ip range
 *
 * @param boolean TRUE - to include false statuses, which don't exist in DB
 * @return array Status titles
 */
function aipr_status_titles( $include_false_statuses = true )
{
	$status_titles = array();
	$status_titles['trusted'] = T_('Trusted');
	if( $include_false_statuses )
	{ // Include Unknown status
		$status_titles[''] = T_('Unknown');
	}
	$status_titles['suspect'] = T_('Suspect');
	$status_titles['blocked'] = T_('Blocked');

	return $status_titles;
}


/**
 * Get status colors of ip range
 *
 * @return array Color values
 */
function aipr_status_colors()
{
	return array(
			''        => '999999',
			'trusted' => '00CC00',
			'suspect' => 'FFAA00',
			'blocked' => 'FF0000',
		);
}


/**
 * Get array of status icons for email address
 *
 * @return array Status icons
 */
function aipr_status_icons()
{
	return array(
			''        => get_icon( 'bullet_white', 'imgtag', array( 'title' => aipr_status_title( '' ) ) ),
			'trusted' => get_icon( 'bullet_green', 'imgtag', array( 'title' => aipr_status_title( 'trusted' ) ) ),
			'suspect' => get_icon( 'bullet_orange', 'imgtag', array( 'title' => aipr_status_title( 'suspect' ) ) ),
			'blocked' => get_icon( 'bullet_red', 'imgtag', array( 'title' => aipr_status_title( 'blocked' ) ) )
		);
}


/**
 * Get status title of ip range by status value
 *
 * @param string Status value
 * @return string Status title
 */
function aipr_status_title( $status )
{
	$aipr_statuses = aipr_status_titles();

	return isset( $aipr_statuses[ $status ] ) ? $aipr_statuses[ $status ] : $status;
}


/**
 * Get status color of ip range by status value
 *
 * @param string Status value
 * @return string Color value
 */
function aipr_status_color( $status )
{
	if( $status == 'NULL' )
	{
		$status = '';
	}

	$aipr_status_colors = aipr_status_colors();

	return isset( $aipr_status_colors[ $status ] ) ? '#'.$aipr_status_colors[ $status ] : 'none';
}


/**
 * Get status icon of ip range by status value
 *
 * @param string Status value
 * @return string Icon
 */
function aipr_status_icon( $status )
{
	$aipr_status_icons = aipr_status_icons();

	return isset( $aipr_status_icons[ $status ] ) ? $aipr_status_icons[ $status ] : '';
}


/**
 * Get blogs with comments numbers
 *
 * @param string Comment status
 * @return array Blogs
 */
function antispam_bankruptcy_blogs( $comment_status = NULL )
{
	global $DB, $Settings;

	$SQL = new SQL( 'Get blogs list with number of comments' );
	$SQL->SELECT( 'blog_ID, blog_name, COUNT( comment_ID ) AS comments_count' );
	$SQL->FROM( 'T_comments' );
	$SQL->FROM_add( 'INNER JOIN T_items__item ON comment_item_ID = post_ID' );
	$SQL->FROM_add( 'INNER JOIN T_categories ON post_main_cat_ID = cat_ID' );
	$SQL->FROM_add( 'INNER JOIN T_blogs ON cat_blog_ID = blog_ID' );
	if( !empty( $comment_status ) )
	{ // Limit by comment status
		$SQL->WHERE( 'comment_status = '.$DB->quote( $comment_status ) );
	}
	$SQL->GROUP_BY( 'blog_ID' );
	$SQL->ORDER_BY( 'blog_'.$Settings->get('blogs_order_by').' '.$Settings->get('blogs_order_dir') );

	return $DB->get_results( $SQL->get() );
}


/**
 * Delete ALL comments from selected blogs
 *
 * @param string Comment status
 * @param array Blog IDs
 */
function antispam_bankruptcy_delete( $blog_IDs = array(), $comment_status = NULL )
{
	global $DB;

	if( empty( $blog_IDs ) )
	{ // No blogs selected
		echo T_('Please select at least one blog.');
		return;
	}

	echo T_('The comments are deleting...');
	evo_flush();

	$DB->begin();

	$items_IDs_SQL = new SQL( 'Get all posts IDs of selected blogs' );
	$items_IDs_SQL->SELECT( 'postcat_post_ID' );
	$items_IDs_SQL->FROM( 'T_postcats' );
	$items_IDs_SQL->FROM_add( 'INNER JOIN T_categories ON postcat_cat_ID = cat_ID' );
	$items_IDs_SQL->WHERE( 'cat_blog_ID IN ( '.$DB->quote( $blog_IDs ).' )' );
	$items_IDs = $DB->get_col( $items_IDs_SQL->get() );

	$comments_IDs_SQL = new SQL( 'Get all comments IDs of selected blogs' );
	$comments_IDs_SQL->SELECT( 'comment_ID' );
	$comments_IDs_SQL->FROM( 'T_comments' );
	$comments_IDs_SQL->WHERE( 'comment_item_ID IN ( '.$DB->quote( $items_IDs ).' )' );
	if( !empty( $comment_status ) )
	{ // Limit by comment status
		$comments_IDs_SQL->WHERE_and( 'comment_status = '.$DB->quote( $comment_status ) );
	}

	$affected_rows = 1;
	while( $affected_rows > 0 )
	{
		$affected_rows = 0;

		// Delete the cascades
		$affected_rows += $DB->query( 'DELETE FROM T_links
			WHERE link_cmt_ID IN ( '.$comments_IDs_SQL->get().' )
			LIMIT 10000' );
		$affected_rows += $DB->query( 'DELETE FROM T_comments__prerendering
			WHERE cmpr_cmt_ID IN ( '.$comments_IDs_SQL->get().' )
			LIMIT 10000' );
		$affected_rows += $DB->query( 'DELETE FROM T_comments__votes
			WHERE cmvt_cmt_ID IN ( '.$comments_IDs_SQL->get().' )
			LIMIT 10000' );

		// Delete the comments
		$sql_comments_where = '';
		if( !empty( $comment_status ) )
		{ // Limit by comment status
			$sql_comments_where = ' AND comment_status = '.$DB->quote( $comment_status );
		}
		$affected_rows += $DB->query( 'DELETE FROM T_comments
			WHERE comment_item_ID IN ( '.$DB->quote( $items_IDs ).' )'.
			$sql_comments_where.'
			LIMIT 10000' );

		echo ' .';
		evo_flush();
	}

	echo 'OK';

	$DB->commit();
}


/**
 * Increase a counter in DB antispam ip range table
 *
 * @param string Counter name: 'user', 'contact_email'
 */
function antispam_increase_counter( $counter_name )
{
	switch( $counter_name )
	{
		case 'user':
			$field_name = 'aipr_user_count';
			break;

		case 'contact_email':
			$field_name = 'aipr_contact_email_count';
			break;

		default:
			debug_die( 'Wrong antispam counter name' );
	}

	foreach( get_ip_list() as $ip )
	{
		if( $ip === '' )
		{ // Skip an empty
			continue;
		}

		$ip = int2ip( ip2int( $ip ) ); // Convert IPv6 to IPv4
		if( preg_match( '#^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$#i', $ip ) )
		{ // Check IP for correct format
			$ip_24bit_start = ip2int( preg_replace( '#\.\d{1,3}$#i', '.0', $ip ) );
			$ip_24bit_end = ip2int( preg_replace( '#\.\d{1,3}$#i', '.255', $ip ) );

			global $DB;
			if( $iprange = get_ip_range( $ip_24bit_start, $ip_24bit_end ) )
			{ // Update ip range
				$DB->query( 'UPDATE T_antispam__iprange
								SET '.$field_name.' = '.$field_name.' + 1
								WHERE aipr_ID = '.$DB->quote( $iprange->aipr_ID ) );
			}
			else
			{ // Insert new ip range
				$DB->query( 'INSERT INTO T_antispam__iprange ( aipr_IPv4start, aipr_IPv4end, '.$field_name.' )
								VALUES ( '.$DB->quote( $ip_24bit_start ).', '.$DB->quote( $ip_24bit_end ).', 1 ) ' );
			}
		}
	}
}
?>