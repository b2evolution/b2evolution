<?php
/**
 * This file implements the UI view for the user/group list for user/group editing.
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
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id: _user_list.view.php 6411 2014-04-07 15:17:33Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'users/model/_userlist.class.php', 'UserList' );

/**
 * @var User
 */
global $current_User;
/**
 * @var GeneralSettings
 */
global $Settings;
/**
 * @var UserSettings
 */
global $UserSettings;
/**
 * @var DB
 */
global $DB;

global $collections_Module, $admin_url, $action;

if( !isset( $display_params ) )
{ // init display_params
	$display_params = array();
}

// query which groups have users (in order to prevent deletion of groups which have users)
global $usedgroups;	// We need this in a callback below
$usedgroups = $DB->get_col( 'SELECT grp_ID
                             FROM T_groups INNER JOIN T_users ON user_grp_ID = grp_ID
							 GROUP BY grp_ID');

$UserList = new UserList( 'admin', $UserSettings->get('results_per_page'), 'users_', array( 'join_city' => false ) );

$default_filters = array( 'order' => '/user_lastseen_ts/D' );

$UserList->title = T_('Groups & Users').get_manual_link('users_and_groups');

/*
 * Table icons:
 */
if( $current_User->check_perm( 'users', 'edit', false ) )
{	// create new user/group link
	$UserList->global_icon( T_('Refresh users list...'), 'refresh', '?ctrl=users&amp;filter=refresh', T_('Refresh').' &raquo;', 3, 4 );
	$UserList->global_icon( T_('Create a new user...'), 'new', '?ctrl=user&amp;action=new&amp;user_tab=profile', T_('Add user').' &raquo;', 3, 4 );
	$UserList->global_icon( T_('Create a new group...'), 'new', '?ctrl=groups&amp;action=new', T_('Add group').' &raquo;', 3, 4 );
}


$UserList->set_default_filters( $default_filters );
$UserList->load_from_Request();


if( $UserList->filters['group'] != -1 )
{ // List is grouped

	/*
	 * Grouping params:
	 */
	$UserList->group_by = 'grp_ID';


	/*
	 * Group columns:
	 */
	$UserList->grp_cols[] = array(
							'td_class' => 'firstcol'.($current_User->check_perm( 'users', 'edit', false ) ? '' : ' lastcol' ),
							'td_colspan' => -1,  // nb_colds - 1
							'td' => '<a href="?ctrl=groups&amp;grp_ID=$grp_ID$">$grp_name$</a>'
											.'~conditional( (#grp_ID# == '.$Settings->get('newusers_grp_ID').'), \' <span class="notes">('.T_('default group for new users').')</span>\' )~',
						);

	function grp_actions( & $row )
	{
		global $usedgroups, $Settings, $current_User;

		$r = '';
		if( $current_User->check_perm( 'users', 'edit', false ) )
		{
			$r = action_icon( T_('Edit this group...'), 'edit', regenerate_url( 'ctrl,action', 'ctrl=groups&amp;action=edit&amp;grp_ID='.$row->grp_ID ) );

			$r .= action_icon( T_('Duplicate this group...'), 'copy', regenerate_url( 'ctrl,action', 'ctrl=groups&amp;action=new&amp;grp_ID='.$row->grp_ID ) );

			if( ($row->grp_ID != 1) && ($row->grp_ID != $Settings->get('newusers_grp_ID')) && !in_array( $row->grp_ID, $usedgroups ) )
			{ // delete
				$r .= action_icon( T_('Delete this group!'), 'delete', regenerate_url( 'ctrl,action', 'ctrl=groups&amp;action=delete&amp;grp_ID='.$row->grp_ID.'&amp;'.url_crumb('group') ) );
			}
			else
			{
				$r .= get_icon( 'delete', 'noimg' );
			}
		}
		return $r;
	}
	$UserList->grp_cols[] = array(
							'td_class' => 'shrinkwrap',
							'td' => '%grp_actions( {row} )%',
						);

}

/*
 * Data columns:
 */
$UserList->cols[] = array(
						'th' => T_('ID'),
						'th_class' => 'shrinkwrap small',
						'td_class' => 'shrinkwrap small',
						'order' => 'user_ID',
						'td' => '$user_ID$',
					);

if( $Settings->get('allow_avatars') )
{
	function user_avatar( $user_ID )
	{
		global $Blog;

		$UserCache = & get_UserCache();
		$User = & $UserCache->get_by_ID( $user_ID );

		return $User->get_identity_link( array(
			'link_text' => 'only_avatar',
			'thumb_size' => 'crop-top-48x48',
			) );
	}
	$UserList->cols[] = array(
							'th' => T_('Picture'),
							'th_class' => 'shrinkwrap small',
							'td_class' => 'shrinkwrap center small',
							'order' => 'has_picture',
							'default_dir' => 'D',
							'td' => '%user_avatar( #user_ID#, #user_avatar_file_ID# )%',
						);
}

$UserList->cols[] = array(
						'th' => T_('Login'),
						'th_class' => 'shrinkwrap small',
						'td_class' => 'small',
						'order' => 'user_login',
						'td' => '%get_user_identity_link( #user_login#, #user_ID#, "profile", "text" )%',
					);

$nickname_editing = $Settings->get( 'nickname_editing' );
if( $nickname_editing != 'hidden' && $current_User->check_perm( 'users', 'edit' ) )
{
	$UserList->cols[] = array(
							'th' => T_('Nickname'),
							'th_class' => 'shrinkwrap small',
							'td_class' => 'small',
							'order' => 'user_nickname',
							'td' => '$user_nickname$',
						);
}

$UserList->cols[] = array(
						'th' => T_('Name'),
						'th_class' => 'small',
						'td_class' => 'small',
						'order' => 'user_lastname, user_firstname',
						'td' => '$user_firstname$ $user_lastname$',
					);

$UserList->cols[] = array(
						'th' => T_('Gender'),
						'th_class' => 'shrinkwrap small',
						'td_class' => 'shrinkwrap small',
						'order' => 'user_gender',
						'td' => '$user_gender$',
					);

$UserList->cols[] = array(
						'th' => T_('Country'),
						'th_class' => 'shrinkwrap small',
						'td_class' => 'shrinkwrap small',
						'order' => 'c.ctry_name',
						'td' => '%country_flag( #ctry_code#, #ctry_name#, "w16px", "flag", "", false, true, "", false )% $ctry_name$',
					);

function user_mailto( $email )
{
	if( empty( $email ) )
	{
		return '&nbsp;';
	}
	return action_icon( T_('Email').': '.$email, 'email', 'mailto:'.$email, T_('Email') );
}

function user_pm ( $user_ID, $user_login )
{
	global $current_User;

	if( $user_ID == $current_User->ID )
	{
		return '&nbsp;';
	}

	$UserCache = & get_UserCache();
	$User = & $UserCache->get_by_ID( $user_ID );
	if( $User && ( $User->get_msgform_possibility() == 'PM' ) )
	{ // return new pm link only, if current User may send private message to User
		return action_icon( T_('Private Message').': '.$user_login, 'comments', '?ctrl=threads&action=new&user_login='.$user_login );
	}

	return '';
}

function user_status( $user_status, $user_ID )
{
	global $current_User;

	$user_status_icons = get_user_status_icons( true );
	$status_content = $user_status_icons[ $user_status ];

	if( is_admin_page() && ( $current_User->check_perm( 'users', 'edit' ) ) )
	{ // current User is an administrator and view is displayed on admin interface, return link to user admin tab
		return '<a href="'.get_user_identity_url( $user_ID, 'admin' ).'">'.$status_content.'</a>';
	}

	return $status_content;
}

if( isset($collections_Module) )
{	// We are handling blogs:
	$UserList->cols[] = array(
							'th' => T_('Blogs'),
							'order' => 'nb_blogs',
							'th_class' => 'shrinkwrap small',
							'td_class' => 'center small',
							'td' => '~conditional( (#nb_blogs# > 0), \'<a href="admin.php?ctrl=user&amp;user_tab=activity&amp;user_ID=$user_ID$" title="'.format_to_output( T_('View personal blogs'), 'htmlattr' ).'">$nb_blogs$</a>\', \'&nbsp;\' )~',
						);
}

if( $current_User->check_perm( 'users', 'edit', false ) )
{
	$UserList->cols[] = array(
						'th' => T_('Source'),
						'th_class' => 'shrinkwrap small',
						'td_class' => 'center small',
						'order' => 'user_source',
						'default_dir' => 'D',
						'td' => '$user_source$',
					);
}

$UserList->cols[] = array(
					'th' => T_('Registered'),
					'th_class' => 'shrinkwrap small',
					'td_class' => 'center small',
					'order' => 'user_created_datetime',
					'default_dir' => 'D',
					'td' => '%mysql2localedate( #user_created_datetime#, "M-d" )%',
				);

/**
 * Get a flag of registration country with a link to user's sessions page
 *
 * @param integer User ID
 * @param string Country code
 * @param string Country name
 * @return string
*/
function user_reg_country( $user_ID, $country_code, $country_name )
{
	global $current_User, $admin_url;

	$flag = country_flag( $country_code, $country_name, 'w16px', 'flag', '', false, true, '', false );
	if( empty( $flag ) )
	{ // No flag or registration country
		$flag = '?';
	}

	if( $current_User->check_perm( 'users', 'edit' ) )
	{ // Only users with edit all users permission can see the 'Sessions' tab
		$flag = '<a href="'.$admin_url.'?ctrl=user&amp;user_tab=sessions&amp;user_ID='.$user_ID.'">'.$flag.'</a>';
	}

	return $flag;
}
$UserList->cols[] = array(
					'th' => T_('RC'),
					'th_title' => T_('Registration country'),
					'th_class' => 'shrinkwrap small',
					'td_class' => 'shrinkwrap small',
					'order' => 'rc.ctry_name',
					'td' => '%user_reg_country( #user_ID#, #reg_ctry_code#, #reg_ctry_name# )%',
				);

$UserList->cols[] = array(
					'th' => T_('Profile update'),
					'th_class' => 'shrinkwrap small',
					'td_class' => 'center small',
					'order' => 'user_profileupdate_date',
					'default_dir' => 'D',
					'td' => '%mysql2localedate( #user_profileupdate_date#, "M-d" )%',
				);
$UserList->cols[] = array(
					'th' => T_('Last Visit'),
					'th_class' => 'shrinkwrap small',
					'td_class' => 'center small',
					'order' => 'user_lastseen_ts',
					'default_dir' => 'D',
					'td' => '%mysql2localedate( #user_lastseen_ts#, "M-d" )%',
				);

$UserList->cols[] = array(
					'th' => T_('Contact'),
					'th_class' => 'shrinkwrap small',
					'td_class' => 'shrinkwrap small',
					'td' => '%user_mailto( #user_email# )%
					%user_pm( #user_ID#, #user_login# )%'.
					('~conditional( (#user_url# != \'http://\') && (#user_url# != \'\'), \' <a href="$user_url$" target="_blank" title="'.format_to_output( T_('Website'), 'htmlattr' ).': $user_url$">'
							.get_icon( 'www', 'imgtag', array( 'class' => 'middle', 'title' => format_to_output( T_('Website'), 'htmlattr' ).': $user_url$' ) ).'</a>\', \'&nbsp;\' )~'),
				);

$filter_reported = param( 'reported', 'integer' );
if( $filter_reported )
{	// Filter is set to 'Reported users'
	$userlist_col_reputaion = array(
						'th' => T_('Rep'),
						'th_class' => 'shrinkwrap small',
						'td_class' => 'shrinkwrap small',
						'order' => 'user_rep',
						'default_dir' => 'D',
						'td' => '$user_rep$',
					);
}

if( $UserList->filters['group'] == -1 )
{ // List is ungrouped, Display column with group name
	$UserList->cols[] = array(
			'th' => T_('Group'),
			'th_class' => 'shrinkwrap small',
			'td_class' => 'shrinkwrap small',
			'order' => 'grp_name',
			'td' => '$grp_name$',
		);
}

if( ! $current_User->check_perm( 'users', 'edit', false ) )
{
	if( $filter_reported )
	{
		$UserList->cols[] = $userlist_col_reputaion;
	}

	$UserList->cols[] = array(
						'th' => T_('Level'),
						'th_class' => 'shrinkwrap small',
						'td_class' => 'shrinkwrap small',
						'order' => 'user_level',
						'default_dir' => 'D',
						'td' => '$user_level$',
					);
}
else
{
	$UserList->cols[] = array(
						'th' => /* TRANS: Account status */ T_( 'Status' ),
						'th_class' => 'shrinkwrap small',
						'td_class' => 'shrinkwrap small',
						'order' => 'user_status',
						'default_dir' => 'D',
						'td' => '%user_status( #user_status#, #user_ID# )%'
					);

	if( $filter_reported )
	{
		$UserList->cols[] = $userlist_col_reputaion;
	}

	$UserList->cols[] = array(
						'th' => T_('Level'),
						'th_class' => 'shrinkwrap small',
						'td_class' => 'shrinkwrap user_level_edit small',
						'order' => 'user_level',
						'default_dir' => 'D',
						'td' => '<a href="#" rel="$user_level$">$user_level$</a>',
					);

	$UserList->cols[] = array(
						'th' => T_('Actions'),
						'th_class' => 'small',
						'td_class' => 'shrinkwrap small',
						'td' => action_icon( T_('Edit this user...'), 'edit', '%regenerate_url( \'ctrl,action\', \'ctrl=user&amp;user_ID=$user_ID$&amp;user_tab=profile\' )%' )
										.action_icon( T_('Duplicate this user...'), 'copy', '%regenerate_url( \'ctrl,action\', \'ctrl=user&amp;action=new&amp;user_ID=$user_ID$&amp;user_tab=profile\' )%' )
										.'~conditional( (#user_ID# != 1) && (#user_ID# != '.$current_User->ID.'), \''
											.action_icon( T_('Delete this user!'), 'delete',
												'%regenerate_url( \'action\', \'action=delete&amp;user_ID=$user_ID$&amp;'.url_crumb('user').'\' )%' ).'\', \''
	                    .get_icon( 'delete', 'noimg' ).'\' )~'
					);
}

if( $action == 'show_recent' )
{ // Sort an users list by "Registered" field
	$UserList->set_order( 'user_created_datetime' );
}

// Execute query
$UserList->query();


$filter_presets = array(
		'all' => array( T_('All users'), get_dispctrl_url( 'users&amp;filter=new' ) ),
		'men' => array( T_('Men'), get_dispctrl_url( 'users', 'gender_men=1&amp;filter=new' ) ),
		'women' => array( T_('Women'), get_dispctrl_url( 'users', 'gender_women=1&amp;filter=new' ) ),
	);

if( is_admin_page() )
{ // Add show only activated users filter only on admin interface
	$filter_presets['activated'] = array( T_('Activated users'), get_dispctrl_url( 'users', 'status_activated=1&amp;filter=new' ) );
	if( $current_User->check_perm( 'users', 'edit' ) )
	{ // Show "Reported Users" filter only for users with edit user permission
		$filter_presets['reported'] = array( T_('Reported users'), get_dispctrl_url( 'users', 'reported=1&amp;filter=new' ) );
	}
}

if( $UserList->is_filtered() )
{	// Display link to reset filters only if some filter is applied
	$filter_presets['reset'] = array( T_('Reset Filters'), get_dispctrl_url( 'users&amp;filter=reset' ), 'class="floatright"' );
}

$UserList->filter_area = array(
	'callback' => 'callback_filter_userlist',
	'url_ignore' => 'users_paged,u_paged,keywords',
	'presets' => $filter_presets,
	);


// Display result :
$UserList->display( $display_params );


if( $current_User->check_perm( 'emails', 'edit' ) && $UserList->result_num_rows > 0 )
{ // Newsletter button
	echo '<p class="center">';
	echo '<input type="button" value="'.T_('Send newsletter to the current selection').'" onclick="location.href=\''.$admin_url.'?ctrl=campaigns&amp;action=users&amp;'.url_crumb( 'campaign' ).'\'" class="btn btn-default" />';
	echo '</p>';
}

load_funcs( 'users/model/_user_js.funcs.php' );

?>