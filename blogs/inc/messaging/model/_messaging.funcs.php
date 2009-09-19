<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Extract list of contacts of current user from his message threads
 *
 * @param current user ID
 */
function load_messaging_threads_recipients( $user_ID )
{
	global $DB;

	$SQL = & new SQL();

	$SQL->SELECT( 'DISTINCT u.*' );

	$SQL->FROM( 'T_messaging__threadstatus ts
					LEFT OUTER JOIN T_messaging__threadstatus tsr
						ON ts.tsta_thread_ID = tsr.tsta_thread_ID
					LEFT OUTER JOIN T_users u
						ON tsr.tsta_user_ID = u.user_ID' );

	$SQL->WHERE( 'ts.tsta_user_ID = '.$user_ID );

	$UserCache = & get_Cache( 'UserCache' );

	foreach( $DB->get_results( $SQL->get() ) as $row )
	{
		if( !isset($UserCache->cache[$row->user_ID]) )
		{
			$UserCache->add( new User( $row ) );
		}
	}
}


/**
 * Load all of the recipients of current thread
 *
 * @param current thread ID
 */
function load_messaging_thread_recipients( $thrd_ID )
{
	global $DB;

	$SQL = & new SQL();

	$SQL->SELECT( 'u.*' );

	$SQL->FROM( 'T_messaging__threadstatus ts
					LEFT OUTER JOIN T_users u
						ON ts.tsta_user_ID = u.user_ID' );

	$SQL->WHERE( 'ts.tsta_thread_ID = '.$thrd_ID );

	$UserCache = & get_Cache( 'UserCache' );

	foreach( $DB->get_results( $SQL->get() ) as $row )
	{
		if( !isset($UserCache->cache[$row->user_ID]) )
		{
			$UserCache->add( new User( $row ) );
		}
	}
}


/**
 * Check blocked contacts in recipients list
 *
 * @param recipients list
 * @return blocked contacts array
 */
function check_blocked_contacts( $recipients_list )
{
	global $DB, $current_User;

	$SQL = & new SQL();

	$SQL->SELECT( 'u.user_login' );

	$SQL->FROM( 'T_users u
					LEFT OUTER JOIN T_messaging__contact mcu
						ON u.user_ID = mcu.mct_from_user_ID
    					AND mcu.mct_to_user_ID = '.$current_User->ID.'
    					AND mcu.mct_blocked = 0' );

	$SQL->WHERE( 'u.user_ID <> '.$current_User->ID );
	$SQL->WHERE_and( 'mcu.mct_from_user_ID IS NULL' );
	$SQL->WHERE_and( 'u.user_ID IN ('.implode( ',', $recipients_list ).')' );

	$SQL->ORDER_BY( 'u.user_login' );

	$blocked_contacts = array();
	foreach( $DB->get_results( $SQL->get() ) as $row )
	{
		$blocked_contacts[] = $row->user_login;
	}

	return $blocked_contacts;
}

?>