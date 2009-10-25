<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var instance of User class
 */
global $current_User;

$Form = & new Form( NULL, 'usersettings_checkchanges' );

$Form->begin_form( 'fform', T_('Edit settings') );

$Form->begin_fieldset( T_('Nickname') );

$Form->radio( 'uset_nickname', 'a', array(
					array( 'a', T_('Can be edited by user') ),
					array( 'b', T_('Can be edited by admins only') ),
					array( 'c', T_('Hidden') )
				), T_('Nickname'), true );

$Form->end_fieldset();

$Form->begin_fieldset( T_('Multiple sessions') );

$Form->radio( 'uset_multiple_sessions', 'a', array(
					array( 'never', T_('Never allow') ),
					array( 'default-no', T_('Let users decide, default to "no" for new users') ),
					array( 'default-yes', T_('Let users decide, default to "yes" for new users') ),
					array( 'always', T_('Always allow') )
				), T_('Nickname'), true );

$Form->end_fieldset();

if( $current_User->check_perm( 'options', 'edit' ) )
{
	$Form->buttons( array( array( 'submit', 'submit', T_('Save !'), 'SaveButton' ),
													array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
}

$Form->end_form();

/*
 * $Log$
 * Revision 1.1  2009/10/25 15:22:48  efy-maxim
 * user - identity, password, preferences tabs
 *
 */
?>