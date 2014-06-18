<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009-2014 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * The Evo Factory grants Francois PLANQUE the right to license
 * The Evo Factory's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore

 *
 * @version $Id: _display.form.php 6505 2014-04-17 15:11:37Z yura $
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

$Form->begin_fieldset( T_('Profile pictures').get_manual_link('profile-picture-settings') );

	$Form->checkbox_input( 'use_gravatar', $Settings->get('use_gravatar'), T_('Use gravatar'), array( 'note' => T_('Fall back to Gravatar if a user has not uploaded a profile picture.') ) );

	$default_avatar_unknown = get_default_avatar_url();
	$default_avatar_men = get_default_avatar_url( 'M' );
	$default_avatar_women = get_default_avatar_url( 'F' );
	$default_images_info = '<br />'.T_('For unknow gender').': <a href="'.$default_avatar_unknown.'" target="_blank">'.$default_avatar_unknown.'</a>';
	$default_images_info .= '<br />'.T_('For men').': <a href="'.$default_avatar_men.'" target="_blank">'.$default_avatar_men.'</a>';
	$default_images_info .= '<br />'.T_('For women').': <a href="'.$default_avatar_women.'" target="_blank">'.$default_avatar_women.'</a>';
	$Form->radio( 'default_gravatar', $Settings->get('default_gravatar'),
		array( array( 'b2evo', T_('Default image'), $default_images_info ),
					array( '', 'Gravatar' ),
					array( 'identicon', 'Identicon' ),
					array( 'monsterid', 'Monsterid' ),
					array( 'wavatar', 'Wavatar' ),
					array( 'retro', 'Retro' ),
		), T_('Default gravatars'), true, T_('Gravatar users can choose to set up a unique icon for themselves, and if they don\'t, they will be assigned a default image.') );

$Form->end_fieldset();

// --------------------------------------------

$Form->begin_fieldset( T_('Back-office display options').get_manual_link('user-backoffice-display-settings') );

		$Form->checkbox_input( 'gender_colored', $Settings->get('gender_colored'), T_('Display gender'), array( 'note'=>T_('Use colored usernames to differentiate men & women.') ) );

$Form->end_fieldset();

// --------------------------------------------

$Form->begin_fieldset( T_('Bubble tips in back-office').get_manual_link('user-bubble-tips-settings')  );

	$Form->checkbox_input( 'bubbletip', $Settings->get('bubbletip'), T_('Username bubble tips'), array( 'note'=>T_('Check to enable bubble tips on usernames') ) );

	if( isset($GLOBALS['files_Module']) )
	{
		$Form->select_input_array( 'bubbletip_size_admin', $Settings->get('bubbletip_size_admin') , get_available_thumb_sizes(), T_('Bubble tip image format'), '', $params );
	}

$Form->end_fieldset();

// --------------------------------------------

$Form->begin_fieldset( T_('Bubble tips in front-office for logged in users').get_manual_link('user-bubble-tips-settings') );

	$Form->info( T_('Note'), T_('Enable bubble tips in each skin\'s settings.') );

	if( isset($GLOBALS['files_Module']) )
	{
		$Form->select_input_array( 'bubbletip_size_front', $Settings->get('bubbletip_size_front') , get_available_thumb_sizes(), T_('Bubble tip image format'), '', $params );
	}

$Form->end_fieldset();

// --------------------------------------------

$Form->begin_fieldset( T_('Bubble tips for anonymous users').get_manual_link('user-bubble-tips-settings') );

	$Form->info( T_('Note'), T_('Enable bubble tips in each skin\'s settings.') );

	$Form->checkbox_input( 'bubbletip_anonymous', $Settings->get('bubbletip_anonymous'), T_('Allow to see bubbletips'), array( 'note'=>T_('Check to enable bubble tips on usernames') ) );

	if( isset($GLOBALS['files_Module']) )
	{
		$Form->select_input_array( 'bubbletip_size_anonymous', $Settings->get('bubbletip_size_anonymous') , get_available_thumb_sizes(), T_('Bubble tip image format'), '', $params );
	}

	$Form->textarea( 'bubbletip_overlay', $Settings->get( 'bubbletip_overlay' ), 5, T_('Image overlay text'), '', 20 );

$Form->end_fieldset();


// --------------------------------------------

$Form->begin_fieldset( T_('Other permissions for anonymous users').get_manual_link('anonymous-users-permissions') );

	$Form->checkbox_input( 'allow_anonymous_user_list', $Settings->get('allow_anonymous_user_list'), T_('Allow to see user list') );
	// Allow anonymous users to see the user display ( disp=user )
	$Form->checkbox_input( 'allow_anonymous_user_profiles', $Settings->get('allow_anonymous_user_profiles'), T_('Allow to see user profiles') );

$Form->end_fieldset();

// --------------------------------------------

if( $current_User->check_perm( 'users', 'edit' ) )
{
	$Form->end_form( array( array( 'submit', 'submit', T_('Save Changes!'), 'SaveButton' ) ) );
}

?>