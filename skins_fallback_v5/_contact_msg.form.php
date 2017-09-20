<?php
/**
 * This is the template that displays the email message form
 *
 * This file is not meant to be called directly.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $dummy_fields, $Plugins;

// Default params:
$default_params = array(
		'skin_form_params'   => array(),
		'skin_form_before'   => '',
		'skin_form_after'    => '',
		'msgform_form_title' => '',
	);

if( isset( $params ) )
{	// Merge with default params
	$params = array_merge( $default_params, $params );
}
else
{	// Use a default params
	$params = $default_params;
}


$submit_url = get_htsrv_url().'message_send.php';

if( ( $unsaved_message_params = get_message_params_from_session() ) == NULL )
{ // set message default to empty string
	$message = '';
}
else
{ // set saved message params
	$subject = $unsaved_message_params[ 'subject' ];
	$message = $unsaved_message_params[ 'message' ];
	$email_author = $unsaved_message_params[ 'sender_name' ];
	$email_author_address = $unsaved_message_params[ 'sender_address' ];
}

echo str_replace( '$form_title$', $params['msgform_form_title'], $params['skin_form_before'] );

$Form = new Form( $submit_url );

$Form->switch_template_parts( $params['skin_form_params'] );

	$Form->begin_form( 'bComment'  );

	$Form->add_crumb( 'newmessage' );
	if( isset($Blog) )
	{
		$Form->hidden( 'blog', $Blog->ID );
	}
	$Form->hidden( 'recipient_id', $recipient_id );
	$Form->hidden( 'post_id', $post_id );
	$Form->hidden( 'comment_id', $comment_id );
	$Form->hidden( 'redirect_to', url_rel_to_same_host( $redirect_to, get_htsrv_url() ) );

	if( $Blog->get_setting( 'msgform_display_recipient' ) )
	{
		$Form->info( T_('Message to'), $recipient_link );
	}

	// Note: we use funky field names in order to defeat the most basic guestbook spam bots:
	// email form
	$Form->text_input( $dummy_fields[ 'name' ], $email_author, 40, T_('From'), T_('Your name.'), array( 'maxlength'=>50, 'class'=>'wide_input', 'required'=>true ) );
	$Form->text_input( $dummy_fields[ 'email' ], $email_author_address, 40, T_('Email'), T_('Your email address. (Will <strong>not</strong> be displayed on this site.)'),
		 array( 'maxlength'=>150, 'class'=>'wide_input', 'required'=>true ) );

	$Form->text_input( $dummy_fields[ 'subject' ], $subject, 40, T_('Subject'), T_('Subject of your message.'), array( 'maxlength'=>255, 'class'=>'wide_input', 'required'=>true ) );

	$Form->textarea( $dummy_fields[ 'content' ], $message, 15, T_('Message'), T_('Plain text only.'), 35, 'wide_textarea', true );

	$Plugins->trigger_event( 'DisplayMessageFormFieldset', array( 'Form' => & $Form,
		'recipient_ID' => & $recipient_id, 'item_ID' => $post_id, 'comment_ID' => $comment_id ) );

	// Form buttons:
	echo $Form->begin_field( NULL, '' );

		// Standard button to send a message
		$Form->button_input( array( 'name' => 'submit_message_'.$recipient_id, 'class' => 'submit', 'value' => T_('Send message') ) );

		// Additional buttons from plugins
		$Plugins->trigger_event( 'DisplayMessageFormButton', array( 'Form' => & $Form,
			'recipient_ID' => & $recipient_id, 'item_ID' => $post_id, 'comment_ID' => $comment_id ) );

	echo $Form->end_field();

$Form->end_form();

echo $params['skin_form_after'];

?>