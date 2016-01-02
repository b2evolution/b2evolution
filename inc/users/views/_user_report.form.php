<?php
/**
 * This file implements the UI view for the user report form.
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
$form_class = 'fform user_report_form';
$Form->title_fmt = '<span style="float:right">$global_icons$</span><div>$title$</div>'."\n";

if( $display_mode != 'js' )
{
	if( !$user_profile_only )
	{
		echo_user_actions( $Form, $edited_User, $action );
	}

	$form_text_title = T_( 'Report User' ); // used for js confirmation message on leave the changed form
	$form_title = get_usertab_header( $edited_User, '', $form_text_title );
}

$Form->begin_form( $form_class, $form_title, array( 'title' => ( isset( $form_text_title ) ? $form_text_title : $form_title ) ) );

$Form->hidden_ctrl();
if( is_admin_page() )
{ // Params for backoffice
	$Form->hidden( 'user_tab', $user_tab );
	$Form->hidden( 'is_backoffice', 1 );
}
else
{ // Params for frontoffice
	global $Blog;
	$Form->hidden( 'blog', $Blog->ID );
}

$close_icon = '';
if( $display_mode == 'js' )
{ // Display a close link for popup window
	$close_icon = action_icon( T_('Close this window'), 'close', '', '', 0, 0, array( 'id' => 'close_button', 'class' => 'floatright' ) );
}
$Form->begin_fieldset( T_('Report User').$close_icon, array( 'class' => 'fieldset clear' ) );

user_report_form( array(
		'Form'       => $Form,
		'user_ID'    => $edited_User->ID,
		'crumb_name' => 'user',
		'cancel_url' => get_secure_htsrv_url().'profile_update.php?'
										.( is_admin_page() ? 'is_backoffice=1&amp;' : '' )
										.'action=remove_report&amp;'
										.'user_ID='.$edited_User->ID.'&amp;'
										.( empty( $Blog ) || is_admin_page() ? '' : 'blog='.$Blog->ID.'&amp;' )
										.url_crumb( 'user' ),
	) );

$Form->end_fieldset();

$Form->end_form();

?>