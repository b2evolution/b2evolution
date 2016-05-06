<?php
/**
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2016 by Francois Planque - {@link http://fplanque.com/}
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

global $last_read_status_list, $leave_status_list;

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
	'messages_list_start'       => is_admin_page() ? '<div class="evo_private_messages_list">' : '',
	'messages_list_end'         => is_admin_page() ? '</div>' : '',
	'messages_list_title'       => $edited_Thread->title,
	'messages_list_title_start' => '<div class="panel-heading"><h2 class="panel-title">',
	'messages_list_title_end'   => '</h2></div>',
	'messages_list_form_start'  => '<div class="evo_private_messages_form">',
	'messages_list_form_end'    => '</div>',
	'messages_list_body_start'  => '<div class="panel-body">',
	'messages_list_body_end'    => '</div>',
	), $params );

echo $params['messages_list_start'];

if( $params['display_navigation'] )
{	// Display navigation:
	echo '<div class="evo_private_messages_list__navigation">'
		.'<div class="pull-left">'
			.'<a href="'.get_dispctrl_url( 'threads' ).'">&laquo; '.T_('Back to list').'</a>'
		.'</div>'
		.get_thread_prevnext_links( $edited_Thread->ID )
		.'<div class="clearfix"></div>'
	.'</div>';
}

// Load Thread recipients:
$recipient_list = $edited_Thread->load_recipients();
// Load Thread recipient users into the UserCache:
$UserCache = & get_UserCache();
$UserCache->load_list( $recipient_list );

// Get all available recipient in this thread:
$available_recipients = array();
foreach( $recipient_list as $recipient_ID )
{
	$recipient_User = & $UserCache->get_by_ID( $recipient_ID, false );
	if( ( $recipient_ID != $current_User->ID ) && $recipient_User && !$recipient_User->check_status( 'is_closed' ) && empty( $leave_status_list[ $recipient_ID ] ) )
	{
		$available_recipients[ $recipient_ID ] = $recipient_User->login;
	}
}

// Select all recipients with their statuses:
$last_read_msg_SQL = new SQL( 'SUBQUERY to get what last message has been read by user' );
$last_read_msg_SQL->SELECT( 'msg_ID' );
$last_read_msg_SQL->FROM( 'T_messaging__message' );
$last_read_msg_SQL->WHERE( 'tsta_thread_ID = '.$edited_Thread->ID );
$last_read_msg_SQL->WHERE_and( 'msg_ID < tsta_first_unread_msg_ID' ); // to get first/previous message before first unread message
$last_read_msg_SQL->ORDER_BY( 'msg_datetime DESC' );
$last_read_msg_SQL->LIMIT( '1' );

$recipients_status_SQL = new SQL( 'Get read/unread/leave message IDs on thread #'.$edited_Thread->ID.' for each user' );
$recipients_status_SQL->SELECT( 'tsta_user_ID, tsta_first_unread_msg_ID, tsta_thread_leave_msg_ID, ' );
$recipients_status_SQL->SELECT_add( '('.$last_read_msg_SQL->get().') AS last_read_msg_ID' );
$recipients_status_SQL->FROM( 'T_messaging__threadstatus' );
$recipients_status_SQL->WHERE( 'tsta_thread_ID = '.$edited_Thread->ID );
$recipient_status_list = $DB->get_results( $recipients_status_SQL->get(), OBJECT, $recipients_status_SQL->title );

$last_read_status_list = array();
$leave_status_list = array();
foreach( $recipient_status_list as $row )
{
	$leave_status_list[ $row->tsta_user_ID ] = $row->tsta_thread_leave_msg_ID;
	if( $row->tsta_first_unread_msg_ID === NULL && $row->tsta_thread_leave_msg_ID === NULL )
	{	// Get last message ID if user has read all messages in the thread:
		$last_read_status_list[ $row->tsta_user_ID ] = $edited_Thread->get_last_message_ID();
	}
	else
	{	// Get last read message ID for the user:
		$last_read_status_list[ $row->tsta_user_ID ] = $row->last_read_msg_ID;
	}
}
$is_recipient = $edited_Thread->check_thread_recipient( $current_User->ID );
$leave_msg_ID = ( $is_recipient ? $leave_status_list[ $current_User->ID ] : NULL );


// ---------------- Header - START ---------------- //
echo '<div class="evo_private_messages_list__header">';

// Display title:
echo '<div class="pull-left">';
	echo '<h2>'.sprintf( T_('Conversation: %s'), $edited_Thread->title ).'</h2>';
	echo '<p>'.sprintf( T_('With: %s'), get_avatar_imgtags( $available_recipients, true, true, 'crop-top-15x15', 'avatar_before_login', '', NULL, true, ', ' ) ).'</p>';
echo '</div>';

// Display Leave or Close conversation action if they are available:
if( $is_recipient && empty( $leave_msg_ID ) && ( count( $available_recipients ) > 0 ) )
{	// Current user is recipient and didn't leave this conversation yet and this conversation is not closed:
	echo '<div class="pull-right">';
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
	echo '<a href="'.$leave_url.'" onclick="return confirm( \''.$confirm_leave_text.'\' );" class="btn btn-default btn-sm">'.$leave_text.'</a>';
	if( $leave_action == 'close' )
	{ // user want's to close this conversation ( there is only one recipient )
		echo ' <a href="'.$close_and_block_url.'" onclick="return confirm( \''.$confirm_block_text.'\' );" class="btn btn-default btn-sm">'.$block_text.'</a>';
	}
	echo '</div>';
}

echo '<div class="clearfix"></div>';

echo '</div>';
// ---------------- Header - END ---------------- //


// ---------------- Messages list - START ---------------- //
// Create SELECT query:
$select_SQL = new SQL();

$select_SQL->SELECT( 'msg_ID, msg_author_user_ID, msg_thread_ID, msg_datetime,
	user_ID AS msg_user_ID, user_login AS msg_author,
	user_firstname AS msg_firstname, user_lastname AS msg_lastname,
	user_avatar_file_ID AS msg_user_avatar_ID,
	msg_text, msg_renderers,
	'.$DB->quote( $edited_Thread->title ).' AS thread_title,
	DATE_FORMAT( msg_datetime, "%Y-%m-%d" ) AS message_day_date' );

$select_SQL->FROM( 'T_messaging__message' );
$select_SQL->FROM_add( 'LEFT OUTER JOIN T_users ON user_ID = msg_author_user_ID' );

$select_SQL->WHERE( 'msg_thread_ID = '.$edited_Thread->ID );
if( !empty( $leave_msg_ID ) && ( !$perm_abuse_management ) )
{
	$select_SQL->WHERE_and( 'msg_ID <= '.$leave_msg_ID );
}

// Create COUNT query:
$count_SQL = new SQL();
$count_SQL->SELECT( 'COUNT(*)' );
$count_SQL->FROM( 'T_messaging__message' );
$count_SQL->WHERE( 'msg_thread_ID = '.$edited_Thread->ID );

// Get params from request:
$s = param( 's', 'string', '', true );

if( ! empty( $s ) )
{	// Filter by search keyword:
	$select_SQL->WHERE_and( 'CONCAT_WS( " ", user_login, user_firstname, user_lastname, user_nickname, msg_text ) LIKE "%'.$DB->escape($s).'%"' );

	$count_SQL->FROM_add( 'LEFT OUTER JOIN T_users ON user_ID = msg_author_user_ID' );
	$count_SQL->WHERE_and( 'CONCAT_WS( " ", user_login, user_firstname, user_lastname, user_nickname, msg_text ) LIKE "%'.$DB->escape($s).'%"' );
}

$select_sql = $select_SQL->get();

if( $action == 'preview' )
{	// Init PREVIEW message:
	global $localtimenow;

	$count_SQL->SELECT( 'COUNT(*) + 1' );

	$select_sql = 'SELECT msg_ID, msg_author_user_ID, msg_thread_ID, msg_datetime,
					msg_user_ID, msg_author, msg_firstname, msg_lastname, msg_user_avatar_ID,
					msg_text, msg_renderers, thread_title, message_day_date
	FROM (
	SELECT
		"preview" AS msg_ID,
		'.$current_User->ID.' AS msg_author_user_ID,
		'.$edited_Thread->ID.' AS msg_thread_ID,
		'.$DB->quote( date( 'Y-m-d H:i:s', $localtimenow ) ).' AS msg_datetime,
		'.$current_User->ID.' AS msg_user_ID,
		'.$DB->quote( $current_User->login ).' AS msg_author,
		'.$DB->quote( $current_User->firstname ).' AS msg_firstname,
		'.$DB->quote( $current_User->lastname ).' AS msg_lastname,
		'.$DB->quote( $current_User->avatar_file_ID ).' AS msg_user_avatar_ID,
		'.$DB->quote( $edited_Message->get_prerendered_content() ).' AS msg_text,
		'.$DB->quote( $edited_Message->renderers ).' AS msg_renderers,
		'.$DB->quote( $edited_Thread->title ).' AS thread_title,
		"preview" AS message_day_date
	UNION
	'.$select_sql
	.') AS umm';
}

$select_sql .= '
	GROUP BY msg_ID, message_day_date
	ORDER BY msg_datetime ASC';

// Create result set:
$Results = new Results( $select_sql, 'msg_', '', 0, $count_SQL->get() );

$Results->Cache = & get_MessageCache();

// Grouping params:
$Results->group_by = 'message_day_date';
$Results->ID_col = 'msg_ID';

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

// Date row:
$Results->grp_cols[] = array(
		'td_colspan' => 4,
		'td'         => '%col_msg_group_date( #message_day_date# )%',
	);

/**
 * Author:
 */
