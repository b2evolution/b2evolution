<?php
/**
 * This file implements the UI view for the Collection features contact form properties.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}.
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

// Load to use function get_available_thumb_sizes():
load_funcs( 'files/model/_image.funcs.php' );

$Form = new Form( NULL, 'coll_contact_checkchanges' );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'collection' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'update' );
$Form->hidden( 'tab', 'contact' );
$Form->hidden( 'blog', $edited_Blog->ID );

$Form->begin_fieldset( TB_('Contact form').' (disp=msgform)'.get_manual_link( 'contact-form-features' ) );
	$Form->text_input( 'msgform_title', $edited_Blog->get_setting( 'msgform_title' ), 80, TB_('Page Title'), TB_('Leave empty for default').': "'.TB_('Contact').'".' );
	$Form->begin_line( TB_('Display recipient') );
		$Form->checkbox( 'msgform_display_recipient', $edited_Blog->get_setting( 'msgform_display_recipient' ), '' );
		$Form->text_input( 'msgform_recipient_label', $edited_Blog->get_setting( 'msgform_recipient_label' ), 40, TB_('Label').':', TB_('Leave empty for default').': "'.TB_('Message to').'".' );
	$Form->end_line();
	$Form->begin_line( '' );
		$Form->checkbox( 'msgform_display_avatar', $edited_Blog->get_setting( 'msgform_display_avatar' ), '' );
		$Form->select_input_array( 'msgform_avatar_size', $edited_Blog->get_setting( 'msgform_avatar_size' ), get_available_thumb_sizes(), TB_('Avatar').':', '', array( 'force_keys_as_values' => true ) );
	$Form->end_line();
	$Form->radio( 'msgform_user_name', $edited_Blog->get_setting( 'msgform_user_name' ), array(
			array( 'fullname', TB_('First + Last Name') ),
			array( 'nickname', TB_('Nickname') ),
			array( 'none',     TB_('None') ),
		), TB_('Sender name input for logged in users') );
	$Form->checkbox( 'msgform_require_name', $edited_Blog->get_setting( 'msgform_require_name' ), TB_('Require name'), TB_('Check to require name.') );
	$Form->textarea( 'msgform_subject_list', $edited_Blog->get_setting( 'msgform_subject_list' ), 10, TB_('Subject option list'), TB_('Enter one option per line. Max length 255 symbols.') );
	$Form->checkbox( 'msgform_display_subject', $edited_Blog->get_setting( 'msgform_display_subject' ), TB_('Free subject input'), TB_('Check to display "Subject:" or "Other:" in case pre-filled options are provided above.') );
	$Form->checkbox( 'msgform_require_subject', $edited_Blog->get_setting( 'msgform_require_subject' ), TB_('Require subject'), TB_('Check to require a subject selection or input.') );
	$msgform_additional_fields = $edited_Blog->get_msgform_additional_fields();
	$saved_additional_fields = '';
	foreach( $msgform_additional_fields as $UserField )
	{
		$saved_additional_fields .= $Form->infostart
			.'<input type="hidden" name="msgform_additional_fields[]" value="'.$UserField->ID.'" />'
			.$UserField->get_input_label()
			.' '.get_icon( 'minus', 'imgtag', array( 'class' => 'remove_user_field', 'style' => 'cursor:pointer' ) )
			.$Form->infoend;
	}
	$saved_additional_fields .= '<div class="clearfix"></div>';
	$Form->select_input( 'new_user_field', 0, 'callback_options_user_new_fields', TB_('Additional fields'), array(
			'input_prefix' => $saved_additional_fields,
			'field_suffix' => '<button type="button" id="button_add_field" class="btn btn-default">'.TB_('Add').'</button>'
		) );
	$Form->checkbox( 'msgform_contact_method', $edited_Blog->get_setting( 'msgform_contact_method' ), TB_('Reply method'), TB_('Check to let user specify a preferred contact method.') );
	$Form->checkbox( 'msgform_display_message', $edited_Blog->get_setting( 'msgform_display_message' ), TB_('Allow message field'), TB_('Check to display textarea.') );
	$Form->checkbox( 'msgform_require_message', $edited_Blog->get_setting( 'msgform_require_message' ), TB_('Require message field'), TB_('Check to require a custom message.'), '', 1, ! $edited_Blog->get_setting( 'msgform_display_message' ) );
	$msgform_message_label_params = array();
	if( ! $edited_Blog->get_setting( 'msgform_display_message' ) )
	{	// Disable field "Label of message field" if message is not allowed:
		$msgform_message_label_params['disabled'] = 'disabled';
	}
	$Form->text_input( 'msgform_message_label', $edited_Blog->get_setting( 'msgform_message_label' ), 40, TB_('Label of message field'), TB_('Leave empty for default').': "'.TB_('Message').'".', $msgform_message_label_params );
$Form->end_fieldset();

$Form->end_form( array( array( 'submit', 'submit', TB_('Save Changes!'), 'SaveButton', 'data-shortcut' => 'ctrl+s,command+s,ctrl+enter,command+enter' ) ) );

?>
<script>
jQuery( '#button_add_field' ).click( function ()
{	// Action for the button to add a new field in the additional fields:
	var field_id = jQuery( this ).prev().find( 'option:selected' ).val();

	if( field_id == 0 )
	{	// We should stop the action without field_id:
		return false;
	}
	var field_title = jQuery( this ).prev().find( 'option:selected' ).html();

	var separator_obj = jQuery( this ).prev().prev();
	if( separator_obj.length == 0 )
	{	// Add separator clearfix between fields and control elements:
		jQuery( this ).prev().before( '<div class="clearfix"></div>' );
		separator_obj = jQuery( this ).prev().prev();
	}

	var added_field = jQuery( 'input[type=hidden][name="msgform_additional_fields[]"][value=' + field_id + ']' );
	if( added_field.length )
	{	// Remove already added field to add new at the end:
		added_field.parent().parent().remove();
	}

	separator_obj.before( '<?php echo format_to_js( $Form->infostart ); ?>'
		+ '<input type="hidden" name="msgform_additional_fields[]" value="' + field_id + '" />'
		+ field_title
		+ ' <?php echo format_to_js( get_icon( 'minus', 'imgtag', array( 'class' => 'remove_user_field', 'style' => 'cursor:pointer' ) ) ); ?>'
		+ '<?php echo format_to_js( $Form->infoend ); ?>' );

	return false;
} );
jQuery( document ).on( 'click', '.remove_user_field', function ()
{	// Action for the icon to remove a field from the additional fields:
	jQuery( this ).parent().parent().remove();
} );

// Disable/Enable settings of message field depending on setting "Allow message field":
jQuery( '#msgform_display_message' ).click( function()
{
	jQuery( '#msgform_require_message, #msgform_message_label' ).prop( 'disabled', ( ! jQuery( this ).is( ':checked' ) ) );
} );
</script>
