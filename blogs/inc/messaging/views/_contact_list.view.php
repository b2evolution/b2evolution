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

global $dispatcher;
global $current_User;
global $unread_messages_count;
global $read_unread_recipients;

// Create SELECT query

$select_SQL = & new SQL();
$select_SQL->SELECT( 'mc.mct_to_user_ID, mc.mct_blocked, mc.mct_last_contact_datetime, u.user_login AS mct_to_user_login' );

$select_SQL->FROM( 'T_messaging__contact mc
						LEFT OUTER JOIN T_users u
						ON mc.mct_to_user_ID = u.user_ID' );

$select_SQL->WHERE( 'mc.mct_from_user_ID = '.$current_User->ID );

$select_SQL->ORDER_BY( 'u.user_login' );

// Create COUNT quiery

$count_SQL = & new SQL();

$count_SQL->SELECT( 'COUNT(*)' );
$count_SQL->FROM( 'T_messaging__contact' );
$count_SQL->WHERE( 'mct_from_user_ID = '.$current_User->ID );

// Create result set:

$Results = & new Results( $select_SQL->get(), 'mct_', '', NULL, $count_SQL->get() );

$Results->title = T_('Contacts list');

$Results->cols[] = array(
					'th' => T_('Contact'),
					'td' => '%get_avatar_imgtag( #mct_to_user_login# )%',
					);

$Results->cols[] = array(
					'th' => T_('Last_Contact'),
					'th_class' => 'shrinkwrap',
					'td_class' => 'shrinkwrap',
					'td' => '%mysql2localedatetime(#mct_last_contact_datetime#)%' );

/**
 * Get block/unblock icon
 *
 * @param block value
 * @param user ID
 * @return icon
 */
function contact_block( $block, $user_ID )
{
	global $admin_url;

	if( $block == 0 )
	{
		return action_icon( T_('Block contact'), 'file_allowed', $admin_url.'?ctrl=contacts&action=block&user_ID='.$user_ID );
	}
	else
	{
		return action_icon( T_('Unblock contact'), 'file_not_allowed', $admin_url.'?ctrl=contacts&action=unblock&user_ID='.$user_ID );
	}
}

$Results->cols[] = array(
					'th' => T_('Block / Unblock'),
					'th_class' => 'shrinkwrap',
					'td_class' => 'shrinkwrap',
					'td' => '%contact_block( #mct_blocked#, #mct_to_user_ID# )%' );

$Results->display();

/*
 * $Log$
 * Revision 1.3  2009/09/19 20:31:39  efy-maxim
 * 'Reply' permission : SQL queries to check permission ; Block/Unblock functionality; Error messages on insert thread/message
 *
 * Revision 1.2  2009/09/19 01:15:49  fplanque
 * minor
 *
 */
?>
