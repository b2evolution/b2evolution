<?php
/**
 * This file display the automation form
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $edited_Automation, $action;

// Determine if we are creating or updating:
$creating = is_create_action( $action );

$Form = new Form( NULL, 'automation_checkchanges', 'post', 'compact' );

$Form->global_icon( T_('Cancel editing').'!', 'close', regenerate_url( 'action,autm_ID' ) );

$Form->begin_form( 'fform', ( $creating ?  T_('New automation') : T_('Automation') ).get_manual_link( 'automation-form-settings' ) );

$Form->add_crumb( 'automation' );
$Form->hidden( 'action',  $creating ? 'create' : 'update' );
$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',autm_ID' : '' ) ) );

$Form->text_input( 'autm_name', $edited_Automation->get( 'name' ), 40, T_('Name'), '', array( 'maxlength' => 255, 'required' => true ) );

$Form->select_input_array( 'autm_status', $edited_Automation->get( 'status' ), autm_get_status_titles(), T_('Status'), '', array( 'force_keys_as_values' => true, 'required' => true ) );

// Tied Lists:
$NewsletterCache = & get_NewsletterCache();
$NewsletterCache->load_all();
$newsletters = $edited_Automation->get_newsletters();
foreach( $newsletters as $n => $newsletter )
{
	$Form->begin_line( T_('Tied to List').' <span class="evo_tied_list_number">'.( $n + 1 ).'</span>', '', '', array( 'required' => true ) );
		$Form->select_input_object( 'aunl_enlt_ID[]', $newsletter['ID'], $NewsletterCache, '', array( 'allow_none' => true ) );
		$Form->checkbox_input( 'aunl_autostart', $newsletter['autostart'], '', array( 'input_prefix' => '<label>', 'input_suffix' => ' '.T_('auto start on list subscribe').'</label> &nbsp; ' ) );
		$Form->checkbox_input( 'aunl_autoexit', $newsletter['autoexit'], '', array( 'input_prefix' => '<label>', 'input_suffix' => ' '.T_('auto exit on list unsubscribe').'</label>'
			.' &nbsp; <a href="#" class="evo_remove_tied_list">'.get_icon( 'minus' ).'</a>' ) );
	$Form->end_line();
}
// Initialize a template to add more newsletter fields by JS code below:
$Form->output = false;
$newsletter_fields = $Form->begin_line( T_('Tied to List').' <span class="evo_tied_list_number"></span>', '', '', array( 'required' => true ) )
		.$Form->select_input_object( 'aunl_enlt_ID[]', '', $NewsletterCache, '', array( 'allow_none' => true ) )
		.$Form->checkbox_input( 'aunl_autostart', 1, '', array( 'input_prefix' => '<label>', 'input_suffix' => ' '.T_('auto start on list subscribe').'</label> &nbsp; ' ) )
		.$Form->checkbox_input( 'aunl_autoexit', 1, '', array( 'input_prefix' => '<label>', 'input_suffix' => ' '.T_('auto exit on list unsubscribe').'</label>'
			.' &nbsp; <a href="#" class="evo_remove_tied_list">'.get_icon( 'minus' ).'</a>' ) )
	.$Form->end_line();
$Form->output = true;
// Display a button to add more newsletter:
$Form->info( '', '<button class="btn btn-default" type="button" id="evo_add_tied_list">'.get_icon( 'add' ).' '.T_('Tie to an additional list...').'</button>' );

$Form->username( 'autm_owner_login', $edited_Automation->get_owner_User(), T_('Owner'), '', '', array( 'required' => true ) );

$Form->end_form( array(
		array( 'submit', 'submit', ( $creating ? T_('Record') : T_('Save Changes!') ), 'SaveButton' )
	) );

?>
<script type="text/javascript">
jQuery( '#evo_add_tied_list' ).click( function()
{
	var list_num = jQuery( '[name="aunl_enlt_ID[]"]' ).length + 1;
	jQuery( this ).closest( 'div.form-group' ).before( '<?php echo format_to_js( $newsletter_fields ); ?>'.replace( '$num$', list_num ) );
	evo_automation_update_tied_list();
} );
jQuery( document ).on( 'click', '.evo_remove_tied_list', function()
{
	jQuery( this ).closest( 'div.form-group' ).remove();
	evo_automation_update_tied_list();
	return false;
} );
jQuery( document ).ready( function()
{
	evo_automation_update_tied_list();
} );
function evo_automation_update_tied_list()
{
	var list_number = 1;
	jQuery( '.evo_tied_list_number' ).each( function()
	{	// Reorder numbers of tied lists:
		jQuery( this ).html( list_number++ );
	} );
	if( list_number <= 2 )
	{	// Single tied list is required and cannot be deleted:
		jQuery( '.evo_remove_tied_list' ).hide();
		jQuery( '.evo_tied_list_number' ).parent().find( '.label_field_required' ).show();
	}
	else
	{	// Multiple tied lists are not required and can be deleted:
		jQuery( '.evo_remove_tied_list' ).show();
		jQuery( '.evo_tied_list_number' ).parent().find( '.label_field_required' ).hide();
	}
}
jQuery( 'form#automation_checkchanges' ).submit( function()
{
	var list_number = 0;
	jQuery( '[name=aunl_autostart]' ).each( function()
	{
		jQuery( this ).attr( 'name', 'aunl_autostart_' + ( list_number++ ) );
	} );
	list_number = 0;
	jQuery( '[name=aunl_autoexit]' ).each( function()
	{
		jQuery( this ).attr( 'name', 'aunl_autoexit_' + ( list_number++ ) );
	} );
} );
</script>