$Results->cols[] = array(
		'th' => '',
		'td_class' => 'shrinkwrap',
		'td' => '%col_msg_author_avatar( #msg_author# )%'
	);

/**
 * Message:
 */
$Results->cols[] = array(
		'th' => '',
		'td' => '<p>%get_user_identity_link( "", #msg_user_ID#, "profile", "auto" )%</p>'
			.'%col_msg_format_text( #msg_ID#, #msg_text# )%',
	);

/**
 * Time:
 */
$Results->cols[] = array(
		'th' => '',
		'td_class' => '',
		'td' => '<span class="shrinkwrap">%col_msg_time( #msg_datetime# )%</span>'
			.'%col_msg_read_last_users( #msg_ID# )%',
	);

/**
 * Actions:
 */
if( $current_User->check_perm( 'perm_messaging', 'delete' ) && ( $Results->get_total_rows() > 1 ) && ( $action != 'preview' ) )
{	// We have permission to modify and there are more than 1 message (otherwise it's better to delete the whole thread):
	$Results->cols[] = array(
			'th' => '',
			'td_class' => 'shrinkwrap',
			'td' => '%col_msg_actions( #msg_thread_ID#, #msg_ID#)%',
		);
}


if( $action == 'preview' )
{	// Display error messages again before preview of message:
	global $Messages;
	$Messages->display();
}

