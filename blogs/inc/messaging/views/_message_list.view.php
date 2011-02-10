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

global $dispatcher, $action, $current_User, $edited_Thread;

global $read_by_list;

$creating = is_create_action( $action );

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

$Results->global_icon( T_('Cancel!'), 'close', '?ctrl=threads' );

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
		'all' => array( T_('All'), '?ctrl=messages&thrd_ID='.$edited_Thread->ID ),
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
	if( ! $GLOBALS['Settings']->get('allow_avatars') ) 
		return '';

	$FileCache = & get_FileCache();

	if( ! $File = & $FileCache->get_by_ID( $user_avatar_file_ID, false, false ) )
	{
		return '';
	}
	return '<a href="?ctrl=user&amp;user_tab=identity&amp;user_ID='.$user_ID.'">'.$File->get_thumb_imgtag( 'crop-80x80' ).'</a>';
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
		'td' => '¤conditional( empty(#msg_text#), \''.$edited_Thread->title.'\', \'%nl2br(#msg_text#)%\')¤',
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

if( $current_User->check_perm( 'perm_messaging', 'delete' ) )
{
	// We have permission to modify:

	$Results->cols[] = array(
							'th' => T_('Actions'),
							'th_class' => 'shrinkwrap',
							'td_class' => 'shrinkwrap',
							// Do not display the icon if the message cannot be deleted
							'td' => $Results->total_rows == 1 ? '' : '@action_icon("delete")@',
						);
}

$Results->display();

$Form = new Form( NULL, 'messages_checkchanges', 'post', 'compact' );

$Form->begin_form( 'fform', '' );

	$Form->add_crumb( 'message' );
	$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',msg_ID' : '' ) ) ); // (this allows to come back to the right list order & page)

	$Form->info_field(T_('Reply to'), get_avatar_imgtags( $recipients ), array('required'=>true));

	$Form->textarea('msg_text', '', 10, '', '', 80, '', true);

$Form->end_form( array( array( 'submit', 'actionArray[create]', T_('Record'), 'SaveButton' ),
												array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
/*
 * $Log$
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
