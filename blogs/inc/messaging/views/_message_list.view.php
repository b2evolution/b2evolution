<?php
/**
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009-2014 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @version $Id: _message_list.view.php 6479 2014-04-16 07:18:54Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $dispatcher, $action, $current_User, $Blog, $perm_abuse_management;

// in front office there is no function call, $edited_Thread is available
if( !isset( $edited_Thread ) )
{ // $edited thread is global in back office, but we are inside of disp_view function call
	global $edited_Thread;

	if( !isset( $edited_Thread ) )
	{
		debug_die( "Missing thread!");
	}
}

global $read_status_list, $leave_status_list;

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
	'cols' => 80,
	'skin_form_params' => array(),
	'display_navigation' => false,
	'display_title' => false,
	'messages_list_start' => '',
	'messages_list_end' => '',
	'messages_list_title' => $edited_Thread->title,
	), $params );

echo $params['messages_list_start'];

if( $params['display_navigation'] )
{	// Display navigation
	echo '<div class="messages_navigation">'
		.'<div class="floatleft">'
			.'<a href="'.get_dispctrl_url( 'threads' ).'">&laquo; '.T_('Back to list').'</a>'
		.'</div>'
		.get_thread_prevnext_links( $edited_Thread->ID )
		.'<div class="clear"></div>'
	.'</div>';
}

if( $params['display_title'] )
{	// Display title
	echo '<h2>'.$edited_Thread->title.'</h2>';
}

// load Thread recipients
$recipient_list = $edited_Thread->load_recipients();
// load Thread recipient users into the UserCache
$UserCache = & get_UserCache();
$UserCache->load_list( $recipient_list );

// Select all recipients with their statuses
$recipients_status_SQL = new SQL();

$recipients_status_SQL->SELECT( 'tsta_user_ID as user_ID, tsta_first_unread_msg_ID, tsta_thread_leave_msg_ID' );

$recipients_status_SQL->FROM( 'T_messaging__threadstatus' );

$recipients_status_SQL->WHERE( 'tsta_thread_ID = '.$edited_Thread->ID );

$recipient_status_list = $DB->get_results( $recipients_status_SQL->get() );
$read_status_list = array();
$leave_status_list = array();
foreach( $recipient_status_list as $row )
{
	$read_status_list[ $row->user_ID ] = $row->tsta_first_unread_msg_ID;
	$leave_status_list[ $row->user_ID ] = $row->tsta_thread_leave_msg_ID;
}
$is_recipient = $edited_Thread->check_thread_recipient( $current_User->ID );
$leave_msg_ID = ( $is_recipient ? $leave_status_list[ $current_User->ID ] : NULL );

// Create SELECT query:
$select_SQL = new SQL();

$select_SQL->SELECT( 'mm.msg_ID, mm.msg_author_user_ID, mm.msg_thread_ID, mm.msg_datetime,
						u.user_ID AS msg_user_ID, u.user_login AS msg_author,
						u.user_firstname AS msg_firstname, u.user_lastname AS msg_lastname,
						u.user_avatar_file_ID AS msg_user_avatar_ID,
						mm.msg_text, '.$DB->quote( $edited_Thread->title ).' AS thread_title' );

$select_SQL->FROM( 'T_messaging__message mm
						LEFT OUTER JOIN T_users u ON u.user_ID = mm.msg_author_user_ID' );

$select_SQL->WHERE( 'mm.msg_thread_ID = '.$edited_Thread->ID );
if( !empty( $leave_msg_ID ) && ( !$perm_abuse_management ) )
{
	$select_SQL->WHERE_and( 'mm.msg_ID <= '.$leave_msg_ID );
}

$select_SQL->ORDER_BY( 'mm.msg_datetime DESC' );

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

$Results->title = $params['messages_list_title'];

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
	'submit_title' => T_('Filter messages'),
	'callback' => 'filter_messages',
	'presets' => array(
		'all' => array( T_('All'), get_dispctrl_url( 'messages', 'thrd_ID='.$edited_Thread->ID ) ),
		)
	);

/*
 * Author col:
 */

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
function author( $user_ID, $datetime)
{
	$author = get_user_avatar_styled( $user_ID, array( 'size' => 'crop-top-80x80' ) );
	return $author.'<div class="note black">'.mysql2date( locale_datefmt().'<\b\r />'.str_replace( ':s', '', locale_timefmt() ), $datetime ).'</div>';
}
$Results->cols[] = array(
		'th' => T_('Author'),
		'th_class' => 'shrinkwrap',
		'td_class' => 'center top',
		'td' => '%author( #msg_user_ID#, #msg_datetime#)%'
	);

