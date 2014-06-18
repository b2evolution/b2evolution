<?php
/**
 * This is the template that displays the message user form
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 * To display a feedback, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?disp=msgform&recipient_id=n
 * Note: don't code this URL by hand, use the template functions to generate it!
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 *
 * @todo dh> A user/blog might want to accept only mails from logged in users (fp>yes!)
 * @todo dh> For logged in users the From name and address should be not editable/displayed
 *           (the same as when commenting). (fp>yes!!!)
 * @todo dh> Display recipient's avatar?! fp> of course! :p
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $cookie_name, $cookie_email;

global $DB;

// Parameters
/* TODO: dh> params should get remembered, e.g. if somebody clicks on the
 *       login/logout link from the msgform page.
 *       BUT, for the logout link remembering it here is too late normally.. :/
 */
$redirect_to = param( 'redirect_to', 'url', '' ); // pass-through (hidden field)
$recipient_id = param( 'recipient_id', 'integer', 0 );
$post_id = param( 'post_id', 'integer', 0 );
$comment_id = param( 'comment_id', 'integer', 0 );
$subject = param( 'subject', 'string', '' );


// User's preferred name or the stored value in her cookie (from commenting):
$email_author = '';
// User's email address or the stored value in her cookie (from commenting):
$email_author_address = '';
if( is_logged_in() )
{
	$email_author = $current_User->get_preferred_name();
	$email_author_address = $current_User->email;
}
if( ! strlen( $email_author ) )
{ // Try to get params from $_COOKIE through the param() function
	$email_author = param_cookie( $cookie_name, 'string', '' );
	$email_author_address = param_cookie( $cookie_email, 'string', '' );
}

$recipient_User = NULL;
// Get the name and email address of the recipient
if( empty($recipient_id) )
{
	$recipient_name = param( 'recipient_name', 'string', '' );
	$recipient_address = param( 'recipient_address', 'string', '' );
}
else
{ // If the email is to a registered user get the email address from the users table
	$UserCache = & get_UserCache();
	$recipient_User = & $UserCache->get_by_ID( $recipient_id );

	if( $recipient_User )
	{ // recipient User found
		$recipient_name = $recipient_User->get('preferredname');
		$recipient_address = $recipient_User->get('email');
	}
}

if( empty($recipient_address) )
{	// We should never have called this in the first place!
	// Could be that commenter did not provide an email, etc...
	echo 'No recipient specified!';
	return;
}

// Form to send email
if( !empty( $Blog ) && ( $Blog->get_ajax_form_enabled() ) )
{
	if( empty( $subject ) )
	{
		$subject = '';
	}
	// init params
	$json_params = array(
		'action' => 'get_msg_form',
		'subject' => $subject,
		'recipient_id' => $recipient_id,
		'recipient_name' => $recipient_name,
		'email_author' => $email_author,
		'email_author_address' => $email_author_address,
		'blog' => $Blog->ID,
		'comment_id' => $comment_id,
		'redirect_to' => $redirect_to,
		'params' => $params );

	// generate form wtih ajax request
	display_ajax_form( $json_params );
}
else
{
	if( ! empty( $recipient_User ) )
	{ // Get identity link for existed users
		$recipient_link = $recipient_User->get_identity_link( array( 'link_text' => 'nickname' ) );
	}
	else
	{ // Get login name for anonymous user
		$gender_class = '';
		if( check_setting( 'gender_colored' ) )
		{ // Set a gender class if the setting is ON
			$gender_class = ' nogender';
		}
		$recipient_link = '<span class="user anonymous'.$gender_class.'" rel="bubbletip_comment_'.$comment_id.'">'.$recipient_name.'</span>';
	}
	require '_contact_msg.form.php';
}

?>