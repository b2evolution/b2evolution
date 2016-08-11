<?php
/**
 * This file implements the UI view for Users > Groups
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

global $blog, $admin_url, $UserSettings;

// query which groups have users (in order to prevent deletion of groups which have users)
global $usedgroups;	// We need this in a callback below
$usedgroups = $DB->get_col( 'SELECT grp_ID
    FROM T_groups INNER JOIN T_users ON user_grp_ID = grp_ID
    GROUP BY grp_ID');

// Create result set:
$SQL = new SQL();
$SQL->SELECT( 'SQL_NO_CACHE grp_ID, grp_name, grp_usage, grp_level' );
$SQL->SELECT_add( ', CASE
	WHEN grp_usage  LIKE "secondary"  THEN "0"
	WHEN gset_value LIKE "no_toolbar" THEN "1_no_toolbar"
	WHEN gset_value LIKE "none"       THEN "2_none"
	WHEN gset_value LIKE "restricted" THEN "3_restricted"
	WHEN gset_value LIKE "normal"     THEN "4_normal"
	ELSE "0"
END AS backoffice' );
$SQL->SELECT_add( ', (SELECT COUNT( user_ID ) FROM T_users WHERE user_grp_ID = grp_ID ) AS primary_users_count' );
$SQL->SELECT_add( ', (SELECT COUNT( sug_grp_ID ) FROM T_users__secondary_user_groups WHERE sug_grp_ID = grp_ID ) AS secondary_users_count' );
$SQL->FROM( 'T_groups' );
$SQL->FROM_add( 'LEFT JOIN T_groups__groupsettings ON gset_grp_ID = grp_ID AND gset_name = "perm_admin"' );

$count_SQL = new SQL();
$count_SQL->SELECT( 'SQL_NO_CACHE COUNT(grp_ID)' );
$count_SQL->FROM( 'T_groups' );

$Results = new Results( $SQL->get(), 'grp_', '--D', $UserSettings->get( 'results_per_page' ), $count_SQL->get() );

$Results->title = T_('Groups (for setting permissions)').get_manual_link( 'user-groups-tab' );

/*
 * Table icons:
 */
if( $current_User->check_perm( 'users', 'edit', false ) )
{	// create new group link
	$Results->global_icon( T_('Create a new group...'), 'new', '?ctrl=groups&amp;action=new', T_('Add group').' &raquo;', 3, 4, array( 'class' => 'action_icon btn-primary' ) );
}

$Results->cols[] = array(
		'th' => T_('ID'),
		'order' => 'grp_ID',
		'th_class' => 'shrinkwrap',
		'td_class' => 'right',
		'td' => '$grp_ID$',
	);

// Check if user can edit users
$has_perm_users_edit = $current_User->check_perm( 'users', 'edit', false );

$Results->cols[] = array(
		'th' => T_('Name'),
		'order' => 'grp_name',
		'td' => $has_perm_users_edit ?
				'<a href="'.$admin_url.'?ctrl=groups&amp;action=edit&amp;grp_ID=$grp_ID$"><b>$grp_name$</b></a>' :
				'$grp_name$',
	);

$Results->cols[] = array(
		'th' => T_('Level'),
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap '.( $has_perm_users_edit ? ' group_level_edit' : '' ),
		'order' => 'grp_level',
		'default_dir' => 'D',
		'td' => $has_perm_users_edit ?
				'<a href="#" rel="$grp_level$">$grp_level$</a>' :
				'$grp_level$',
	);

$Results->cols[] = array(
		'th' => T_('Usage'),
		'order' => 'grp_usage',
		'td' => '%get_admin_badge( "group", "", "", "", #grp_usage# )%',
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
	);

function grp_row_users_count( $group_ID, $primary_users_count, $secondary_users_count )
{
	if( $primary_users_count == 0 && $secondary_users_count == 0 )
	{	// This group is not used at all
		return '<span class="label label-default">0</span>';
	}
	else
	{
		global $admin_url;
		$users_url = $admin_url.'?ctrl=users&amp;filter=new&amp;';
		$r = '';
		if( $primary_users_count > 0 )
		{	// This group is used as primary for several users
			$r .= ' <a href="'.$users_url.'group='.$group_ID.'" class="label label-primary">'.$primary_users_count.'</a> ';
		}
		if( $secondary_users_count > 0 )
		{	// This group is used as secondary for several users
			$r .= ' <a href="'.$users_url.'group2='.$group_ID.'" class="label label-info">'.$secondary_users_count.'</a> ';
		}
		return $r;
	}
}
$Results->cols[] = array(
		'th' => T_('User count'),
		'order' => 'primary_users_count, secondary_users_count',
		'default_dir' => 'D',
		'td' => '%grp_row_users_count( #grp_ID#, #primary_users_count#, #secondary_users_count# )%',
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
	);

function grp_row_backoffice( $backoffice_access )
{
	switch( $backoffice_access )
	{
		case '4_normal':
			return T_( 'Normal' );
		case '3_restricted':
			return T_( 'Restricted' );
		case '2_none':
			return T_( 'No Access' );
		case '1_no_toolbar':
			return T_( 'No Toolbar' );
		default:
			// Secondary group:
			return '';
	}
}
$Results->cols[] = array(
		'th' => T_('Back-office access'),
		'order' => 'backoffice, grp_ID',
		'td' => '%grp_row_backoffice( #backoffice# )%',
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
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
$Results->cols[] = array(
		'th' => T_('Actions'),
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
		'td' => '%grp_actions( {row} )%',
	);

// Display results:
$Results->display();

if( $current_User->check_perm( 'users', 'edit', false ) )
{ // If user can edit the users - Init js to edit group level by AJAX
	$group_levels = array();
	for( $l = 0; $l <= 10; $l++ )
	{
		$group_levels[ $l ] = $l;
	}
	// Print JS to edit a group level
	echo_editable_column_js( array(
		'column_selector' => '.group_level_edit',
		'ajax_url'        => get_htsrv_url().'async.php?action=group_level_edit&'.url_crumb( 'grouplevel' ),
		'options'         => $group_levels,
		'new_field_name'  => 'new_group_level',
		'ID_value'        => 'jQuery( ":first", jQuery( this ).parent() ).text()',
		'ID_name'         => 'group_ID' ) );
}
?>