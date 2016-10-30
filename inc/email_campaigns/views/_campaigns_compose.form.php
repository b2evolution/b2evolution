<?php
/**
 * This file implements the UI view for Emails > Campaigns > Edit > HTML
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

global $admin_url, $tab;
global $edited_EmailCampaign, $Plugins, $UserSettings;

$Form = new Form( NULL, 'campaign_form' );
$Form->begin_form( 'fform' );

$Form->add_crumb( 'campaign' );
$Form->hidden( 'ctrl', 'campaigns' );
$Form->hidden( 'current_tab', $tab );
$Form->hidden( 'ecmp_ID', $edited_EmailCampaign->ID );

$Form->begin_fieldset( sprintf( T_('Compose message for: %s'), $edited_EmailCampaign->dget( 'name' ) ).get_manual_link( 'creating-an-email-campaign' ) );
	$Form->text_input( 'ecmp_email_title', $edited_EmailCampaign->get( 'email_title' ), 60, T_('Email title'), '', array( 'maxlength' => 255, 'required' => true ) );

	// Plugin toolbars:
	ob_start();
	echo '<div class="email_toolbars">';
	// CALL PLUGINS NOW:
	$Plugins->trigger_event( 'DisplayEmailToolbar' );
	echo '</div>';
	$email_toolbar = ob_get_clean();

	// Plugin buttons:
	ob_start();
	echo '<div class="edit_actions">';
	echo '<div class="pull-left" style="display: flex; flex-direction: row; align-items: center;">';
	// CALL PLUGINS NOW:
	$Plugins->trigger_event( 'AdminDisplayEditorButton', array(
			'target_type'   => 'EmailCampaign',
			'target_object' => $edited_EmailCampaign,
			'content_id'    => 'ecmp_email_text',
			'edit_layout'   => 'expert',
		) );

	echo '<div style="margin: 7px 0 0 5px; display: flex; align-items: center;">';
	ob_start();
	$Plugins->trigger_event( 'AdminDisplayEditorButton', array(
			'target_type'   => 'EmailCampaign',
			'target_object' => $edited_EmailCampaign,
			'content_id'    => 'ecmp_email_text',
			'edit_layout'   => 'expert_quicksettings',
		) );
	$quick_setting_switch = ob_get_flush();
	echo '</div>';

	echo '</div>';
	echo '</div>';
	$email_plugin_buttons = ob_get_clean();

	$form_inputstart = $Form->inputstart;
	$form_inputend = $Form->inputend;
	$Form->inputstart .= $email_toolbar;
	$Form->inputend = $email_plugin_buttons.$Form->inputend;
	$Form->textarea_input( 'ecmp_email_text', $edited_EmailCampaign->get( 'email_text' ), 20, T_('HTML Message'), array( 'required' => true ) );
	$Form->inputstart = $form_inputstart;
	$Form->inputend = $form_inputend;



	// set b2evoCanvas for plugins:
	echo '<script type="text/javascript">var b2evoCanvas = document.getElementById( "ecmp_email_text" );</script>';

	// Display renderers
	$current_renderers = !empty( $edited_EmailCampaign ) ? $edited_EmailCampaign->get_renderers_validated() : array( 'default' );
	$email_renderer_checkboxes = $Plugins->get_renderer_checkboxes( $current_renderers, array( 'setting_name' => 'email_apply_rendering' ) );
	if( !empty( $email_renderer_checkboxes ) )
	{
		$Form->info( T_('Text Renderers'), $email_renderer_checkboxes );
	}
$Form->end_fieldset();

$buttons = array();
if( $current_User->check_perm( 'emails', 'edit' ) )
{ // User must has a permission to edit emails
	$buttons[] = array( 'submit', 'actionArray[save]', T_('Save & continue').' >>', 'SaveButton' );
}
$Form->end_form( $buttons );

?>
<script type="text/javascript">
function toggleWYSIWYGSwitch( val )
{
	if( val )
	{
		jQuery( 'p#active_wysiwyg_switch' ).show();
		jQuery( 'p#disable_wysiwyg_switch' ).hide();
	}
	else
	{
		jQuery( 'p#active_wysiwyg_switch' ).hide();
		jQuery( 'p#disable_wysiwyg_switch' ).show();
	}
}
</script>