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
	 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
	 *
	 * @package evoskins
	 */
	if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );


	// Parameters
	param( 'redirect_to', 'string', '' );
	param( 'recipient_id', 'integer', '' );
	param( 'post_id', 'integer', '' );
	param( 'comment_id', 'integer', '' );
	param( 'subject', 'string', '' );


	// If the user has the cookies set from commenting use those as a default from.
	$email_author = isset($_COOKIE[$cookie_name]) ? trim($_COOKIE[$cookie_name]) : '';
	$email_author_address = isset($_COOKIE[$cookie_email]) ? trim($_COOKIE[$cookie_email]) : '';

	// Accept the redirect_to string for the page to visit after the mail is sent, otherwise
	// default to the referer of the form.
	if ( empty($redirect_to) )
	{
		$redirect_to = isset( $_SERVER['HTTP_REFERER'] )
										? $_SERVER['HTTP_REFERER']
										: $baseurl; // TODO: better default!?
	}

	// Get the name of the recipient
	if(!empty($recipient_id))
	{ // If the email is to a registerd user get the email address from the users table
		$User = & $UserCache->get_by_ID( $recipient_id );
		$recipient_name = $User->get('preferedname');
 		$recipient_address = $User->get('email');
	}
	elseif(!empty($comment_id))
	{ // If the email is to a non user comment poster get the email address from the comments table
		// TODO: use object
		$sql = 'SELECT comment_author, comment_author_email
						FROM T_comments
						WHERE comment_ID = '.$comment_id;
		$row = $DB->get_row( $sql );
		$recipient_name = $row->comment_author;
		$recipient_address = $row->comment_author_email;
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
		$sql = "SELECT post_title
						FROM T_posts
						WHERE ID = '$post_id'";
		$row = $DB->get_row( $sql );
		$subject = T_('Re:').' '.$row->post_title;
	}
?>

	<!-- form to send email -->
	<form action="<?php echo $htsrv_url ?>message_send.php" method="post" class="bComment">

		<input type="hidden" name="blog" value="<?php echo $blog  ?>" />
		<input type="hidden" name="recipient_id" value="<?php echo $recipient_id ?>" />
		<input type="hidden" name="post_id" value="<?php echo $post_id ?>" />
		<input type="hidden" name="comment_id" value="<?php echo $comment_id ?>" />
		<input type="hidden" name="redirect_to" value="<?php echo $redirect_to ?>" />


		<?php
		if( is_logged_in() )
		{ // If the user is logged in default the from address to that info.

			$email_author = $current_User->get('preferedname');
			$email_author_address = $current_User->email;

		}
		?>


		<fieldset>
			<div class="label"><label for="to"><?php echo T_('To')?>:</label></div>
			<div class="info"><strong><?php echo $recipient_name;?></strong></div>
		</fieldset>

		<?php
			form_text( 'sender_name', $email_author, 40, T_('From'),  T_('Your name.'), 50, 'bComment' );
			form_text( 'sender_address', $email_author_address, 40, T_('Email'), T_('Your email address. (Will <strong>not</strong> be displayed on this site.)'), 100, 'bComment' );
			form_text( 'subject', $subject, 40, T_('Subject'), T_('Subject of email message.'), 50, 'bComment' );
			form_textarea( 'message', '', 15, T_('Message'), T_('Plain text only.'), 40, 'bComment' );
		?>

		<fieldset>
			<div class="input">
				<input type="submit" name="submit" class="submit" value="<?php echo T_('Send message') ?>" />
			</div>
		</fieldset>

		<div class="clear"></div>

	</form>