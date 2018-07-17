<?php
/**
 * This file sends an email to the user!
 * It's used to handle the contact form send message action. Even visitors are able to send emails.
 *
 * It's the form action for {@link _msgform.disp.php}.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package htsrv
 *
 * @author Jeff Bearer - {@link http://www.jeffbearer.com/} + blueyed, fplanque
 *
 * @todo dh> we should use the current_User's ID, if he's logged in here. It seems that only the message form gets pre-filled with hidden fields currently.
 */

/**
 * Includes
 */
require_once dirname(__FILE__).'/../conf/_config.php';

require_once $inc_path.'_main.inc.php';

// Stop a request from the blocked IP addresses or Domains
antispam_block_request();

global $Session, $Settings, $admin_url, $baseurl, $dummy_fields;

header( 'Content-Type: text/html; charset='.$io_charset );

// Check that this action request is not a CSRF hacked request:
$Session->assert_received_crumb( 'newmessage' );

if( $Settings->get('system_lock') )
{ // System is locked for maintenance, users cannot send a message
	$Messages->add( T_('You cannot send a message at this time because the system is under maintenance. Please try again in a few moments.'), 'error' );
	header_redirect(); // Will save $Messages into Session
}

// TODO: Flood protection (Use Hit class to prevent mass mailings to members..)

// Get rediredt_to param
$redirect_to = param( 'redirect_to', 'url', '' );

// Getting GET or POST parameters:
param( 'blog', 'integer', '' );
param( 'recipient_id', 'integer', '' );
param( 'post_id', 'integer', '' );
param( 'comment_id', 'integer', '' );

// Activate the blog locale because all params were introduced with that locale
activate_blog_locale( $blog );

// Note: we use funky field names in order to defeat the most basic guestbook spam bots:
$sender_name = param( $dummy_fields['name'], 'string', '' );
$sender_address = utf8_strtolower( param( $dummy_fields['email'], 'string', '' ) );
$subject = param( $dummy_fields['subject'], 'string', '' );
$subject_other = param( $dummy_fields['subject'].'_other', 'string', '' );
$message = param( $dummy_fields['content'], 'html', '' );	// We accept html but we will NEVER display it
$contact_method = param( 'contact_method', 'string', '' );
$user_fields = param( 'user_fields', 'array', array() );
// save the message original content
$original_content = $message;

// Prevent register_globals injection!
$recipient_address = '';
$recipient_name = '';
$recipient_User = NULL;
$Comment = NULL;

// Getting current collection:
$BlogCache = & get_BlogCache();
if( ! empty( $comment_id ) || ! empty( $post_id ) )
{
	$Collection = $Blog = & $BlogCache->get_by_ID( $blog );	// Required
}
else
{
	$Collection = $Blog = & $BlogCache->get_by_ID( $blog, true, false );	// Optional
}

// Core param validation

if( $Blog->get_setting( 'msgform_display_subject' ) &&
    $Blog->get_setting( 'msgform_require_subject' ) &&
    empty( $subject ) &&
    empty( $subject_other ) )
{	// If subject is required:
	$Messages->add_to_group( T_('Please fill in the subject of your message.'), 'error', T_('Validation errors:') );
}

if( $Blog->get_setting( 'msgform_display_message' ) )
{	// If message field is displayed:
	if( $Blog->get_setting( 'msgform_require_message' ) && empty( $message ) )
	{	// If message is required:
		$Messages->add_to_group( T_('Please do not send empty messages.'), 'error', T_('Validation errors:') );
	}
	elseif( $Settings->get( 'antispam_block_contact_form' ) && ( $block = antispam_check( $message ) ) )
	{	// a blacklisted keyword has been found in the message:
		// Log incident in system log
		syslog_insert( sprintf( 'Antispam: Supplied message is invalid / appears to be spam. Message contains blacklisted word "%s".', $block ), 'error' );

		$Messages->add_to_group( T_('The supplied message is invalid / appears to be spam.'), 'error', T_('Validation errors:') );
	}
}

if( ! empty( $recipient_id ) )
{	// Get the recipient User by requested ID:
	$UserCache = & get_UserCache();
	$recipient_User = & $UserCache->get_by_ID( $recipient_id );
}
elseif( ! empty( $comment_id ) )
{	// Get the recipient data to send a message to comment's author:
	$CommentCache = & get_CommentCache();
	if( ! ( $Comment = & $CommentCache->get_by_ID( $comment_id, false, false ) ) )
	{	// The requested comment must exists in DB:
		debug_die( 'Invalid request for message sending because comment #'.$comment_id.' doesn\'t exists in DB!' );
	}

	// Get recipient User if it is a registered user:
	$recipient_User = & $Comment->get_author_User();

	if( empty( $recipient_User ) )
	{	// If comment's author is anonymous user:
		if( empty( $Comment->allow_msgform ) )
		{	// If anonymous user didn't allow to send email messages:
			debug_die( 'Author of the comment #'.$comment_id.' does not want to get contacted through the message form!' );
		}

		// Get the recipient data of the comment's author:
		$recipient_name = $Comment->get_author_name();
		$recipient_address = $Comment->get_author_email();
	}
}

