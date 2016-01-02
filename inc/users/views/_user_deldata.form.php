<?php
/**
 * This file implements the UI view for the form to delete data of user.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
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

	$form_text_title = T_( 'Delete user data' ); // used for js confirmation message on leave the changed form
	$form_title = get_usertab_header( $edited_User, '', $form_text_title );
}

$Form->begin_form( $form_class, $form_title, array( 'title' => ( isset( $form_text_title ) ? $form_text_title : $form_title ) ) );

$Form->add_crumb( 'user' );
$Form->hidden_ctrl();
$Form->hidden( 'user_tab', $user_tab );
$Form->hidden( 'user_ID', $edited_User->ID );

$close_icon = '';
if( $display_mode == 'js' )
{ // Display a close link for popup window
	$close_icon = action_icon( T_('Close this window'), 'close', '', '', 0, 0, array( 'id' => 'close_button', 'class' => 'floatright' ) );
}
$Form->begin_fieldset( T_('Delete user data').get_manual_link( 'delete-user-data' ).$close_icon, array( 'class' => 'fieldset clear' ) );

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

$Form->end_fieldset();

$Form->end_form();

?>