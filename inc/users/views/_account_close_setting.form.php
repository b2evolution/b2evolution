<?php
/**
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

/**
 * @var User
 */
global $current_User;
/**
 * @var GeneralSettings
 */
global $Settings;

global $dispatcher;

global $collections_Module;

$Form = new Form( NULL, 'closing_checkchanges' );
$Form->begin_form( 'fform', '',
	// enable all form elements on submit (so values get sent):
	array( 'onsubmit'=>'var es=this.elements; for( var i=0; i < es.length; i++ ) { es[i].disabled=false; };' ) );

	$Form->add_crumb( 'accountclose' );
	$Form->hidden( 'ctrl', 'accountclose' );
	$Form->hidden( 'action', 'update' );

// --------------------------------------------

$Form->begin_fieldset( T_('Account closing').get_manual_link('account-closing-settings') );

	$Form->checkbox_input( 'account_close_enabled', $Settings->get( 'account_close_enabled' ), T_('Allow closing'), array( 'note' => T_('check to allow users to close their account themselves.') ) );

	$Form->textarea( 'account_close_intro', $Settings->get( 'account_close_intro' ), 5, T_('Intro text'), T_('Enter a message to display to users who want to close their account.'), 60 );

	$Form->textarea( 'account_close_reasons', $Settings->get( 'account_close_reasons' ), 5, T_('Closing reasons'), T_('Enter one possible reason per line. There will always be an "Other" reason added at the end.'), 60 );

	$Form->textarea( 'account_close_byemsg', $Settings->get( 'account_close_byemsg' ), 5, T_('Good-bye message'), T_('Enter a text to display after closing the account.'), 60 );

$Form->end_fieldset();

// --------------------------------------------

if( $current_User->check_perm( 'users', 'edit' ) )
{
	$Form->end_form( array( array( 'submit', 'submit', T_('Save Changes!'), 'SaveButton' ) ) );
}

?>