$update_user_fields = false;
if( is_logged_in() )
{	// Set name and email of the current logged in user:
	$sender_name = '';
	$sender_address = $current_User->get( 'email' );
	$edited_user_perms = array( 'edited-user', 'edited-user-required' );
	switch( $Blog->get_setting( 'msgform_user_name' ) )
	{
		case 'fullname':
			$firstname_editing = $Settings->get( 'firstname_editing' );
			if( in_array( $firstname_editing, $edited_user_perms ) )
			{	// Get first name:
				$user_firstname = param( 'user_firstname', 'string', '' );
				if( $firstname_editing == 'edited-user-required' && empty( $user_firstname ) )
				{	// First name is required:
					param_error( 'user_firstname', T_('Please enter your first name.'), NULL, T_('Validation errors:') );
				}
				$sender_name .= $user_firstname;
				if( ! empty( $user_firstname ) && $user_firstname != $current_User->get( 'firstname' ) )
				{	// Set new first name if it has been entered as new:
					$current_User->set( 'firstname', $user_firstname );
					$update_user_fields = true;
				}
			}

			$lastname_editing = $Settings->get( 'lastname_editing' );
			if( in_array( $lastname_editing, $edited_user_perms ) )
			{	// Get last name:
				$user_lastname = param( 'user_lastname', 'string', '' );
				if( $lastname_editing == 'edited-user-required' && empty( $user_lastname ) )
				{	// Lsst name is required:
					param_error( 'user_lastname', T_('Please enter your last name.'), NULL, T_('Validation errors:') );
				}
				$sender_name .= ' '.$user_lastname;
				if( ! empty( $user_lastname ) && $user_lastname != $current_User->get( 'lastname' ) )
				{	// Set new last name if it has been entered as new:
					$current_User->set( 'lastname', $user_lastname );
					$update_user_fields = true;
				}
			}
			$sender_name = utf8_trim( $sender_name );
			break;

		case 'nickname':
			$nickname_editing = $Settings->get( 'nickname_editing' );
			if( in_array( $nickname_editing, $edited_user_perms ) )
			{	// Get nickname:
				$user_nickname = param( 'user_nickname', 'string', '' );
				if( $nickname_editing == 'edited-user-required' && empty( $user_nickname ) )
				{	// Nickname is required:
					param_error( 'user_nickname', T_('Please enter your nickname.'), NULL, T_('Validation errors:') );
				}
				$sender_name .= $user_nickname;
				if( ! empty( $user_nickname ) && $user_nickname != $current_User->get( 'nickname' ) )
				{	// Set new nickname if it has been entered as new:
					$current_User->set( 'nickname', $user_nickname );
					$update_user_fields = true;
				}
			}
			break;
	}

	if( empty( $sender_name ) )
	{	// If sender name has not been detected from first/last/nickname fields then use preferred name of current User:
		$sender_name = $current_User->get_username();
	}
}
else
{	// Ask name and email only for anonymous users:
	if( $Blog->get_setting( 'msgform_require_name' ) && empty( $sender_name ) )
	{	// If name is required:
		$Messages->add_to_group( T_('Please fill in your name.'), 'error', T_('Validation errors:') );
	}
	if( empty( $sender_address ) )
	{
		$Messages->add_to_group( T_('Please fill in your email.'), 'error', T_('Validation errors:') );
	}
	elseif( ! is_email( $sender_address ) || ( $block = antispam_check( $sender_address ) ) ) // TODO: dh> using antispam_check() here might not allow valid users to contact the admin in case of problems due to the antispam list itself.. :/
	{
		// Log incident in system log
		syslog_insert( sprintf( 'Antispam: Supplied email address "%s" contains blacklisted word "%s".', $sender_address, $block ), 'error' );

		$Messages->add_to_group( T_('Supplied email address is invalid.'), 'error', T_('Validation errors:') );
	}
}

if( empty( $recipient_User ) && empty( $recipient_address ) )
{ // should be prevented by UI
	debug_die( 'No recipient specified!' );
}

