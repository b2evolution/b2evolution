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
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
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
$redirect_to = param( 'redirect_to', 'string', '' ); // pass-through (hidden field)
$recipient_id = param( 'recipient_id', 'integer', '' );
$post_id = param( 'post_id', 'integer', '' );
$comment_id = param( 'comment_id', 'integer', '' );
$subject = param( 'subject', 'string', '' );


// User's preferred name or the stored value in her cookie (from commenting):
$email_author = '';
if( is_logged_in() )
{
	$email_author = $current_User->get_preferred_name();
}
if( ! strlen($email_author) && isset($_COOKIE[$cookie_name]) )
{
	$email_author = trim($_COOKIE[$cookie_name]);
}

// User's email address or the stored value in her cookie (from commenting):
$email_author_address = '';
if( is_logged_in() )
{
	$email_author_address = $current_User->email;
}
if( ! strlen($email_author_address) && isset($_COOKIE[$cookie_email]) )
{
	$email_author_address = trim($_COOKIE[$cookie_email]);
}

$recipient_User = NULL;
$Comment = NULL;


// Get the name of the recipient and check if he wants to receive mails through the message form


if( ! empty($recipient_id) )
{ // If the email is to a registered user get the email address from the users table
	$UserCache = & get_UserCache();
	$recipient_User = & $UserCache->get_by_ID( $recipient_id );

	if( $recipient_User )
	{
		if( empty($recipient_User->allow_msgform) )
		{ // should be prevented by UI
			echo '<p class="error">The user does not want to receive emails through the message form.</p>';
			return;
		}
		$recipient_name = $recipient_User->get('preferredname');
		$recipient_address = $recipient_User->get('email');
	}
}
elseif( ! empty($comment_id) )
{ // If the email is through a comment, get the email address from the comments table (or the linked member therein):

	// Load comment from DB:
	$row = $DB->get_row( '
		SELECT *
		  FROM T_comments
		 WHERE comment_ID = '.$comment_id, ARRAY_A );

	if( $row )
	{
		$Comment = & new Comment( $row );

		if( $comment_author_User = & $Comment->get_author_User() )
		{ // Source comment is from a registered user:
			if( ! $comment_author_User->allow_msgform )
			{
				echo '<p class="error">The user does not want to get contacted through the message form.</p>'; // should be prevented by UI
				return;
			}
		}
		elseif( ! $Comment->allow_msgform )
		{ // Source comment is from an anonymou suser:
			echo '<p class="error">This commentator does not want to get contacted through the message form.</p>'; // should be prevented by UI
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

// Get the suggested subject for the email:
if( empty($subject) )
{ // no subject provided by param:
	if( ! empty($comment_id) )
	{
		$row = $DB->get_row( '
			SELECT post_title
			  FROM T_items__item, T_comments
			 WHERE comment_ID = '.$DB->quote($comment_id).'
			   AND post_ID = comment_post_ID' );

		if( $row )
		{
			$subject = T_('Re:').' '.sprintf( /* TRANS: Used as mail subject; %s gets replaced by an item's title */ T_( 'Comment on %s' ), $row->post_title );
		}
	}

	if( empty($subject) && ! empty($post_id) )
	{
		$row = $DB->get_row( '
				SELECT post_title
				  FROM T_items__item
				 WHERE post_ID = '.$post_id );
		if( $row )
		{
			$subject = T_('Re:').' '.$row->post_title;
		}
	}
}
?>

<!-- form to send email -->
<?php

$Form = new Form( $htsrv_url.'message_send.php' );
	$Form->begin_form( 'bComment' );

	if( !empty( $Blog ) )
	{
		$Form->hidden( 'blog', $Blog->ID );
	}
	$Form->hidden( 'recipient_id', $recipient_id );
	$Form->hidden( 'post_id', $post_id );
	$Form->hidden( 'comment_id', $comment_id );
	$Form->hidden( 'redirect_to', url_rel_to_same_host($redirect_to, $htsrv_url) );

	?>

	<fieldset>
		<div class="label"><label><?php echo T_('To')?>:</label></div>
		<div class="info"><strong><?php echo $recipient_name;?></strong></div>
	</fieldset>

	<?php
	// Note: we use funky field name in order to defeat the most basic guestbook spam bots:
	$Form->text( 'd', $email_author, 40, T_('From'),  T_('Your name.'), 50, 'bComment' );
	$Form->text( 'f', $email_author_address, 40, T_('Email'), T_('Your email address. (Will <strong>not</strong> be displayed on this site.)'), 100, 'bComment' );
	$Form->text( 'g', $subject, 40, T_('Subject'), T_('Subject of email message.'), 255, 'bComment' );
	$Form->textarea( 'h', '', 15, T_('Message'), T_('Plain text only.'), 40, 'bComment' );

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


/*
 * $Log$
 * Revision 1.8  2009/09/26 12:00:44  tblue246
 * Minor/coding style
 *
 * Revision 1.7  2009/09/25 07:33:31  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.6  2009/09/20 18:13:20  fplanque
 * doc
 *
 * Revision 1.5  2009/09/20 14:00:35  blueyed
 * todo
 *
 * Revision 1.4  2009/03/08 23:57:56  fplanque
 * 2009
 *
 * Revision 1.3  2008/01/21 09:35:42  fplanque
 * (c) 2008
 *
 * Revision 1.2  2008/01/09 00:26:07  blueyed
 * todo
 *
 * Revision 1.1  2007/11/29 19:29:22  fplanque
 * normalized skin filenames
 *
 * Revision 1.39  2007/09/22 19:23:56  fplanque
 * various fixes & enhancements
 *
 * Revision 1.38  2007/09/17 18:03:52  blueyed
 * Fixed cases for no $Blog, e.g. with contact.php
 *
 * Revision 1.37  2007/09/08 14:50:02  fplanque
 * FIX
 *
 * Revision 1.36  2007/07/09 20:15:59  fplanque
 * doc
 *
 * Revision 1.35  2007/07/09 20:03:59  blueyed
 * - Prefer current User's name+email instead of the ones from comment remember-me cookies
 * - todos
 *
 * Revision 1.34  2007/06/30 01:25:15  fplanque
 * fixes
 *
 * Revision 1.33  2007/05/14 02:43:06  fplanque
 * Started renaming tables. There probably won't be a better time than 2.0.
 *
 * Revision 1.32  2007/04/26 00:11:04  fplanque
 * (c) 2007
 *
 * Revision 1.31  2007/03/18 01:39:55  fplanque
 * renamed _main.php to main.page.php to comply with 2.0 naming scheme.
 * (more to come)
 *
 * Revision 1.30  2006/10/15 21:30:46  blueyed
 * Use url_rel_to_same_host() for redirect_to params.
 *
 * Revision 1.29  2006/08/19 07:56:32  fplanque
 * Moved a lot of stuff out of the automatic instanciation in _main.inc
 *
 * Revision 1.28  2006/07/06 19:56:29  fplanque
 * no message
 *
 * Revision 1.27  2006/06/16 20:34:20  fplanque
 * basic spambot defeating
 *
 * Revision 1.26  2006/05/30 21:51:03  blueyed
 * Lazy-instantiate "expensive" properties of Comment and Item.
 *
 * Revision 1.25  2006/05/19 18:15:06  blueyed
 * Merged from v-1-8 branch
 *
 * Revision 1.24.2.1  2006/05/19 15:06:26  fplanque
 * dirty sync
 *
 * Revision 1.24  2006/05/06 21:52:50  blueyed
 * trans
 *
 * Revision 1.23  2006/05/04 14:28:15  blueyed
 * Fix/enhanced
 *
 * Revision 1.22  2006/04/20 16:31:30  fplanque
 * comment moderation (finished for 1.8)
 *
 * Revision 1.21  2006/04/11 21:22:26  fplanque
 * partial cleanup
 *
 */
?>
