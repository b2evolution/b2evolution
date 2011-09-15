<?php
/**
 * This file implements the UI view for those user preferences which are visible only for admin users.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
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
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-asimo: Attila Simo
 *
 * @version $Id$
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var instance of User class
 */
global $edited_User;

global $current_User;

if( $current_User->group_ID != 1 )
{
	debug_die( T_( 'You have no permission to see this tab!' ) );
}

// Begin payload block:
$this->disp_payload_begin();

$Form = new Form( NULL, 'user_checkchanges' );

echo_user_actions( $Form, $edited_User, 'edit' );

$Form->begin_form( 'fform', get_usertab_header( $edited_User, 'admin', T_( 'Edit admin preferences' ) ) );

$Form->add_crumb( 'user' );
$Form->hidden_ctrl();
$Form->hidden( 'user_tab', 'admin' );
$Form->hidden( 'admin_form', '1' );

$Form->hidden( 'user_ID', $edited_User->ID );
$Form->hidden( 'edited_user_login', $edited_User->login );

/***************  User permissions  **************/

$Form->begin_fieldset( T_('User permissions').get_manual_link('user_permissions'), array( 'class'=>'fieldset clear' ) );

$edited_User->get_Group();
$level_fieldnote = '[0 - 10] '.sprintf( T_('See <a %s>online manual</a> for details.'), 'href="http://manual.b2evolution.net/User_levels"' );

if( $edited_User->ID == 1 )
{	// This is Admin user
	echo '<input type="hidden" name="edited_user_grp_ID" value="'.$edited_User->Group->ID.'" />';
	$Form->info( T_('User group'), $edited_User->Group->dget('name') );

	$Form->info_field( T_('User level'), $edited_User->get('level'), array( 'note' => $level_fieldnote ) );
}
else
{
	$GroupCache = & get_GroupCache();
	$Form->select_object( 'edited_user_grp_ID', $edited_User->Group->ID, $GroupCache, T_('User group') );

	$Form->text_input( 'edited_user_level', $edited_User->get('level'), 2, T_('User level'), $level_fieldnote, array( 'required' => true ) );
}

$Form->end_fieldset(); // user permissions

$Form->begin_fieldset( T_('Additional info') );

	$Form->info_field( T_('ID'), $edited_User->ID );

	$Form->info_field( T_('Posts'), $edited_User->get_num_posts() );
	$Form->info_field( T_('Comments'), $edited_User->get_num_comments() );
	$Form->info_field( T_('Last seen on'), $edited_User->get_last_session_param('lastseen') );
	$Form->info_field( T_('On IP'), $edited_User->get_last_session_param('ipaddress') );
	$Form->info_field( T_('Created on'), $edited_User->dget('datecreated') );
	$Form->info_field( T_('From IP'), $edited_User->dget('ip') );
	$Form->info_field( T_('From Domain'), $edited_User->dget('domain') );
	$Form->info_field( T_('With Browser'), $edited_User->dget('browser') );

	$Form->text_input( 'edited_user_source', $edited_User->source, 30, T_('Source'), '', array( 'maxlength' => 30 ) );

	$email_fieldnote = '<a href="mailto:'.$edited_User->get('email').'">'.get_icon( 'email', 'imgtag', array('title'=>T_('Send an email')) ).'</a>';
	$Form->text_input( 'edited_user_email', $edited_User->email, 30, T_('Email'), $email_fieldnote, array( 'maxlength' => 100, 'required' => true ) );
	$Form->checkbox( 'edited_user_validated', $edited_User->get('validated'), T_('Validated email'), T_('Has this email address been validated (through confirmation email)?') );

$Form->end_fieldset(); // additional info

$action_buttons = array(
		array( '', 'actionArray[update]', T_('Save !'), 'SaveButton' ),
		array( 'reset', '', T_('Reset'), 'ResetButton' ) );

$Form->buttons( $action_buttons );

$Form->end_form();

// End payload block:
$this->disp_payload_end();

/*
 * $Log$
 * Revision 1.5  2011/09/15 08:58:46  efy-asimo
 * Change user tabs display
 *
 * Revision 1.4  2011/09/14 20:19:48  fplanque
 * cleanup
 *
 * Revision 1.3  2011/09/14 07:54:20  efy-asimo
 * User profile refactoring - modifications
 *
 * Revision 1.2  2011/09/12 06:41:06  efy-asimo
 * Change user edit forms titles
 *
 * Revision 1.1  2011/09/12 05:28:47  efy-asimo
 * User profile form refactoring
 *
 */
?>