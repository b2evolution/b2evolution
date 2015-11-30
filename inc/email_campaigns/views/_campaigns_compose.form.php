<?php
/**
 * This file implements the UI view for Emails > Campaigns > Edit > HTML
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $admin_url, $tab;
global $edited_EmailCampaign, $Plugins;

$Form = new Form( NULL, 'campaign_form' );
$Form->begin_form( 'fform' );

$Form->add_crumb( 'campaign' );
$Form->hidden( 'ctrl', 'campaigns' );
$Form->hidden( 'current_tab', $tab );
$Form->hidden( 'ecmp_ID', $edited_EmailCampaign->ID );

$Form->begin_fieldset( sprintf( T_('Compose message for: %s'), $edited_EmailCampaign->get( 'name' ) ).get_manual_link( 'creating-an-email-campaign' ) );
	$Form->text_input( 'ecmp_email_title', $edited_EmailCampaign->get( 'email_title' ), 60, T_('Email title'), '', array( 'maxlength' => 255, 'required' => true ) );

	ob_start();
	echo '<div class="email_toolbars">';
	// CALL PLUGINS NOW:
	$Plugins->trigger_event( 'DisplayEmailToolbar' );
	echo '</div>';
	$email_toolbar = ob_get_clean();

	$form_inputstart = $Form->inputstart;
	$Form->inputstart .= $email_toolbar;
	$Form->textarea_input( 'ecmp_email_text', $edited_EmailCampaign->get( 'email_text' ), 20, T_('HTML Message'), array( 'required' => true ) );
	$Form->inputstart = $form_inputstart;

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