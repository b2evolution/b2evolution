<?php
/**
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @package messaging
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $dispatcher, $action, $current_User, $Blog, $perm_abuse_management, $Plugins, $edited_Message;

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
	'form_class_msg'            => 'fform',
	'form_action'               => NULL,
	'form_name'                 => 'messages_checkchanges',
	'form_layout'               => 'compact',
	'redirect_to'               => regenerate_url( 'action', '', '', '&' ),
	'cols'                      => 80,
	'skin_form_params'          => array(),
	'display_navigation'        => false,
	'display_title'             => false,
	'messages_list_start'       => '',
	'messages_list_end'         => '',
	'messages_list_title'       => $edited_Thread->title,
	'messages_list_title_start' => '<div class="panel-heading"><h2>',
	'messages_list_title_end'   => '</h2></div>',
	'messages_list_form_start'  => '<div class="panel panel-default">',
	'messages_list_form_end'    => '</div>',
	), $params );

echo $params['messages_list_start'];

if( $params['display_navigation'] )
{ // Display navigation
	echo '<div class="messages_navigation">'
		.'<div class="floatleft">'
			.'<a href="'.get_dispctrl_url( 'threads' ).'">&laquo; '.T_('Back to list').'</a>'
		.'</div>'
		.get_thread_prevnext_links( $edited_Thread->ID )
		.'<div class="clear"></div>'
	.'</div>';
}

echo $params['messages_list_form_start'];
if( $params['display_title'] )
{ // Display title
	echo $params['messages_list_title_start'].$edited_Thread->title.$params['messages_list_title_end'];
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
						mm.msg_text, mm.msg_renderers,
						'.$DB->quote( $edited_Thread->title ).' AS thread_title' );

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

$select_sql = $select_SQL->get();
if( $action == 'preview' )
{ // Init PREVIEW message
	global $localtimenow;

	foreach( $recipient_status_list as $row )
	{ // To make the unread status for each recipient
		$read_status_list[ $row->user_ID ] = -1;
		$leave_status_list[ $row->user_ID ] = 0;
	}

	$count_SQL->SELECT( 'COUNT(*) + 1' );

	$select_sql = '(
	SELECT
		0 AS msg_ID, '.$current_User->ID.' AS msg_author_user_ID, '.$edited_Thread->ID.' AS msg_thread_ID, "'.date( 'Y-m-d H:i:s', $localtimenow ).'" AS msg_datetime,
		'.$current_User->ID.' AS msg_user_ID, '.$DB->quote( $current_User->login ).' AS msg_author,
		'.$DB->quote( $current_User->firstname ).' AS msg_firstname, '.$DB->quote( $current_User->lastname ).' AS msg_lastname,
		'.$DB->quote( $current_User->avatar_file_ID ).' AS msg_user_avatar_ID,
		'.$DB->quote( '<b>'.T_('PREVIEW').':</b><br /> '.$edited_Message->get_prerendered_content() ).' AS msg_text, '.$DB->quote( $edited_Message->renderers ).' AS msg_renderers,
		'.$DB->quote( $edited_Thread->title ).' AS thread_title
	)
	UNION
	('.$select_sql.')
	ORDER BY msg_datetime DESC';
}

// Create result set:
$Results = new Results( $select_sql, 'msg_', '', 0, $count_SQL->get() );

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

/**
 * Author:
 */
$Results->cols[] = array(
		'th' => T_('Author'),
		'th_class' => 'shrinkwrap',
		'td_class' => 'center top',
		'td' => '%col_msg_author( #msg_user_ID#, #msg_datetime#)%'
	);
/**
 * Message:
 */
$Results->cols[] = array(
		'th' => T_('Message'),
		'td_class' => 'left top message_text',
		'td' => '%col_msg_format_text( #msg_ID#, #msg_text# )%',
	);
/**
 * Read?:
 */
$Results->cols[] = array(
		'th' => T_('Read?'),
		'th_class' => 'shrinkwrap',
		'td_class' => 'top',
		'td' => '%col_msg_read_by( #msg_ID# )%',
	);

/**
 * Actions:
 */
if( $current_User->check_perm( 'perm_messaging', 'delete' ) && ( $Results->get_total_rows() > 1 ) && ( $action != 'preview' ) )
{	// We have permission to modify and there are more than 1 message (otherwise it's better to delete the whole thread):
	$Results->cols[] = array(
							'th' => T_('Del'),
							'th_class' => 'shrinkwrap',
							'td_class' => 'shrinkwrap',
							'td' => '%col_msg_actions( #msg_thread_ID#, #msg_ID#)%',
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

		$Form->begin_form( $params['form_class_msg'], '' );

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

			ob_start();
			echo '<div class="message_toolbars">';
			// CALL PLUGINS NOW:
			$Plugins->trigger_event( 'DisplayMessageToolbar', array() );
			echo '</div>';
			$message_toolbar = ob_get_clean();

			$form_inputstart = $Form->inputstart;
			$Form->inputstart .= $message_toolbar;
			$Form->textarea_input( 'msg_text', !empty( $edited_Message ) ? $edited_Message->original_text : '', 10, T_('Message'), array(
					'cols' => $params['cols'],
					'required' => true
				) );
			$Form->inputstart = $form_inputstart;

			// set b2evoCanvas for plugins
			echo '<script type="text/javascript">var b2evoCanvas = document.getElementById( "msg_text" );</script>';

			// Display renderers
			$current_renderers = !empty( $edited_Message ) ? $edited_Message->get_renderers_validated() : array( 'default' );
			$message_renderer_checkboxes = $Plugins->get_renderer_checkboxes( $current_renderers, array( 'setting_name' => 'msg_apply_rendering' ) );
			if( !empty( $message_renderer_checkboxes ) )
			{
				$Form->info( T_('Text Renderers'), $message_renderer_checkboxes );
			}

		$Form->end_form( array(
				array( 'submit', 'actionArray[preview]', T_('Preview'), 'SaveButton' ),
				array( 'submit', 'actionArray[create]', T_('Send message'), 'SaveButton' )
			) );
	}
}

echo $params['messages_list_form_end'];

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

if( $action == 'preview' )
{ // Display error messages again before preview of message
	echo '<div class="fieldset messages_list_actions">';
	global $Messages;
	$Messages->display();
	echo '</div>';
}

// Disable rollover effect on table rows
$Results->display_init( $display_params );
$display_params['list_start'] = str_replace( 'class="grouped', 'class="grouped nohover', $Results->params['list_start'] );

// Dispaly message list
$Results->display( $display_params );

echo $params['messages_list_end'];

?>