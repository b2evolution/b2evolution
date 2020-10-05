<?php
/**
 * This file implements the UI view for the user properties.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
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

$has_full_access = check_user_perm( 'users', 'edit' );
$has_moderate_access = $current_User->can_moderate_user( $edited_User->ID );
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
		$form_title = '<span class="nowrap">'.TB_('New user profile').'</span>';
	}
	else
	{
		$form_text_title = '<span class="nowrap">'.TB_( 'Edit profile' ).'</span>'.get_manual_link( 'user-profile-tab' ); // used for js confirmation message on leave the changed form
		$form_title = get_usertab_header( $edited_User, 'profile', $form_text_title );
		$Form->title_fmt = '$title$';
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
	$Form->begin_fieldset( TB_( 'New user' ).get_manual_link( 'user-edit' ), array( 'class' => 'fieldset clear' ) );

	// Primary and secondary groups:
	display_user_groups_selectors( $edited_User, $Form );

	$Form->text_input( 'edited_user_level', $edited_User->get('level'), 2, TB_('User level'), '[0 - 10]', array( 'required' => true ) );

	$email_fieldnote = '<a href="mailto:'.$edited_User->get('email').'">'.get_icon( 'email', 'imgtag', array('title'=>TB_('Send an email')) ).'</a>';
	$Form->email_input( 'edited_user_email', $edited_User->email, 30, TB_('Email'), array( 'maxlength' => 255, 'required' => true, 'note' => $email_fieldnote ) );
	$Form->select_input_array( 'edited_user_status', $edited_User->get( 'status' ), get_user_statuses(), TB_( 'Account status' ) );

	$Form->end_fieldset();
}

	/***************  Identity  **************/

$Form->begin_fieldset( TB_('Identity').( is_admin_page() ? get_manual_link( 'user-profile-tab-identity' ) : '' ) );

