<?php
/**
 * This file sends an email to the user!
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package htsrv
 * @author Jeff Bearer - {@link http://www.jeffbearer.com/} + blueyed, fplanque
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
	{ // Get the email address for the recipient if a member.
		$user = get_userdata( $recipient_id );
		$user = new User($user);
		// fplanque: this fails on my mailserver:
		// $recipient_address = trim($user->get('preferedname')) . ' <' . $user->get('email') . '>';
		$recipient_address = $user->get('email');
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
		// fplanque: this fails on my mailserver:
		// $recipient_address = trim($row->comment_author) . ' <' . $row->comment_author_email . '>';
		$recipient_address = $row->comment_author_email;
	}

	if( empty($recipient_address) )
	{	// We should never have called this in the first place!
		// Could be that commenter did not provide an email, etc...
		echo 'No recipient specified!';
		exit;
	}

	$message .= "\n \n--\n";

	if( !empty( $comment_id ) )
	{
		$message .= T_('Message sent from your comment:') . "\n";
		$message .= url_add_param( $Blog->get('url'), 'p='.$post_id.'&c=1&tb=1&pb=1#'.$comment_id, '&' );

	}
	elseif( !empty( $post_id ) )
	{
		$message .= T_('Message sent from your post:') . "\n";
		$message .= url_add_param( $Blog->get('url'), 'p='.$post_id.'&c=1&tb=1&pb=1', '&' );
	}

	// Message footer
	$message .= "\n--\n";
	$message .= T_('This message was sent via the messaging system on') . ' ' . $Blog->name . ".\n";
	$message .= $Blog->get('url') . "\n";


	// Send mail
	send_mail( $recipient_address , $subject, $message , "$sender_name <$sender_address>");

	if( isset($user) )
	{
		// restor the locale to the readers language
		locale_restore_previous();
	}

	// Header redirection
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
	header('Cache-Control: no-cache, must-revalidate');
	header('Pragma: no-cache');

	param( 'redirect_to', 'string' );
	$location = (!empty($redirect_to)) ? $redirect_to : $_SERVER['HTTP_REFERER'];
	header('Refresh:0;url=' . $location);

?>