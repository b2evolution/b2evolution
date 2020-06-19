<?php
/**
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

/**
 * @var GeneralSettings
 */
global $Settings;

global $collections_Module;

$Form = new Form( NULL, 'settings_checkchanges' );
$Form->begin_form( 'fform', '',
	// enable all form elements on submit (so values get sent):
	array( 'onsubmit'=>'var es=this.elements; for( var i=0; i < es.length; i++ ) { es[i].disabled=false; };' ) );

	$Form->add_crumb( 'display' );
	$Form->hidden( 'ctrl', 'display' );
	$Form->hidden( 'action', 'update' );
	$Form->hidden( 'tab', 'display' );

	if( isset($GLOBALS['files_Module']) )
	{
		load_funcs( 'files/model/_image.funcs.php' );
		$params['force_keys_as_values'] = true;
	}

// --------------------------------------------

$Form->begin_fieldset( TB_('Profile pictures').get_manual_link('profile-picture-settings') );

	$Form->checkbox_input( 'use_gravatar', $Settings->get('use_gravatar'), TB_('Use gravatar'), array( 'note' => TB_('Fall back to Gravatar if a user has not uploaded a profile picture.') ) );

	$default_avatar_unknown = get_default_avatar_url();
	$default_avatar_men = get_default_avatar_url( 'M' );
	$default_avatar_women = get_default_avatar_url( 'F' );
	$default_images_info = '<br />'.TB_('For unknown gender').': <a href="'.$default_avatar_unknown.'" target="_blank">'.$default_avatar_unknown.'</a>';
	$default_images_info .= '<br />'.TB_('For men').': <a href="'.$default_avatar_men.'" target="_blank">'.$default_avatar_men.'</a>';
	$default_images_info .= '<br />'.TB_('For women').': <a href="'.$default_avatar_women.'" target="_blank">'.$default_avatar_women.'</a>';
	$Form->radio( 'default_gravatar', $Settings->get('default_gravatar'),
		array( array( 'b2evo', TB_('Default image'), $default_images_info ),
					array( '', 'Gravatar' ),
					array( 'identicon', 'Identicon' ),
					array( 'monsterid', 'Monsterid' ),
					array( 'wavatar', 'Wavatar' ),
					array( 'retro', 'Retro' ),
		), TB_('Default gravatars'), true, TB_('Gravatar users can choose to set up a unique icon for themselves, and if they don\'t, they will be assigned a default image.') );

$Form->end_fieldset();

// --------------------------------------------

$Form->begin_fieldset( TB_('Username display options').get_manual_link('user-username-display-options') );

	$Form->radio( 'username_display', $Settings->get( 'username_display' ),
		array( array( 'login', TB_('Usernames/Logins'), TB_('Most secure option') ),
					array( 'name', TB_('Friendly names (Nickname or Firstname if available)'), TB_('WARNING: this may allow users to fake their identity') ),
		), TB_('What to display'), true );

		$Form->checkbox_input( 'gender_colored', $Settings->get('gender_colored'), TB_('Display gender in back-office'), array( 'note'=>TB_('Use colored usernames to differentiate men & women.') ) );

$Form->end_fieldset();

// --------------------------------------------

$Form->begin_fieldset( TB_('Username display in back-office').get_manual_link('user-username-display-backoffice')  );

	$Form->checkbox_input( 'bubbletip', $Settings->get('bubbletip'), TB_('Username bubble tips'), array( 'note'=>TB_('Check to enable bubble tips on usernames') ) );

	if( isset($GLOBALS['files_Module']) )
	{
		$Form->select_input_array( 'bubbletip_size_admin', $Settings->get('bubbletip_size_admin') , get_available_thumb_sizes(), TB_('Bubble tip image format'), '', $params );
	}

$Form->end_fieldset();

// --------------------------------------------

