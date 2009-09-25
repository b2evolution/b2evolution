<?php
/**
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * {@internal Open Source relicensing agreement:
 * The Evo Factory grants Francois PLANQUE the right to license
 * The Evo Factory's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package messaging
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-maxim: Evo Factory / Maxim.
 * @author fplanque: Francois Planque.
 *
 * @version $Id$
 */
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

	$UserCache = & get_UserCache( );

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

	$UserCache = & get_UserCache( );

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

/*
 * $Log$
 * Revision 1.4  2009/09/25 07:32:53  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.3  2009/09/19 20:47:08  fplanque
 * doc
 *
 */
?>