<?php
/**
 * This file implements the UI view for the user export form.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var instance of GeneralSettings class
 */
global $Settings;
/**
 * @var instance of UserSettings class
 */
global $UserSettings;
/**
 * @var instance of User class
 */
global $edited_User;
/**
 * @var current action
 */
global $action;
/**
 * @var user permission, if user is only allowed to edit his profile
 */
global $user_profile_only;
/**
 * @var Plugins
 */
global $Plugins;
/**
 * $var AdminUI
 */
global $AdminUI;
/**
 * @var the action destination of the form (NULL for pagenow)
 */
global $form_action;


// Default params:
$default_params = array(
		'skin_form_params'     => array(),
		'form_class_user_pref' => 'bComment',
	);

if( isset( $params ) )
{	// Merge with default params
	$params = array_merge( $default_params, $params );
}
else
{	// Use a default params
	$params = $default_params;
}

// ------------------- PREV/NEXT USER LINKS -------------------
user_prevnext_links( array(
		'user_tab' => 'export'
	) );
// ------------- END OF PREV/NEXT USER LINKS -------------------

$Form = new Form( $form_action, 'user_checkchanges' );

$Form->switch_template_parts( $params['skin_form_params'] );

if( ! $user_profile_only )
{
	echo_user_actions( $Form, $edited_User, $action );
}

$Form->title_fmt = '<div class="row"><span class="col-xs-12 col-lg-6 col-lg-push-6 text-right">$global_icons$</span><div class="col-xs-12 col-lg-6 col-lg-pull-6">$title$</div></div>'."\n";

$Form->begin_form( 'fform', get_usertab_header( $edited_User, 'export', '<span class="nowrap">'.T_('Export').'</span>'.get_manual_link( 'user-export-xml-zip' ) ) );

	$Form->add_crumb( 'user' );
	$Form->hidden_ctrl();
	$Form->hidden( 'user_tab', 'export' );

	$Form->hidden( 'user_ID', $edited_User->ID );
	$Form->hidden( 'edited_user_login', $edited_User->login );

$Form->begin_fieldset( T_('Export to XML/ZIP file').get_manual_link( 'export-xml-zip' ), array( 'class'=>'fieldset clear' ) );

$Form->checklist( array(
		array( 'options[pass]', 1, T_('Include (md5-hashed) user passwords in export'), isset( $options['pass'] ) ? 1 : 0 ),
		array( 'options[avatar]', 1, T_('Include profile pictures'), isset( $options['avatar'] ) ? 1 : 0, 0, T_('will be included in ZIP file') ),
	), 'export_options', T_('Select what to export') );

$Form->end_fieldset();

$Form->end_form( array( array( 'submit', 'actionArray[export]', T_('Download XML/ZIP file'), 'SaveButton' ) ) );
?>