$Form->begin_fieldset( TB_('Username display for logged-in users (front-office)').get_manual_link('user-username-display-frontoffice') );

	$Form->radio( 'user_url_loggedin', $Settings->get( 'user_url_loggedin' ),
		array( array( 'page', TB_('Always user page'), '' ),
					array( 'url', TB_('User website if available (fallback to user page)'), '' ),
		), TB_('Link to'), true );

	$Form->info( TB_('Note'), TB_('Enable bubble tips in each skin\'s settings.') );

	if( isset($GLOBALS['files_Module']) )
	{
		$Form->select_input_array( 'bubbletip_size_front', $Settings->get('bubbletip_size_front') , get_available_thumb_sizes(), TB_('Bubble tip image format'), '', $params );
	}

$Form->end_fieldset();

// --------------------------------------------

$Form->begin_fieldset( TB_('Username display for anonymous user (front-office)').get_manual_link('user-username-display-anonymous') );

	$Form->radio( 'user_url_anonymous', $Settings->get( 'user_url_anonymous' ),
		array( array( 'page', TB_('Always user page (if allowed)'), '' ),
					array( 'url', TB_('User website if available (fallback to user page)'), '' ),
		), TB_('Link to'), true );

	// Allow anonymous users to see the user display ( disp=user )
	$Form->checkbox_input( 'allow_anonymous_user_profiles', $Settings->get('allow_anonymous_user_profiles'), TB_('Allow to see user profiles') );

	$Form->info( TB_('Note'), TB_('Enable bubble tips in each skin\'s settings.') );

	$Form->checkbox_input( 'bubbletip_anonymous', $Settings->get('bubbletip_anonymous'), TB_('Allow to see bubbletips'), array( 'note'=>TB_('Check to enable bubble tips on usernames') ) );

	if( isset($GLOBALS['files_Module']) )
	{
		$Form->select_input_array( 'bubbletip_size_anonymous', $Settings->get('bubbletip_size_anonymous') , get_available_thumb_sizes(), TB_('Bubble tip image format'), '', $params );
	}

	$Form->textarea( 'bubbletip_overlay', $Settings->get( 'bubbletip_overlay' ), 5, TB_('Image overlay text'), '', 20 );

$Form->end_fieldset();


// --------------------------------------------

$Form->begin_fieldset( TB_('Other permissions for anonymous users').get_manual_link('anonymous-users-permissions') );

	$Form->checkbox_input( 'allow_anonymous_user_list', $Settings->get('allow_anonymous_user_list'), TB_('Allow to see user list') );

	$user_level_params = array();
	if( ! $Settings->get('allow_anonymous_user_list') && ! $Settings->get('allow_anonymous_user_profiles') )
	{ // Disable the user groups levels interval because the users pages are not available for anonymous users
		$user_level_params['disabled'] = 'disabled';
	}
	$Form->begin_line( TB_('Show only User Groups Levels'), 'allow_anonymous_user_level_min' );
		$Form->text_input( 'allow_anonymous_user_level_min', $Settings->get('allow_anonymous_user_level_min'), 2, TB_('from'), '', $user_level_params );
		$Form->text_input( 'allow_anonymous_user_level_max', $Settings->get('allow_anonymous_user_level_max'), 2, TB_('to'), '', $user_level_params );
	$Form->end_line();

$Form->end_fieldset();

// --------------------------------------------

if( check_user_perm( 'users', 'edit' ) )
{
	$Form->end_form( array( array( 'submit', 'submit', TB_('Save Changes!'), 'SaveButton' ) ) );
}

?>
<script>
jQuery( '#allow_anonymous_user_list, #allow_anonymous_user_profiles' ).click( function()
{
	if( ! jQuery( '#allow_anonymous_user_list' ).is( ':checked' ) && ! jQuery( '#allow_anonymous_user_profiles' ).is( ':checked' ) )
	{ // Disable the user groups levels interval, If the users pages are not available for anonymous users
		jQuery( '#allow_anonymous_user_level_min' ).attr( 'disabled', 'disabled' );
		jQuery( '#allow_anonymous_user_level_max' ).attr( 'disabled', 'disabled' );
	}
	else
	{ // Enable the user groups levels interval
		jQuery( '#allow_anonymous_user_level_min' ).removeAttr( 'disabled' );
		jQuery( '#allow_anonymous_user_level_max' ).removeAttr( 'disabled' );
	}
} );
</script>