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
	$form_text_title = '<span class="nowrap">'.T_( 'Change password' ).'</span>'.get_manual_link( 'user-password-tab' ); // used for js confirmation message on leave the changed form
	$form_title = get_usertab_header( $edited_User, 'pwdchange', $form_text_title );
	$form_class = 'fform';
	$Form->title_fmt = '<div class="row"><span class="col-xs-12 col-lg-6 col-lg-push-6 text-right">$global_icons$</span><div class="col-xs-12 col-lg-6 col-lg-pull-6">$title$</div></div>'."\n";
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
		$Form->password_input( 'edited_user_pass2', '', 20, T_('Confirm new password'), array( 'maxlength' => 50, 'required' => ($edited_User->ID == 0), 'autocomplete'=>'off', 'note' => '<span id="pass2_status" class="field_error"></span>' ) );

	$Form->end_fieldset();
}

	/***************  Buttons  **************/

if( $action != 'view' )
{ // Edit buttons
	$Form->buttons( array( array( '', 'actionArray['.$params['form_button_action'].']', T_('Change password').'!', 'SaveButton'.$params['button_class'] ) ) );
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

?><script type="text/javascript">
jQuery( '#current_user_pass' ).keyup( function()
{
	var error_obj = jQuery( this ).parent().find( 'span.field_error' );
	if( error_obj.length )
	{
		if( jQuery( this ).val() == '' )
		{
			error_obj.show();
		}
		else
		{
			error_obj.hide();
		}
	}

	user_pass_clear_style( '#current_user_pass' );
} );

jQuery( '#edited_user_pass1, #edited_user_pass2' ).keyup( function()
{
	var minpass_obj = jQuery( this ).parent().find( '.pass_check_min' );
	if( minpass_obj.length )
	{ // Hide/Show a message about min pass length
		if( jQuery.trim( jQuery( this ).val() ).length >= <?php echo intval( $Settings->get('user_minpwdlen') ); ?> )
		{
			minpass_obj.hide();
		}
		else
		{
			minpass_obj.show();
		}
	}

	var diff_obj = jQuery( '.pass_check_diff' );
	if( diff_obj.length && jQuery( '#edited_user_pass1' ).val() == jQuery( ' #edited_user_pass2' ).val() )
	{ // Hide message about different passwords
		diff_obj.hide();
	}

	// Hide message about that new password must be entered
	var new_obj = jQuery( this ).parent().find( '.pass_check_new' );
	if( new_obj.length )
	{
		if( jQuery( this ).val() == '' )
		{
			new_obj.show();
		}
		else
		{
			new_obj.hide();
		}
	}

	// Hide message about that new password must be entered twice
	var twice_obj = jQuery( this ).parent().find( '.pass_check_twice' );
	if( twice_obj.length )
	{
		if( jQuery( this ).val() == '' )
		{
			twice_obj.show();
		}
		else
		{
			twice_obj.hide();
		}
	}

	var warning_obj = jQuery( this ).parent().find( '.pass_check_warning' );
	if( jQuery.trim( jQuery( this ).val() ) != jQuery( this ).val() )
	{ // Password contains the leading and trailing spaces
		if( ! warning_obj.length )
		{
			jQuery( this ).parent().append( '<span class="pass_check_warning notes field_error"><?php echo TS_('The leading and trailing spaces will be trimmed.'); ?></span>' );
		}
	}
	else if( warning_obj.length )
	{ // No spaces, Remove warning
		warning_obj.remove();
	}

	user_pass_clear_style( '#edited_user_pass1, #edited_user_pass2' );
} );

/**
 * Hide/Show error style of input depending on visibility of the error messages
 *
 * @param string jQuery selector
 */
function user_pass_clear_style( obj_selector )
{
	jQuery( obj_selector ).each( function()
	{
		if( jQuery( this ).parent().find( 'span.field_error span:visible' ).length )
		{
			jQuery( this ).addClass( 'field_error' );
		}
		else
		{
			jQuery( this ).removeClass( 'field_error' );
		}
	} );
}
</script>