function format_msg_text( $msg_text, $thread_title )
{
	global $evo_charset;

	if( empty( $msg_text ) )
	{
		return format_to_output( $thread_title, 'htmlspecialchars' );
	}

	// WARNING: the messages may contain MALICIOUS HTML and javascript snippets. They must ALWAYS be ESCAPED prior to display!
	$msg_text = evo_htmlentities( $msg_text, ENT_COMPAT, $evo_charset );

	$msg_text = make_clickable( $msg_text );
	$msg_text = preg_replace( '#<a #i', '<a rel="nofollow" target="_blank"', $msg_text );
	$msg_text = nl2br( $msg_text );

	return $msg_text;
}
/*
 * Message col
 */
$Results->cols[] = array(
		'th' => T_('Message'),
		'td_class' => 'left top message_text',
		'td' => '%format_msg_text( #msg_text#, #thread_title# )%',
	);

function get_read_by( $message_ID )
{
	global $read_status_list, $leave_status_list, $Blog, $current_User, $perm_abuse_management;

	$UserCache = & get_UserCache();

	if( empty( $Blog ) )
	{	// Set avatar size for a case when blog is not defined
		$avatar_size = 'crop-top-32x32';
	}
	else
	{	// Get avatar size from blog settings
		$avatar_size = $Blog->get_setting('image_size_messaging');
	}

	$read_recipients = array();
	$unread_recipients = array();
	foreach( $read_status_list as $user_ID => $first_unread_msg_ID )
	{
		if( $user_ID == $current_User->ID )
		{ // Current user status: current user should not be displayed except in case of abuse management
			if( $perm_abuse_management )
			{ // current user has seen all received messages for sure, set first unread msg to NULL
				$first_unread_msg_ID = NULL;
			}
			else
			{ // not abuse management
				continue;
			}
		}

		$recipient_User = $UserCache->get_by_ID( $user_ID, false );
		if( !$recipient_User )
		{ // user not exists
			continue;
		}

		$leave_msg_ID = $leave_status_list[ $user_ID ];
		if( !empty( $leave_msg_ID ) && ( $leave_msg_ID < $message_ID ) )
		{ // user has left the conversation and didn't receive this message
			$left_recipients[] = $recipient_User->login;
		}
		elseif( empty( $first_unread_msg_ID ) || ( $first_unread_msg_ID > $message_ID ) )
		{ // user has read all message from this thread or at least this message
			// user didn't leave the conversation before this message
			$read_recipients[] = $recipient_User->login;
		}
		else
		{ // User didn't read this message, but didn't leave the conversation either
			$unread_recipients[] = $recipient_User->login;
		}
	}

	$read_by = '';
	if( !empty( $read_recipients ) )
	{ // There are users who have read this message
		asort( $read_recipients );
		$read_by .= '<div>'.get_avatar_imgtags( $read_recipients, true, false, $avatar_size, '', '', true, false );
		if( !empty ( $unread_recipients ) )
		{
			$read_by .= '<br />';
		}
		$read_by .= '</div>';
	}

	if( !empty ( $unread_recipients ) )
	{ // There are users who didn't read this message
		asort( $unread_recipients );
		$read_by .= '<div>'.get_avatar_imgtags( $unread_recipients, true, false, $avatar_size, '', '', false, false ).'</div>';
	}

	if( !empty ( $left_recipients ) )
	{ // There are users who left the conversation before this message
		asort( $left_recipients );
		$read_by .= '<div>'.get_avatar_imgtags( $left_recipients, true, false, $avatar_size, '', '', 'left_message', false ).'</div>';
	}
	return $read_by;
}

