<?php
	/*
	 * This is the template that displays the message user form
	 *
	 * This file is not meant to be called directly.
	 * It is meant to be called by an include in the _main.php template.
	 * To display a feedback, you should call a stub AND pass the right parameters
	 * For example: /blogs/index.php?disp=msgform&recipient_id=n
	 * Note: don't code this URL by hand, use the template functions to generate it!
	 *
	 * $Id$
	 */
	if( substr(basename($_SERVER['SCRIPT_FILENAME']), 0, 1) == '_' )
		die('Please, do not access this page directly.');

	// --- //

	// Parameters
	param( 'redirect_to', 'string', '' );
	param( 'recipient_id', 'integer', '' );
	param( 'post_id', 'integer', '' );
	param( 'comment_id', 'integer', '' );


	// If the user has the cookies set from commenting use those as a default from.
	$email_author = isset($_COOKIE[$cookie_name]) ? trim($_COOKIE[$cookie_name]) : '';
	$email_author_address = isset($_COOKIE[$cookie_email]) ? trim($_COOKIE[$cookie_email]) : '';

	// Accept the redirect_to string for the page to visit after the mail is sent, otherwise
	// default to the referer of the form.
	if ( empty($redirect_to) ) $redirect_to = $_SERVER['HTTP_REFERER'];

	// Get the name of the reciepeint
	if(!empty($recipient_id))
	{ // If the email is to a registerd user get the email address from the users table
		$sql="SELECT user_firstname, user_lastname 
			FROM ".$tableusers." 
			WHERE ID='$recipient_id'";
		$row = $DB->get_row( $sql );
		$recipient_name = $row->user_firstname ." ". $row->user_lastname;
	}
	elseif (!empty($comment_id))
	{ // If the email is to a non user comment poster get the email address from the comments table
		$sql="SELECT comment_author, comment_author_email 
			FROM ".$tablecomments." 
			WHERE comment_ID = '$comment_id'";
		$row = $DB->get_row( $sql );
		$recipient_name = $row->comment_author;
	}
	else
	{ // Error Gracefully
		echo 'error';
		exit;
	}

	// Get the subject of the email
	if( !empty($comment_id) || !empty($post_id))
	{
		$sql="SELECT post_title FROM ".$tableposts." WHERE ID = '$post_id'";
		$row = $DB->get_row( $sql );
		$subject = T_('Re:') . " " . $row->post_title;
	}
?>

	<!-- form to send email -->
	<form action="<?php echo $htsrv_url ?>/message_send.php" method="post" class="bComment">

		<input type="hidden" name="blog" value="<?php echo $blog  ?>" />
		<input type="hidden" name="recipient_id" value="<?php echo $recipient_id ?>" />
		<input type="hidden" name="post_id" value="<?php echo $post_id ?>" />
		<input type="hidden" name="comment_id" value="<?php echo $comment_id ?>" />
		<input type="hidden" name="redirect_to" value="<?php echo $redirect_to ?>" />

		
		<?php 
		if( is_logged_in() ) 
		{ // If the user is logged in default the from address to that info. 

			$email_author = $current_User->get(preferedname);
			$email_author_address = $current_User->email;

		} 
		?>
					

		<fieldset>
			<div class="label"><label for="to"><?php echo T_('To')?>:</label></div>
 			<div class="input"><?php echo $recipient_name;?></div>
		</fieldset>

		<fieldset>
		<?php
			form_text( 'sender_name', $email_author, 40, T_('From'), '', 50, 'bComment' );
			form_text( 'sender_address', $email_author_address, 40, T_('E-mail Address'), '',50, 'bComment' );
			form_text( 'subject', $subject, 40, T_('Subject'), '',50, 'bComment' );

		?>

		<fieldset>
			<div class="label"><label for="message"><?php echo T_('Message')?>:</label></div>
 			<div class="input"><textarea name="message" id="message" rows="15" cols="25" class="bComment"></textarea></div> 		</fieldset> 
		<fieldset>
			<div class="input">
				<input type="submit" name="submit" value="<?php echo T_('Send') ?>" class="search" />
 				<input type="reset" value="<?php echo T_('Reset') ?>" class="search" />
			</div>
		</fieldset>

		<div class="clear"></div>

	</form>
