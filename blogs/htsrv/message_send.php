<?php
/**
 * This file sends an email to the user!
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package htsrv
 * @author Jeff Bearer - {@link http://www.jeffbearer.com/} + blueyed, fplanque
 */

// Initialize everything:
require_once dirname(__FILE__).'/../evocore/_main.inc.php';


// TODO: Use Hit class to prevent mass mailings to members..


// Getting GET or POST parameters:
param( 'blog', 'integer', '' );
param( 'recipient_id', 'integer', '' );
param( 'post_id', 'integer', '' );
param( 'comment_id', 'integer', '' );
param( 'sender_name', 'string', '' );
param( 'sender_address', 'string', '' );
param( 'subject', 'string', '' );
param( 'message', 'string', '' );

// Getting current blog info:
$Blog = Blog_get_by_ID( $blog ); /* TMP: */ $blogparams = get_blogparams_by_ID( $blog );

// Prevent register_globals injection!
$recipient_address = '';

if( !empty( $recipient_id ) )
{ // Get the email address for the recipient if a member.
	$user = & $UserCache->get_by_ID( $recipient_id );
	$recipient_address = trim($user->get('preferedname')) . ' <' . $user->get('email') . '>';
	// Change the locale so the email is in the recipients language
	locale_temp_switch($user->locale);
}
elseif( !empty( $comment_id ) )
{ // Get the email address for the recipient if a visiting commenter.
	// TODO: use object
	$sql = 'SELECT comment_author, comment_author_email
					FROM T_comments
					WHERE comment_ID =' . $comment_id;
	$row = $DB->get_row( $sql );
	$recipient_address = trim($row->comment_author) . ' <' . $row->comment_author_email . '>';
}

if( empty($recipient_address) )
{ // We should never have called this in the first place!
	// Could be that commenter did not provide an email, etc...
	exit( 'No recipient specified!' );
}


// Message footer
$message .= "\n\n-- \n";

if( !empty( $comment_id ) )
{
	$message .= T_('Message sent from your comment:') . "\n";
	$message .= url_add_param( $Blog->get('url'), 'p='.$post_id.'&c=1&tb=1&pb=1#'.$comment_id, '&' );
	$message .= "\n\n";
}
elseif( !empty( $post_id ) )
{
	$message .= T_('Message sent from your post:') . "\n";
	$message .= url_add_param( $Blog->get('url'), 'p='.$post_id.'&c=1&tb=1&pb=1', '&' );
	$message .= "\n\n";
}

$message .= sprintf( T_('This message was sent via the messaging system on %s.'), $Blog->name ).".\n";
$message .= $Blog->get('url') . "\n";


// Send mail
send_mail( $recipient_address, $subject, $message, "$sender_name <$sender_address>");

if( isset($user) )
{
	// restore the locale to the readers language
	locale_restore_previous();
}

// Header redirection
header_nocache();
header_redirect();

?>