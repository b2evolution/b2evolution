<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var instance of GeneralSettings class
 */
global $Settings;
/**
 * @var instance of User class
 */
global $current_User;


$Form = new Form( NULL, 'usersettings_checkchanges' );

$Form->begin_form( 'fform', '' );

	$Form->add_crumb( 'user' );
	$Form->hidden( 'ctrl', 'usersettings' );
	$Form->hidden( 'action', 'update' );

$Form->begin_fieldset( T_('Users settings') );

	$Form->checkbox_input( 'allow_avatars', $Settings->get('allow_avatars'), T_('Allow avatars'), array( 'note'=>T_('Allow users to upload avatars.') ) );

	$Form->radio( 'uset_nickname_editing', $Settings->get( 'nickname_editing' ), array(
					array( 'edited-user', T_('Can be edited by user') ),
					array( 'edited-admin', T_('Can be edited by admins only') ),
					array( 'hidden', T_('Hidden') )
				), T_('Nickname'), true );

	$Form->radio( 'uset_multiple_sessions', $Settings->get( 'multiple_sessions' ), array(
					array( 'never', T_('Never allow') ),
					array( 'adminset_default_no', T_('Let admins decide for each user, default to "no" for new users') ),
					array( 'userset_default_no', T_('Let users decide, default to "no" for new users') ),
					array( 'userset_default_yes', T_('Let users decide, default to "yes" for new users') ),
					array( 'adminset_default_yes', T_('Let admins decide for each user, default to "yes" for new users') ),
					array( 'always', T_('Always allow') )
				), T_('Multiple sessions'), true );

$Form->end_fieldset();

if( $current_User->check_perm( 'options', 'edit' ) )
{
	$Form->buttons( array( array( 'submit', 'submit', T_('Save !'), 'SaveButton' ),
													array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
}

$Form->end_form();

/*
 * $Log$
 * Revision 1.7  2010/01/03 13:45:37  fplanque
 * set some crumbs (needs checking)
 *
 * Revision 1.6  2009/12/12 19:14:12  fplanque
 * made avatars optional + fixes on img props
 *
 * Revision 1.5  2009/10/28 13:41:57  efy-maxim
 * default multiple sessions settings
 *
 * Revision 1.4  2009/10/26 12:59:37  efy-maxim
 * users management
 *
 * Revision 1.3  2009/10/25 19:24:51  efy-maxim
 * multiple_sessions param
 *
 * Revision 1.2  2009/10/25 19:20:30  efy-maxim
 * users settings
 *
 * Revision 1.1  2009/10/25 15:22:48  efy-maxim
 * user - identity, password, preferences tabs
 *
 */
?>