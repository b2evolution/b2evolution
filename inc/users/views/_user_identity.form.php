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

load_class( 'regional/model/_country.class.php', 'Country' );
load_funcs( 'regional/model/_regional.funcs.php' );

/**
 * @var instance of GeneralSettings class
 */
global $Settings;
/**
 * @var instance of Plugins class
 */
global $Plugins;
/**
 * @var instance of UserSettings class
 */
global $UserSettings;
/**
 * @var instance of User class
 */
global $edited_User;
/**
 * @var instance of User class
 */
global $current_User;
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
 * @var url of RSC folder
 */
global $rsc_url;
global $is_admin_page;

// Default params:
$default_params = array(
		'skin_form_params'         => array(),
		'form_class_user_identity' => 'bComment',
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
		'user_tab' => 'profile'
	) );
// ------------- END OF PREV/NEXT USER LINKS -------------------

$has_full_access = $current_User->check_perm( 'users', 'edit' );
$edited_user_perms = array( 'edited-user', 'edited-user-required' );
$new_user_creating = ( $edited_User->ID == 0 );

$Form = new Form( $form_action, 'user_checkchanges' );

$Form->switch_template_parts( $params['skin_form_params'] );

if( !$user_profile_only )
{
	echo_user_actions( $Form, $edited_User, $action );
}

$is_admin = is_admin_page();
if( $is_admin )
{
	if( $new_user_creating )
	{
		$form_title = '<span class="nowrap">'.T_('Edit user profile').'</span>';
	}
	else
	{
		$form_text_title = '<span class="nowrap">'.T_( 'Edit profile' ).'</span>'.get_manual_link( 'user-profile-tab' ); // used for js confirmation message on leave the changed form
		$form_title = get_usertab_header( $edited_User, 'profile', $form_text_title );
		$Form->title_fmt = '<div class="row"><span class="col-xs-12 col-lg-6 col-lg-push-6 text-right">$global_icons$</span><div class="col-xs-12 col-lg-6 col-lg-pull-6">$title$</div></div>'."\n";
	}
	$form_class = 'fform';
}
else
{
	$form_title = '';
	$form_class = $params['form_class_user_identity'];
}

	$Form->begin_form( $form_class, $form_title, array( 'title' => ( isset( $form_text_title ) ? $form_text_title : $form_title ) ) );

	// We should print out this submit "update" before all other buttons (because form is submitted by first button)
	// It gives to update a form when we press Enter key on the form element
	echo '<div style="position:absolute;top:-1000px;left:-1000px">';
	$Form->button( array( 'type' => 'submit', 'name' => 'actionArray[update]' ) );
	echo '</div>';

	$Form->add_crumb( 'user' );
	$Form->hidden_ctrl();
	$Form->hidden( 'user_tab', 'profile' );
	$Form->hidden( 'identity_form', '1' );

	$Form->hidden( 'user_ID', $edited_User->ID );
	if( isset( $Blog ) )
	{
		$Form->hidden( 'blog', $Blog->ID );
	}

if( $new_user_creating )
{
	$current_User->check_perm( 'users', 'edit', true );
	$edited_User->get_Group();

	$Form->begin_fieldset( T_( 'New user' ).get_manual_link( 'user-edit' ), array( 'class' => 'fieldset clear' ) );

	$chosengroup = ( $edited_User->Group === NULL ) ? $Settings->get( 'newusers_grp_ID' ) : $edited_User->grp_ID;
	$GroupCache = & get_GroupCache();
	$Form->select_object( 'edited_user_grp_ID', $chosengroup, $GroupCache, sprintf( T_('<span %s>Primary</span> user group'), 'class="label label-primary"' ) );

	$field_note = '[0 - 10]';
	$Form->text_input( 'edited_user_level', $edited_User->get('level'), 2, T_('User level'), $field_note, array( 'required' => true ) );

	$email_fieldnote = '<a href="mailto:'.$edited_User->get('email').'">'.get_icon( 'email', 'imgtag', array('title'=>T_('Send an email')) ).'</a>';
	$Form->text_input( 'edited_user_email', $edited_User->email, 30, T_('Email'), $email_fieldnote, array( 'maxlength' => 255, 'required' => true ) );
	$Form->select_input_array( 'edited_user_status', $edited_User->get( 'status' ), get_user_statuses(), T_( 'Account status' ) );

	$Form->end_fieldset();
}

	/***************  Identity  **************/

$Form->begin_fieldset( T_('Identity').( is_admin_page() ? get_manual_link( 'user-profile-tab' ) : '' ) );

if( ($url = $edited_User->get('url')) != '' )
{
	if( !preg_match('#://#', $url) )
	{
		$url = 'http://'.$url;
	}
	$url_fieldnote = '<a href="'.$url.'" target="_blank">'.get_icon( 'play', 'imgtag', array('title'=>T_('Visit the site')) ).'</a>';
}
else
	$url_fieldnote = '';

