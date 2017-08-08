<?php
/**
 * This file implements the UI view for the Collection features contact form properties.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $edited_Blog;


$Form = new Form( NULL, 'coll_contact_checkchanges' );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'collection' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'update' );
$Form->hidden( 'tab', 'contact' );
$Form->hidden( 'blog', $edited_Blog->ID );

$Form->begin_fieldset( T_('Contact form').' (disp=msgform)'.get_manual_link( 'contact-form' ) );
	$Form->text_input( 'msgform_title', $edited_Blog->get_setting( 'msgform_title' ), 80, T_('Page Title'), T_('Leave empty for default').': "'.T_('Contact').'".' );
	$Form->begin_line( T_('Display recipient') );
		$Form->checkbox( 'msgform_display_recipient', $edited_Blog->get_setting( 'msgform_display_recipient' ), '' );
		$Form->text_input( 'msgform_recipient_label', $edited_Blog->get_setting( 'msgform_recipient_label' ), 40, T_('Label').':', T_('Leave empty for default').': "'.T_('Message to').'".' );
	$Form->end_line();
	$Form->radio( 'msgform_user_name', $edited_Blog->get_setting( 'msgform_user_name' ), array(
			array( 'fullname', T_('First + Last Name') ),
			array( 'nickname', T_('Nickname') ),
			array( 'none',     T_('None') ),
		), T_('Name input for logged in users') );
	$Form->checkbox( 'msgform_require_name', $edited_Blog->get_setting( 'msgform_require_name' ), T_('Require name'), T_('Check to require name.') );
	$Form->textarea( 'msgform_subject_list', $edited_Blog->get_setting( 'msgform_subject_list' ), 10, T_('Subject option list'), T_('Enter one option per line. Max length 255 symbols.') );
	$Form->checkbox( 'msgform_display_subject', $edited_Blog->get_setting( 'msgform_display_subject' ), T_('Free subject input'), T_('Check to display "Subject:" or "Other:" in case pre-filled options are provided above.') );
	$Form->checkbox( 'msgform_require_subject', $edited_Blog->get_setting( 'msgform_require_subject' ), T_('Require subject'), T_('Check to require a subject selection or input.') );
	$Form->info( T_('Additional fields'), '---' );
	$Form->checkbox( 'msgform_contact_method', $edited_Blog->get_setting( 'msgform_contact_method' ), T_('Preferred contact method'), T_('Check to let user specify a preferred contact method.') );
	$Form->begin_line( T_('Allow message field') );
		$Form->checkbox( 'msgform_display_message', $edited_Blog->get_setting( 'msgform_display_message' ), '', T_('Check to display textarea.') );
		$Form->checkbox( 'msgform_require_message', $edited_Blog->get_setting( 'msgform_require_message' ), T_('Required').':', T_('Check to require a custom message.') );
		$Form->text_input( 'msgform_message_label', $edited_Blog->get_setting( 'msgform_message_label' ), 40, T_('Label').':', T_('Leave empty for default').': "'.T_('Message').'".' );
	$Form->end_line();
$Form->end_fieldset();

$Form->end_form( array( array( 'submit', 'submit', T_('Save Changes!'), 'SaveButton' ) ) );

?>