// Initialize display params:
$Results->display_init( $display_params );
$Results->params['before'] = '<div class="evo_private_messages_list__table">';
$Results->params['after'] = '</div>';
$Results->params['filters_start'] = '<div class="evo_private_messages_list__filters">';
$Results->params['filters_end'] = '</div>';
$Results->params['list_start'] = '<table class="table">';
$Results->params['list_end'] = "</table>\n\n";
$Results->params['grp_line_start'] = '<tbody class="group"><tr>'."\n";
$Results->params['grp_line_end'] = "</tr></tbody>\n\n";

// Dispaly message list
$Results->display();

echo $params['messages_list_end'];
// ---------------- Messages list - END ---------------- //


// ---------------- Form to send new message - START ---------------- //
if( $is_recipient )
{	// Current user is involved in this thread, only involved users can send a message
	// we had to check this because admin user can see all messages in 'Abuse management', but should not be able to reply

	global $Messages;
	$Messages->clear();
	if( empty( $available_recipients ) )
	{ // There are no other existing and not closed users who are still part of this conversation
		$Messages->add( T_( 'You cannot reply because this conversation was closed.' ), 'warning' );
		$Messages->display();
	}
	elseif( !empty( $leave_msg_ID ) )
	{ // Current user has already left this conversation
		$Messages->add( T_( 'You cannot reply because you have already left this conversation.' ), 'warning' );
		$Messages->display();
	}
	else
	{ // Current user is still part of this conversation, should be able to reply
		echo $params['messages_list_form_start'];

		$Form = new Form( $params['form_action'], $params['form_name'], 'post', $params['form_layout'] );

		if( ! is_admin_page() )
		{	// Add hidden blog ID to correctly redirect after message posting:
			$Form->hidden( 'blog', $Blog->ID );
		}

		$Form->switch_template_parts( array(
				'formstart'             => '',
				'formend'               => '',
				'no_title_no_icons_fmt' => '',
				'labelempty'            => '',
				'inputstart'            => '<div class="controls col-md-10 col-sm-9">',
			) );

		$Form->begin_form();

		$Form->add_crumb( 'messaging_messages' );
		if( $perm_abuse_management )
		{	// To back in the abuse management
			memorize_param( 'tab', 'string', 'abuse' );
		}
		$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',msg_ID' : '' ) ) ); // (this allows to come back to the right list order & page)
		$Form->hidden( 'redirect_to', $params[ 'redirect_to' ] );

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

		// Get available renderer checkboxes:
		$current_renderers = !empty( $edited_Message ) ? $edited_Message->get_renderers_validated() : array( 'default' );
		$message_renderer_checkboxes = $Plugins->get_renderer_checkboxes( $current_renderers, array( 'setting_name' => 'msg_apply_rendering' ) );

		// Form buttons:
		$Form->output = false;
		$form_buttons = '<div class="evo_private_messages_form__actions col-md-2 col-sm-3">';
		if( ! empty( $message_renderer_checkboxes ) )
		{	// Display the options button only when plugins renderers are displayed for message:
			$form_buttons .= $Form->button( array( 'button', 'message_options_button', T_('Options'), 'btn-default' ) );
		}
		$form_buttons .= $Form->button( array( 'submit', 'actionArray[preview]', T_('Preview'), 'SaveButton btn-info' ) );
		$form_buttons .= $Form->button( array( 'submit', 'actionArray[create]', T_('Send'), 'SaveButton' ) );
		$form_buttons .= '</div>';
		$Form->output = true;

		$form_inputstart = $Form->inputstart;
		$form_inputend = $Form->inputend;
		$Form->inputstart .= $message_toolbar;
		$Form->inputend .= $form_buttons;
		$Form->textarea_input( 'msg_text', ! empty( $edited_Message ) ? $edited_Message->original_text : '', 10, '', array(
				'cols'     => $params['cols'],
				'required' => true
			) );
		$Form->inputstart = $form_inputstart;
		$Form->inputend = $form_inputend;

		// set b2evoCanvas for plugins
		echo '<script type="text/javascript">var b2evoCanvas = document.getElementById( "msg_text" );</script>';

		if( ! empty( $message_renderer_checkboxes ) )
		{	// Initialize hidden checkboxes of renderer plugins:
			echo '<div id="message_options_block" style="display:none"><div class="form-horizontal">';
			$Form->info( T_('Text Renderers'), $message_renderer_checkboxes );
			echo '</div></div>';
			// If JavaScript is disabled then display standard checklist of renderer plugins without modal window:
			echo '<noscript><style type="text/css">
					#message_options_block{ display:block !important; }
					#message_options_button{ display: none; }
				</style></noscript>';
		}

		$Form->end_form();

		echo $params['messages_list_form_end'];
	}
}
// ---------------- Form to send new message - END ---------------- //

// Initialize JavaScript for AJAX loading of popup window to display message options:
echo_message_options_window();
?>