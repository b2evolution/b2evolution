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

/**
 * @var Message
 */
global $edited_Message;
global $edited_Thread;
global $creating_success;

global $DB, $action, $Plugins, $Settings;

global $Collection, $Blog;

global $thrd_recipients_array, $recipients_selected;

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
	'form_class_thread' => 'fform',
	'form_title' => TB_('New thread').( is_admin_page() ? get_manual_link( 'messages-new-thread' ) : '' ),
	'form_action' => NULL,
	'form_name' => 'thread_checkchanges',
	'form_layout' => 'compact',
	'redirect_to' => regenerate_url( 'action', '', '', '&' ),
	'cols' => 80,
	'thrdtype' => param( 'thrdtype', 'string', 'discussion' ),  // alternative: individual
	'skin_form_params' => array(),
	'allow_select_recipients' => true,
	'messages_list_start' => is_admin_page() ? '<div class="evo_private_messages_list">' : '',
	'messages_list_end' => is_admin_page() ? '</div>' : '',
	'messages_list_title' => $edited_Thread->title,
	), $params );

$Form = new Form( $params['form_action'], $params['form_name'], 'post', $params['form_layout'] );

$Form->switch_template_parts( $params['skin_form_params'] );

if( is_admin_page() )
{
	$Form->global_icon( TB_('Cancel editing').'!', 'close', regenerate_url( 'action' ) );
}

$Form->begin_form( $params['form_class_thread'], $params['form_title'], array( 'onsubmit' => 'return check_form_thread()') );

	$Form->add_crumb( 'messaging_threads' );
	$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',msg_ID' : '' ) ) ); // (this allows to come back to the right list order & page)
	$Form->hidden( 'redirect_to', $params[ 'redirect_to' ] );
	if( !empty( $Blog ) )
	{ // Set blog as hidden param, because we may need the blog locale after submit
		// This issues should be solved differently
		$Form->hidden( 'blog', $Blog->ID );
	}

if( $params['allow_select_recipients'] )
{	// User can select recipients
	$Form->text_input( 'thrd_recipients', $edited_Thread->recipients, $params['cols'], TB_('Recipients'),
		'<noscript>'.TB_('Enter usernames. Separate with comma (,)').'</noscript>',
		array(
			'maxlength'=> 255,
			'required'=>true,
			'class'=>'wide_input'
		) );

	echo '<div id="multiple_recipients">';
	$Form->radio( 'thrdtype', $params['thrdtype'], array(
									array( 'discussion', TB_( 'Start a group discussion' ) ),
									array( 'individual', TB_( 'Send individual messages' ) )
								), TB_('Multiple recipients'), true );
	echo '</div>';
}
else
{	// No available to select recipients, Used in /contact.php
	$Form->info( TB_('Recipients'), $edited_Thread->recipients );
	if( $recipients_selected )
	{
		foreach( $recipients_selected as $recipient )
		{
			$Form->hidden( 'thrd_recipients_array[id][]', $recipient['id'] );
			$Form->hidden( 'thrd_recipients_array[login][]', $recipient['login'] );
		}
	}
}

$Form->text_input( 'thrd_title', $edited_Thread->title, $params['cols'], TB_('Subject'), '', array( 'maxlength'=> 255, 'required'=>true, 'class'=>'wide_input large' ) );

// Display plugin captcha for message form before textarea:
$Plugins->display_captcha( array(
		'Form'              => & $Form,
		'form_type'         => 'message',
		'form_position'     => 'before_textarea',
		'form_use_fieldset' => false,
	) );

if( is_admin_page() && check_user_perm( 'files', 'view' ) )
{	// If current user has a permission to view the files AND it is back-office:
	load_class( 'links/model/_linkmessage.class.php', 'LinkMessage' );
	// Initialize this object as global because this is used in many link functions:
	global $LinkOwner;
	$LinkOwner = new LinkMessage( $edited_Message, param( 'temp_link_owner_ID', 'integer', 0 ) );
}

ob_start();
echo '<div class="message_toolbars">';
// CALL PLUGINS NOW:
$message_toolbar_params = array( 'Message' => & $edited_Message );
if( isset( $LinkOwner) && $LinkOwner->is_temp() )
{
	$message_toolbar_params['temp_ID'] = $LinkOwner->get_ID();
}
$Plugins->trigger_event( 'DisplayMessageToolbar', $message_toolbar_params );
echo '</div>';
$message_toolbar = ob_get_clean();

// CALL PLUGINS NOW:
ob_start();
$admin_editor_params = array(
		'target_type'   => 'Message',
		'target_object' => $edited_Message,
		'content_id'    => 'msg_text',
		'edit_layout'   => NULL,
	);
if( isset( $LinkOwner) && $LinkOwner->is_temp() )
{
	$admin_editor_params['temp_ID'] = $LinkOwner->get_ID();
}
$Plugins->trigger_event( 'AdminDisplayEditorButton', $admin_editor_params );
$quick_setting_switch = ob_get_clean();

$form_inputstart = $Form->inputstart;
$form_inputend = $Form->inputend;
$Form->inputstart .= $message_toolbar;
$Form->inputend = $quick_setting_switch.$Form->inputend;
$Form->textarea_input( 'msg_text', $edited_Message->original_text, 10, TB_('Message'), array(
		'cols' => $params['cols'],
		'required' => true
	) );