// Additional fields:
$send_additional_fields = array();
if( is_array( $user_fields ) && ! empty( $user_fields ) )
{
	$UserFieldCache = & get_UserFieldCache();
	$coll_additional_fields = $Blog->get_msgform_additional_fields();
	foreach( $user_fields as $user_field_ID => $user_field_value )
	{
		if( ! isset( $coll_additional_fields[ $user_field_ID ] ) )
		{	// Skip wrong field which is not selected for current collection as additional field:
			continue;
		}

		if( ! ( $UserField = & $UserFieldCache->get_by_ID( $user_field_ID, false, false ) ) )
		{	// Skip wrong field:
			continue;
		}

		$text_value = utf8_trim( is_array( $user_field_value ) ? implode( ', ', $user_field_value ) : $user_field_value );
		if( empty( $text_value ) )
		{	// Skip empty values:
			continue;
		}

		if( is_logged_in() )
		{	// Update user fields of the logged in User:
			$userfields = $current_User->userfields_by_ID( $UserField->ID );
			if( ! is_array( $user_field_value ) )
			{
				$user_field_value = array(  $user_field_value );
			}
			foreach( $user_field_value as $u => $uf_value )
			{
				if( empty( $uf_value ) )
				{	// Skip empty entered values from contact form:
					continue;
				}

				// Format and check the entered value:
				$field_type = $UserField->get( 'type' );
				if( $field_type == 'number' )
				{	// Change number type of integer because we have this type name preparing in function param_format():
					$field_type = 'integer';
				}
				elseif( $field_type != 'text' && $field_type != 'url' )
				{	// Use all other params as string, Only text and url have a preparing in function param_format():
					$field_type = 'string';
				}
				$uf_value = param_format( $uf_value, $field_type );
				if( $field_type == 'url' && ( $error_detail = validate_url( $uf_value, 'commenting' ) ) )
				{	// Skip it if the entered URL is wrong:
					$Messages->add_to_group( /* TRANS: %s contains error details */ sprintf( T_('Supplied URL is invalid. (%s)'), $error_detail ), 'error', T_('Validation errors:') );
					continue;
				}

				if( $userfields === false )
				{	// Add new field value:
					$current_User->userfield_add( $UserField->ID, $uf_value );
					$update_user_fields = true;
				}
				else
				{	// Update existing field value:
					$add_multifield_value = false;
					foreach( $userfields as $m => $userfield_data )
					{
						if( ! empty( $userfield_data->list ) )
						{	// Update a list field:
							$s = 0;
							foreach( $userfield_data->list as $saved_uf_ID => $saved_uf_value )
							{
								if( $s == $u && $uf_value != $saved_uf_value )
								{	// Only if it was changed:
									$current_User->userfield_update( $saved_uf_ID, $user_field_value[ $s ] );
									$update_user_fields = true;
								}
								$s++;
							}
							// If count of entered values is more than count of saved values in DB:
							$add_multifield_value = ( count( $user_field_value ) > count( $userfield_data->list ) );
						}
						elseif( $m == $u && $uf_value != $userfield_data->uf_varchar )
						{	// Update a single field only if it was changed:
							$current_User->userfield_update( $userfield_data->uf_ID, $uf_value );
							$update_user_fields = true;
						}
						elseif( $UserField->get( 'duplicated' ) == 'allowed' )
						{	// Add second value for multiple field:
							$add_multifield_value = true;
						}

						if( $add_multifield_value && $u > $m && $u < 2 )
						{	// It means second value of multiple field should be added:
							$current_User->userfield_add( $UserField->ID, $uf_value );
							$update_user_fields = true;
						}
					}
				}
			}
		}

		// Prepare user field for correct html displaying:
		$userfield = (object)array(
				'ufdf_type'  => $UserField->get( 'type' ),
				'uf_varchar' => $text_value,
			);
		userfield_prepare( $userfield );
		$html_value = $userfield->uf_varchar;

		$send_additional_fields[] = array(
				'title'       => $UserField->get( 'name' ),
				'text_value'  => $text_value,
				'html_value'  => $html_value,
			);
	}
}


if( $update_user_fields )
{	// If at least one field of current user must be updated:
	$current_User->dbupdate();
}

