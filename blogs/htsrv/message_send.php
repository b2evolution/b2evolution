<?php
/**
 * This file sends an email to the user!
 * It's used to handle the contact form send message action. Even visitors are able to send emails.
 *
 * It's the form action for {@link _msgform.disp.php}.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
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
$redirect_to = param( 'redirect_to', 'string', '' );

// Getting GET or POST parameters:
param( 'blog', 'integer', '' );
param( 'recipient_id', 'integer', '' );
param( 'post_id', 'integer', '' );
param( 'comment_id', 'integer', '' );
// Note: we use funky field names in order to defeat the most basic guestbook spam bots:
$sender_name = param( $dummy_fields[ 'name' ], 'string', '' );
$sender_address = param( $dummy_fields[ 'email' ], 'string', '' );
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
	$Messages->add( T_('Please fill in the subject of your message.'), 'error' );
}

if( empty( $message ) )
{ // message should not be empty!
	$Messages->add( T_('Please do not send empty messages.'), 'error' );
}
elseif( $antispam_on_message_form && antispam_check( $message ) )
{ // a blacklisted keyword ha sbeen found in the message:
	$Messages->add( T_('The supplied message is invalid / appears to be spam.'), 'error' );
}


// Build message footer:
$BlogCache = & get_BlogCache();
$message_footer = '';
if( !empty( $comment_id ) )
{
	// Getting current blog info:
	$Blog = & $BlogCache->get_by_ID( $blog );	// Required
	$message_footer .= T_('Message sent from your comment:') . "\n"
		.url_add_param( $Blog->get('url'), 'p='.$post_id.'#'.$comment_id, '&' )
		."\n\n";
}
elseif( !empty( $post_id ) )
{
	// Getting current blog info:
	$Blog = & $BlogCache->get_by_ID( $blog );	// Required
	$message_footer .= T_('Message sent from your post:') . "\n"
		.url_add_param( $Blog->get('url'), 'p='.$post_id, '&' )
		."\n\n";
}
else
{
	// Getting current blog info:
	$Blog = & $BlogCache->get_by_ID( $blog, true, false );	// Optional
}

$allow_msgform = '';
if( ! empty( $recipient_id ) )
{ // Get the email address for the recipient if a member:
	$UserCache = & get_UserCache();
	$recipient_User = & $UserCache->get_by_ID( $recipient_id );

	$allow_msgform = $recipient_User->get_msgform_possibility();
	if( ! $allow_msgform )
	{ // should be prevented by UI
		debug_die( 'Invalid recipient!' );
	}

	// Change the locale so the email is in the recipients language
	locale_temp_switch($recipient_User->locale);
}
elseif( ! empty( $comment_id ) )
{ // Get the email address for the recipient if a visiting commenter.
	$CommentCache = & get_CommentCache();
	$Comment = $CommentCache->get_by_ID( $comment_id );

	if( empty( $Comment ) )
	{
		debug_die( 'Invalid request, comment doesn\'t exists!' );
	}

	if( $recipient_User = & $Comment->get_author_User() )
	{ // Comment is from a registered user:
		$allow_msgform = $recipient_User->get_msgform_possibility();
		if( ! $allow_msgform )
		{ // should be prevented by UI
			debug_die( 'Invalid recipient!' );
		}
	}
	elseif( empty($Comment->allow_msgform) )
	{ // should be prevented by UI
		debug_die( 'Invalid recipient!' );
	}
	else
	{
		$recipient_name = $Comment->get_author_name();
		$recipient_address = $Comment->get_author_email();
	}

	// We don't know the recipient's language - Change the locale so the email is in the blog's language:
	locale_temp_switch($Blog->locale);
}

if( empty($sender_name) )
{
	$Messages->add( T_('Please fill in your name.'), 'error' );
}
if( empty($sender_address) )
{
	$Messages->add( T_('Please fill in your email.'), 'error' );
}
elseif( !is_email($sender_address) || antispam_check( $sender_address ) ) // TODO: dh> using antispam_check() here might not allow valid users to contact the admin in case of problems due to the antispam list itself.. :/
{
	$Messages->add( T_('Supplied email address is invalid.'), 'error' );
}

if( empty( $recipient_User ) && empty( $recipient_address ) )
{ // should be prevented by UI
	debug_die( 'No recipient specified!' );
}

// opt-out links:
if( $recipient_User )
{ // Member:
	if( $Settings->get( 'emails_msgform' ) == 'userset' )
	{ // user can allow/deny to receive emails
		$edit_preferences_url = NULL;
		if( !empty( $Blog ) )
		{ // go to blog
			$edit_preferences_url = url_add_param( str_replace( '&amp;', '&', $Blog->gen_blogurl() ), 'disp=userprefs', '&' );
		}
		elseif( $recipient_User->check_perm( 'admin', 'restricted' ) )
		{ // go to admin
			$edit_preferences_url = $admin_url.'?ctrl=user&user_tab=userprefs&user_ID='.$recipient_User->ID;
		}
		if( !empty( $edit_preferences_url ) )
		{ // add edit preferences link
			$message_footer .= T_("You can edit your profile to not receive emails through a form:")."\n".$edit_preferences_url."\n";
		}
	}
	// Add quick unsubcribe link so users can deny receiving emails through b2evo message form in any circumstances
	$message_footer .= T_( 'If you don\'t want to receive any more emails through a message form, click here' ).':'
		."\n".$htsrv_url.'quick_unsubscribe.php?type=msgform&user_ID=$user_ID$&key=$unsubscribe_key$';
}
elseif( $Comment )
{ // Visitor:
	$message_footer .= T_("Click on the following link to not receive e-mails on your comments\nfor this e-mail address anymore:")
		."\n".$samedomain_htsrv_url.'anon_unsubscribe.php?type=comment&c='.$Comment->ID.'&anon_email='.rawurlencode($Comment->author_email);
}


// Trigger event: a Plugin could add a $category="error" message here..
$Plugins->trigger_event( 'MessageFormSent', array(
	'recipient_ID' => $recipient_id,
	'item_ID' => $post_id,
	'comment_ID' => $comment_id,
	'subject' => & $subject,
	'message' => & $message,
	'message_footer' => & $message_footer,
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
			'message_footer' => $message_footer,
			'Blog'           => $Blog,
			'message'        => $message,
		);

	if( empty( $recipient_User ) )
	{ // Send mail to visitor
		// Get a message text from template file
		$message = mail_template( 'message_sent', 'text', $email_template_params );
		$success_message = send_mail( $recipient_address, $recipient_name, $subject, $message, NULL, NULL, array( 'Reply-To' => $sender_address ) );
	}
	else
	{ // Send mail to registered user
		$success_message = send_mail_to_User( $recipient_User->ID, $subject, 'message_sent', $email_template_params, false, array( 'Reply-To' => $sender_address ) );
	}

	if( !$success_message )
	{ // could not send email
		if( $demo_mode )
		{
			$Messages->add( T_('Sorry, could not send email. Sending email in demo mode is disabled.' ), 'error' );
		}
		else
		{
			$Messages->add( T_('Sorry, could not send email.')
				.'<br />'.T_('Possible reason: the PHP mail() function may have been disabled on the server.'), 'error' );
		}
	}
}


// Plugins should cleanup their temporary data here:
$Plugins->trigger_event( 'MessageFormSentCleanup', array(
		'success_message' => $success_message,
	) );


// restore the locale to the blog visitor language
locale_restore_previous();

if( empty( $redirect_to ) && empty( $Blog ) )
{
	$redirect_to = $baseurl;
}
if( $success_message )
{
	// Never say to whom we sent the email -- prevent user enumeration.
	$Messages->add( T_('Your message has been sent.'), 'success' );
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

if( empty( $redirect_to ) )
{
	$redirect_to = url_add_param( $Blog->gen_blogurl(), 'disp=msgform&recipient_id='.$recipient_id );
}
header_redirect( $redirect_to );
//exited here

/*
 * $Log$
 * Revision 1.84  2013/11/06 08:03:44  efy-asimo
 * Update to version 5.0.1-alpha-5
 *
 */
?>