$Form->inputstart = $form_inputstart;
$Form->inputend = $form_inputend;

// set b2evoCanvas for plugins
echo '<script>var b2evoCanvas = document.getElementById( "msg_text" );</script>';

// Display renderers
$current_renderers = !empty( $edited_Message ) ? $edited_Message->get_renderers_validated() : array( 'default' );
$message_renderer_checkboxes = $Plugins->get_renderer_checkboxes( $current_renderers, array( 'setting_name' => 'msg_apply_rendering' ) );
if( !empty( $message_renderer_checkboxes ) )
{
	$Form->info( TB_('Text Renderers'), $message_renderer_checkboxes );
}

// ####################### ATTACHMENTS/LINKS #########################
$Form->attachments_fieldset( $edited_Message );

if( !empty( $thrd_recipients_array ) )
{	// Initialize the preselected users (from post request or when user send a message to own contacts)
	foreach( $thrd_recipients_array['id'] as $rnum => $recipient_ID )
	{
		$recipients_selected[] = array(
			'id'    => $recipient_ID,
			'login' => $thrd_recipients_array['login'][$rnum]
		);
	}
}

// Display plugin captcha for message form before submit button:
$Plugins->display_captcha( array(
		'Form'              => & $Form,
		'form_type'         => 'message',
		'form_position'     => 'before_submit_button',
		'form_use_fieldset' => false,
	) );

// display submit button, but only if enabled
$Form->end_form( array(
		array( 'submit', 'actionArray[preview]', /* TRANS: Verb */ TB_('Preview'), 'SaveButton btn-info' ),
		array( 'submit', 'actionArray[create]', TB_('Send message'), 'SaveButton' )
	) );

if( $params['allow_select_recipients'] )
{	// User can select recipients
	$thread_form_config = array(
			'missing_username_msg' => T_('Please complete the entering of an username.'),
			'username_display'     => $Settings->get( 'username_display' ) == 'name' ? 'fullname' : 'login',
			'thrd_recipients_has_error' => param_has_error( 'thrd_recipients' ),
			'token_input_config' => array(
					'theme'             => 'facebook',
					'queryParam'        => 'q',
					'propertyToSearch'  => 'login',
					'preventDuplicates' => true,
					'prePopulate'       => $recipients_selected,
					'hintText'          => T_('Type in a username'),
					'noResultsText'     => T_('No results'),
					'searchingText'     => T_('Searching...'),
					'jsonContainer'     => 'users',
				),
		);

	expose_var_to_js( 'evo_thread_form_config', evo_json_encode( $thread_form_config ) );
}

echo_image_insert_modal();
if( $action == 'preview' )
{	// ------------------ PREVIEW MESSAGE START ------------------ //
	if( isset( $edited_Thread->recipients_list ) )
	{
		$recipients_list = $edited_Thread->recipients_list;
	}
	else
	{
		$recipients_list = !empty( $edited_Thread->recipients ) ? explode( ',', $edited_Thread->recipients ) : array();
	}

	// load Thread recipient users into the UserCache
	$UserCache = & get_UserCache();
	$UserCache->load_list( $recipients_list );

	// Init recipients list
	global $read_status_list, $leave_status_list, $localtimenow;
	$read_status_list = array();
	$leave_status_list = array();
	foreach( $recipients_list as $user_ID )
	{
		$read_status_list[ $user_ID ] = -1;
		$leave_status_list[ $user_ID ] = 0;
	}

	$preview_SQL = new SQL();
	$preview_SQL->SELECT( $current_User->ID.' AS msg_author_user_ID, 0 AS msg_thread_ID, 0 AS msg_ID, "'.date( 'Y-m-d H:i:s', $localtimenow ).'" AS msg_datetime,
		'.$current_User->ID.' AS msg_user_ID,
		'.$DB->quote( $edited_Message->text ).' AS msg_text, "" AS msg_renderers,
		'.$DB->quote( $edited_Thread->title ).' AS thread_title' );

	$Results = new Results( $preview_SQL->get(), 'pvwmsg_', '', NULL, 1 );

	$Results->Cache = & get_MessageCache();

	if( $creating_success )
	{	// Display error messages again before preview of message
		global $Messages;
		$Messages->display();
	}

	$Results->title = $params['messages_list_title'];
	/**
	 * Author:
	 */
	$Results->cols[] = array(
			'th' => TB_('Author'),
			'th_class' => 'shrinkwrap',
			'td_class' => 'center top #msg_ID#',
			'td' => '%col_msg_author( #msg_user_ID#, #msg_datetime# )%'
		);
	/**
	 * Message:
	 */
	$Results->cols[] = array(
			'th' => TB_('Message'),
			'td_class' => 'left top message_text',
			'td' => '@get_content()@@get_images()@@get_files()@',
		);
	/**
	 * Read?:
	 */
	$Results->cols[] = array(
		'th' => TB_('Read?'),
		'th_class' => 'shrinkwrap',
		'td_class' => 'top',
		'td' => '%col_msg_read_by( #msg_ID# )%',
		);

	echo $params['messages_list_start'];

	// Dispaly message list
	$Results->display( $display_params );

	echo $params['messages_list_end'];
} // ------------------ PREVIEW MESSAGE END ------------------ //
?>