if( $action != 'view' )
{	// We can edit the values:

	if( $action != 'new' )
	{
		// Get other pictures (not main avatar)
		$user_avatars = $edited_User->get_avatar_Links();

		$forbid_link = '';
		if( is_admin_page() )
		{
			$ctrl_param = '?ctrl=user&amp;user_tab=avatar&amp;user_ID='.$edited_User->ID;
			if( $current_User->can_moderate_user( $edited_User->ID ) )
			{
				$forbid_link = action_icon( T_('Forbid using as main profile picture'), 'move_down_orange', $ctrl_param.'&amp;action=forbid_avatar&amp;'.url_crumb( 'user' ), ' '.T_('Forbid using as main profile picture'), 3, 4 ).'<br />';
			}
			$remove_picture_url = $ctrl_param.'&amp;action=remove_avatar&amp;'.url_crumb( 'user' );
			$delete_picture_url = $ctrl_param.'&amp;action=delete_avatar&amp;file_ID='.$edited_User->avatar_file_ID.'&amp;'.url_crumb( 'user' );
		}
		else
		{
			$remove_picture_url = get_htsrv_url().'profile_update.php?user_tab=avatar&amp;blog='.$Blog->ID.'&amp;action=remove_avatar&amp;'.url_crumb( 'user' );
			$delete_picture_url = get_htsrv_url().'profile_update.php?user_tab=avatar&amp;blog='.$Blog->ID.'&amp;action=delete_avatar&amp;file_ID='.$edited_User->avatar_file_ID.'&amp;'.url_crumb( 'user' );
		}

		if( $edited_User->has_avatar() || count( $user_avatars ) )
		{ // If user uploaded at least one profile picture
			$change_picture_title = T_('Change').' &raquo;';
			$change_picture_icon = 'edit';
		}
		else
		{ // If user has no profile picture yet
			$change_picture_title = T_('Upload now').' &raquo;';
			$change_picture_icon = 'move_up_green';
		}

		// Main profile picture with action icons to modify it
		$user_pictures = '<div class="avatartag main image_rounded">'
				.$edited_User->get_avatar_imgtag( 'crop-top-320x320', 'avatar', 'top', true, '', 'user', '160x160' )
				.'<div class="avatar_actions">'
					.action_icon( $change_picture_title, $change_picture_icon, get_user_settings_url( 'avatar', $edited_User->ID ), ' '.$change_picture_title, 3, 4 );
		if( $edited_User->has_avatar() && ( $avatar_Link = & $edited_User->get_avatar_Link() ) )
		{ // Display these actions only for existing avatar file
			$user_pictures .= '<br />'
					.action_icon( T_('No longer use this as main profile picture'), 'move_down', $remove_picture_url, ' '.T_('No longer use this as main profile picture'), 3, 4 ).'<br />'
					.$forbid_link
					.action_icon( T_('Delete this profile picture'), 'delete', $delete_picture_url, ' '.T_('Delete this profile picture'), 3, 4, array( 'onclick' => 'return confirm(\''.TS_('Are you sure want to delete this picture?').'\');' ) ).'<br />'
					.$edited_User->get_rotate_avatar_icons( $edited_User->avatar_file_ID, array(
							'before'   => '',
							'after'    => '<br />',
							'text'     => ' '.T_('Rotate'),
							'user_tab' => 'avatar',
						) )
					.$edited_User->get_crop_avatar_icon( $edited_User->avatar_file_ID, array(
							'before'   => '',
							'after'    => '',
							'text'     => ' '.T_('Crop'),
							'user_tab' => 'avatar',
							'onclick'  => 'return user_crop_avatar( '.$edited_User->ID.', '.$edited_User->avatar_file_ID.', \'avatar\' )'
						) );
		}
		$user_pictures .= '</div><div class="clear"></div>'
			.'</div>';

		// Append the other pictures to main avatar
		foreach( $user_avatars as $user_Link )
		{
			$user_pictures .= $user_Link->get_tag( array(
					'before_image'        => '<div class="avatartag image_rounded">',
					'before_image_legend' => '',
					'after_image_legend'  => '',
					'after_image'         => '</div>',
					'image_size'          => 'crop-top-160x160',
					'image_link_title'    => $edited_User->login,
					'image_link_rel'      => 'lightbox[user]',
					'tag_size'            => '80x80'
				) );
		}

		$Form->info( T_('Profile picture'), $user_pictures );
	}

	$Form->text_input( 'edited_user_login', $edited_User->login, 20, T_('Login'), '', array( 'maxlength' => 60, 'required' => true ) );

	$firstname_editing = $Settings->get( 'firstname_editing' );
	if( ( in_array( $firstname_editing, $edited_user_perms ) && $edited_User->ID == $current_User->ID ) || ( $firstname_editing != 'hidden' && $has_full_access ) )
	{
		$Form->text_input( 'edited_user_firstname', $edited_User->firstname, 20, T_('First name'), '', array( 'maxlength' => 50, 'required' => ( $firstname_editing == 'edited-user-required' ) ) );
	}

	$lastname_editing = $Settings->get( 'lastname_editing' );
	if( ( in_array( $lastname_editing, $edited_user_perms ) && $edited_User->ID == $current_User->ID ) || ( $lastname_editing != 'hidden' && $has_full_access ) )
	{
		$Form->text_input( 'edited_user_lastname', $edited_User->lastname, 20, T_('Last name'), '', array( 'maxlength' => 50, 'required' => ( $lastname_editing == 'edited-user-required' ) ) );
	}

	$nickname_editing = $Settings->get( 'nickname_editing' );
	if( ( in_array( $nickname_editing, $edited_user_perms ) && $edited_User->ID == $current_User->ID ) || ( $nickname_editing != 'hidden' && $has_full_access ) )
	{
		$Form->text_input( 'edited_user_nickname', $edited_User->nickname, 20, T_('Nickname'), '', array( 'maxlength' => 50, 'required' => ( $nickname_editing == 'edited-user-required' ) ) );
	}

	$Form->radio( 'edited_user_gender', $edited_User->get('gender'), array(
			array( 'M', T_('A man') ),
			array( 'F', T_('A woman') ),
			array( 'O', T_('Other') ),
		), T_('I am'), false, '', $Settings->get( 'registration_require_gender' ) == 'required' );

	$button_refresh_regional = '<button id="%s" type="submit" name="actionArray[refresh_regional]" class="action_icon refresh_button">'.get_icon( 'refresh' ).'</button>';
	$button_refresh_regional .= '<img src="'.$rsc_url.'img/ajax-loader.gif" alt="'.T_('Loading...').'" title="'.T_('Loading...').'" style="display:none;margin:2px 0 0 5px" align="top" />';

	if( user_country_visible() )
	{	// Display a select input for Country
		$CountryCache = & get_CountryCache();
		$Form->select_country( 'edited_user_ctry_ID',
				$edited_User->ctry_ID,
				$CountryCache,
				T_('Country'),
				array(	// field params
						'required' => $Settings->get( 'location_country' ) == 'required', // true if Country is required
						'allow_none' => // Allow none value:
						                $has_full_access || // Current user has permission to edit users
						                empty( $edited_User->ctry_ID ) || // Country is not defined yet
						                ( !empty( $edited_User->ctry_ID ) && $Settings->get( 'location_country' ) != 'required' ) // Country is defined but this field is not required
					)
			);
	}

	if( user_region_visible() )
	{	// Display a select input for Region
		$regions_option_list = get_regions_option_list( $edited_User->ctry_ID, $edited_User->rgn_ID, array( 'none_option_text' => T_( 'Unknown' ) ) );
		$Form->select_input_options( 'edited_user_rgn_ID',
				$regions_option_list,
				T_( 'Region' ),
				sprintf( $button_refresh_regional, 'button_refresh_region' ), // Button to refresh regions list
				array(	// field params
						'required' => $Settings->get( 'location_region' ) == 'required' // true if Region is required
					)
			);
	}

	if( user_subregion_visible() )
	{	// Display a select input for Subregion
		$subregions_option_list = get_subregions_option_list( $edited_User->rgn_ID, $edited_User->subrg_ID, array( 'none_option_text' => T_( 'Unknown' ) ) );
		$Form->select_input_options( 'edited_user_subrg_ID',
				$subregions_option_list,
				T_( 'Sub-region' ),
				sprintf( $button_refresh_regional, 'button_refresh_subregion' ), // Button to refresh subregions list
				array(	// field params
						'required' => $Settings->get( 'location_subregion' ) == 'required' // true if Subregion is required
					)
			);
	}

	if( user_city_visible() )
	{	// Display a select input for City
		$cities_option_list = get_cities_option_list( $edited_User->ctry_ID, $edited_User->rgn_ID, $edited_User->subrg_ID, $edited_User->city_ID, array( 'none_option_text' => T_( 'Unknown' ) ) );
		$Form->select_input_options( 'edited_user_city_ID',
				$cities_option_list,
				T_( 'City' ),
				sprintf( $button_refresh_regional, 'button_refresh_city' ), // Button to refresh cities list
				array(	// field params
						'required' => $Settings->get( 'location_city' ) == 'required' // true if City is required
					)
			);
	}

	$Form->begin_line( T_('My age group'), 'edited_user_age_min' );
		$Form->text( 'edited_user_age_min', $edited_User->age_min, 3, '' );
		$Form->text( 'edited_user_age_max', $edited_User->age_max, 3, T_('to') );
	$Form->end_line();

	// Organization select fields:
	$OrganizationCache = & get_OrganizationCache();
	$OrganizationCache->clear();
	// Load only organizations that allow to join members or own organizations of the current user:
	$OrganizationCache->load_where( '( org_accept != "no" OR org_owner_user_ID = "'.$current_User->ID.'" )' );
	$count_all_orgs = count( $OrganizationCache->cache );
	$count_user_orgs = 0;
	if( $count_all_orgs > 0 )
	{ // Display an organization select box if at least one is defined
		$user_orgs = $edited_User->get_organizations_data();
		$org_allow_none = false;
		if( empty( $user_orgs ) )
		{ // Set it for first(empty) organization select box
			$user_orgs[0] = 0;
			// Allow None option for <select> only when user has no organization yet
			$org_allow_none = true;
		}

		$count_user_orgs = count( $user_orgs );
		// Display a button to add user in new organization only if the user is not in all organizations
		$add_org_icon_style = ( $count_all_orgs > 1 && $count_all_orgs > $count_user_orgs ) ? '' : ';display:none';
		$org_add_icon = ' '.get_icon( 'add', 'imgtag', array( 'class' => 'add_org', 'style' => 'cursor:pointer'.$add_org_icon_style ) );

		foreach( $user_orgs as $org_ID => $org_data )
		{
			$perm_edit_orgs = false;
			if( ! empty( $org_ID ) )
			{	// $org_ID can be 0 for case when user didn't select an organization yet
				$user_Organization = & $OrganizationCache->get_by_ID( $org_ID );
				$perm_edit_orgs = $current_User->check_perm( 'orgs', 'edit', false, $user_Organization );
			}

			// Display a button to remove user from organization
			$remove_org_icon_style = $org_ID > 0 ? '' : ';display:none';
			$org_remove_icon = ' '.get_icon( 'minus', 'imgtag', array( 'class' => 'remove_org', 'style' => 'cursor:pointer'.$remove_org_icon_style ) );

			$form_infostart = $Form->infostart;
			$form_inputstart = $Form->inputstart;
			$inputstart_icon = '';
			if( $org_ID > 0 )
			{ // User is assigned to this organization, Display the accepted status icon
				if( $perm_edit_orgs )
				{ // Set the spec params for icon if user is admin
					$accept_icon_params = array( 'style' => 'cursor:pointer', 'rel' => 'org_status_'.( $org_data['accepted'] ? 'y' : 'n' ).'_'.$org_ID.'_'.$edited_User->ID );
				}
				else
				{
					$accept_icon_params = array();
				}
				if( $org_data['accepted'] )
				{ // Organization is accepted by admin
					$accept_icon = get_icon( 'allowback', 'imgtag', array_merge( array( 'title' => T_('Membership has been accepted.') ), $accept_icon_params ) );
				}
				else
				{ // Organization is not accepted by admin yet
					$accept_icon = get_icon( 'bullet_red', 'imgtag', array_merge( array( 'title' => T_('Membership pending acceptance.') ), $accept_icon_params ) );
				}
				$inputstart_icon = $accept_icon.' ';
			}

			if( $org_ID > 0 && ! $perm_edit_orgs && $org_data['accepted'] )
			{ // Display only info of the assigned organization
				$Form->infostart = $Form->infostart.$inputstart_icon;
				$org_role_input = ( empty( $org_data['role'] ) ? '' : ' &nbsp; <strong>'.T_('Role').':</strong> '.$org_data['role'] ).' &nbsp; ';
				$org_hidden_fields = '<input type="hidden" name="organizations[]" value="'.$org_ID.'" />';
				$Form->info_field( T_('Organization'), $org_data['name'], array(
						'field_suffix' => $org_role_input.$org_add_icon.$org_remove_icon.$org_hidden_fields,
						'name'         => 'organizations[]'
					) );
			}
			else
			{ // Allow to update the organization fields
				$perm_edit_org_role = false;
				if( ! empty( $org_ID ) )
				{
					$perm_edit_org_role = ( $user_Organization->owner_user_ID == $current_User->ID ) || ( $user_Organization->perm_role == 'owner and member' && $org_data['accepted'] );
				}

				$Form->output = false;
				$Form->switch_layout( 'none' );
				if( $perm_edit_org_role )
				{
					$org_role_input = ' &nbsp; <strong>'.T_('Role').':</strong> '.
							$Form->text_input( 'org_roles[]', $org_data['role'], 20, '', '', array( 'maxlength' => 255 ) ).' &nbsp; ';
				}
				else
				{
					$org_role_input = ( empty( $org_data['role'] ) ? '' : ' &nbsp; <strong>'.T_('Role').':</strong> '.$org_data['role'] ).' &nbsp; ';
				}
				$Form->switch_layout( NULL );
				$Form->output = true;

				$Form->inputstart = $Form->inputstart.$inputstart_icon;
				$Form->select_input_object( 'organizations[]', $org_ID, $OrganizationCache, T_('Organization'), array(
						'allow_none'   => $org_allow_none,
						'field_suffix' => $org_role_input.$org_add_icon.$org_remove_icon
					) );
			}
			$Form->infostart = $form_infostart;
			$Form->inputstart = $form_inputstart;
		}
	}

	if( $new_user_creating )
	{
		$Form->text_input( 'edited_user_source', $edited_User->source, 30, T_('Source'), '', array( 'maxlength' => 30 ) );
	}

	if( $edited_User->get_field_url() != '' )
	{	// At least one url field is existing for current user
		$Form->info( T_('URL'), $edited_User->get_field_link(), T_('(This is the main URL advertised for this profile. It is automatically selected from the URLs you enter in the fields below.)') );
	}
}
else
{ // display only

	if( $Settings->get('allow_avatars') )
	{
		$Form->info( T_('Profile picture'), $edited_User->get_avatar_imgtag( 'crop-top-64x64', 'avatar', '', true ) );
	}

	$Form->info( T_('Login'), $edited_User->get('login') );
	$Form->info( T_('First name'), $edited_User->get('firstname') );
	$Form->info( T_('Last name'), $edited_User->get('lastname') );
	$Form->info( T_('Nickname'), $edited_User->get('nickname') );
	$Form->info( T_('Identity shown'), $edited_User->get('preferredname') );

	$user_gender = $edited_User->get( 'gender' );
	if( ! empty( $user_gender ) )
	{
		$Form->info( T_('Gender'), $edited_User->get_gender() );
	}

	if( ! empty( $edited_User->ctry_ID ) )
	{	// Display country
		load_class( 'regional/model/_country.class.php', 'Country' );
		$Form->info( T_('Country'), $edited_User->get_country_name() );
	}

	if( ! empty( $edited_User->rgn_ID ) )
	{	// Display region
		load_class( 'regional/model/_region.class.php', 'Region' );
		$Form->info( T_( 'Region' ), $edited_User->get_region_name() );
	}

	if( ! empty( $edited_User->rgn_ID ) )
	{	// Display sub-region
		load_class( 'regional/model/_subregion.class.php', 'Subregion' );
		$Form->info( T_( 'Sub-region' ), $edited_User->get_subregion_name() );
	}

	if( ! empty( $edited_User->city_ID ) )
	{	// Display city
		load_class( 'regional/model/_city.class.php', 'City' );
		$Form->info( T_( 'City' ), $edited_User->get_city_name() );
	}

	//$Form->info( T_('My ZIP/Postcode'), $edited_User->get('postcode') );
	$Form->info( T_('My age group'), $edited_User->get('age_min') );
	$Form->info( T_('to'), $edited_User->get('age_max') );

	$Form->info( T_('URL'), $edited_User->get('url'), $url_fieldnote );
}

