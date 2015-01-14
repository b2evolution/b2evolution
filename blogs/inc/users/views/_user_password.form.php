<?php
/**
 * This file implements the UI view for the user properties.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
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
 *
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-maxim: Evo Factory / Maxim.
 * @author fplanque: Francois PLANQUE
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id: _user_password.form.php 7878 2014-12-23 11:54:05Z yura $
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

// Default params:
$default_params = array(
		'skin_form_params' => array(),
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
		'user_tab' => 'pwdchange'
	) );
// ------------- END OF PREV/NEXT USER LINKS -------------------

// check if reqID exists. If exists it means that this form is displayed because a password change request by email.
$reqID = param( 'reqID', 'string', '' );

$Form = new Form( $form_action, 'user_checkchanges' );

$Form->switch_template_parts( $params['skin_form_params'] );

if( !$user_profile_only )
{
	echo_user_actions( $Form, $edited_User, $action );
}

$is_admin = is_admin_page();
if( $is_admin )
{
	$form_text_title = T_( 'Change password' ); // used for js confirmation message on leave the changed form
	$form_title = get_usertab_header( $edited_User, 'pwdchange', $form_text_title );
	$form_class = 'fform';
	$Form->title_fmt = '<span style="float:right">$global_icons$</span><div>$title$</div>'."\n";
}
else
{
	$form_title = '';
	$form_class = 'bComment';
}

$has_full_access = $current_User->check_perm( 'users', 'edit' );


$Form->begin_form( $form_class, $form_title, array( 'title' => ( isset( $form_text_title ) ? $form_text_title : $form_title ) ) );

	$Form->add_crumb( 'user' );
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

	$Form->begin_fieldset( $is_admin ? T_('Password') : '', array( 'class'=>'fieldset clear' ) );

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
				$Form->password_input( 'current_user_pass', '', 20, T_('Enter your current password'), array( 'maxlength' => 50, 'required' => ($edited_User->ID == 0), 'autocomplete'=>'off', 'style' => 'width:163px', 'note' => T_('We ask for <b>your</b> <i>current</i> password as an additional security measure.') ) );
			}
		}
		$Form->password_input( 'edited_user_pass1', '', 20, T_('New password'), array( 'note' => sprintf( T_('Minimum length: %d characters.'), $Settings->get('user_minpwdlen') ), 'maxlength' => 50, 'required' => ($edited_User->ID == 0), 'autocomplete'=>'off' ) );
		$Form->password_input( 'edited_user_pass2', '', 20, T_('Confirm new password'), array( 'maxlength' => 50, 'required' => ($edited_User->ID == 0), 'autocomplete'=>'off' ) );

	$Form->end_fieldset();
}

	/***************  Buttons  **************/

if( $action != 'view' )
{ // Edit buttons
	$Form->buttons( array( array( '', 'actionArray[update]', T_('Change password!'), 'SaveButton' ) ) );
}

	$Form->info( '', '<div><a href="'.regenerate_url( 'disp', 'disp=profile' ).'">'.T_( 'Abandon password change' ).'</a></div>' );


$Form->end_form();

// Display javascript password strength indicator bar
display_password_indicator( array(
			'pass1-id'    => 'edited_user_pass1',
			'pass2-id'    => 'edited_user_pass2',
			'login-id'    => 'edited_user_login',
			'field-width' => 165,
	) );

?>