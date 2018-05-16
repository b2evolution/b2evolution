<?php
/**
 * This file implements the UI view for Emails > Campaigns > New
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

global $action;

$enlt_ID = param( 'enlt_ID', 'integer' );
$Form = new Form( NULL, 'campaign' );
$Form->begin_form( 'fform' );

$Form->add_crumb( 'campaign' );
$Form->hidden( 'ctrl', 'campaigns' );
$Form->hidden( 'action', $action == 'copy' ? 'duplicate' : 'add' );

if( $action == 'copy' )
{
	global $edited_EmailCampaign;
	$fieldset_title = T_('Duplicate campaign').get_manual_link( 'duplicating-an-email-campaign' );
	if( empty( $enlt_ID ) )
	{ // No list specified, use list of original campaign
		$enlt_ID = $edited_EmailCampaign->enlt_ID;
	}
}
else
{
	$fieldset_title = T_('New campaign').get_manual_link( 'creating-an-email-campaign' );
}

$Form->begin_fieldset( $fieldset_title );
	$NewsletterCache = & get_NewsletterCache();
	$NewsletterCache->load_where( 'enlt_active = 1' );
	$Form->select_input_object( 'ecmp_enlt_ID', $enlt_ID, $NewsletterCache, T_('Send to subscribers of'), array( 'required' => true ) );
	if( isset( $edited_EmailCampaign ) )
	{
		$campaign_name = $edited_EmailCampaign->get( 'name' );
	}
	else
	{
		$campaign_name = '';
	}
	$Form->text_input( 'ecmp_name', $campaign_name, 60, T_('Campaign name'), T_('for internal use'), array( 'maxlength' => 255, 'required' => true ) );
$Form->end_fieldset();

if( $action == 'copy' )
{
	$Form->hidden( 'ecmp_ID', $edited_EmailCampaign->ID );
	$buttons[] = array( 'submit', 'submit', sprintf( T_('Save and duplicate all settings from %s'), $edited_EmailCampaign->get( 'name' ) ), 'SaveButton' );
}
else
{
	$buttons[] = array( 'submit', 'submit', T_('Create campaign'), 'SaveButton' );
}
$Form->end_form( $buttons );

?>