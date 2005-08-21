<?php
/**
 * This file implements the UI view for the general settings.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

$Form = & new Form( 'reg_settings.php', 'form' );

$Form->begin_form( 'fform', T_('Registration Settings') );

$Form->hidden( 'action', 'update' );
$Form->hidden( 'tab', 'general' );

// --------------------------------------------

$Form->fieldset( T_('Default user rights') );

	$Form->checkbox( 'newusers_canregister', $Settings->get('newusers_canregister'), T_('New users can register'), T_('Check to allow new users to register themselves.' ) );

	$Form->select_object( 'newusers_grp_ID', $Settings->get('newusers_grp_ID'), $GroupCache, T_('Group for new users'), T_('Groups determine user roles and permissions.') );

	$Form->text( 'newusers_level', $Settings->get('newusers_level'), 1, T_('Level for new users'), T_('Levels determine hierarchy of users in blogs.' ), 1 );

$Form->fieldset_end();

//	-------------------------------------------

$Form->fieldset( T_('Default user rights') );

	$Form->checkbox( 'use_rules', $Settings->get('use_rules'), T_('Use rules'), T_('Check to use rules during registration.' ) );

	$Form->textarea( 'the_rules', $Settings->get('the_rules'), 10 , T_('The rules'), T_('These are the rules that must be agreed to on registration' ) );

	$Form->textarea( 'confmail', $Settings->get('conf_email') , 10 , T_('Confirmation email'), T_('[name] and [link] will be replaced as appropriate' ) );

$Form->fieldset_end();

if( $current_User->check_perm( 'options', 'edit' ) )
{
	$Form->end_form( array( array( 'submit', 'submit', T_('Save !'), 'SaveButton' ),
													array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
}

?>