if( intval( $contact_method ) > 0 )
{	// If preferred contact method is selected as user field:
	$UserFieldCache = & get_UserFieldCache();
	$msgform_additional_fields = $Blog->get_msgform_additional_fields();
	if( isset( $msgform_additional_fields[ $contact_method ] ) &&
	    $UserField = & $UserFieldCache->get_by_ID( $contact_method, false, false ) )
	{
		$contact_method_field_is_filled = false;
		if( isset( $user_fields[ $contact_method ] ) )
		{	// Check the entered field for preferred contact method:
			$entered_user_field = $user_fields[ $contact_method ];
			$entered_user_field = trim( is_array( $entered_user_field ) ? implode( '', $entered_user_field ) : $entered_user_field );
			if( ! empty( $entered_user_field ) )
			{	// Allow to use the selected field for preferred contact method because it has been entered:
				$contact_method_field_is_filled = true;
			}
		}
		if( ! $contact_method_field_is_filled )
		{	// If the selected contact method field is not filled:
			$Messages->add_to_group( sprintf( T_('Please fill the field "%s" because you have selected this as preferred contact method.'), $UserField->get_name() ),'error', T_('Validation errors:') );
		}
	}
	else
	{	// If the selected contact method is not used as additional field or it doesn't exist in DB:
		$Messages->add_to_group( /* NO TRANS because should never happen */ 'You have selected an unavailable preferred contact method.', 'error', T_('Validation errors:') );
	}
}

// opt-out links:
if( $recipient_User )
{ // Member:
	// Change the locale so the email is in the recipients language
	locale_temp_switch( $recipient_User->locale );
}
else
{ // Visitor:
	// We don't know the recipient's language - Change the locale so the email is in the blog's language:
	locale_temp_switch( $Blog->locale );
}

// Trigger event: a Plugin could add a $category="error" message here..
$Plugins->trigger_event( 'MessageFormSent', array(
	'recipient_ID' => $recipient_id,
	'item_ID' => $post_id,
	'comment_ID' => $comment_id,
	'subject' => & $subject,
	'message' => & $message,
	'Blog' => & $Blog,
	'sender_name' => & $sender_name,
	'sender_email' => & $sender_address,
	) );


$success_message = ( !$Messages->has_errors() );
if( $success_message )
{	// No errors, try to send the message:

	$send_subject = ( empty( $subject_other ) ? $subject : $subject_other );

	$send_contact_method = $contact_method;
	if( ! empty( $send_contact_method ) )
	{	// If a preferred contact method is selected of the form:
		if( $send_contact_method === 'pm' && is_logged_in() &&
		    $current_User->get_msgform_possibility( $recipient_User, 'PM' ) )
		{	// PM option only if it is allowed between current and recipient users:
			$send_contact_method = T_('Private Message on this Site');
		}
		elseif( $send_contact_method === 'email' )
		{	// Email option:
			$send_contact_method = T_('Email');
			if( is_logged_in() )
			{	// Display an email of current user:
				$send_contact_method .= ' ('.$current_User->get( 'email' ).')';
			}
		}
		elseif( intval( $send_contact_method ) > 0 )
		{	// User field option:
			$UserFieldCache = & get_UserFieldCache();
			if( $UserField = & $UserFieldCache->get_by_ID( $send_contact_method, false, false ) )
			{	// Get real name of the selected user field:
				$send_contact_method = $UserField->get( 'name' );
				if( is_logged_in() )
				{	// Display a value of the selected contact method of current user:
					$user_field_values = $current_User->userfield_values_by_code( $UserField->get( 'code' ) );
					if( ! empty( $user_field_values ) )
					{	// Only if it is entered by user:
						$send_contact_method .= ' ('.implode( ', ', $user_field_values ).')';
					}
				}
			}
			else
			{	// Wrong user field:
				$send_contact_method = '';
			}
		}
	}

	$send_method_type = 'email';
	// Check if sender and recipient can use private message instead of email:
	if( $recipient_User && // recipient is a registered user
	    $recipient_User->get_msgform_possibility() == 'PM' && // recipient allows to send PM
	    ! check_create_thread_limit() ) // sender can create a thread today
	{
		$send_method_type = 'PM';
	}

	if( $send_method_type == 'PM' )
	{	// Send private message:
		load_class( 'messaging/model/_thread.class.php', 'Thread' );
		load_class( 'messaging/model/_message.class.php', 'Message' );

		$send_message = array();

		if( ! empty( $send_additional_fields ) )
		{	// Append all filled additonal fields:
			$send_message[0] = array();
			foreach( $send_additional_fields as $send_additional_field )
			{
				$send_message[0][] = $send_additional_field['title'].': '.$send_additional_field['text_value'];
			}
			$send_message[0] = implode( "\n\n", $send_message[0] );
		}

		if( ! empty( $send_contact_method ) )
		{	// Append a preferred contact method to the message text:
			$send_message[1] = T_('Preferred contact method').': '.$send_contact_method;
		}

		if( ! empty( $message ) )
		{	// Append message text:
			$send_message[2] = $message;
		}

		$send_message = implode( "\n\n---\n\n", $send_message );

		$edited_Thread = new Thread();
		$edited_Message = new Message();

		$edited_Thread->set( 'title', $send_subject );
		$edited_Thread->set( 'recipients', $current_User->ID.','.$recipient_User->ID );
		$edited_Thread->recipients_list = array( $recipient_User->ID );
		$edited_Message->Thread = & $edited_Thread;
		$edited_Message->set( 'text', $send_message );

		if( $edited_Message->dbinsert_discussion() )
		{	// Successful creating of new thread:
			$success_message = true;
			// update author user last new thread setting
			update_todays_thread_settings( 1 );
		}
		else
		{	// Failed
			$success_message = false;
		}
	}
	if( $send_method_type == 'email' ||
	    ( $send_method_type == 'PM' && ! $success_message ) )
	{	// Send email message:
		$send_method_type = 'email';
		$email_template_params = array(
				'sender_name'       => $sender_name,
				'sender_address'    => $sender_address,
				'Blog'              => $Blog,
				'message'           => $message,
				'contact_method'    => $send_contact_method,
				'additional_fields' => $send_additional_fields,
				'comment_id'        => $comment_id,
				'post_id'           => $post_id,
				'recipient_User'    => $recipient_User,
				'Comment'           => $Comment,
			);

		if( empty( $recipient_User ) )
		{	// Send email to visitor/anonymous:
			// Get a message text from template file
			$email_template_params['anonymous_recipient_name'] = $recipient_name;
			$message = mail_template( 'contact_message_new', 'text', $email_template_params );
			$success_message = send_mail( $recipient_address, $recipient_name, $send_subject, $message, NULL, NULL, array( 'Reply-To' => $sender_address ) );
		}
		else
		{	// Send mail to registered user:
			$success_message = send_mail_to_User( $recipient_User->ID, $send_subject, 'contact_message_new', $email_template_params, false, array( 'Reply-To' => $sender_address ) );
		}
	}

	// restore the locale to the blog visitor language, before we would display an error message
	locale_restore_previous();

	if( $success_message )
	{ // Email has been sent successfully
		if( ! is_logged_in() )
		{ // We should save a counter (only for anonymous users)
			antispam_increase_counter( 'contact_email' );
		}
	}
	else
	{ // Could not send email
		if( $demo_mode )
		{
			$Messages->add( 'Sorry, could not send email. Sending email in demo mode is disabled.', 'error' );
		}
		else
		{
			$Messages->add( T_('Sorry, could not send email.')
				.'<br />'.T_('Possible reason: the PHP mail() function may have been disabled on the server.'), 'error' );
		}
	}
}
else
{ // Restore the locale to the blog visitor language even in case of errors
	locale_restore_previous();
}


