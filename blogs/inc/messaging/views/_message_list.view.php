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

global $dispatcher, $action, $current_User;

// in front office there is no function call, $edited_Thread is available
if( !isset( $edited_Thread ) )
{ // $edited thread is global in back office, but we are inside of disp_view function call
	global $edited_Thread;

	if( !isset( $edited_Thread ) )
	{
		debug_die( "Missing thread!");
	}
}

global $read_by_list;

$creating = is_create_action( $action );

if( !isset( $display_params ) )
{
	$display_params = array();
}

if( !isset( $params ) )
{
	$params = array();
}
$params = array_merge( array(
	'form_class' => 'fform',
	'form_action' => NULL,
	'form_name' => 'messages_checkchanges',
	'form_layout' => 'compact',
	'redirect_to' => regenerate_url( 'action', '', '', '&' ),
	'cols' => 80
	), $params );

// Update message statuses

$DB->query( 'UPDATE T_messaging__threadstatus
				SET tsta_first_unread_msg_ID = NULL
				WHERE tsta_thread_ID = '.$edited_Thread->ID.'
				AND tsta_user_ID = '.$current_User->ID );

// Select all recipients

$recipients_SQL = new SQL();

$recipients_SQL->SELECT( 'GROUP_CONCAT(u.user_login ORDER BY u.user_login SEPARATOR \',\')' );

$recipients_SQL->FROM( 'T_messaging__threadstatus mts
								LEFT OUTER JOIN T_users u ON mts.tsta_user_ID = u.user_ID' );

$recipients_SQL->WHERE( 'mts.tsta_thread_ID = '.$edited_Thread->ID.'
								AND mts.tsta_user_ID <> '.$current_User->ID );

$recipients = explode( ',', $DB->get_var( $recipients_SQL->get() ) );

// Select unread recipients

$unread_recipients_SQL = new SQL();

$unread_recipients_SQL->SELECT( 'mm.msg_ID, GROUP_CONCAT(uu.user_login ORDER BY uu.user_login SEPARATOR \',\') AS msg_unread' );

$unread_recipients_SQL->FROM( 'T_messaging__message mm
										LEFT OUTER JOIN T_messaging__threadstatus tsu ON mm.msg_ID = tsu.tsta_first_unread_msg_ID
										LEFT OUTER JOIN T_users uu ON tsu.tsta_user_ID = uu.user_ID' );

$unread_recipients_SQL->WHERE( 'mm.msg_thread_ID = '.$edited_Thread->ID );

$unread_recipients_SQL->GROUP_BY( 'mm.msg_ID' );

$unread_recipients_SQL->ORDER_BY( 'mm.msg_datetime' );

$unread_recipients = array();

// Create array for read by

foreach( $DB->get_results( $unread_recipients_SQL->get() ) as $row )
{
	if( !empty( $row->msg_unread ) )
	{
		$unread_recipients = array_merge( $unread_recipients, explode( ',', $row->msg_unread ) );
	}

	$read_recipiens = array_diff( $recipients, $unread_recipients );
	$read_recipiens[] = $current_User->login;

	asort( $read_recipiens );
	asort( $unread_recipients );

	$read_by = '';
	if( !empty( $read_recipiens ) )
	{
		$read_by .= '<span style="color:green">'.get_avatar_imgtags( $read_recipiens, true, false );
		if( !empty ( $unread_recipients ) )
		{
			$read_by .= ', ';
		}
		$read_by .= '</span>';
	}

	if( !empty ( $unread_recipients ) )
	{
		$read_by .= '<span style="color:red">'.get_avatar_imgtags( $unread_recipients, true, false ).'</span>';
	}

	$read_by_list[$row->msg_ID] = $read_by ;
}


// Create SELECT query:

$select_SQL = new SQL();

$select_SQL->SELECT( 	'mm.msg_ID, mm.msg_author_user_ID, mm.msg_thread_ID, mm.msg_datetime,
						u.user_ID AS msg_user_ID, u.user_login AS msg_author,
						u.user_firstname AS msg_firstname, u.user_lastname AS msg_lastname,
						u.user_avatar_file_ID AS msg_user_avatar_ID, mm.msg_text' );

$select_SQL->FROM( 'T_messaging__message mm
						LEFT OUTER JOIN T_users u ON u.user_ID = mm.msg_author_user_ID' );

$select_SQL->WHERE( 'mm.msg_thread_ID = '.$edited_Thread->ID );

$select_SQL->ORDER_BY( 'mm.msg_datetime' );

// Create COUNT query

$count_SQL = new SQL();
$count_SQL->SELECT( 'COUNT(*)' );

// Get params from request
$s = param( 's', 'string', '', true );

if( !empty( $s ) )
{
	$select_SQL->WHERE_and( 'CONCAT_WS( " ", u.user_login, u.user_firstname, u.user_lastname, u.user_nickname, msg_text ) LIKE "%'.$DB->escape($s).'%"' );

	$count_SQL->FROM( 'T_messaging__message mm LEFT OUTER JOIN T_users u ON u.user_ID = mm.msg_author_user_ID' );
	$count_SQL->WHERE( 'mm.msg_thread_ID = '.$edited_Thread->ID );
	$count_SQL->WHERE_and( 'CONCAT_WS( " ", u.user_login, u.user_firstname, u.user_lastname, u.user_nickname, msg_text ) LIKE "%'.$DB->escape($s).'%"' );
}
else
{
	$count_SQL->FROM( 'T_messaging__message' );
	$count_SQL->WHERE( 'msg_thread_ID = '.$edited_Thread->ID );
}

// Create result set:

$Results = new Results( $select_SQL->get(), 'msg_', '', 0, $count_SQL->get() );

$Results->Cache = & get_MessageCache();

$Results->title = $edited_Thread->title;

if( is_admin_page() )
{
	$Results->global_icon( T_('Cancel!'), 'close', '?ctrl=threads' );
}

/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_messages( & $Form )
{
	$Form->text( 's', get_param('s'), 30, T_('Search'), '', 255 );
}

$Results->filter_area = array(
	'callback' => 'filter_messages',
	'presets' => array(
		'all' => array( T_('All'), get_messaging_url( 'messages' ).'&thrd_ID='.$edited_Thread->ID ),
		)
	);

/*
 * Author col:
 */

/**
 * Get user avatar
 *
 * @param integer user ID
 * @param integer avatar ID
 * @return string
 */
function user_avatar( $user_ID, $user_avatar_file_ID )
{
	global $current_User, $admin_url;

	if( ! $GLOBALS['Settings']->get('allow_avatars') ) 
		return '';

	$FileCache = & get_FileCache();

	if( ! $File = & $FileCache->get_by_ID( $user_avatar_file_ID, false, false ) )
	{
		return '';
	}

	if( $current_User->check_perm( 'users', 'view' ) )
	{
		return '<a href="'.$admin_url.'?ctrl=user&amp;user_tab=profile&amp;user_ID='.$user_ID.'">'.$File->get_thumb_imgtag( 'crop-80x80' ).'</a>';
	}
	else
	{
		return $File->get_thumb_imgtag( 'crop-80x80' );
	}
}
/**
 * Create author cell for message list table
 *
 * @param integer user ID
 * @param string login
 * @param string first name
 * @param string last name
 * @param integer avatar ID
 * @param string datetime
 */
function author( $user_ID, $user_login, $user_first_name, $user_last_name, $user_avatar_ID, $datetime)
{
	$author = '<b>'.$user_login.'</b>';

	$avatar = user_avatar( $user_ID, $user_avatar_ID );

	if( !empty( $avatar ) )
	{
		$author = $avatar.'<br />'.$author;
	}

	$full_name = '';

	if( !empty( $user_first_name ) )
	{
		$full_name .= $user_first_name;
	}

	if( !empty( $user_last_name ) )
	{
		$full_name .= ' '.$user_last_name;
	}

	if( !empty( $full_name ) )
	{
		$author .= '<br />'.$full_name;
	}

	return $author.'<br /><span class="note">'.mysql2localedatetime( $datetime ).'</span>';
}
$Results->cols[] = array(
		'th' => T_('Author'),
		'th_class' => 'shrinkwrap',
		'td_class' => 'left top',
		'td' => '%author( #msg_user_ID#, #msg_author#, #msg_firstname#, #msg_lastname#, #msg_user_avatar_ID#, #msg_datetime#)%'
	);

/*
 * Message col
 */
$Results->cols[] = array(
		'th' => T_('Message'),
		'td_class' => 'left top',
		'td' => '~conditional( empty(#msg_text#), \''.$edited_Thread->title.'\', \'%nl2br(#msg_text#)%\')~',
	);

function get_read_by( $message_ID )
{
	global $read_by_list;

	return $read_by_list[$message_ID];
}

$Results->cols[] = array(
					'th' => T_('Read by'),
					'th_class' => 'shrinkwrap',
					'td_class' => 'top',
					'td' => '%get_read_by( #msg_ID# )%',
					);

function delete_action( $thrd_ID, $msg_ID )
{
	global $Blog, $samedomain_htsrv_url;;
	if( is_admin_page() )
	{
		return action_icon( T_( 'Delete'), 'delete', regenerate_url( 'action', 'thrd_ID='.$thrd_ID.'&msg_ID='.$msg_ID.'&action=delete&'.url_crumb( 'message' ) ) );
	}
	else
	{
		$redirect_to = url_add_param( $Blog->gen_blogurl(), 'disp=messages&thrd_ID='.$thrd_ID );
		$action_url = $samedomain_htsrv_url.'messaging.php?disp=messages&thrd_ID='.$thrd_ID.'&msg_ID='.$msg_ID.'&action=delete';
		$action_url = url_add_param( $action_url, 'redirect_to='.rawurlencode( $redirect_to ), '&' );
		return action_icon( T_( 'Delete'), 'delete', $action_url.'&'.url_crumb( 'message' ) );
	}
}

if( $current_User->check_perm( 'perm_messaging', 'delete' ) )
{
	// We have permission to modify:

	$Results->cols[] = array(
							'th' => T_('Actions'),
							'th_class' => 'shrinkwrap',
							'td_class' => 'shrinkwrap',
							// Do not display the icon if the message cannot be deleted
							'td' => $Results->total_rows == 1 ? '' : '%delete_action( #msg_thread_ID#, #msg_ID#)%',
						);
}

$Results->display( $display_params );

echo '<div class="fieldset clear"></div>';

$Form = new Form( $params[ 'form_action' ], $params[ 'form_name' ], 'post', $params[ 'form_layout' ] );

$Form->begin_form( $params['form_class'], '' );

	$Form->add_crumb( 'message' );
	$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',msg_ID' : '' ) ) ); // (this allows to come back to the right list order & page)
	$Form->hidden( 'redirect_to', $params[ 'redirect_to' ] );

	$Form->info_field(T_('Reply to'), get_avatar_imgtags( $recipients ), array('required'=>true));

	$Form->textarea('msg_text', '', 10, '', '', $params[ 'cols' ], '', true);

$Form->end_form( array( array( 'submit', 'actionArray[create]', T_('Send message'), 'SaveButton' ) ) );
/*
 * $Log$
 * Revision 1.36  2011/09/27 07:45:58  efy-asimo
 * Front office messaging hot fixes
 *
 * Revision 1.35  2011/09/26 14:53:27  efy-asimo
 * Login problems with multidomain installs - fix
 * Insert globals: samedomain_htsrv_url, secure_htsrv_url;
 *
 * Revision 1.34  2011/09/22 08:55:00  efy-asimo
 * Login problems with multidomain installs - fix
 *
 * Revision 1.33  2011/09/07 00:28:26  sam2kb
 * Replace non-ASCII character in regular expressions with ~
 *
 * Revision 1.32  2011/08/11 09:05:09  efy-asimo
 * Messaging in front office
 *
 * Revision 1.31  2011/05/11 07:11:51  efy-asimo
 * User settings update
 *
 * Revision 1.30  2011/02/15 15:37:00  efy-asimo
 * Change access to admin permission
 *
 * Revision 1.29  2011/02/10 23:07:21  fplanque
 * minor/doc
 *
 * Revision 1.28  2010/11/03 19:44:15  sam2kb
 * Increased modularity - files_Module
 * Todo:
 * - split core functions from _file.funcs.php
 * - check mtimport.ctrl.php and wpimport.ctrl.php
 * - do not create demo Photoblog and posts with images (Blog A)
 *
 * Revision 1.27  2010/01/30 18:55:32  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.26  2010/01/03 16:28:35  fplanque
 * set some crumbs (needs checking)
 *
 * Revision 1.25  2009/12/07 23:07:34  blueyed
 * Whitespace.
 *
 * Revision 1.24  2009/11/21 13:31:59  efy-maxim
 * 1. users controller has been refactored to users and user controllers
 * 2. avatar tab
 * 3. jQuery to show/hide custom duration
 *
 * Revision 1.23  2009/10/11 12:15:51  efy-maxim
 * filter by author of the message and message text
 *
 * Revision 1.22  2009/10/10 10:45:44  efy-maxim
 * messaging module - @action_icon()@
 *
 * Revision 1.21  2009/10/08 20:05:52  efy-maxim
 * Modular/Pluggable Permissions
 *
 * Revision 1.20  2009/09/26 12:00:43  tblue246
 * Minor/coding style
 *
 * Revision 1.19  2009/09/25 07:32:53  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.18  2009/09/20 02:02:45  fplanque
 * fixed read/unread colors
 *
 * Revision 1.17  2009/09/18 10:38:31  efy-maxim
 * 15x15 icons next to login in messagin module
 *
 * Revision 1.16  2009/09/16 13:30:35  efy-maxim
 * A red close "X" on the right of the title bar of messages list
 *
 * Revision 1.15  2009/09/16 09:15:32  efy-maxim
 * Messaging module improvements
 *
 * Revision 1.14  2009/09/15 23:17:12  fplanque
 * minor
 *
 * Revision 1.13  2009/09/15 16:46:21  efy-maxim
 * 1. Avatar in Messages List has been added
 * 2. Duplicated recipients issue has been fixed
 *
 * Revision 1.12  2009/09/15 15:49:32  efy-maxim
 * "read by" column
 *
 * Revision 1.11  2009/09/14 19:33:02  efy-maxim
 * Some queries has been wrapped by SQL object
 *
 * Revision 1.10  2009/09/14 15:18:00  efy-maxim
 * 1. Recipients can be separated by commas or spaces.
 * 2. Message list: author, full name date in the first column.
 * 3. Message list: message in the second column
 *
 * Revision 1.9  2009/09/14 13:52:07  tblue246
 * Translation fixes; removed now pointless doc comment.
 *
 * Revision 1.8  2009/09/14 10:33:20  efy-maxim
 * messagin module improvements
 *
 * Revision 1.7  2009/09/14 07:31:43  efy-maxim
 * 1. Messaging permissions have been fully implemented
 * 2. Messaging has been added to evo bar menu
 *
 * Revision 1.6  2009/09/13 15:56:12  fplanque
 * minor
 *
 * Revision 1.5  2009/09/12 18:44:11  efy-maxim
 * Messaging module improvements
 *
 * Revision 1.4  2009/09/10 18:24:07  fplanque
 * doc
 *
 */
?>
