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

$current_User->check_perm( 'users', 'view', true );

$Form = new Form( NULL, 'usersettings_checkchanges' );

$Form->begin_form( 'fform', '' );

	$Form->add_crumb( 'usersettings' );
	$Form->hidden( 'ctrl', 'usersettings' );
	$Form->hidden( 'action', 'update' );

$Form->begin_fieldset( T_('Session Settings').get_manual_link('session-settings') );

	$Form->text_input( 'redirect_to_after_login', $Settings->get( 'redirect_to_after_login' ), 60, T_('After login, redirect to'), T_('Users will be redirected there upon successful login, unless they are in process of doing something.'), array( 'maxlength' => NULL ) );

	// fp>TODO: enhance UI with a general Form method for Days:Hours:Minutes:Seconds

	$Form->duration_input( 'timeout_sessions', $Settings->get('timeout_sessions'), T_('Session timeout'), 'months', 'seconds',
						array( 'minutes_step' => 1, 'required' => true, 'note' => T_( 'If the user stays inactive for this long, he will have to log in again.' ) ) );
	// $Form->text_input( 'timeout_sessions', $Settings->get('timeout_sessions'), 9, T_('Session timeout'), T_('seconds. How long can a user stay inactive before automatic logout?'), array( 'required'=>true) );

	// fp>TODO: It may make sense to have a different (smaller) timeout for sessions with no logged user.
	// fp>This might reduce the size of the Sessions table. But this needs to be checked against the hit logging feature.

	$Form->duration_input( 'timeout_online', $Settings->get('timeout_online'), T_('Online/Offline timeout'), 'hours', 'seconds',
						array( 'minutes_step' => 1, 'required' => true, 'note' => T_( 'If the user stays inactive for this long, we will no longer display him as "online" and we will start sending him email notifications when things happen while he is away.' ) ) );
$Form->end_fieldset();

$Form->begin_fieldset( T_('User latitude').get_manual_link('user-profile-latitude-settings') );

	$Form->checkbox_input( 'allow_avatars', $Settings->get( 'allow_avatars', true ), T_('Allow profile pictures'), array( 'note'=>T_('Allow users to upload profile pictures.') ) );

	$Form->text_input( 'uset_min_picture_size', $Settings->get( 'min_picture_size' ), 5, T_('Minimum picture size'), '', array( 'note' => T_('pixels (width and height)') ) );

	$name_editing_options = array(
			array( 'edited-user', T_('Can be edited by user') ),
			array( 'edited-user-required', T_('Can be edited by user + required') ),
			array( 'edited-admin', T_('Can be edited by admins only') ),
			array( 'hidden', T_('Hidden') )
		);

	$Form->radio( 'uset_nickname_editing', $Settings->get( 'nickname_editing' ), $name_editing_options, T_('Nickname'), true );

	$Form->radio( 'uset_firstname_editing', $Settings->get( 'firstname_editing' ), $name_editing_options, T_('Fistname'), true );

	$Form->radio( 'uset_lastname_editing', $Settings->get( 'lastname_editing' ), $name_editing_options, T_('Lastname'), true );

	$location_options = array(
			array( 'optional', T_('Optional') ),
			array( 'required', T_('Required') ),
			array( 'hidden', T_('Hidden') )
		);

	$Form->radio( 'uset_location_country', $Settings->get( 'location_country' ), $location_options, T_('Country') );

	$Form->radio( 'uset_location_region', $Settings->get( 'location_region' ), $location_options, T_('Region') );

	$Form->radio( 'uset_location_subregion', $Settings->get( 'location_subregion' ), $location_options, T_('Sub-region') );

	$Form->radio( 'uset_location_city', $Settings->get( 'location_city' ), $location_options, T_('City') );

	$Form->text_input( 'uset_minimum_age', $Settings->get( 'minimum_age' ), 3, T_('Minimum age'), '', array( 'input_suffix' => ' '.T_('years old.') ) );

	$Form->radio( 'uset_multiple_sessions', $Settings->get( 'multiple_sessions' ), array(
					array( 'never', T_('Never allow') ),
					array( 'adminset_default_no', T_('Let admins decide for each user, default to "no" for new users') ),
					array( 'userset_default_no', T_('Let users decide, default to "no" for new users') ),
					array( 'userset_default_yes', T_('Let users decide, default to "yes" for new users') ),
					array( 'adminset_default_yes', T_('Let admins decide for each user, default to "yes" for new users') ),
					array( 'always', T_('Always allow') )
				), T_('Multiple sessions'), true );

	$Form->radio( 'uset_emails_msgform', $Settings->get( 'emails_msgform' ), array(
					array( 'never', T_('Never allow') ),
					array( 'adminset', T_('Let admins decide for each user, default set on Registration tab') ),
					array( 'userset', T_('Let users decide, default set on Registration tab') ),
				), T_('Receiving emails through a message form'), true );

$Form->end_fieldset();

if( $current_User->check_perm( 'users', 'edit' ) )
{
	$Form->buttons( array( array( 'submit', 'submit', T_('Save Changes!'), 'SaveButton' ) ) );
}

$Form->end_form();


load_funcs( 'regional/model/_regional.funcs.php' );
echo_regional_required_js( 'uset_location_' );

?>