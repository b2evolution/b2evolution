<?php
/**
 * This file implements the UI view for the form to delete data of user.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
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
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id: _user_deldata.form.php 7043 2014-07-02 08:35:45Z yura $
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $display_mode, $user_tab, $admin_url;

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

if( $display_mode != 'js' )
{
	// ------------------- PREV/NEXT USER LINKS -------------------
	user_prevnext_links( array(
			'user_tab'     => 'report'
		) );
	// ------------- END OF PREV/NEXT USER LINKS -------------------
}

$Form = new Form( $form_action, 'user_checkchanges' );

$form_title = '';
$form_class = 'fform';
$Form->title_fmt = '<span style="float:right">$global_icons$</span><div>$title$</div>'."\n";

if( $display_mode != 'js' )
{
	if( !$user_profile_only )
	{
		echo_user_actions( $Form, $edited_User, $action );
	}

	$form_title = get_usertab_header( $edited_User, '', T_( 'Report User' ) );
}

$Form->begin_form( $form_class, $form_title );

$Form->add_crumb( 'user' );
$Form->hidden_ctrl();
$Form->hidden( 'user_tab', $user_tab );
$Form->hidden( 'user_ID', $edited_User->ID );

$Form->begin_fieldset( T_('Delete user data').get_manual_link( 'delete-user-data' ), array( 'class'=>'fieldset clear' ) );

	$posts_created = $edited_User->get_num_posts();
	// Get the number of comments created by the edited user, but count recycled comments only if user has global editall blogs permission
	$comments_created = $edited_User->get_num_comments( '', $current_User->check_perm( 'blogs', 'editall', false ) );
	$messages_sent = $edited_User->get_num_messages( 'sent' );

	$delete_options = array();
	if( $posts_created )
	{
		$delete_options[] = array( 'delete_posts', 1, sprintf( T_('%s posts'), $posts_created ), 1 );
	}
	if( $comments_created )
	{
		$delete_options[] = array( 'delete_comments', 1, sprintf( T_('%s comments'), $comments_created ), 1 );
	}
	if( $messages_sent )
	{
		$delete_options[] = array( 'delete_messages', 1, sprintf( T_('%s private messages'), $messages_sent ), 1 );
	}
	$Form->checklist( $delete_options, 'default_user_notification', sprintf( T_( 'Please confirm deletion of the contents contributed by %s' ), $edited_User->get_colored_login() ), false, false, array( 'wide' => true ) );

	echo '<p class="center">';
	$Form->button( array( '', 'actionArray[delete_data]', T_('Delete selected data'), 'SaveButton' ) );
	echo '</p>';

if( $display_mode == 'js' )
{ // Display a close link for popup window
	echo '<div class="center">'.action_icon( T_('Close this window'), 'close', '', ' '.T_('Close this window'), 3, 4, array( 'id' => 'close_button', 'class' => 'small' ) ).'</div>';
}
$Form->end_fieldset();

$Form->end_form();

?>