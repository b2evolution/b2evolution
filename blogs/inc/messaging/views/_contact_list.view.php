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
global $current_User, $Settings;
global $unread_messages_count;
global $read_unread_recipients;

if( !isset( $display_params ) )
{
	$display_params = array();
}
$display_params = array_merge( array(
	'show_only_date' => 0,
	'show_columns' => 'login,nickname,name',
	), $display_params );

// show following optional colums
$show_columns = explode( ',', $display_params['show_columns'] );

// Create SELECT query
$select_SQL = new SQL();
$select_SQL->SELECT( 	'mc.mct_to_user_ID, mc.mct_blocked, mc.mct_last_contact_datetime,
						u.user_login AS mct_to_user_login, u.user_nickname AS mct_to_user_nickname,
						CONCAT_WS( " ", u.user_firstname, u.user_lastname ) AS mct_to_user_name,
						u.user_email AS mct_to_user_email' );

$select_SQL->FROM( 'T_messaging__contact mc
						LEFT OUTER JOIN T_users u
						ON mc.mct_to_user_ID = u.user_ID' );

$select_SQL->WHERE( 'mc.mct_from_user_ID = '.$current_User->ID );

// Create COUNT quiery

$count_SQL = new SQL();

$count_SQL->SELECT( 'COUNT(*)' );

// Get params from request
$s = param( 's', 'string', '', true );

if( !empty( $s ) )
{
	$select_SQL->WHERE_and( 'CONCAT_WS( " ", u.user_login, u.user_firstname, u.user_lastname, u.user_nickname ) LIKE "%'.$DB->escape($s).'%"' );

	$count_SQL->FROM( 'T_messaging__contact mc LEFT OUTER JOIN T_users u ON mc.mct_to_user_ID = u.user_ID' );
	$count_SQL->WHERE( 'mct_from_user_ID = '.$current_User->ID );
	$count_SQL->WHERE_and( 'CONCAT_WS( " ", u.user_login, u.user_firstname, u.user_lastname, u.user_nickname ) LIKE "%'.$DB->escape($s).'%"' );
}
else
{
	$count_SQL->FROM( 'T_messaging__contact' );
	$count_SQL->WHERE( 'mct_from_user_ID = '.$current_User->ID );
}

// Create result set:

$Results = new Results( $select_SQL->get(), 'mct_', '', NULL, $count_SQL->get() );

$Results->title = T_('Contacts list');

/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_contacts( & $Form )
{
	$Form->text( 's', get_param('s'), 30, T_('Search'), '', 255 );
}

$Results->filter_area = array(
	'callback' => 'filter_contacts',
	'presets' => array(
		'all' => array( T_('All'), get_messaging_url( 'contacts' ) ),
		)
	);


if( $Settings->get('allow_avatars') )
{
	/**
	 * Get user avatar
	 *
	 * @param integer user ID
	 * @return string
	 */
	function user_avatar( $user_ID )
	{
		$UserCache = & get_UserCache();
		$User = & $UserCache->get_by_ID( $user_ID, false, false );
		if( $User )
		{
			return $User->get_avatar_imgtag();
		}
		return '';
	}
	$Results->cols[] = array(
						'th' => T_('Picture'),
						'th_class' => 'shrinkwrap',
						'td_class' => 'shrinkwrap',
						'td' => '%user_avatar( #mct_to_user_ID# )%',
						);
}

if( in_array( 'login', $show_columns ) )
{
$Results->cols[] = array(
					'th' => T_('Login'),
					'order' => 'mct_to_user_login',
					'th_class' => 'shrinkwrap',
					'td_class' => 'shrinkwrap',
					'td' => '<strong>$mct_to_user_login$</strong>',
					);
}

if( in_array( 'nickname', $show_columns ) )
{
$Results->cols[] = array(
					'th' => T_('Nickname'),
					'order' => 'mct_to_user_nickname',
					'th_class' => 'shrinkwrap',
					'td_class' => 'shrinkwrap',
					'td' => '$mct_to_user_nickname$',
					);
}

if( in_array( 'name', $show_columns ) )
{
$Results->cols[] = array(
					'th' => T_('Name'),
					'order' => 'mct_to_user_name',
					'td' => '$mct_to_user_name$',
					);
}


/**
 * Get user email
 *
 * @param email
 * @return string
 */
function user_mailto( $email )
{
	if( !empty( $email ) )
	{
		return action_icon( T_('Email').': '.$email, 'email', 'mailto:'.$email, T_('Email') );
	}
	return '';
}

/**
 * Get user private message
 *
 * @param block
 * @param user login
 * @return string
 */
function user_pm ( $block, $user_login )
{
	if( $block == 0 )
	{
		return action_icon( T_('Private Message').': '.$user_login, 'comments', get_messaging_url( 'threads' ).'&action=new&user_login='.$user_login );
	}
	return '';
}

function last_contact( $date, $show_only_date )
{
	//global $show_only_date;
	if( $show_only_date )
	{
		return mysql2localedate( $date );
	}

	return mysql2localedatetime( $date );
}

$Results->cols[] = array(
	'th' => /* TRANS: time related */ T_('Last contact'),
	'th_class' => 'shrinkwrap',
	'td_class' => 'shrinkwrap',
	'td' => '%last_contact(#mct_last_contact_datetime#, '.$display_params[ 'show_only_date' ].')%'
);

/**
 * Get block/unblock icon
 *
 * @param block value
 * @param user ID
 * @return icon
 */
function contact_block( $block, $user_ID )
{
	global $samedomain_htsrv_url;
	// set action url
	$action_url = get_messaging_url( 'contacts' );
	if( !is_admin_page() )
	{ // in front office the action will be processed by messaging.php
		$action_url = $samedomain_htsrv_url.'messaging.php?disp=contacts&redirect_to='.$action_url;
	}

	if( $block == 0 )
	{
		return action_icon( T_('Block contact'), 'file_allowed', $action_url.'&action=block&user_ID='.$user_ID.'&amp;'.url_crumb('contact') );
	}
	else
	{
		return action_icon( T_('Unblock contact'), 'file_not_allowed', $action_url.'&action=unblock&user_ID='.$user_ID.'&amp;'.url_crumb('contact') );
	}
}

$Results->cols[] = array(
					'th' => T_('Actions'),
					'th_class' => 'shrinkwrap',
					'td_class' => 'shrinkwrap',
					'td' => '%user_pm( #mct_blocked#, #mct_to_user_login# )%'.
							'%user_mailto( #mct_to_user_email# )%'.
							'%contact_block( #mct_blocked#, #mct_to_user_ID# )%'
					);

$Results->display( $display_params );

/*
 * $Log$
 * Revision 1.15  2011/09/26 14:53:27  efy-asimo
 * Login problems with multidomain installs - fix
 * Insert globals: samedomain_htsrv_url, secure_htsrv_url;
 *
 * Revision 1.14  2011/09/22 08:55:00  efy-asimo
 * Login problems with multidomain installs - fix
 *
 * Revision 1.13  2011/09/06 00:54:39  fplanque
 * i18n update
 *
 * Revision 1.12  2011/08/11 09:05:09  efy-asimo
 * Messaging in front office
 *
 * Revision 1.11  2010/11/03 19:44:15  sam2kb
 * Increased modularity - files_Module
 * Todo:
 * - split core functions from _file.funcs.php
 * - check mtimport.ctrl.php and wpimport.ctrl.php
 * - do not create demo Photoblog and posts with images (Blog A)
 *
 * Revision 1.10  2010/01/30 18:55:32  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.9  2010/01/03 13:10:58  fplanque
 * set some crumbs (needs checking)
 *
 * Revision 1.8  2009/12/07 23:54:13  blueyed
 * trans doc. indent.
 *
 * Revision 1.7  2009/12/07 23:07:34  blueyed
 * Whitespace.
 *
 * Revision 1.6  2009/10/11 12:26:07  efy-maxim
 * filter by user login, full name, nick name in contacts list
 *
 * Revision 1.5  2009/10/02 15:07:27  efy-maxim
 * messaging module improvements
 *
 * Revision 1.4  2009/09/30 19:00:23  blueyed
 * trans fix, doc
 *
 * Revision 1.3  2009/09/19 20:31:39  efy-maxim
 * 'Reply' permission : SQL queries to check permission ; Block/Unblock functionality; Error messages on insert thread/message
 *
 * Revision 1.2  2009/09/19 01:15:49  fplanque
 * minor
 *
 */
?>