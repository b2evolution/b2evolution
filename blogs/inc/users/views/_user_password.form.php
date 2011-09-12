<?php
/**
 * This file implements the UI view for the user properties.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
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
 * @version $Id$
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

$Form = new Form( $form_action, 'user_checkchanges' );

if( !$user_profile_only )
{
	echo_user_actions( $Form, $edited_User, $action );
}

$is_admin = is_admin_page();
if( $is_admin )
{
	$form_title = get_editform_title( $edited_User, T_( 'Change password' ) );
	$form_class = 'fform';
}
else
{
	$form_title = '';
	$form_class = 'bComment';
}

$Form->begin_form( $form_class, $form_title );

	$Form->add_crumb( 'user' );
	$Form->hidden_ctrl();
	$Form->hidden( 'user_tab', 'pwdchange' );
	$Form->hidden( 'password_form', '1' );

	$Form->hidden( 'user_ID', $edited_User->ID );
	$Form->hidden( 'edited_user_login', $edited_User->login );

	/***************  Password  **************/

if( $action != 'view' )
{ // We can edit the values:

	$Form->begin_fieldset( $is_admin ? T_('Password') : '', array( 'class'=>'fieldset clear' ) );
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


/*
 * $Log$
 * Revision 1.10  2011/09/12 06:41:06  efy-asimo
 * Change user edit forms titles
 *
 * Revision 1.9  2011/09/04 22:13:21  fplanque
 * copyright 2011
 *
 * Revision 1.8  2011/05/11 07:11:52  efy-asimo
 * User settings update
 *
 * Revision 1.7  2011/04/06 13:30:56  efy-asimo
 * Refactor profile display
 *
 * Revision 1.6  2010/10/17 18:53:04  sam2kb
 * Added a link to delete edited user
 *
 * Revision 1.5  2010/02/08 17:54:47  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.4  2010/01/30 18:55:35  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.3  2010/01/03 16:28:35  fplanque
 * set some crumbs (needs checking)
 *
 * Revision 1.2  2009/11/21 13:39:05  efy-maxim
 * 'Cancel editing' fix
 *
 * Revision 1.1  2009/10/28 10:02:42  efy-maxim
 * rename some php files
 *
 */
?>