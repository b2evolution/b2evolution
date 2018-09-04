<?php
/**
 * This file implements the UI view to user to automation.
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

// Begin payload block:
$this->disp_payload_begin();

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

	$form_text_title = T_( 'Add user to an automation...' ); // used for js confirmation message on leave the changed form
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
$Form->begin_fieldset( T_('Add user to an automation...').get_manual_link( 'add-user-to-automation' ).$close_icon, array( 'style' => 'width:420px' ) );

	// Get automations where user is NOT added yet:
	$AutomationCache = & get_AutomationCache();
	$automation_cache_SQL = $AutomationCache->get_SQL_object();
	$automation_cache_SQL->FROM_add( 'LEFT JOIN T_automation__user_state ON aust_autm_ID = autm_ID AND aust_user_ID = '.$edited_User->ID );
	$automation_cache_SQL->WHERE_and( 'aust_autm_ID IS NULL' );
	$AutomationCache->load_by_sql( $automation_cache_SQL );
	if( count( $AutomationCache->cache ) > 0 )
	{	// Allow to select automation if at least one is avaialble:
		$Form->select_input_object( 'autm_ID', '', $AutomationCache, T_('Automation'), array( 'required' => true ) );

		echo '<p class="center">';
		$Form->button( array( '', 'actionArray[add_automation]', T_('Add'), 'SaveButton' ) );
		echo '</p>';
	}
	else
	{	// Otherwise display a message:
		$Form->custom_content( '<p class="alert alert-info"><strong>'.T_( 'This user was already added to all available automations.' ).'</strong></p>' );
	}

$Form->end_fieldset();

$Form->end_form();

// End payload block:
$this->disp_payload_end();
?>