if( ($url = $edited_User->get('url')) != '' )
{
	if( !preg_match('#://#', $url) )
	{
		$url = 'http://'.$url;
	}
	$url_fieldnote = '<a href="'.$url.'" target="_blank">'.get_icon( 'play', 'imgtag', array('title'=>TB_('Visit the site')) ).'</a>';
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
				$forbid_link = action_icon( TB_('Forbid using as main profile picture'), 'move_down_orange', $ctrl_param.'&amp;action=forbid_avatar&amp;'.url_crumb( 'user' ), ' '.TB_('Forbid using as main profile picture'), 3, 4 ).'<br />';
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
			$change_picture_text = T_('Change').' &raquo;';
			$change_picture_title = T_('Change profile picture').'...';
			$change_picture_icon = 'edit';
		}
		else
		{ // If user has no profile picture yet
			$change_picture_text = T_('Upload now').' &raquo;';
			$change_picture_title = T_('Upload profile picture').'...';
			$change_picture_icon = 'move_up_green';
		}

		// Main profile picture with action icons to modify it
		$user_pictures = '<div class="avatartag main image_rounded">'
				.$edited_User->get_avatar_imgtag( 'crop-top-320x320', 'avatar', 'top', true, '', 'user', '160x160' )
				.'<div class="avatar_actions">'
					.action_icon( $change_picture_title, $change_picture_icon, get_user_settings_url( 'avatar', $edited_User->ID ), ' '.$change_picture_text, 3, 4 );
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

		$Form->info( TB_('Profile picture'), $user_pictures );
	}

	$Form->text_input( 'edited_user_login', $edited_User->login, 20, /* TRANS: noun */ TB_('Login'), '', array( 'maxlength' => 20, 'required' => true ) );

	$firstname_editing = $Settings->get( 'firstname_editing' );
	if( ( in_array( $firstname_editing, $edited_user_perms ) && $edited_User->ID == $current_User->ID ) || ( $firstname_editing != 'hidden' && $has_moderate_access ) )
	{
		$Form->text_input( 'edited_user_firstname', $edited_User->firstname, 20, TB_('First name'), '', array( 'maxlength' => 50, 'required' => ( $firstname_editing == 'edited-user-required' ) ) );
	}

	$lastname_editing = $Settings->get( 'lastname_editing' );
	if( ( in_array( $lastname_editing, $edited_user_perms ) && $edited_User->ID == $current_User->ID ) || ( $lastname_editing != 'hidden' && $has_moderate_access ) )
	{
		$Form->text_input( 'edited_user_lastname', $edited_User->lastname, 20, TB_('Last name'), '', array( 'maxlength' => 50, 'required' => ( $lastname_editing == 'edited-user-required' ) ) );
	}

	$nickname_editing = $Settings->get( 'nickname_editing' );
	if( ( in_array( $nickname_editing, $edited_user_perms ) && $edited_User->ID == $current_User->ID ) || ( $nickname_editing != 'hidden' && $has_moderate_access ) )
	{
		$Form->text_input( 'edited_user_nickname', $edited_User->nickname, 20, TB_('Nickname'), '', array( 'maxlength' => 50, 'required' => ( $nickname_editing == 'edited-user-required' ) ) );
	}

	if( $edited_User->ID == $current_User->ID || $has_moderate_access )
	{
		$Form->radio( 'edited_user_gender', $edited_User->get('gender'), array(
				array( 'M', TB_('A man') ),
				array( 'F', TB_('A woman') ),
				array( 'O', TB_('Other') ),
			), TB_('I am'), false, '' );
	}

	$button_refresh_regional = '<button id="%s" type="submit" name="actionArray[refresh_regional]" class="action_icon refresh_button">'.get_icon( 'refresh' ).'</button>';
	$button_refresh_regional .= '<img src="'.$rsc_url.'img/ajax-loader.gif" alt="'.TB_('Loading...').'" title="'.TB_('Loading...').'" style="display:none;margin:2px 0 0 5px" align="top" />';

	if( user_country_visible() )
	{	// Display a select input for Country
		$CountryCache = & get_CountryCache();
		$Form->select_country( 'edited_user_ctry_ID',
				$edited_User->ctry_ID,
				$CountryCache,
				TB_('Country'),
				array(	// field params
						'required' => ( $Settings->get( 'location_country' ) == 'required' ? 'mark_only' : false ), // true if Country is required
						'allow_none' => // Allow none value:
						                $has_moderate_access || // Current user has permission to moderate users
						                empty( $edited_User->ctry_ID ) || // Country is not defined yet
						                ( !empty( $edited_User->ctry_ID ) && $Settings->get( 'location_country' ) != 'required' ) // Country is defined but this field is not required
					)
			);
	}

	if( user_region_visible() )
	{	// Display a select input for Region
		$regions_option_list = get_regions_option_list( $edited_User->ctry_ID, $edited_User->rgn_ID, array( 'none_option_text' => TB_( 'Unknown' ) ) );
		$Form->select_input_options( 'edited_user_rgn_ID',
				$regions_option_list,
				TB_( 'Region' ),
				sprintf( $button_refresh_regional, 'button_refresh_region' ), // Button to refresh regions list
				array(	// field params
						'required' => ( $Settings->get( 'location_region' ) == 'required' ? 'mark_only' : false ) // true if Region is required
					)
			);
	}

	if( user_subregion_visible() )
	{	// Display a select input for Subregion
		$subregions_option_list = get_subregions_option_list( $edited_User->rgn_ID, $edited_User->subrg_ID, array( 'none_option_text' => TB_( 'Unknown' ) ) );
		$Form->select_input_options( 'edited_user_subrg_ID',
				$subregions_option_list,
				TB_( 'Sub-region' ),
				sprintf( $button_refresh_regional, 'button_refresh_subregion' ), // Button to refresh subregions list
				array(	// field params
						'required' => ( $Settings->get( 'location_subregion' ) == 'required' ? 'mark_only' : false ) // true if Subregion is required
					)
			);
	}

	if( user_city_visible() )
	{	// Display a select input for City
		$cities_option_list = get_cities_option_list( $edited_User->ctry_ID, $edited_User->rgn_ID, $edited_User->subrg_ID, $edited_User->city_ID, array( 'none_option_text' => TB_( 'Unknown' ) ) );
		$Form->select_input_options( 'edited_user_city_ID',
				$cities_option_list,
				TB_( 'City' ),
				sprintf( $button_refresh_regional, 'button_refresh_city' ), // Button to refresh cities list
				array(	// field params
						'required' => ( $Settings->get( 'location_city' ) == 'required' ? 'mark_only' : false ) // true if City is required
					)
			);
	}

	if( $Settings->get( 'self_selected_age_group' ) != 'hidden' )
	{
		$Form->begin_line( TB_('My age group'), 'edited_user_age_min', '', array( 'required' => $Settings->get( 'self_selected_age_group' ) == 'required' ) );
			$Form->text_input( 'edited_user_age_min', $edited_User->age_min, 3, '', '', array( 'required' => $Settings->get( 'self_selected_age_group' ) == 'required', 'input_suffix' => ' '.TB_('to').' ' ) );
			$Form->text_input( 'edited_user_age_max', $edited_User->age_max, 3, '', '', array( 'required' => $Settings->get( 'self_selected_age_group' ) == 'required' ) );
		$Form->end_line();
	}

	if( $Settings->get( 'birthday_year' ) != 'hidden' || $Settings->get( 'birthday_month') != 'hidden' || $Settings->get( 'birthday_day') != 'hidden' )
	{
		$Form->begin_line( TB_('Birthday'), 'edited_user_birthday_month', '', array( 'required' => ( $Settings->get( 'birthday_year' ) == 'required' || $Settings->get( 'birthday_month') == 'required' || $Settings->get( 'birthday_day') == 'required' ) ) );
			if( $Settings->get( 'birthday_month' ) != 'hidden' )
			{
				global $month;
				$birthday_months = array();
				if( $Settings->get( 'birthday_month' ) == 'optional' )
				{
					$birthday_months[NULL] = '---';
				}
				foreach( $month as $key => $value )
				{
					if( $key == '00' )
					{
						continue;
					}
					$birthday_months[(int) $key] = $value;
				}
				$Form->select_input_array( 'edited_user_birthday_month', $edited_User->birthday_month, $birthday_months, '', '', array( 'force_keys_as_values' => true ) );
			}

			if( $Settings->get( 'birthday_day' ) != 'hidden' )
			{
				$birthday_days = range( 1, 31 );
				if( $Settings->get( 'birthday_day' ) == 'optional' )
				{
					$birthday_days = array( NULL => '---' ) + $birthday_days;
				}
				$Form->select_input_array( 'edited_user_birthday_day', $edited_User->birthday_day, $birthday_days, '' );
			}

			if( $Settings->get( 'birthday_year' ) != 'hidden' )
			{
				$birthday_years = range( (int) date( 'Y' ), 1900, -1 );
				if( $Settings->get( 'birthday_year' ) == 'optional' )
				{
					$birthday_years = array( NULL => '---' ) + $birthday_years;
				}
				$Form->select_input_array( 'edited_user_birthday_year', $edited_User->birthday_year, $birthday_years, '' );
			}
		$Form->end_line();
	}

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
				$perm_edit_orgs = check_user_perm( 'orgs', 'edit', false, $user_Organization );
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
					$accept_icon = get_icon( 'allowback', 'imgtag', array_merge( array( 'title' => TB_('Membership has been accepted.') ), $accept_icon_params ) );
				}
				else
				{ // Organization is not accepted by admin yet
					$accept_icon = get_icon( 'bullet_red', 'imgtag', array_merge( array( 'title' => TB_('Membership pending acceptance.') ), $accept_icon_params ) );
				}
				$inputstart_icon = $accept_icon.' ';
			}

			if( $org_ID > 0 && ! $perm_edit_orgs && $org_data['accepted'] )
			{ // Display only info of the assigned organization
				$Form->infostart = $Form->infostart.$inputstart_icon;
				$org_role_input = ( empty( $org_data['role'] ) ? '' : ' &nbsp; <strong>'.TB_('Role').':</strong> '.$org_data['role'] ).' &nbsp; '
					.'<input type="hidden" name="org_roles[]" value="" />';
				$org_priority_input = ( empty( $org_data['role'] ) ? '' : ' &nbsp; <strong>'.TB_('Order').':</strong> '.$org_data['priority'] ).' &nbsp; '
						.'<input type="hidden" name="org_priorities[]" value="" />';
				$org_hidden_fields = '<input type="hidden" name="organizations[]" value="'.$org_ID.'" />';
				$Form->info_field( TB_('Organization'), $org_data['name'], array(
						'field_suffix' => $org_role_input.$org_priority_input.$org_add_icon.$org_remove_icon.$org_hidden_fields,
						'name'         => 'organizations[]'
					) );
			}
			else
			{ // Allow to update the organization fields
				$perm_edit_org_role = false;
				$perm_edit_org_priority = false;
				if( ! empty( $org_ID ) )
				{
					$perm_edit_org_role = ( $user_Organization->owner_user_ID == $current_User->ID ) || ( $user_Organization->perm_role == 'owner and member' && $org_data['accepted'] );
					$perm_edit_org_priority = ( $user_Organization->owner_user_ID == $current_User->ID || $perm_edit_orgs );
				}

				$Form->output = false;
				$Form->switch_layout( 'none' );
				if( $perm_edit_org_role )
				{
					$org_role_input = ' &nbsp; <strong>'.TB_('Role').':</strong> '.
							$Form->text_input( 'org_roles[]', $org_data['role'], 20, '', '', array( 'maxlength' => 255 ) ).' &nbsp; ';
				}
				else
				{
					$org_role_input = ( empty( $org_data['role'] ) ? '' : ' &nbsp; <strong>'.TB_('Role').':</strong> '.$org_data['role'] ).' &nbsp; '
						.'<input type="hidden" name="org_roles[]" value="" />';
				}
				if( $perm_edit_org_priority )
				{
					$org_priority_input = ' &nbsp; <strong>'.TB_('Order').':</strong> '.
							$Form->text_input( 'org_priorities[]', $org_data['priority'], 10, '', '', array( 'type' => 'number', 'min' => -2147483648, 'max' => 2147483647 ) ).' &nbsp; ';
				}
				else
				{
					$org_priority_input = ( empty( $org_data['priority'] ) ? '' : ' &nbsp; <strong>'.TB_('Order').':</strong> '.$org_data['priority'] ).' &nbsp; '
						.'<input type="hidden" name="org_priorities[]" value="" />';
				}
				$Form->switch_layout( NULL );
				$Form->output = true;

				$Form->inputstart = $Form->inputstart.$inputstart_icon;
				$Form->select_input_object( 'organizations[]', $org_ID, $OrganizationCache, TB_('Organization'), array(
						'allow_none'   => $org_allow_none,
						'field_suffix' => $org_role_input.$org_priority_input.$org_add_icon.$org_remove_icon
					) );
			}
			$Form->infostart = $form_infostart;
			$Form->inputstart = $form_inputstart;
		}
	}

	if( $new_user_creating )
	{
		$Form->text_input( 'edited_user_source', $edited_User->source, 30, TB_('Source'), '', array( 'maxlength' => 30 ) );
	}

	if( $edited_User->get_field_url() != '' )
	{	// At least one url field is existing for current user
		$Form->info( TB_('URL'), $edited_User->get_field_link(), TB_('(This is the main URL advertised for this profile. It is automatically selected from the URLs you enter in the fields below.)') );
	}
}
else
{ // display only

	if( $Settings->get('allow_avatars') )
	{
		// Main profile picture:
		$user_pictures = '<div class="avatartag main image_rounded">'
				.$edited_User->get_avatar_imgtag( 'crop-top-320x320', 'avatar', 'top', true, '', 'user', '160x160' )
			.'<div class="clear"></div></div>';
		// Append the other pictures to main avatar:
		$user_avatars = $edited_User->get_avatar_Links();
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
		$Form->info( TB_('Profile picture'), $user_pictures );
	}

	$Form->info( /* TRANS: noun */ TB_('Login'), $edited_User->get('login') );
	$Form->info( TB_('First name'), $edited_User->get('firstname') );
	$Form->info( TB_('Last name'), $edited_User->get('lastname') );
	$Form->info( TB_('Nickname'), $edited_User->get('nickname') );
	$Form->info( TB_('Identity shown'), $edited_User->get('preferredname') );

	$user_gender = $edited_User->get( 'gender' );
	if( ! empty( $user_gender ) )
	{
		$Form->info( TB_('Gender'), $edited_User->get_gender() );
	}

	if( ! empty( $edited_User->ctry_ID ) )
	{	// Display country
		load_class( 'regional/model/_country.class.php', 'Country' );
		$Form->info( TB_('Country'), $edited_User->get_country_name() );
	}

	if( ! empty( $edited_User->rgn_ID ) )
	{	// Display region
		load_class( 'regional/model/_region.class.php', 'Region' );
		$Form->info( TB_( 'Region' ), $edited_User->get_region_name() );
	}

	if( ! empty( $edited_User->rgn_ID ) )
	{	// Display sub-region
		load_class( 'regional/model/_subregion.class.php', 'Subregion' );
		$Form->info( TB_( 'Sub-region' ), $edited_User->get_subregion_name() );
	}

	if( ! empty( $edited_User->city_ID ) )
	{	// Display city
		load_class( 'regional/model/_city.class.php', 'City' );
		$Form->info( TB_( 'City' ), $edited_User->get_city_name() );
	}

	$Form->info( TB_('My age group'), ( $edited_User->get( 'age_min' ) > 0 || $edited_User->get( 'age_max' ) > 0 ? $edited_User->get( 'age_min' ).' '.TB_('to').' '.$edited_User->get( 'age_max' ) : '' ) );

	// Organizations:
	$user_organizations = $edited_User->get_organizations();
	$org_names = array();
	foreach( $user_organizations as $org )
	{
		$org_names[] = empty( $org->url ) ? $org->name : '<a href="'.$org->url.'" rel="nofollow" target="_blank">'.$org->name.'</a>';
	}
	$Form->info( TB_('Organizations'), implode( ' &middot; ', $org_names ) );
}

