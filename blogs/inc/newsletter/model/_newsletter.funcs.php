<?php
/**
 * This file implements newsletter functions.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Get number of users for newsletter from UserList filterset
 *
 * @return array 
 * 		'all' - Number of accounts in filterset
 * 		'active' - Number of active accounts in filterset
 * 		'newsletter' - Number of active accounts which accept newsletter email
 */
function get_newsletter_users_numbers()
{
	$numbers = array(
			'all'        => 0,
			'active'     => 0,
			'newsletter' => 0,
		);

	load_class( 'users/model/_userlist.class.php', 'UserList' );
	// Initialize users list from session cache in order to know number of users
	$UserList = new UserList( 'admin' );
	$UserList->memorize = false;
	$UserList->load_from_Request();

	$users_IDs = $UserList->filters['users'];

	if( count( $users_IDs ) )
	{	// Found users in the filterset
		global $DB;

		$numbers['all'] = count( $users_IDs );

		// Get number of all active users
		$SQL = new SQL();
		$SQL->SELECT( 'COUNT( * )' );
		$SQL->FROM( 'T_users' );
		$SQL->WHERE( 'user_ID IN ( '.implode( ', ', $users_IDs ).' )' );
		$SQL->WHERE_and( 'user_status IN ( \'activated\', \'autoactivated\' )' );
		$numbers['active'] = $DB->get_var( $SQL->get() );

		// Get number of all active users which accept newsletter email
		$SQL = get_newsletter_users_sql( $users_IDs );
		$SQL->SELECT( 'COUNT( * )' );
		$numbers['newsletter'] = $DB->get_var( $SQL->get() );
	}

	return $numbers;
}


/**
 * Get SQL for active users which accept newsletter email
 *
 * @param array users IDs
 * @return object SQL
 */
function get_newsletter_users_sql( $users_IDs )
{
	global $Settings;

	$SQL = new SQL();
	$SQL->SELECT( 'u.user_ID' );
	$SQL->FROM( 'T_users u' );
	$SQL->FROM_add( 'LEFT OUTER JOIN T_users__usersettings us ON u.user_ID = us.uset_user_ID' );
	$SQL->FROM_add( 'AND us.uset_name = \'newsletter_news\'' );
	$SQL->WHERE( 'u.user_ID IN ( '.implode( ', ', $users_IDs ).' )' );
	$SQL->WHERE_and( 'u.user_status IN ( \'activated\', \'autoactivated\' )' );
	if( $Settings->get( 'def_newsletter_news' ) )
	{	// If General setting "newsletter_news" = 1 we also should include all users without defined user's setting "newsletter_news"
		$SQL->WHERE_and( '( us.uset_value = 1 OR us.uset_value IS NULL )' );
	}
	else
	{	// If General setting "newsletter_news" = 0 we take only users which accept newsletter email
		$SQL->WHERE_and( 'us.uset_value = 1' );
	}

	return $SQL;
}

/**
 * Send newsletter emails
 */
function newsletter_send()
{
	global $DB, $Session;

	load_class( 'users/model/_userlist.class.php', 'UserList' );
	// Initialize users list from session cache in order to get users IDs for newsletter
	$UserList = new UserList( 'admin' );
	$UserList->memorize = false;
	$UserList->load_from_Request();

	$users_IDs = $UserList->filters['users'];

	// Get all active users which accept newsletter email
	$SQL = get_newsletter_users_sql( $users_IDs );
	$users = $DB->get_col( $SQL->get() );

	echo sprintf( T_('Newsletter is sending for %s users...'), count( $users ) ).'<br /><br />';
	evo_flush();

	$email_newsletter_params = array(
			'message' => $Session->get( 'newsletter_message' )
		);

	foreach( $users as $user_ID )
	{
		$UserCache = & get_UserCache();
		$User = $UserCache->get_by_ID( $user_ID );

		echo sprintf( T_('Email is sending for %s (%s)...'), $User->get_identity_link(), $User->get( 'email' ) ).' ';

		// Send a newsletter in user's locale
		locale_temp_switch( $User->get( 'locale' ) );
		$email_result = send_mail_to_User( $user_ID, $Session->get( 'newsletter_title' ), 'newsletter', $email_newsletter_params );
		locale_restore_previous();

		if( $email_result )
		{	// Success sending
			echo T_('OK');
		}
		else
		{	// Failed sending
			echo '<span class="red">'.T_('Failed').'</span>';
		}
		echo '<br />';
		evo_flush();
	}
}

?>