// Plugins should cleanup their temporary data here:
$Plugins->trigger_event( 'MessageFormSentCleanup', array(
		'success_message' => $success_message,
	) );

if( empty( $redirect_to ) && empty( $Blog ) )
{
	$redirect_to = $baseurl;
}
if( $success_message )
{
	if( $send_method_type == 'PM' )
	{	// If PM has been sent:
		$Messages->add( T_('Your private message has been sent.'), 'success' );
	}
	else
	{	// If EMAIL has been sent:
		$Messages->add( sprintf( T_('You have successfully sent an email to %s.'),
			( empty( $recipient_User ) ? $recipient_name : $recipient_User->get_username() ) ), 'success' );
	}
	if( empty( $redirect_to ) )
	{
		$redirect_to = $Blog->gen_blogurl();
		if( !empty( $recipient_User ) )
		{
			$redirect_to = url_add_param( $redirect_to, 'disp=msgform&recipient_id='.$recipient_User->ID );
		}
	}
	header_redirect( $redirect_to );
	// exited here
}

// unsuccessful message send, save message params into the Session to not lose the content
$unsaved_message_params = array();
$unsaved_message_params[ 'sender_name' ] = $sender_name;
$unsaved_message_params[ 'sender_address' ] = $sender_address;
$unsaved_message_params[ 'subject' ] = $subject;
$unsaved_message_params[ 'subject_other' ] = $subject_other;
$unsaved_message_params[ 'message' ] = $original_content;
$unsaved_message_params[ 'contact_method' ] = $contact_method;
$unsaved_message_params[ 'user_fields' ] = $user_fields;
save_message_params_to_session( $unsaved_message_params );

if( param_errors_detected() || empty( $redirect_to ) )
{
	$redirect_to = url_add_param( $Blog->gen_blogurl(), 'disp=msgform&recipient_id='.$recipient_id, '&' );
}
header_redirect( $redirect_to );
//exited here

?>