$Results->cols[] = array(
					'th' => T_('Read?'),
					'th_class' => 'shrinkwrap',
					'td_class' => 'top',
					'td' => '%get_read_by( #msg_ID# )%',
					);

function delete_action( $thrd_ID, $msg_ID )
{
	global $Blog, $samedomain_htsrv_url, $perm_abuse_management;
	if( is_admin_page() )
	{
		$tab = '';
		if( $perm_abuse_management )
		{	// We are in Abuse Management
			$tab = '&tab=abuse';
		}
		return action_icon( T_( 'Delete'), 'delete', regenerate_url( 'action', 'thrd_ID='.$thrd_ID.'&msg_ID='.$msg_ID.'&action=delete'.$tab.'&'.url_crumb( 'messaging_messages' ) ) );
	}
	else
	{
		$redirect_to = url_add_param( $Blog->gen_blogurl(), 'disp=messages&thrd_ID='.$thrd_ID );
		$action_url = $samedomain_htsrv_url.'action.php?mname=messaging&disp=messages&thrd_ID='.$thrd_ID.'&msg_ID='.$msg_ID.'&action=delete';
		$action_url = url_add_param( $action_url, 'redirect_to='.rawurlencode( $redirect_to ), '&' );
		return action_icon( T_( 'Delete'), 'delete', $action_url.'&'.url_crumb( 'messaging_messages' ) );
	}
}

if( $current_User->check_perm( 'perm_messaging', 'delete' ) && ( $Results->get_total_rows() > 1 ) )
{	// We have permission to modify and there are more than 1 message (otherwise it's better to delete the whole thread):
	$Results->cols[] = array(
							'th' => T_('Del'),
							'th_class' => 'shrinkwrap',
							'td_class' => 'shrinkwrap',
							'td' => '%delete_action( #msg_thread_ID#, #msg_ID#)%',
						);
}

if( $is_recipient )
{ // Current user is involved in this thread, only involved users can send a message
	// we had to check this because admin user can see all messages in 'Abuse management', but should not be able to reply
	// get all available recipient in this thread
	$available_recipients = array();
	foreach( $recipient_list as $recipient_ID )
	{
		$recipient_User = & $UserCache->get_by_ID( $recipient_ID, false );
		if( ( $recipient_ID != $current_User->ID ) && $recipient_User && !$recipient_User->check_status( 'is_closed' ) && empty( $leave_status_list[ $recipient_ID ] ) )
		{
			$available_recipients[ $recipient_ID ] = $recipient_User->login;
		}
	}
	if( empty( $available_recipients ) )
	{ // There are no other existing and not closed users who are still part of this conversation
		echo '<span class="error">'.T_( 'You cannot reply because this conversation was closed.' ).'</span>';
	}
	elseif( !empty( $leave_msg_ID ) )
	{ // Current user has already left this conversation
		echo '<span class="error">'.T_( 'You cannot reply because you have already left this conversation.' ).'</span>';
	}
	else
	{ // Current user is still part of this conversation, should be able to reply
		$Form = new Form( $params[ 'form_action' ], $params[ 'form_name' ], 'post', $params[ 'form_layout' ] );

		$Form->switch_template_parts( $params['skin_form_params'] );

		$Form->begin_form( $params['form_class'], '' );

			$Form->add_crumb( 'messaging_messages' );
			if( $perm_abuse_management )
			{	// To back in the abuse management
				memorize_param( 'tab', 'string', 'abuse' );
			}
			$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',msg_ID' : '' ) ) ); // (this allows to come back to the right list order & page)
			$Form->hidden( 'redirect_to', $params[ 'redirect_to' ] );

			$Form->info_field(T_('Reply to'), get_avatar_imgtags( $available_recipients, true, true, 'crop-top-15x15', 'avatar_before_login mb1' ), array('required'=>true));

			if( !empty( $closed_recipients ) )
			{
				$Form->info_field( '', T_( 'The other users involved in this conversation have closed their account.' ) );
			}

			$Form->textarea_input( 'msg_text', !empty( $edited_Message ) ? $edited_Message->original_text : '', 10, T_('Message'), array(
					'cols' => $params['cols'],
					'required' => true
				) );

		$Form->end_form( array( array( 'submit', 'actionArray[create]', T_('Send message'), 'SaveButton' ) ) );
	}
}

