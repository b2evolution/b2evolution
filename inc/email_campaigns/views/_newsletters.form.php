<?php
/**
 * This file implements the UI view for Emails > Newsletters > Edit
 *
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

global $action, $edited_Newsletter;

$creating = is_create_action( $action );

$Form = new Form( NULL, 'newsletter_form' );

if( ! $creating && check_user_perm( 'emails', 'edit' ) )
{	// Display a button to delete existing newsletter if current User has a perm:
	$Form->global_icon( TB_('Delete this list!'), 'delete', regenerate_url( 'action', 'action=delete&amp;'.url_crumb( 'newsletter' ) ) );
}
// Display a button to close the newsletter form:
$Form->global_icon( TB_('Cancel editing!'), 'close', regenerate_url( 'action,enlt_ID' ) );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'newsletter' );
$Form->hidden( 'ctrl', 'newsletters' );
$Form->hidden( 'enlt_ID', $edited_Newsletter->ID );
$Form->hidden( 'action',  $creating ? 'create' : 'update' );

$Form->begin_fieldset( ( $creating ? TB_('Create list') : TB_('List') ).get_manual_link( 'editing-an-email-list' ) );

$Form->checkbox( 'enlt_active', $edited_Newsletter->get( 'active' ), TB_('Active') );

$Form->text_input( 'enlt_name', $edited_Newsletter->get( 'name' ), 30, TB_('Name'), '', array( 'maxlength' => 255, 'required' => true ) );

$Form->text_input( 'enlt_label', $edited_Newsletter->get( 'label' ), 150, TB_('Label'), '', array( 'maxlength' => 255 ) );

$Form->text_input( 'enlt_order', $edited_Newsletter->get( 'order' ), 10, TB_('Order'), '', array( 'maxlength' => 11 ) );

$Form->username( 'enlt_owner_login', $edited_Newsletter->get_owner_User(), TB_('Owner'), '', '', array( 'required' => true ) );

$Form->end_fieldset();

$Form->begin_fieldset( TB_('Permissions').get_manual_link( 'email-list-permissions' ) );

$Form->radio_input( 'enlt_perm_subscribe', $edited_Newsletter->get( 'perm_subscribe' ), array(
		array( 'value' => 'admin',  'label' => TB_('Forbidden (Only admin can subscribe users)') ),
		array( 'value' => 'anyone', 'label' => TB_('Anyone can subscribe') ),
		array( 'value' => 'group',  'label' => TB_('Only for members of any of the following groups can subscribe:') ),
	), TB_('Self-subscribing'), array( 'lines' => true ) );

	$GroupCache = & get_GroupCache();
	$perm_groups = $edited_Newsletter->get( 'perm_groups' ) != '' ? explode( ',', $edited_Newsletter->get( 'perm_groups' ) ) : array();
	$groups_options = array();
	$groups = $GroupCache->get_option_array();
	foreach( $groups as $group_ID => $group_name )
	{
		$groups_options[] = array( 'enlt_perm_groups[]', $group_ID, $group_name, in_array( $group_ID, $perm_groups ), $edited_Newsletter->get( 'perm_subscribe' ) != 'group' );
	}
	$Form->checklist( $groups_options, 'enlt_perm_groups', TB_('Allowed User Groups') );

$Form->end_fieldset();

$buttons = array();
if( check_user_perm( 'emails', 'edit' ) )
{	// Display a button to create/update newsletter if current User has a perm:
	if( $creating )
	{	// Create:
		$buttons[] = array( 'submit', 'actionArray[create]', TB_('Record'), 'SaveButton' );
	}
	else
	{	// Update:
		$buttons[] = array( 'submit', 'actionArray[update]', TB_('Save Changes!'), 'SaveButton' );
	}
}
$Form->end_form( $buttons );
?>
<script>
jQuery( '[name=enlt_perm_subscribe]' ).click( function()
{
	jQuery( '[name="enlt_perm_groups[]"]' ).prop( 'disabled', jQuery( this ).val() != 'group' );
} );
</script>