<?php
/**
 * This file sends an email to the user!
 *
 * It's the form action for {@link _msgform.php}.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @todo Plugin hook.
 * @todo Respect/provide user profile setting if he wants to be available for e-mail through msgform.
 */

/**
 * Includes
 */
require_once dirname(__FILE__).'/../conf/_config.php';

require_once $inc_path.'_main.inc.php';

header( 'Content-Type: text/html; charset='.$io_charset );


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

		debug_info();
		exit;
	}

	$req_ID = generate_random_key(32);

	$message = sprintf( T_("We have received a request that you do not want to receive emails through\na message form on your comments anymore.\n\nTo confirm that this request is from you, please click on the following link:") )
		."\n\n"
		.$htsrv_url.'message_send.php?optout_cmt_email='.$optout_cmt_email.'&req_ID='.$req_ID
		."\n\n"
		.T_('Please note:')
		.' '.T_('For security reasons the link is only valid for your current session (by means of your session cookie).')
		."\n\n"
		.T_('If it was not you that requested this, simply ignore this mail.');

	if( send_mail( $optout_cmt_email, T_('Confirm opt-out for emails through message form'), $message ) )
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

	debug_info();
	exit;
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
$message = param( 'h', 'string', '' );


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
if( empty($subject) )
{
	$Messages->add( T_('Please fill in the subject of your message.'), 'error' );
}
if( empty( $message ) )
{ // message should not be empty!
	$Messages->add( T_('Please do not send empty messages.'), 'error' );
}


// Prevent register_globals injection!
$recipient_address = '';
$recipient_name = '';
$recipient_User = NULL;
$Comment = NULL;


// Build message footer:
$BlogCache = & get_Cache( 'BlogCache' );
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


if( ! empty( $recipient_id ) )
{ // Get the email address for the recipient if a member:
	$UserCache = & get_Cache( 'UserCache' );
	$recipient_User = & $UserCache->get_by_ID( $recipient_id );

	if( empty($recipient_User->allow_msgform) )
	{ // should be prevented by UI
		debug_die( 'Invalid recipient!' );
	}

	$recipient_name = trim($recipient_User->get('preferredname'));
	$recipient_address =  $recipient_name.' <'.$recipient_User->get('email').'>';

	// Change the locale so the email is in the recipients language
	locale_temp_switch($recipient_User->locale);
}
elseif( ! empty( $comment_id ) )
{ // Get the email address for the recipient if a visiting commenter.

	// Load comment from DB:
	$row = $DB->get_row(
		'SELECT *
		   FROM T_comments
		  WHERE comment_ID = '.$comment_id, ARRAY_A );
	$Comment = & new Comment( $row );

	if( $comment_author_User = & $Comment->get_author_User() )
	{ // Comment is from a registered user:
		if( ! $comment_author_User->allow_msgform )
		{ // should be prevented by UI
			debug_die( 'Invalid recipient!' );
		}
		$recipient_User = & $comment_author_User;
	}
	elseif( empty($Comment->allow_msgform) )
	{ // should be prevented by UI
		debug_die( 'Invalid recipient!' );
	}

	$recipient_name = trim($Comment->get_author_name());
	$recipient_address =  $recipient_name.' <'.$Comment->get_author_email().'>';

	// We don't know the recipient's language - Change the locale so the email is in the blog's language:
	locale_temp_switch($Blog->locale);
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
		$message_footer .= T_("You can edit your profile to not reveive mails through a form:")
			."\n".url_add_param( str_replace( '&amp;', '&', $Blog->get('url') ), 'disp=profile', '&' );
	}
	// TODO: else go to admin
}
elseif( $Comment )
{ // Visitor:
	$message_footer .= T_("Click on the following link to not receive e-mails on your comments\nfor this e-mail address anymore:")
		."\n".$htsrv_url.'message_send.php?optout_cmt_email='.rawurlencode($Comment->author_email);
}


// Trigger event: a Plugin could add a $category="error" message here..
$Plugins->trigger_event( 'MessageFormSent', array(
	'recipient_ID' => & $recipient_id,
	'item_ID' => $post_id,
	'comment_ID' => $comment_id,
	'message' => & $message,
	'message_footer' => & $message_footer,
	'Blog' => & $Blog,
	) );


if( $Messages->count( 'error' ) )
{ // there were errors: display them and get out of here
	$Messages->display( T_('Cannot send email, please correct these errors:'),
	'[<a href="javascript:history.go(-1)">'. T_('Back to email editing') . '</a>]' );
	debug_info();
	exit;
}

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
$success_mail = send_mail( $recipient_address, $subject, $message, "$sender_name <$sender_address>" );


// Plugins should cleanup their temporary data here:
$Plugins->trigger_event( 'MessageFormSentCleanup' );


// restore the locale to the blog visitor language
locale_restore_previous();

if( $success_mail )
{
	// Never say to whom we sent the email -- prevent user enumeration.
	$Messages->add( T_('Your message has been sent.'), 'success' );
}
else
{
	$Messages->add( T_('Sorry, could not send email.')
				.'<br />'.T_('Possible reason: the PHP mail() function may have been disabled on the server.'), 'error' );
}


// Header redirection
header_nocache();
// redirect Will save $Messages into Session:
header_redirect(); // exits!


/*
 * $Log$
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