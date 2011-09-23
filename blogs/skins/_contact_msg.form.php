<?php
/**
 * This is the template that displays the comment form for a post
 *
 * This file is not meant to be called directly.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

$submit_url = get_samedomain_htsrv_url().'message_send.php';

$Form = new Form( $submit_url );

	$Form->begin_form( 'bComment' );

	$Form->add_crumb( 'newmessage' );
	if( isset($Blog) )
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
	// Note: we use funky field names in order to defeat the most basic guestbook spam bots:
	$Form->text_input( 'd', $email_author, 40, T_('From'), T_('Your name.'), array( 'maxlength'=>50, 'class'=>'wide_input', 'required'=>true ) );

	if( $allow_msgform == 'email' )
	{
		$Form->text_input( 'f', $email_author_address, 40, T_('Email'), T_('Your email address. (Will <strong>not</strong> be displayed on this site.)'),
			 array( 'maxlength'=>150, 'class'=>'wide_input', 'required'=>true ) );
	}

	$Form->text_input( 'g', $subject, 40, T_('Subject'), T_('Subject of your message.'), array( 'maxlength'=>255, 'class'=>'wide_input', 'required'=>true ) );

	$Form->textarea( 'h', '', 15, T_('Message'), T_('Plain text only.'), 35, 'wide_textarea' );

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
 * Revision 1.7  2011/09/23 01:29:05  fplanque
 * small changes
 *
 * Revision 1.6  2011/09/22 08:55:00  efy-asimo
 * Login problems with multidomain installs - fix
 *
 * Revision 1.5  2011/09/18 01:07:20  fplanque
 * forms cleanup
 *
 * Revision 1.4  2011/09/18 00:58:44  fplanque
 * forms cleanup
 *
 * Revision 1.3  2011/09/17 02:31:58  fplanque
 * Unless I screwed up with merges, this update is for making all included files in a blog use the same domain as that blog.
 *
 * Revision 1.2  2011/09/04 22:13:24  fplanque
 * copyright 2011
 *
 * Revision 1.1  2011/06/29 13:14:01  efy-asimo
 * Use ajax to display comment and contact forms
 *
 */
?>