$Form->end_fieldset();

	/***************  Password  **************/

if( empty( $edited_User->ID ) && $action != 'view' )
{ // We can edit the values:

	$Form->begin_fieldset( T_('Password') );
		$Form->password_input( 'edited_user_pass1', '', 20, T_('New password'), array( 'maxlength' => 50, 'required' => true, 'autocomplete'=>'off' ) );
		$Form->password_input( 'edited_user_pass2', '', 20, T_('Confirm new password'), array( 'note'=>sprintf( T_('Minimum length: %d characters.'), $Settings->get('user_minpwdlen') ), 'maxlength' => 50, 'required' => true, 'autocomplete'=>'off' ) );
	$Form->end_fieldset();
}

	/***************  Multiple sessions  **************/

if( empty( $edited_User->ID ) && $action != 'view' )
{	// New user will be created with default multiple_session setting

	$multiple_sessions = $Settings->get( 'multiple_sessions' );
	if( $multiple_sessions == 'userset_default_yes' || ( $has_full_access && $multiple_sessions == 'adminset_default_yes' ) )
	{
		$Form->hidden( 'edited_user_set_login_multiple_sessions', 1 );
	}
	else
	{
		$Form->hidden( 'edited_user_set_login_multiple_sessions', 0 );
	}
}

/***************  Additional info  **************/

