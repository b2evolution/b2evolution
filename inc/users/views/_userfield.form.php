<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'users/model/_userfield.class.php', 'Userfield' );

/**
 * @var Userfield
 */
global $edited_Userfield;

// Determine if we are creating or updating...
global $action;

$creating = is_create_action( $action );

$Form = new Form( NULL, 'userfield_checkchanges', 'post', 'compact' );

$Form->global_icon( TB_('Delete this user field!'), 'delete', regenerate_url( 'action', 'action=delete&amp;'.url_crumb('userfield') ) );
$Form->global_icon( TB_('Cancel editing').'!', 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', $creating ?  TB_('New user field') : TB_('User field') );

	$Form->add_crumb( 'userfield' );

	$Form->hiddens_by_key( get_memorized( 'action' ) ); // (this allows to come back to the right list order & page)

	$Form->select_input_array( 'ufdf_ufgp_ID', $edited_Userfield->ufgp_ID, $edited_Userfield->get_groups(),
		TB_('Group'), '', array( 'required' => true, 'force_keys_as_values' => true ) );

	$Form->text_input( 'ufdf_code', $edited_Userfield->code, 20, TB_('Field code'), '', array( 'maxlength' => 20, 'required' => true ) );

	$Form->text_input( 'ufdf_name', $edited_Userfield->name, 50, TB_('Field name'), '', array( 'maxlength' => 255, 'required' => true ) );

	$Form->text_input( 'ufdf_icon_name', $edited_Userfield->icon_name, 20, TB_('Icon name'), '', array( 'maxlength' => 100 ) );

	if( is_pro() )
	{	// Allow to select user group for PRO version:
		load_funcs( '_core/_pro_features.funcs.php' );
		$user_group_selector = pro_user_field_group_selector( $edited_Userfield->grp_ID, $Form );
	}
	else
	{	// Display info about unavailable feature:
		$user_group_selector = sprintf( TB_('This is a %s feature'), get_pro_label() );
	}
	$Form->select_input_array( 'ufdf_type', $edited_Userfield->type, Userfield::get_types(),
		TB_('Field type'), '', array(
			'required' => true,
			'input_suffix' => '<span id="div_ufdf_user_type_options"'.( $edited_Userfield->type == 'user' ? '' : ' style="display:none"' ).'> '.$user_group_selector.'</span>'
		) );

	// Show this textarea only for field type with "Option list"
	echo '<div id="div_ufdf_options"'.( $edited_Userfield->type != 'list' ? ' style="display:none"' : '' ).'>';
	$Form->textarea_input( 'ufdf_options', $edited_Userfield->options, 10, TB_('Options'), array( 'required' => ( $edited_Userfield->type == 'list' ? true : 'mark_only' ), 'maxlength' => 255, 'note' => TB_('Enter one option per line. Max length 255 symbols.') ) );
	echo '</div>';

	// Suggest values only for field type with "Single word"
	echo '<div id="div_ufdf_suggest"'. ( $edited_Userfield->type != 'word' ? ' style="display:none"' : '' ) .'>';
	$Form->checkbox_input( 'ufdf_suggest', $edited_Userfield->suggest, TB_('Suggest values') );
	echo '</div>';

	$Form->radio_input( 'ufdf_duplicated', $edited_Userfield->duplicated, Userfield::get_duplicateds( 'radio' ), TB_('Multiple values'), array( 'required'=>true, 'lines'=>true ) );

	$Form->radio_input( 'ufdf_required', $edited_Userfield->required, Userfield::get_requireds( 'radio' ), TB_('Required?'), array( 'required'=>true ) );

	$Form->radio_input( 'ufdf_visibility', $edited_Userfield->visibility, Userfield::get_visibilities( 'radio' ), TB_('Field visibility'), array( 'required' => true ) );

	$Form->textarea_input( 'ufdf_bubbletip', $edited_Userfield->bubbletip, 5, TB_('Bubbletip text') );

if( $creating )
{
	$Form->end_form( array( array( 'submit', 'actionArray[create]', TB_('Record'), 'SaveButton' ),
													array( 'submit', 'actionArray[create_new]', TB_('Record, then Create New'), 'SaveButton' ),
													array( 'submit', 'actionArray[create_copy]', TB_('Record, then Create Similar'), 'SaveButton' ) ) );
}
else
{
	$Form->end_form( array( array( 'submit', 'actionArray[update]', TB_('Save Changes!'), 'SaveButton' ) ) );
}
?>
<script>
function evo_check_userfield_type_user_select()
{	// Resctrict "User select" for not PRO version:
<?php if( ! is_pro() ) { ?>
	jQuery( '#userfield_checkchanges input[type=submit]' ).prop( 'disabled', jQuery( '#ufdf_type' ).val() == 'user' );
<?php } ?>
}

jQuery( '#ufdf_type' ).change( function()
{	// Show textarea input only for field type with "Option list"
	if( jQuery( this ).val() == 'list' )
	{
		jQuery( '#div_ufdf_options' ).show();
		jQuery( '#ufdf_options' ).attr( 'required', 'required' );
	}
	else
	{
		jQuery( '#div_ufdf_options' ).hide();
		jQuery( '#ufdf_options' ).removeAttr( 'required' );
	}
	// Suggest values only for field type with "Single word"
	jQuery( '#div_ufdf_suggest' ).toggle( jQuery( this ).val() == 'word' );
	// Suggest to select group for type "User select":
	jQuery( '#div_ufdf_user_type_options' ).toggle( jQuery( this ).val() == 'user' );
	// Check for type "User select":
	evo_check_userfield_type_user_select();
} );
evo_check_userfield_type_user_select();
</script>