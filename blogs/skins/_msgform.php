<?php
/**
 * This is the template that displays the message user form
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the _main.php template.
 * To display a feedback, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?disp=msgform&recipient_id=n
 * Note: don't code this URL by hand, use the template functions to generate it!
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Parameters
param( 'redirect_to', 'string', '' ); // pass-through (hidden field)
param( 'recipient_id', 'integer', '' );
param( 'post_id', 'integer', '' );
param( 'comment_id', 'integer', '' );
param( 'subject', 'string', '' );


// If the user has the cookies set from commenting use those as a default from.
if( isset($_COOKIE[$cookie_name]) )
{
	$email_author = trim($_COOKIE[$cookie_name]);
}
elseif( is_logged_in() )
{
	$email_author = $current_User->get('preferredname');
}
else
{
	$email_author = '';
}

if( isset($_COOKIE[$cookie_email]) )
{
	$email_author_address = trim($_COOKIE[$cookie_email]);
}
elseif( is_logged_in() )
{
	$email_author_address = $current_User->email;
}
else
{
	$email_author_address = '';
}

$recipient_User = NULL;
$Comment = NULL;


// Get the name of the recipient and check if he wants to receive mails through the message form
if( ! empty($recipient_id) )
{ // If the email is to a registerd user get the email address from the users table
	$recipient_User = & $UserCache->get_by_ID( $recipient_id );

	if( empty($recipient_User->allow_msgform) )
	{ // should be prevented by UI
		echo '<p class="error">The user does not want to receive emails through the message form.</p>';
		return;
	}
	$recipient_name = $recipient_User->get('preferredname');
	$recipient_address = $recipient_User->get('email');
}
elseif( ! empty($comment_id) )
{ // If the email is to a non user comment poster get the email address from the comments table
	$row = $DB->get_row( '
		SELECT *
		  FROM T_comments
		 WHERE comment_ID = '.$comment_id, ARRAY_A );
	if( $row )
	{
		$Comment = new Comment( $row );

		if( ! $Comment->allow_msgform )
		{ // should be prevented by UI
			echo '<p class="error">This commentator does not want to get contacted through message form.</p>';
			return;
		}

		$recipient_name = $Comment->get_author_name();
		$recipient_address = $Comment->get_author_email();
	}
}

if( empty($recipient_address) )
{	// We should never have called this in the first place!
	// Could be that commenter did not provide an email, etc...
	echo 'No recipient specified!';
	return;
}

// Get the subject of the email
if( !empty($comment_id) || !empty($post_id) )
{
	$sql = 'SELECT post_title
					FROM T_posts
					WHERE post_ID = '.$post_id;
	$row = $DB->get_row( $sql );
	$subject = T_('Re:').' '.$row->post_title;
}
?>

<!-- form to send email -->
<?php

$Form = new Form( $htsrv_url.'message_send.php' );
	$Form->begin_form( 'bComment' );

	$Form->hidden( 'blog', $blog );
	$Form->hidden( 'recipient_id', $recipient_id );
	$Form->hidden( 'post_id', $post_id );
	$Form->hidden( 'comment_id', $comment_id );
	$Form->hidden( 'redirect_to', $redirect_to );

	?>

	<fieldset>
		<div class="label"><label><?php echo T_('To')?>:</label></div>
		<div class="info"><strong><?php echo $recipient_name;?></strong></div>
	</fieldset>

	<?php
	$Form->text( 'sender_name', $email_author, 40, T_('From'),  T_('Your name.'), 50, 'bComment' );
	$Form->text( 'sender_address', $email_author_address, 40, T_('Email'), T_('Your email address. (Will <strong>not</strong> be displayed on this site.)'), 100, 'bComment' );
	$Form->text( 'subject', $subject, 40, T_('Subject'), T_('Subject of email message.'), 255, 'bComment' );
	$Form->textarea( 'message', '', 15, T_('Message'), T_('Plain text only.'), 40, 'bComment' );

	$Plugins->trigger_event( 'DisplayMessageFormFieldset', array( 'Form' => & $Form,
		'recipient_ID' => & $recipient_id, 'item_ID' => $post_id, 'comment_ID' => $comment_id ) );

	$Form->begin_fieldset();
	?>
		<div class="input">
			<?php
			$Form->button_input( array( 'name' => 'submit_message_'.$recipient_id, 'class' => 'submit', 'value' => T_('Send message') ) );

			$Plugins->trigger_event( 'DisplayMessageFormButton', array( 'Form' => & $Form,
				'recipient_ID' => & $recipient_id, 'item_ID' => $post_id, 'comment_ID' => $comment_id ) );
			?>
		</div>
		<?php
	$Form->end_fieldset();
	?>

	<div class="clear"></div>

<?php
$Form->end_form();
?>