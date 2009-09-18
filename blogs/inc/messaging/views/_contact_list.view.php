<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $dispatcher;
global $current_User;
global $unread_messages_count;
global $read_unread_recipients;

// Create SELECT query

$select_SQL = & new SQL();
$select_SQL->SELECT( 'mc.mct_to_user_ID, mc.mct_last_contact_datetime, u.user_login AS mct_to_user_ID' );

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
					'td' => '%get_avatar_imgtag( #mct_to_user_ID# )%',
					);

$Results->cols[] = array(
					'th' => T_('Last_Contact'),
					'th_class' => 'shrinkwrap',
					'td_class' => 'shrinkwrap',
					'td' => '%mysql2localedatetime(#mct_last_contact_datetime#)%' );

$Results->cols[] = array(
					'th' => T_('Block / Unblock'),
					'th_class' => 'shrinkwrap',
					'td_class' => 'shrinkwrap',
					'td' => '&nbsp;' );

$Results->display();

?>
