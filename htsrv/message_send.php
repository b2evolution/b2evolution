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
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
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
$sender_name = param( $dummy_fields[ 'name' ], 'string', '' );
$sender_address = utf8_strtolower( param( $dummy_fields[ 'email' ], 'string', '' ) );
$subject = param( $dummy_fields[ 'subject' ], 'string', '' );
$message = param( $dummy_fields[ 'content' ], 'html', '' );	// We accept html but we will NEVER display it
// save the message original content
$original_content = $message;

// Prevent register_globals injection!
$recipient_address = '';
$recipient_name = '';
$recipient_User = NULL;
$Comment = NULL;

// Core param validation

if( empty($subject) )
{
	$Messages->add_to_group( T_('Please fill in the subject of your message.'), 'error', T_('Validation errors:') );
}

if( empty( $message ) )
{ // message should not be empty!
	$Messages->add_to_group( T_('Please do not send empty messages.'), 'error', T_('Validation errors:') );
}
elseif( $Settings->get( 'antispam_block_contact_form' ) && ( $block = antispam_check( $message ) ) )
{ // a blacklisted keyword has been found in the message:
	// Log incident in system log
	syslog_insert( sprintf( 'Antispam: Supplied message is invalid / appears to be spam. Message contains blacklisted word "%s".', $block ), 'error' );

	$Messages->add_to_group( T_('The supplied message is invalid / appears to be spam.'), 'error', T_('Validation errors:') );
}

// Getting current blog info:
$BlogCache = & get_BlogCache();
if( !empty( $comment_id ) || !empty( $post_id ) )
{
	$Collection = $Blog = & $BlogCache->get_by_ID( $blog );	// Required
}
else
{
	$Collection = $Blog = & $BlogCache->get_by_ID( $blog, true, false );	// Optional
}

$allow_msgform = '';
if( ! empty( $recipient_id ) )
{ // Get the email address for the recipient if a member:
	$UserCache = & get_UserCache();
	$recipient_User = & $UserCache->get_by_ID( $recipient_id );

	// Check if current User allows to be contacted by email:
	$allow_msgform = $recipient_User->get_msgform_possibility( NULL, 'email' );
	if( $allow_msgform != 'email' )
	{ // should be prevented by UI
		debug_die( 'Invalid recipient or no permission to contact by email!' );
	}
}
elseif( ! empty( $comment_id ) )
{ // Get the email address for the recipient if a visiting commenter:
	$CommentCache = & get_CommentCache();
	$Comment = $CommentCache->get_by_ID( $comment_id );

	if( empty( $Comment ) )
	{
		debug_die( 'Invalid request, comment doesn\'t exists!' );
	}

	if( $recipient_User = & $Comment->get_author_User() )
	{ // Comment is from a registered user:
		// Check if current User allows to be contacted by email:
		$allow_msgform = $recipient_User->get_msgform_possibility( NULL, 'email' );
		if( $allow_msgform != 'email' )
		{ // should be prevented by UI
			debug_die( 'Invalid recipient or no permission to contact by email!' );
		}
	}
	elseif( empty($Comment->allow_msgform) )
	{ // should be prevented by UI
		debug_die( 'Invalid recipient or no permission to contact by email!' );
	}
	else
	{
		$recipient_name = $Comment->get_author_name();
		$recipient_address = $Comment->get_author_email();
	}
}

if( empty($sender_name) )
{
	$Messages->add_to_group( T_('Please fill in your name.'), 'error', T_('Validation errors:') );
}
if( empty($sender_address) )
{
	$Messages->add_to_group( T_('Please fill in your email.'), 'error', T_('Validation errors:') );
}
elseif( !is_email($sender_address) || ( $block = antispam_check( $sender_address ) ) ) // TODO: dh> using antispam_check() here might not allow valid users to contact the admin in case of problems due to the antispam list itself.. :/
{
	// Log incident in system log
	syslog_insert( sprintf( 'Antispam: Supplied email address "%s" contains blacklisted word "%s".', $sender_address, $block ), 'error' );

	$Messages->add_to_group( T_('Supplied email address is invalid.'), 'error', T_('Validation errors:') );
}

if( empty( $recipient_User ) && empty( $recipient_address ) )
{ // should be prevented by UI
	debug_die( 'No recipient specified!' );
}

// opt-out links:
if( $recipient_User )
{ // Member:
	// Change the locale so the email is in the recipients language
	locale_temp_switch($recipient_User->locale);
}
else
{ // Visitor:
	// We don't know the recipient's language - Change the locale so the email is in the blog's language:
	locale_temp_switch($Blog->locale);
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
{ // no errors, try to send the message
	$email_template_params = array(
			'sender_name'    => $sender_name,
			'sender_address' => $sender_address,
			'Blog'           => $Blog,
			'message'        => $message,
			'comment_id'     => $comment_id,
			'post_id'        => $post_id,
			'recipient_User' => $recipient_User,
			'Comment'        => $Comment,
		);

	if( empty( $recipient_User ) )
	{ // Send mail to visitor
		// Get a message text from template file
		$message = mail_template( 'contact_message_new', 'text', $email_template_params );
		$success_message = send_mail( $recipient_address, $recipient_name, $subject, $message, NULL, NULL, array( 'Reply-To' => $sender_address ) );
	}
	else
	{ // Send mail to registered user
		$success_message = send_mail_to_User( $recipient_User->ID, $subject, 'contact_message_new', $email_template_params, false, array( 'Reply-To' => $sender_address ) );
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
	$Messages->add( sprintf( T_('You have successfully sent an email to %s.'),
		( empty( $recipient_User ) ? $recipient_name : $recipient_User->get_username() ) ), 'success' );
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
$unsaved_message_params[ 'message' ] = $original_content;
save_message_params_to_session( $unsaved_message_params );

if( param_errors_detected() || empty( $redirect_to ) )
{
	$redirect_to = url_add_param( $Blog->gen_blogurl(), 'disp=msgform&recipient_id='.$recipient_id, '&' );
}
header_redirect( $redirect_to );
//exited here

?>