$Form->end_fieldset();

	/***************  Password  **************/

if( empty( $edited_User->ID ) && $action != 'view' )
{	// Display password fields for new creating user:
	$Form->begin_fieldset( TB_('Password') );
		$Form->radio( 'init_pass', param( 'init_pass', 'string', 'user' ), array(
					array( 'user', TB_('User must initialize') ),
					array( 'admin', TB_('Initialize as below:') ),
			 ), TB_('Initial password'), true );
		$Form->password_input( 'edited_user_pass1', '', 20, TB_('New password'), array( 'maxlength' => 50, 'autocomplete'=>'off' ) );
		$Form->password_input( 'edited_user_pass2', '', 20, TB_('Confirm new password'), array( 'note'=>sprintf( TB_('Minimum length: %d characters.'), $Settings->get('user_minpwdlen') ), 'maxlength' => 50, 'autocomplete'=>'off' ) );
		$Form->checkbox( 'send_pass_email', param( 'send_pass_email', 'integer', 1 ), TB_('Send email'), TB_('Inform user by email') );
?>
<script>
function new_user_pass_visibility()
{
	jQuery( '#ffield_edited_user_pass1, #ffield_edited_user_pass2' ).toggle( jQuery( 'input[name=init_pass]:checked' ).val() == 'admin' );
}
jQuery( 'input[name=init_pass]' ).click( new_user_pass_visibility );
new_user_pass_visibility();
</script>
<?php
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
SELECT ufdf_ID, uf_ID, ufdf_type, ufdf_code, ufdf_name, ufdf_icon_name, uf_varchar, ufdf_required, ufdf_visibility, ufdf_options, ufgp_order, ufdf_order, ufdf_suggest, ufdf_duplicated, ufdf_grp_ID, ufgp_ID, ufgp_name, ufdf_ufgp_ID, ufdf_bubbletip
FROM
	(
		SELECT ufdf_ID, uf_ID, ufdf_type, ufdf_code, ufdf_name, ufdf_icon_name, uf_varchar, ufdf_required, ufdf_visibility, ufdf_options, ufgp_order, ufdf_order, ufdf_suggest, ufdf_duplicated, ufdf_grp_ID, ufgp_ID, ufgp_name, ufdf_ufgp_ID, ufdf_bubbletip
			FROM T_users__fields
				LEFT JOIN T_users__fielddefs ON uf_ufdf_ID = ufdf_ID
				LEFT JOIN T_users__fieldgroups ON ufdf_ufgp_ID = ufgp_ID
		WHERE uf_user_ID = '.$user_id.'

		UNION

		SELECT ufdf_ID, "0" AS uf_ID, ufdf_type, ufdf_code, ufdf_name, ufdf_icon_name, "" AS uf_varchar, ufdf_required, ufdf_visibility, ufdf_options, ufgp_order, ufdf_order, ufdf_suggest, ufdf_duplicated, ufdf_grp_ID, ufgp_ID, ufgp_name, ufdf_ufgp_ID, ufdf_bubbletip
			FROM T_users__fielddefs
				LEFT JOIN T_users__fieldgroups ON ufdf_ufgp_ID = ufgp_ID
		WHERE ufdf_required IN ( "recommended", "require" )
			AND ufdf_ID NOT IN ( SELECT uf_ufdf_ID FROM T_users__fields WHERE uf_user_ID = '.$user_id.' )
	) tfields
ORDER BY ufgp_order, ufdf_order, uf_ID' );

userfields_display( $userfields, $Form, 'new', true, $user_id );

if( $action != 'view' )
{	// Edit mode
// ------------------- Add new field: -------------------------------
$Form->begin_fieldset( TB_('Add new fields').( is_admin_page() ? get_manual_link( 'user-profile-tab-addnewfields' ) : '' ) );

	// -------------------  Display new added userfields: -------------------------------
	global $add_field_types, $Messages;

	if( $Messages->has_errors() )
	{	// Display new added fields(from submitted form) only if errors are exist
		if( is_array( $add_field_types ) && count( $add_field_types ) > 0 )
		{	// This case happens when user add a new required field and he doesn't fill it, then we should show all fields again
			foreach( $add_field_types as $add_field_type )
			{	// We use "foreach" because sometimes the user adds several fields with the same type
				$userfields = $DB->get_results( '
				SELECT ufdf_ID, "0" AS uf_ID, ufdf_type, ufdf_code, ufdf_name, ufdf_icon_name, "" AS uf_varchar, ufdf_required, ufdf_visibility, ufdf_options, ufdf_suggest, ufdf_duplicated, ufgp_ID, ufgp_name, ufdf_ufgp_ID, ufdf_bubbletip
					FROM T_users__fielddefs
						LEFT JOIN T_users__fieldgroups ON ufdf_ufgp_ID = ufgp_ID
				WHERE ufdf_ID = '.intval( $add_field_type ) );

				userfields_display( $userfields, $Form, 'add', false, $user_id );
			}
		}
	}

	$button_add_field = '<button type="submit" id="button_add_field" name="actionArray[add_field]" class="btn btn-default">'.TB_('Add').'</button>';

	$Form->select_input( 'new_field_type', param( 'new_field_type', 'integer', 0 ), 'callback_options_user_new_fields', TB_('Add a field of type'), array( 'field_suffix' => $button_add_field ) );

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
	$action_buttons = array( array( '', 'actionArray[update]', $new_user_creating ? TB_('Create User!') : TB_('Save Changes!'), 'SaveButton' ) );
	if( $is_admin )
	{
		// dh> TODO: Non-Javascript-confirm before trashing all settings with a misplaced click.
		$action_buttons[] = array( 'type' => 'submit', 'name' => 'actionArray[default_settings]', 'value' => TB_('Restore defaults'), 'class' => 'ResetButton',
			'onclick' => "return confirm('".TS_('This will reset all your user settings.').'\n'.TS_('This cannot be undone.').'\n'.TS_('Are you sure?')."');" );
	}
	$Form->buttons( $action_buttons );
}


$Form->end_form();

global $b2evo_icons_type;
$user_identity_form_config = array(
		'fieldstart' => str_ireplace( '$id$', "' + field_id + '", $Form->fieldstart ),
		'fieldend'   => $Form->fieldend,
		'labelclass' => $Form->labelclass,
		'labelstart' => $Form->labelstart,
		'labelend'   => $Form->labelend,
		'inputstart' => $Form->inputstart,
		'inputend'   => $Form->inputend,

		'msg_select_field_type'   => T_('Please select a field type.'),
		'msg_field_already_added' => T_('You already added this field, please select another.'),

		'params'  => empty( $b2evo_icons_type ) ? '' : '&b2evo_icons_type='.$b2evo_icons_type,
		'user_ID' => $edited_User->ID,
		'max_organizations' => $count_all_orgs,
	);

expose_var_to_js( 'evo_user_identity_form_config', evo_json_encode( $user_identity_form_config ) );

// AJAX changing of an accept status of organizations for each user
echo_user_organization_js();

// Location
echo_regional_js( 'edited_user', user_region_visible() );
?>