// Display Leave or Close conversation action if they are available
if( $is_recipient && empty( $leave_msg_ID ) && ( count( $available_recipients ) > 0 ) )
{ // user is recipient and didn't leave this conversation yet and this conversation is not closed
	echo '<div class="fieldset messages_list_actions">';
	if( count( $available_recipients ) > 1 )
	{ // there are more then one recipients
		$leave_text = T_( 'I want to leave this conversation now!' );
		$confirm_leave_text = TS_( 'If you leave this conversation,\\nother users can still continue the conversation\\nbut you will not receive their future replies.\\nAre you sure?' );
		$leave_action = 'leave';
	}
	else
	{ // only one recipient exists if the user leave the conversation then it will be closed.
		$recipient_ID = key( $available_recipients );
		$recipient_login = $available_recipients[$recipient_ID];
		$leave_text = get_icon( 'stop', 'imgtag', array( 'style' => 'margin-right:5px' ) ).T_( 'I want to end this conversation now!' );
		$block_text = get_icon( 'ban', 'imgtag', array( 'style' => 'margin:0 7px 2px 1px;vertical-align:middle;' ) ).sprintf( T_( 'I want to block %s from sending me any more messages!' ), $recipient_login );
		$confirm_leave_text = T_( 'Are you sure you want to close this conversation?' );
		$confirm_block_text = sprintf( TS_( 'This action will close this conversion\\nand will block %s from starting any new\\nconversation with you.\\n(You can see blocked users in your contacts list)\\nAre you sure you want to close and block?' ), $recipient_login );
		$leave_action = 'close';
	}
	if( is_admin_page() )
	{ // backoffice
		$leave_url = '?ctrl=threads&thrd_ID='.$edited_Thread->ID.'&action='.$leave_action.'&'.url_crumb( 'messaging_threads' );
		$close_and_block_url = '?ctrl=threads&thrd_ID='.$edited_Thread->ID.'&action=close_and_block&block_ID='.$recipient_ID.'&'.url_crumb( 'messaging_threads' );
	}
	else
	{ // frontoffice
		$leave_url = url_add_param( $params[ 'form_action' ], 'disp=threads&thrd_ID='.$edited_Thread->ID.'&action='.$leave_action.'&redirect_to='.rawurlencode( url_add_param( $Blog->gen_blogurl(), 'disp=threads', '&' ) ).'&'.url_crumb( 'messaging_threads' ) );
		$close_and_block_url = url_add_param( $params[ 'form_action' ], 'disp=threads&thrd_ID='.$edited_Thread->ID.'&action=close_and_block&block_ID='.$recipient_ID.'&redirect_to='.rawurlencode( url_add_param( $Blog->gen_blogurl(), 'disp=threads', '&' ) ).'&'.url_crumb( 'messaging_threads' ) );
	}
	echo '<p>';
	echo '<a href="'.$leave_url.'" onclick="return confirm( \''.$confirm_leave_text.'\' );">'.$leave_text.'</a>';
	if( $leave_action == 'close' )
	{ // user want's to close this conversation ( there is only one recipient )
		echo '<br />';
		echo '<a href="'.$close_and_block_url.'" onclick="return confirm( \''.$confirm_block_text.'\' );">'.$block_text.'</a>';
	}
	echo '</p>';
	echo '</div>';
}

// Disable rollover effect on table rows
$Results->display_init( $display_params );
$display_params['list_start'] = str_replace( 'class="grouped', 'class="grouped nohover', $Results->params['list_start'] );

// Dispaly message list
$Results->display( $display_params );

echo $params['messages_list_end'];

?>