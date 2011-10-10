<?php
/**
 * This file sends an email or a private message to the user! 
 * It's used to handle the contact form send message action. Even visitors are able to send emails.
 *
 * It's the form action for {@link _msgform.disp.php}.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
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

global $Session;

header( 'Content-Type: text/html; charset='.$io_charset );

// Check that this action request is not a CSRF hacked request:
$Session->assert_received_crumb( 'newmessage' );

// TODO: Flood protection (Use Hit class to prevent mass mailings to members..)

// --------------------------------------------------
// TODO: fp> v2.0: this bloats this file. MOVE to msg_remove.php or sth alike
if( param( 'optout_cmt_email', 'string', '' ) )
{ // an anonymous commentator wants to opt-out from receiving mails through a message form:

	if( param( 'req_ID', 'string', '' ) )
	{ // clicked on link from e-mail
		if( $req_ID == $Session->get( 'core.msgform.optout_cmt_reqID' )
		    && $optout_cmt_email == $Session->get( 'core.msgform.optout_cmt_email' ) )
		{
			$DB->query( '
				UPDATE T_comments
				   SET comment_allow_msgform = 0
				 WHERE comment_author_email = '.$DB->quote($optout_cmt_email) );

			$Messages->add( T_('All your comments have been marked not to allow emailing you through a message form.'), 'success' );

			$Session->delete('core.msgform.optout_cmt_email');
		}
		else
		{
			$Messages->add( T_('The request not to receive emails through a message form for your comments failed.'), 'error' );
		}

		$Messages->display();
		exit(0);
	}

	$req_ID = generate_random_key(32);

	$message = sprintf( T_("We have received a request that you do not want to receive emails through\na message form on your comments anymore.\n\nTo confirm that this request is from you, please click on the following link:") )
		."\n\n"
		.$samedomain_htsrv_url.'message_send.php?optout_cmt_email='.$optout_cmt_email.'&req_ID='.$req_ID
		."\n\n"
		.T_('Please note:')
		.' '.T_('For security reasons the link is only valid for your current session (by means of your session cookie).')
		."\n\n"
		.T_('If it was not you that requested this, simply ignore this mail.');

	if( send_mail( $optout_cmt_email, NULL, T_('Confirm opt-out for emails through message form'), $message ) )
	{
		echo T_('An email has been sent to you, with a link to confirm your request not to receive emails through the comments you have made on this blog.');
		$Session->set( 'core.msgform.optout_cmt_email', $optout_cmt_email );
		$Session->set( 'core.msgform.optout_cmt_reqID', $req_ID );
	}
	else
	{
		$Messages->add( T_('Sorry, could not send email.')
					.'<br />'.T_('Possible reason: the PHP mail() function may have been disabled on the server.'), 'error' );
	}

	exit(0);
}
// END OF BLOCK TO BE MOVED
// --------------------------------------------------


// Getting GET or POST parameters:
param( 'blog', 'integer', '' );
param( 'recipient_id', 'integer', '' );
param( 'post_id', 'integer', '' );
param( 'comment_id', 'integer', '' );
// Note: we use funky field names in order to defeat the most basic guestbook spam bots:
$sender_name = param( 'd', 'string', '' );
$sender_address = param( 'f', 'string', '' );
$subject = param( 'g', 'string', '' );
$message = param( 'h', 'html', '' );	// We accept html but we will NEVER display it
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

	$recipient_name = trim($recipient_User->get('preferredname'));
	$recipient_address = $recipient_User->get('email');

	// Change the locale so the email is in the recipients language
	locale_temp_switch($recipient_User->locale);
}
elseif( ! empty( $comment_id ) )
{ // Get the email address for the recipient if a visiting commenter.

	// Load comment from DB:
	$row = $DB->get_row(
		'SELECT *
		   FROM T_comments
		  WHERE comment_ID = '.$comment_id );
	$Comment = new Comment( $row );

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
		$allow_msgform = 'email';
	}

	$recipient_name = trim($Comment->get_author_name());
	$recipient_address = $Comment->get_author_email();

	// We don't know the recipient's language - Change the locale so the email is in the blog's language:
	locale_temp_switch($Blog->locale);
}

if( $allow_msgform == 'email' )
{
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

	if( empty($recipient_address) )
	{ // should be prevented by UI
		debug_die( 'No recipient specified!' );
	}

	// opt-out links:
	if( $recipient_User )
	{ // Member:
		if( !empty( $Blog ) )
		{
			$message_footer .= T_("You can edit your profile to not receive emails through a form:")
				."\n".url_add_param( str_replace( '&amp;', '&', $Blog->get('url') ), 'disp=profile', '&' );
		}
		// TODO: else go to admin
	}
	elseif( $Comment )
	{ // Visitor:
		$message_footer .= T_("Click on the following link to not receive e-mails on your comments\nfor this e-mail address anymore:")
			."\n".$samedomain_htsrv_url.'message_send.php?optout_cmt_email='.rawurlencode($Comment->author_email);
	}


	// Trigger event: a Plugin could add a $category="error" message here..
	$Plugins->trigger_event( 'MessageFormSent', array(
		'recipient_ID' => & $recipient_id,
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
		// show sender name
		$message_header = $sender_name." has sent you this message:\n\n";

		// show sender email address
		$message_footer = sprintf( T_( 'By replying, your email will go directly to %s.' ), $sender_address )."\n\n".$message_footer;

		if( !empty( $Blog ) )
		{
			$message = $message
				."\n\n-- \n"
				.sprintf( T_('This message was sent via the messaging system on %s.'), $Blog->name )."\n"
				.$Blog->get('url')."\n\n"
				.$message_footer;
		}
		else
		{
			$message = $message
				."\n\n-- \n"
				.sprintf( T_('This message was sent via the messaging system on %s.'), $baseurl )."\n\n"
				.$message_footer;
		}

		 // Send mail
		$success_message = send_mail( $recipient_address, $recipient_name, $subject, $message, $notify_from, NULL, array( 'Reply-To' => $sender_address ) );
		if( !$success_message )
		{
			$Messages->add( T_('Sorry, could not send email.')
				.'<br />'.T_('Possible reason: the PHP mail() function may have been disabled on the server.'), 'error' );
		}
	}
}
elseif( ! $Messages->has_errors() )
{ // There were no errors, Send private message
	load_funcs( 'messaging/model/_messaging.funcs.php' );
	if( isset( $recipient_User ) )
	{
		$success_message = send_private_message( $recipient_User->get( 'login' ), $subject, $message );
		if( !$success_message )
		{
			$Messages->add( T_('Sorry, could not send your message.'), 'error' );
		}
	}
}
else
{ // message param error
	$success_message = false;
}


// Plugins should cleanup their temporary data here:
$Plugins->trigger_event( 'MessageFormSentCleanup' );


// restore the locale to the blog visitor language
locale_restore_previous();

if( $success_message )
{
	// Never say to whom we sent the email -- prevent user enumeration.
	$Messages->add( T_('Your message has been sent.'), 'success' );
	if( $allow_msgform == 'PM' )
	{
		header_redirect(  url_add_param( $Blog->gen_blogurl(), 'disp=threads' ) );
		// exited here
	}
}
else
{ // unsuccessful message send, save message params into the Session to not lose the content
	$unsaved_message_params = array();
	$unsaved_message_params[ 'sender_name' ] = $sender_name;
	$unsaved_message_params[ 'sender_address' ] = $sender_address;
	$unsaved_message_params[ 'subject' ] = $subject;
	$unsaved_message_params[ 'message' ] = $original_content;
	save_message_params_to_session( $unsaved_message_params );

	header_redirect( url_add_param( $Blog->gen_blogurl(), 'disp=msgform&recipient_id='.$recipient_id ) );
	//exited here
}

// redirect Will save $Messages into Session:
header_redirect(); // exits!


/*
 * $Log$
 * Revision 1.82  2011/10/10 19:48:31  fplanque
 * i18n & login display cleaup
 *
 * Revision 1.81  2011/10/06 06:18:29  efy-asimo
 * Add messages link to settings
 * Update messaging notifications
 *
 * Revision 1.80  2011/10/04 08:39:29  efy-asimo
 * Comment and message forms save/reload content in case of error
 *
 * Revision 1.79  2011/10/01 07:16:25  efy-asimo
 * Fix message send - update
 *
 * Revision 1.78  2011/10/01 07:09:13  efy-asimo
 * Fix message send
 *
 * Revision 1.77  2011/09/30 07:38:58  efy-yurybakh
 * bubbletip for anonymous comments
 *
 * Revision 1.76  2011/09/26 14:53:27  efy-asimo
 * Login problems with multidomain installs - fix
 * Insert globals: samedomain_htsrv_url, secure_htsrv_url;
 *
 * Revision 1.75  2011/09/04 22:13:13  fplanque
 * copyright 2011
 *
 * Revision 1.74  2011/08/18 11:41:51  efy-asimo
 * Send all emails from noreply and email contents review
 *
 * Revision 1.73  2011/08/11 09:05:08  efy-asimo
 * Messaging in front office
 *
 * Revision 1.72  2010/11/25 15:16:34  efy-asimo
 * refactor $Messages
 *
 * Revision 1.71  2010/07/14 09:06:14  efy-asimo
 * todo fp>asimo modifications
 *
 * Revision 1.70  2010/07/12 09:07:37  efy-asimo
 * rename get_msgform_settings() to get_msgform_possibility
 *
 * Revision 1.69  2010/04/23 11:37:57  efy-asimo
 * send messages - fix
 *
 * Revision 1.68  2010/04/16 10:42:10  efy-asimo
 * users messages options- send private messages to users from front-office - task
 *
 * Revision 1.67  2010/02/08 17:51:14  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.66  2010/01/30 18:55:15  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.65  2010/01/27 02:46:22  sam2kb
 * minor/typo
 *
 * Revision 1.64  2010/01/25 18:18:21  efy-yury
 * add : crumbs
 *
 * Revision 1.63  2009/12/04 23:27:49  fplanque
 * cleanup Expires: header handling
 *
 * Revision 1.62  2009/09/26 12:00:42  tblue246
 * Minor/coding style
 *
 * Revision 1.61  2009/09/25 07:32:51  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.60  2009/03/08 23:57:36  fplanque
 * 2009
 *
 * Revision 1.59  2008/04/13 15:15:59  fplanque
 * attempt to fix email headers for non latin charsets
 *
 * Revision 1.57  2008/02/19 11:11:16  fplanque
 * no message
 *
 * Revision 1.56  2008/01/21 09:35:23  fplanque
 * (c) 2008
 *
 * Revision 1.55  2007/11/29 19:29:22  fplanque
 * normalized skin filenames
 *
 * Revision 1.54  2007/04/26 00:11:14  fplanque
 * (c) 2007
 *
 * Revision 1.53  2007/04/10 16:59:10  fplanque
 * fixed antispam on message form
 *
 * Revision 1.52  2007/03/09 10:07:53  yabs
 * Added antispam check
 *
 * Revision 1.51  2007/02/03 20:25:37  blueyed
 * Added "sender_name", "sender_email" and "subject" params to MessageFormSent
 *
 * Revision 1.50  2007/02/03 19:49:36  blueyed
 * Added "Blog" param to MessageFormSent hook
 *
 * Revision 1.49  2007/01/23 05:30:21  fplanque
 * "Contact the owner"
 *
 * Revision 1.48  2006/12/12 02:53:56  fplanque
 * Activated new item/comments controllers + new editing navigation
 * Some things are unfinished yet. Other things may need more testing.
 *
 * Revision 1.47  2006/11/26 02:30:38  fplanque
 * doc / todo
 *
 * Revision 1.46  2006/11/24 18:27:22  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.45  2006/11/24 18:06:02  blueyed
 * Handle saving of $Messages centrally in header_redirect()
 *
 * Revision 1.44  2006/11/23 01:44:24  fplanque
 * finalized standalone messaging
 * changed block order so that $Blog gets initalized
 *
 * Revision 1.43  2006/11/22 19:20:51  blueyed
 * Output charset header
 *
 * Revision 1.42  2006/11/22 19:12:22  blueyed
 * Normalized. TODO about merge error
 *
 * Revision 1.41  2006/11/22 01:20:33  fplanque
 * contact the admin feature
 *
 * Revision 1.40  2006/11/20 22:21:46  blueyed
 * Fixed typo
 *
 * Revision 1.39  2006/11/15 00:09:16  blueyed
 * Use the blog locale when sending e-mails to non-members - instead of the one from the visitor
 *
 * Revision 1.38  2006/11/14 21:12:55  blueyed
 * doc
 *
 * Revision 1.37  2006/09/10 18:14:24  blueyed
 * Do report error, if sending email fails in message_send.php (msgform and opt-out)
 *
 * Revision 1.36  2006/08/21 00:03:12  fplanque
 * obsoleted some dirty old thing
 *
 * Revision 1.35  2006/08/19 07:56:29  fplanque
 * Moved a lot of stuff out of the automatic instanciation in _main.inc
 *
 * Revision 1.34  2006/06/16 20:34:19  fplanque
 * basic spambot defeating
 *
 * Revision 1.33  2006/05/30 20:32:56  blueyed
 * Lazy-instantiate "expensive" properties of Comment and Item.
 *
 * Revision 1.31  2006/05/04 14:28:15  blueyed
 * Fix/enhanced
 *
 * Revision 1.30  2006/04/20 22:24:07  blueyed
 * plugin hooks cleanup
 *
 * Revision 1.29  2006/04/20 16:31:29  fplanque
 * comment moderation (finished for 1.8)
 *
 * Revision 1.28  2006/04/20 12:15:32  fplanque
 * no message
 *
 * Revision 1.27  2006/04/19 23:50:39  blueyed
 * Normalized Messages handling (error displaying and transport in Session)
 *
 * Revision 1.26  2006/04/19 20:13:48  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.25  2006/04/11 21:22:25  fplanque
 * partial cleanup
 *
 */
?>