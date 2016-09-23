<?php
/**
 * This is the template that displays the user profile page.
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
* @var Blog
*/
global $Blog;
/**
 * @var GeneralSettings
 */
global $Settings;
/**
 * @var Current User
 */
global $current_User;

// init is logged in status
$is_logged_in = is_logged_in();

// Default params:
$params = array_merge( array(
		'edit_my_profile_link_text'        => T_('Edit my profile'),
		'edit_user_admin_link_text'        => T_('Edit in Back-Office'),
		'skin_form_params'                 => array(),
	), $params );

// ------------------- PREV/NEXT USER LINKS (SINGLE USER MODE) -------------------
user_prevnext_links();
// ------------------------- END OF PREV/NEXT USER LINKS -------------------------


// ---- START OF PROFILE CONTENT ---- //
echo '<div class="profile_content">';

$user_ID = param( 'user_ID', 'integer', '' );
if( empty($user_ID) )
{	// Grab the current User
	$user_ID = $current_User->ID;
}

$UserCache = & get_UserCache();
/**
 * @var User
 */
$User = & $UserCache->get_by_ID( $user_ID );

$profileForm = new Form( NULL, '', 'post', NULL, '', 'div' );

$profileForm->switch_template_parts( $params['skin_form_params'] );

$profileForm->switch_layout( 'fixed', false );

$profileForm->begin_form( 'evo_form evo_form_user' );

// ---- START OF LEFT COLUMN ---- //
echo '<div class="profile_column_left">';

	// ------------------------- "User Profile - Left" CONTAINER EMBEDDED HERE --------------------------
	// Display container contents:
	skin_container( /* TRANS: Widget container name */ NT_('User Profile - Left'), array(
		'widget_context' => 'user',	// Signal that we are displaying within an User
		// The following (optional) params will be used as defaults for widgets included in this container:
		// This will enclose each widget in a block:
		'block_start' => '<div class="$wi_class$">',
		'block_end' => '</div>',
		// This will enclose the title of each widget:
		'block_title_start' => '<p><b>',
		'block_title_end' => '</b></p>',
	) );
	// ----------------------------- END OF "User Profile - Left" CONTAINER -----------------------------

echo '</div>';
// ---- END OF LEFT COLUMN ---- //

// ---- START OF RIGHT COLUMN ---- //
echo '<div class="profile_column_right">';

	// ------------------------- "User Profile - Right" CONTAINER EMBEDDED HERE --------------------------
	// Display container contents:
	skin_container( /* TRANS: Widget container name */ NT_('User Profile - Right'), array(
		'widget_context' => 'user',	// Signal that we are displaying within an User
		// The following (optional) params will be used as defaults for widgets included in this container:
		// This will enclose each widget in a block:
		'block_start' => '<div class="$wi_class$">',
		'block_end' => '</div>',
		// This will enclose the title of each widget:
		'block_title_start' => '<h3>',
		'block_title_end' => '</h3>',
		// Template params for "User fields" widget:
		'widget_user_fields_before_group'       => '<fieldset class="fieldset"><div class="panel panel-default">',
		'widget_user_fields_before_group_title' => '<legend class="panel-heading">',
		'widget_user_fields_after_group_title'  => '</legend><div class="panel-body">',
		'widget_user_fields_before_field_title' => '<div class="form-group fixedform-group"><label class="control-label fixedform-label">',
		'widget_user_fields_after_field_title'  => ':</label>',
		'widget_user_fields_before_field_value' => '<div class="controls fixedform-controls form-control-static">',
		'widget_user_fields_after_field_value'  => '</div></div>',
		'widget_user_fields_after_group'        => '</div></div><fieldset>',
	) );
	// ----------------------------- END OF "User Profile - Right" CONTAINER -----------------------------

	$profileForm->begin_fieldset( T_( 'Reputation' ) );

		$profileForm->info( T_('Joined'), mysql2localedate( $User->datecreated ) );

		if( $Blog->get_setting( 'userdir_lastseen' ) )
		{	// Display last visit only if it is enabled by current collection:
			$profileForm->info( T_('Last seen on'), get_lastseen_date( $User->get( 'lastseen_ts' ), $Blog->get_setting( 'userdir_lastseen_view' ), $Blog->get_setting( 'userdir_lastseen_cheat' ) ) );
		}

		$profileForm->info( T_('Number of posts'), $User->get_reputation_posts() );

		$profileForm->info( T_('Comments'), '<span class="reputation_message">'.$User->get_reputation_comments().'</span>' );

		$profileForm->info( T_('Photos'), '<span class="reputation_message">'.$User->get_reputation_files( array( 'file_type' => 'image' ) ).'</span>' );

		$profileForm->info( T_('Audio'), '<span class="reputation_message">'.$User->get_reputation_files( array( 'file_type' => 'audio' ) ).'</span>' );

		$profileForm->info( T_('Other files'), '<span class="reputation_message">'.$User->get_reputation_files( array( 'file_type' => 'other' ) ).'</span>' );

		$profileForm->info( T_('Spam fighter score'), '<span class="reputation_message">'.$User->get_reputation_spam().'</span>' );

	$profileForm->end_fieldset();

	$Plugins->trigger_event( 'DisplayProfileFormFieldset', array( 'Form' => & $profileForm, 'User' => & $User, 'edit_layout' => 'public' ) );

echo '</div>';
// ---- END OF RIGHT COLUMN ---- //

echo '<div class="clear"></div>';

// ---- END OF PROFILE CONTENT ---- //
echo '</div>'; // .profile_content


$profileForm->end_form();

// Init JS for user reporting
echo_user_report_window();
// Init JS for user contact editing
echo_user_contact_groups_window();
?>