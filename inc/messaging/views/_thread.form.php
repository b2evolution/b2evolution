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

global $DB, $action, $Plugins;

global $Blog;

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
	'form_title' => T_('New thread').( is_admin_page() ? get_manual_link( 'messages-new-thread' ) : '' ),
	'form_action' => NULL,
	'form_name' => 'thread_checkchanges',
	'form_layout' => 'compact',
	'redirect_to' => regenerate_url( 'action', '', '', '&' ),
	'cols' => 80,
	'thrdtype' => param( 'thrdtype', 'string', 'discussion' ),  // alternative: individual
	'skin_form_params' => array(),
	'allow_select_recipients' => true,
	'messages_list_start' => '',
	'messages_list_end' => '',
	'messages_list_title' => $edited_Thread->title,
	), $params );

$Form = new Form( $params['form_action'], $params['form_name'], 'post', $params['form_layout'] );

$Form->switch_template_parts( $params['skin_form_params'] );

if( is_admin_page() )
{
	$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action' ) );
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
	$Form->text_input( 'thrd_recipients', $edited_Thread->recipients, $params['cols'], T_('Recipients'),
		'<noscript>'.T_('Enter usernames. Separate with comma (,)').'</noscript>', array( 'maxlength'=> 255, 'required'=>true, 'class'=>'wide_input' ) );

	echo '<div id="multiple_recipients">';
	$Form->radio( 'thrdtype', $params['thrdtype'], array(
									array( 'discussion', T_( 'Start a group discussion' ) ),
									array( 'individual', T_( 'Send individual messages' ) )
								), T_('Multiple recipients'), true );
	echo '</div>';
}
else
{	// No available to select recipients, Used in /contact.php
	$Form->info( T_('Recipients'), $edited_Thread->recipients );
	foreach( $recipients_selected as $recipient )
	{
		$Form->hidden( 'thrd_recipients_array[id][]', $recipient['id'] );
		$Form->hidden( 'thrd_recipients_array[login][]', $recipient['login'] );
	}
}

$Form->text_input( 'thrd_title', $edited_Thread->title, $params['cols'], T_('Subject'), '', array( 'maxlength'=> 255, 'required'=>true, 'class'=>'wide_input large' ) );


ob_start();
echo '<div class="message_toolbars">';
// CALL PLUGINS NOW:
$Plugins->trigger_event( 'DisplayMessageToolbar', array() );
echo '</div>';
$message_toolbar = ob_get_clean();

$form_inputstart = $Form->inputstart;
$Form->inputstart .= $message_toolbar;
$Form->textarea_input( 'msg_text', $edited_Message->original_text, 10, T_('Message'), array(
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

global $thrd_recipients_array, $recipients_selected;
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

// display submit button, but only if enabled
$Form->end_form( array(
		array( 'submit', 'actionArray[preview]', T_('Preview'), 'SaveButton btn-info' ),
		array( 'submit', 'actionArray[create]', T_('Send message'), 'SaveButton' )
	) );

if( $params['allow_select_recipients'] )
{	// User can select recipients
?>
<script type="text/javascript">
jQuery( document ).ready( function()
{
	check_multiple_recipients();
} );

jQuery( '#thrd_recipients' ).tokenInput(
	'<?php echo get_restapi_url(); ?>users/recipients',
	{
		theme: 'facebook',
		queryParam: 'q',
		propertyToSearch: 'login',
		preventDuplicates: true,
		prePopulate: <?php echo evo_json_encode( $recipients_selected ) ?>,
		hintText: '<?php echo TS_('Type in a username') ?>',
		noResultsText: '<?php echo TS_('No results') ?>',
		searchingText: '<?php echo TS_('Searching...') ?>',
		jsonContainer: 'users',
		tokenFormatter: function( user )
		{
			return '<li>' +
					user.login +
					'<input type="hidden" name="thrd_recipients_array[id][]" value="' + user.id + '" />' +
					'<input type="hidden" name="thrd_recipients_array[login][]" value="' + user.login + '" />' +
				'</li>';
		},
		resultsFormatter: function( user )
		{
			var title = user.login;
			if( user.fullname != null && user.fullname !== undefined )
			{
				title += '<br />' + user.fullname;
			}
			return '<li>' +
					user.avatar +
					'<div>' +
						title +
					'</div><span></span>' +
				'</li>';
		},
		onAdd: function()
		{
			check_multiple_recipients();
		},
		onDelete: function()
		{
			check_multiple_recipients();
		},
		<?php
		if( param_has_error( 'thrd_recipients' ) )
		{ // Mark this field as error
		?>
		onReady: function()
		{
			jQuery( '.token-input-list-facebook' ).addClass( 'token-input-list-error' );
		}
		<?php } ?>
	}
);

/**
 * Show the multiple recipients radio selection if the number of recipients more than one
 */
function check_multiple_recipients()
{
	if( jQuery( 'input[name="thrd_recipients_array[login][]"]' ).length > 1 )
	{
		jQuery( '#multiple_recipients' ).show();
	}
	else
	{
		jQuery( '#multiple_recipients' ).hide();
	}
}

/**
 * Check form fields before send a thread data
 *
 * @return boolean TRUE - success filling of the fields, FALSE - some erros, stop a submitting of the form
 */
function check_form_thread()
{
	if( jQuery( 'input#token-input-thrd_recipients' ).val() != '' )
	{	// Don't submit a form with incomplete username
		alert( '<?php echo TS_('Please complete the entering of an username.') ?>' );
		jQuery( 'input#token-input-thrd_recipients' ).focus();
		return false;
	}

	return true;
}
</script>
<?php }

if( $action == 'preview' )
{ // ------------------ PREVIEW MESSAGE START ------------------ //
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
	$preview_SQL->SELECT( '0 AS msg_ID, "'.date( 'Y-m-d H:i:s', $localtimenow ).'" AS msg_datetime,
		'.$current_User->ID.' AS msg_user_ID,
		'.$DB->quote( '<b>'.T_('PREVIEW').':</b><br /> '.$edited_Message->get_prerendered_content() ).' AS msg_text, "" AS msg_renderers,
		'.$DB->quote( $edited_Thread->title ).' AS thread_title' );

	$Results = new Results( $preview_SQL->get(), 'pvwmsg_', '', NULL, 1 );

	if( $creating_success )
	{ // Display error messages again before preview of message
		global $Messages;
		$Messages->display();
	}

	$Results->title = $params['messages_list_title'];
	/**
	 * Author:
	 */
	$Results->cols[] = array(
			'th' => T_('Author'),
			'th_class' => 'shrinkwrap',
			'td_class' => 'center top #msg_ID#',
			'td' => '%col_msg_author( #msg_user_ID#, #msg_datetime# )%'
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

	echo $params['messages_list_start'];

	// Dispaly message list
	$Results->display( $display_params );

	echo $params['messages_list_end'];
} // ------------------ PREVIEW MESSAGE END ------------------ //
?>