// This totally needs to move into User object
global $DB;

// Get original user id for duplicate
if( $edited_User->ID == 0 )
{
	$user_id = param( 'user_ID', 'integer', 0 );
	if( $user_id == 0 )
	{
		$user_id = param( 'orig_user_ID', 'integer', 0 );
	}
}
else
{
	$user_id = $edited_User->ID;
}

// -------------------  Get existing userfields: -------------------------------
$userfields = $DB->get_results( '
SELECT ufdf_ID, uf_ID, ufdf_type, ufdf_code, ufdf_name, ufdf_icon_name, uf_varchar, ufdf_required, ufdf_options, ufgp_order, ufdf_order, ufdf_suggest, ufdf_duplicated, ufgp_ID, ufgp_name
FROM
	(
		SELECT ufdf_ID, uf_ID, ufdf_type, ufdf_code, ufdf_name, ufdf_icon_name, uf_varchar, ufdf_required, ufdf_options, ufgp_order, ufdf_order, ufdf_suggest, ufdf_duplicated, ufgp_ID, ufgp_name
			FROM T_users__fields
				LEFT JOIN T_users__fielddefs ON uf_ufdf_ID = ufdf_ID
				LEFT JOIN T_users__fieldgroups ON ufdf_ufgp_ID = ufgp_ID
		WHERE uf_user_ID = '.$user_id.'

		UNION

		SELECT ufdf_ID, "0" AS uf_ID, ufdf_type, ufdf_code, ufdf_name, ufdf_icon_name, "" AS uf_varchar, ufdf_required, ufdf_options, ufgp_order, ufdf_order, ufdf_suggest, ufdf_duplicated, ufgp_ID, ufgp_name
			FROM T_users__fielddefs
				LEFT JOIN T_users__fieldgroups ON ufdf_ufgp_ID = ufgp_ID
		WHERE ufdf_required IN ( "recommended", "require" )
			AND ufdf_ID NOT IN ( SELECT uf_ufdf_ID FROM T_users__fields WHERE uf_user_ID = '.$user_id.' )
	) tfields
ORDER BY ufgp_order, ufdf_order, uf_ID' );

userfields_display( $userfields, $Form );

if( $action != 'view' )
{	// Edit mode
// ------------------- Add new field: -------------------------------
$Form->begin_fieldset( T_('Add new fields').( is_admin_page() ? get_manual_link( 'user-profile-tab-addnewfields' ) : '' ) );

	// -------------------  Display new added userfields: -------------------------------
	global $add_field_types, $Messages;

	if( $Messages->has_errors() )
	{	// Display new added fields(from submitted form) only if errors are exist
		if( is_array( $add_field_types ) && count( $add_field_types ) > 0 )
		{	// This case happens when user add a new required field and he doesn't fill it, then we should show all fields again
			foreach( $add_field_types as $add_field_type )
			{	// We use "foreach" because sometimes the user adds several fields with the same type
				$userfields = $DB->get_results( '
				SELECT ufdf_ID, "0" AS uf_ID, ufdf_type, ufdf_code, ufdf_name, ufdf_icon_name, "" AS uf_varchar, ufdf_required, ufdf_options, ufdf_suggest, ufdf_duplicated, ufgp_ID, ufgp_name
					FROM T_users__fielddefs
						LEFT JOIN T_users__fieldgroups ON ufdf_ufgp_ID = ufgp_ID
				WHERE ufdf_ID = '.intval( $add_field_type ) );

				userfields_display( $userfields, $Form, 'add', false );
			}
		}
	}

	$button_add_field = '<button type="submit" id="button_add_field" name="actionArray[add_field]" class="action_icon">'.get_icon( 'add' ).'</button>';

	$Form->select( 'new_field_type', param( 'new_field_type', 'integer', 0 ), 'callback_options_user_new_fields', T_('Add a field of type'), $button_add_field );

$Form->end_fieldset();
}

$Form->hidden( 'orig_user_ID', $user_id );

$Plugins->trigger_event( 'DisplayProfileFormFieldset', array(
			'Form' => & $Form,
			'User' => & $edited_User,
			'edit_layout' => 'private',
			'is_admin_page' => $is_admin_page,
		) );

	/***************  Buttons  **************/

if( $action != 'view' )
{ // Edit buttons
	$action_buttons = array( array( '', 'actionArray[update]', $new_user_creating ? T_('Create User!') : T_('Save Changes!'), 'SaveButton' ) );
	if( $is_admin )
	{
		// dh> TODO: Non-Javascript-confirm before trashing all settings with a misplaced click.
		$action_buttons[] = array( 'type' => 'submit', 'name' => 'actionArray[default_settings]', 'value' => T_('Restore defaults'), 'class' => 'ResetButton',
			'onclick' => "return confirm('".TS_('This will reset all your user settings.').'\n'.TS_('This cannot be undone.').'\n'.TS_('Are you sure?')."');" );
	}
	$Form->buttons( $action_buttons );
}


$Form->end_form();

?>
<script type="text/javascript">
	function replace_form_params( result, field_id )
	{
		field_id = ( typeof( field_id ) == 'undefined' ? '' : ' id="' + field_id + '"' );
		return result.replace( '#fieldstart#', '<?php echo str_ireplace( '$id$', "' + field_id + '", format_to_js( $Form->fieldstart ) ); ?>' )
			.replace( '#fieldend#', '<?php echo format_to_js( $Form->fieldend ); ?>' )
			.replace( '#labelclass#', '<?php echo format_to_js( $Form->labelclass ); ?>' )
			.replace( '#labelstart#', '<?php echo format_to_js( $Form->labelstart ); ?>' )
			.replace( '#labelend#', '<?php echo format_to_js( $Form->labelend ); ?>' )
			.replace( '#inputstart#', '<?php echo format_to_js( $Form->inputstart ); ?>' )
			.replace( '#inputend#', '<?php echo format_to_js( $Form->inputend ); ?>' );
	}

	jQuery( '#button_add_field' ).click( function ()
	{	// Action for the button when we want to add a new field in the Additional info
		var field_id = jQuery( this ).parent().prev().find( 'option:selected' ).val();

		if( field_id == '' )
		{	// Mark select element of field types as error
			field_type_error( '<?php echo TS_('Please select a field type.'); ?>' );
			// We should to stop the ajax request without field_id
			return false;
		}
		else
		{	// Remove an error class from the field
			field_type_error_clear();
		}

		var this_obj = jQuery( this );
		var params = '<?php
			global $b2evo_icons_type;
			echo empty( $b2evo_icons_type ) ? '' : '&b2evo_icons_type='.$b2evo_icons_type;
		?>';

		jQuery.ajax({
		type: 'POST',
		url: '<?php echo get_htsrv_url(); ?>anon_async.php',
		data: 'action=get_user_new_field&user_id=<?php echo $edited_User->ID; ?>&field_id=' + field_id + params,
		success: function(result)
			{
				result = ajax_debug_clear( result );
				if( result == '[0]' )
				{	// This field(not duplicated) already exists for current user
					field_type_error( '<?php echo TS_('You already added this field, please select another.'); ?>' );
				}
				else
				{
					result = replace_form_params( result );
					var field_duplicated = parseInt( result.replace( /^\[(\d+)\](.*)/, '$1' ) );
					if( field_duplicated == 0 )
					{	// This field is NOT duplicated
						var field_id = parseInt( result.replace( /(.*)fieldset id="ffield_uf_add_(\d+)_(.*)/, '$2' ) );
						// Remove option from select element
						jQuery( '#new_field_type option[value='+field_id+']').remove();
						if( jQuery( '[id^=uf_new_' + field_id + '], [id^=uf_add_' + field_id + ']' ).length > 0 )
						{	// This field already exists(on the html form, not in DB) AND user cannot add a duplicate
							field_type_error( '<?php echo TS_('You already added this field, please select another.'); ?>' );
							return false;
						}
					}
					// Print out new field on the form
					jQuery( '#ffield_new_field_type' ).before( result.replace( /^\[\d+\](.*)/, '$1' ) );
					// Show a button 'Add(+)' with new field
					jQuery( 'span[rel^=add_ufdf_]' ).show();

					bind_autocomplete( jQuery( '#ffield_new_field_type' ).prev().prev().find( 'input[id^=uf_add_][autocomplete=on]' ) );
				}
			}
		});

		return false;
	} );

	jQuery( document ).on( 'focus', '[rel^=ufdf_]', function ()
	{	// Auto select the value for the field of type
		var field_id = parseInt( jQuery( this ).attr( 'rel' ).replace( /^ufdf_(\d+)$/, '$1' ) );
		if( field_id > 0 )
		{	// Select an option with current field type
			jQuery( '#new_field_type' ).val( field_id );
			field_type_error_clear();
		}
	} );

	jQuery( '#new_field_type' ).change( function ()
	{	// Remove all errors messages from field "Add a field of type:"
		field_type_error_clear();
	} );

	function field_type_error( message )
	{	// Add an error message for the "field of type" select
		jQuery( 'select#new_field_type' ).addClass( 'field_error' );
		var span_error = jQuery( 'select#new_field_type' ).next().find( 'span.field_error' );
		if( span_error.length > 0 )
		{	// Replace a content of the existing span element
			span_error.html( message );
		}
		else
		{	// Create a new span element for error message
			jQuery( 'select#new_field_type' ).next().append( '<span class="field_error">' + message + '</span>' );
		}
	}

	function field_type_error_clear()
	{	// Remove an error style from the "field of type" select
		jQuery( 'select#new_field_type' ).removeClass( 'field_error' )
																			.next().find( 'span.field_error' ).remove();
	}

	<?php /*jQuery( 'span[rel^=add_ufdf_]' ).each( function()
	{	// Show only last button 'Add(+)' for each field type
		// These buttons is hidden by default to ignore browsers without javascript
		jQuery( 'span[rel=' + jQuery( this ).attr( 'rel' ) + ']:last' ).show();
	} );*/ ?>
	// Show a buttons 'Add(+)' for each field
	// These buttons is hidden by default to ignore a browsers without javascript
	jQuery( 'span[rel^=add_ufdf_]' ).show();

	jQuery( document ).on( 'click', 'span[rel^=add_ufdf_]', function()
	{	// Click event for button 'Add(+)'
		var this_obj = jQuery( this );
		var field_id = this_obj.attr( 'rel' ).replace( /^add_ufdf_(\d+)$/, '$1' );
		var params = '<?php
			global $b2evo_icons_type;
			echo empty( $b2evo_icons_type ) ? '' : '&b2evo_icons_type='.$b2evo_icons_type;
		?>';

		jQuery.ajax({
		type: 'POST',
		url: '<?php echo get_htsrv_url(); ?>anon_async.php',
		data: 'action=get_user_new_field&user_id=<?php echo $edited_User->ID; ?>&field_id=' + field_id + params,
		success: function( result )
			{
				result = ajax_debug_clear( result );
				if( result == '[0]' )
				{	// This field(not duplicated) already exists for current user
					field_type_error( '<?php echo TS_('You already added this field, please select another.'); ?>' );
				}
				else
				{
					result = replace_form_params( result );
					var field_duplicated = parseInt( result.replace( /^\[(\d+)\](.*)/, '$1' ) );
					if( field_duplicated == 0 )
					{	// This field is NOT duplicated
						field_type_error( '<?php echo TS_('You already added this field, please select another.'); ?>' );
						return false;
					}
					var cur_fieldset_obj = this_obj.parent().parent().parent();
					<?php /* // Remove current button 'Add(+)' and then we will show button with new added field
					this_obj.remove();*/ ?>
					// Print out new field on the form
					cur_fieldset_obj.after( result.replace( /^\[\d+\](.*)/, '$1' ) )
					// Show a button 'Add(+)' with new field
													.next().find( 'span[rel^=add_ufdf_]' ).show();

					var new_field = cur_fieldset_obj.next().find( 'input[id^=uf_add_]' );
					if( new_field.attr( 'autocomplete' ) == 'on' )
					{	// Bind autocomplete event
						bind_autocomplete( new_field );
					}
					// Set auto focus on new created field
					new_field.focus();
				}
			}
		} );
	} );

	jQuery( document ).on( 'mouseover', 'span[rel^=add_ufdf_]', function()
	{	// Grab event from input to show bubbletip
		jQuery( this ).parent().prev().focus();
		jQuery( this ).css( 'z-index', jQuery( this ).parent().prev().css( 'z-index' ) );
	} );
	jQuery( document ).on( 'mouseout', 'span[rel^=add_ufdf_]', function()
	{	// Grab event from input to hide bubbletip
		var input = jQuery( this ).parent().prev();
		if( input.is( ':focus' ) )
		{	// Don't hide bubbletip if current input is focused
			return false;
		}
		input.blur();
	} );

<?php
// JS code to add new organization for user
?>
	var org_fieldset_selector = '[id^="ffield_organizations_"]';
	var max_organizations = <?php echo $count_all_orgs; ?>;
	var user_org_num = 0;
	jQuery( document ).on( 'click', 'span.add_org', function()
	{ // Add new organization select box
		var this_obj = jQuery( this );
		var params = '<?php
			global $b2evo_icons_type;
			echo empty( $b2evo_icons_type ) ? '' : '&b2evo_icons_type='.$b2evo_icons_type;
		?>';

		params += ( typeof( remove_obj_after_org_adding ) != 'undefined' ? '&first_org=1' : '' );
		jQuery.ajax(
		{
			type: 'POST',
			url: '<?php echo get_htsrv_url(); ?>anon_async.php',
			data: 'action=get_user_new_org' + params,
			success: function( result )
			{
				result = replace_form_params( ajax_debug_clear( result ), 'ffield_organizations_' + user_org_num );
				var cur_fieldset_obj = this_obj.closest( org_fieldset_selector );
				cur_fieldset_obj.after( result );

				if( typeof( remove_obj_after_org_adding ) != 'undefined' )
				{ // Delete last fieldset
					remove_obj_after_org_adding.remove();
					delete remove_obj_after_org_adding;
				}

				if( jQuery( org_fieldset_selector ).length >= max_organizations )
				{ // It was last organization, Hide all "add" buttons
					jQuery( 'span.add_org' ).hide();
				}

				// Show/Hide all "remove" buttons
				( jQuery( org_fieldset_selector ).length > 1 ) ?
					jQuery( 'span.remove_org' ).show() :
					jQuery( 'span.remove_org' ).hide();

				user_org_num++;
			}
		} );
	} );

	jQuery( document ).on( 'click', 'span.remove_org', function()
	{ // Remove organization select box
		if( jQuery( org_fieldset_selector ).length > 1 )
		{
			jQuery( this ).closest( org_fieldset_selector ).remove();
		}
		else
		{ // Add a form to select an organization
			remove_obj_after_org_adding = jQuery( this ).closest( org_fieldset_selector );
			jQuery( this ).parent().find( 'span.add_org' ).click();
		}

		if( jQuery( org_fieldset_selector ).length < max_organizations )
		{ // Show the "add" buttons
			jQuery( 'span.add_org' ).show();
		}

		// Show/Hide all "remove" buttons
		( jQuery( org_fieldset_selector ).length > 0 ) ?
			jQuery( 'span.remove_org' ).show() :
			jQuery( 'span.remove_org' ).hide();
	} );
</script>

<script type="text/javascript">
function bind_autocomplete( field_objs )
{	// Bind autocomplete plugin event
	if( field_objs.length > 0 )
	{	// If selected elements are exists
		field_objs.autocomplete( {
			source: function(request, response) {
				jQuery.getJSON( '<?php echo get_htsrv_url(); ?>anon_async.php?action=get_user_field_autocomplete', {
					term: request.term, attr_id: this.element[0].getAttribute( 'id' )
				}, response);
			},
		} );
	}
}
// Plugin jQuery(...).live() doesn't work with autocomplete
// We should assign an autocomplete event for each new added field
bind_autocomplete( jQuery( 'input[id^=uf_][autocomplete=on]' ) );
</script>
<?php
// AJAX changing of an accept status of organizations for each user
echo_user_organization_js();

// Location
echo_regional_js( 'edited_user', user_region_visible() );
?>