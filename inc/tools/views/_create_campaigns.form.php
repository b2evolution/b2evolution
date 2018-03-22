<?php
/**
 * This file display the form to create sample email campaigns for testing
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

global $num_campaigns, $campaign_lists, $send_campaign_emails;

$Form = new Form( NULL, 'create_campaigns', 'campaign', 'compact' );

$Form->global_icon( T_('Cancel').'!', 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform',  T_('Create sample email campaigns') );

	$Form->add_crumb( 'tools' );
	$Form->hidden( 'ctrl', 'tools' );
	$Form->hidden( 'action',  'create_sample_campaigns' );
	$Form->hidden( 'tab3', get_param( 'tab3' ) );

	$Form->text_input( 'num_campaigns', ( is_null( $num_campaigns ) ? 1000 : $num_campaigns ), 11, T_( 'How many email campaigns' ), '', array( 'maxlength' => 10, 'required' => true ) );

	$NewsletterCache = & get_NewsletterCache();
	$NewsletterCache->load_all();
	$list_options = array();
	foreach( $NewsletterCache->cache as $Newsletter )
	{
		$list_options[] = array( 'campaign_lists[]', $Newsletter->ID, $Newsletter->name, is_null( $campaign_lists ) || in_array( $Newsletter->ID, $campaign_lists ), 0 );
	}
	$Form->checklist( $list_options, 'campaign_lists', T_('Create new email campaigns in'), true, false,
		array( 'note' => T_('Note: For each campaign it creates, the tool will randomly select between the allowed (checked) options above') ) );

	$Form->checkbox( 'send_campaign_emails', is_null( $send_campaign_emails ) ? false : $send_campaign_emails, T_('Send test email') );

$Form->end_form( array( array( 'submit', 'submit', T_('Create'), 'SaveButton' ) ) );
?>