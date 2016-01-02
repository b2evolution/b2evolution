<?php
/**
 * This file implements the UI view for the user properties.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var instance of GeneralSettings class
 */
global $Settings;
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
 * @var the action destination of the form (NULL for pagenow)
 */
global $form_action;
/**
 * @var instance of User class
 */
global $current_User;

global $Session;

// check if reqID exists. If exists it means that this form is displayed because a password change request by email.
$reqID = param( 'reqID', 'string', '' );

// Default params:
$default_params = array(
		'skin_form_params'     => array(),
		'form_class_user_pass' => 'bComment',
		'display_abandon_link' => true,
		'button_class'         => '',
		'form_button_action'   => 'update',
		'form_hidden_crumb'    => 'user',
		'form_hidden_reqID'    => $reqID,
	);

if( isset( $params ) )
{ // Merge with default params
	$params = array_merge( $default_params, $params );
}
else
{ // Use a default params
	$params = $default_params;
}

// ------------------- PREV/NEXT USER LINKS -------------------
user_prevnext_links( array(
		'user_tab' => 'pwdchange'
	) );
// ------------- END OF PREV/NEXT USER LINKS -------------------

$Form = new Form( $form_action, 'user_checkchanges' );

$Form->switch_template_parts( $params['skin_form_params'] );

if( !$user_profile_only )
{
	echo_user_actions( $Form, $edited_User, $action );
}

$is_admin = is_admin_page();
if( $is_admin )
{
	$form_text_title = T_( 'Change password' ).get_manual_link( 'user-password-tab' ); // used for js confirmation message on leave the changed form
	$form_title = get_usertab_header( $edited_User, 'pwdchange', $form_text_title );
	$form_class = 'fform';
	$Form->title_fmt = '<span style="float:right">$global_icons$</span><div>$title$</div>'."\n";
}
else
{
	$form_title = '';
	$form_class = $params['form_class_user_pass'];
}

$has_full_access = $current_User->check_perm( 'users', 'edit' );


$Form->begin_form( $form_class, $form_title, array( 'title' => ( isset( $form_text_title ) ? $form_text_title : $form_title ) ) );

	$Form->add_crumb( $params['form_hidden_crumb'] );
	$Form->hidden_ctrl();
	$Form->hidden( 'user_tab', 'pwdchange' );
	$Form->hidden( 'password_form', '1' );
	$Form->hidden( 'reqID', $reqID );

	$Form->hidden( 'user_ID', $edited_User->ID );
	$Form->hidden( 'edited_user_login', $edited_User->login );
	if( isset( $Blog ) )
	{
		$Form->hidden( 'blog', $Blog->ID );
	}

	/***************  Password  **************/

if( $action != 'view' )
{ // We can edit the values:

	$Form->begin_fieldset( $is_admin ? T_('Password').get_manual_link( 'user-password-tab' ) : '', array( 'class'=>'fieldset clear' ) );

		// current password is not required:
		//   - password change requested by email
		if( empty( $reqID ) || $reqID != $Session->get( 'core.changepwd.request_id' ) )
		{
			if( ! $has_full_access || $edited_User->ID == $current_User->ID )
			{ // Current user has no full access or editing his own pasword
				$Form->password_input( 'current_user_pass', '', 20, T_('Current password'), array( 'maxlength' => 50, 'required' => ($edited_User->ID == 0), 'autocomplete'=>'off', 'style' => 'width:163px' ) );
			}
			else
			{ // Ask password of current admin
				$Form->password_input( 'current_user_pass', '', 20, T_('Enter your current password'), array( 'maxlength' => 50, 'required' => ($edited_User->ID == 0), 'autocomplete'=>'off', 'style' => 'width:163px', 'note' => sprintf( T_('We ask for <b>your</b> (%s) <i>current</i> password as an additional security measure.'), $current_User->get( 'login' ) ) ) );
			}
		}
		$Form->password_input( 'edited_user_pass1', '', 20, T_('New password'), array( 'note' => sprintf( T_('Minimum length: %d characters.'), $Settings->get('user_minpwdlen') ), 'maxlength' => 50, 'required' => ($edited_User->ID == 0), 'autocomplete'=>'off' ) );
		$Form->password_input( 'edited_user_pass2', '', 20, T_('Confirm new password'), array( 'maxlength' => 50, 'required' => ($edited_User->ID == 0), 'autocomplete'=>'off', 'note' => '<span id="pass2_status" class="red"></span>' ) );

	$Form->end_fieldset();
}

	/***************  Buttons  **************/

if( $action != 'view' )
{ // Edit buttons
	$Form->buttons( array( array( '', 'actionArray['.$params['form_button_action'].']', T_('Change password!'), 'SaveButton'.$params['button_class'] ) ) );
}

if( $params['display_abandon_link'] )
{ // Display a link to go away from this form
	$Form->info( '', '<div><a href="'.regenerate_url( 'disp', 'disp=profile' ).'">'.T_( 'Abandon password change' ).'</a></div>' );
}


$Form->end_form();

// Display javascript password strength indicator bar
display_password_indicator( array(
			'pass1-id'    => 'edited_user_pass1',
			'pass2-id'    => 'edited_user_pass2',
			'login-id'    => 'edited_user_login',
			'field-width' => 165,
	) );

?>