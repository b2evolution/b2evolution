<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003-2004 by Francois PLANQUE - http://fplanque.net/
 * Copyright (c) 2003-2004 by Jeff Bearer - http://www.jeffbearer.com/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * This file sends email to the user!
 * $Id$
 */

// Initialize everything:
require_once( dirname(__FILE__) . '/../b2evocore/_main.php' );

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

if( !empty( $recipient_id ) )
{ // Get the email address for the recipient if a user.
	$user = get_userdata( $recipient_id );
	$user = new User($user);
	$recipient_address = $user->get('preferedname') . ' <' . $user->email . '>';
}
elseif( !empty( $comment_id ) )
{ // Get the email address for the recipient if a commenter.
	$sql = 'SELECT comment_author, comment_author_email 
		FROM ' . $tablecomments . ' 
		WHERE comment_ID =' . $comment_id;
	$row = $DB->get_row( $sql );
	$recipient_address = $row->comment_author . ' <' . $row->comment_author_email . '>';
}
else
{
	echo T_('Unable to find the users e-mail address... uh oh. exiting.');
	exit;
}

if( !empty( $comment_id ) )
{
	$message .= "\n\n" . T_('Message sent from your comment:') . "\n";
	$message .= $Blog->get('url') . '&p=' . $post_id . '&c=1&tb=1&pb=1#' . $comment_id;

}
elseif(!empty($post_id))
{
	$message .= "\n\n" . T_('Message sent from your post:') . "\n";
	$message .= $Blog->get('url') . '&p=' . $post_id . '&c=1&tb=1&pb=1';
}

// Message footer
$message .= "\n\n**************************************************\n";
$message .= T_('This message was sent via the messaging system on') . ' ' . $Blog->name . ".\n";
$message .= $Blog->get('url') . "\n";


// Send mail
mail($recipient_address , $subject , $message , "From: $sender_name <$sender_address>\r\n");

// Header redirection
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

param( 'redirect_to', 'string' );
$location = (!empty($redirect_to)) ? $redirect_to : $_SERVER['HTTP_REFERER'];
header('Refresh:0;url=' . $location);

?>
