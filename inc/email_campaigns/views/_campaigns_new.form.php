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

$Form = new Form( NULL, 'campaign' );
$Form->begin_form( 'fform' );

$Form->add_crumb( 'campaign' );
$Form->hidden( 'ctrl', 'campaigns' );
$Form->hidden( 'action', 'add' );

$Form->begin_fieldset( T_('New campaign').get_manual_link( 'creating-an-email-campaign' ) );
	$Form->text_input( 'ecmp_name', '', 60, T_('Name'), '', array( 'maxlength' => 255, 'required' => true ) );
$Form->end_fieldset();

$Form->end_form( array( array( 'submit', 'submit', T_('Create campaign and select recipients'), 'SaveButton' ) ) );

?>