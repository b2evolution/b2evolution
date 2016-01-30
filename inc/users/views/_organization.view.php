<?php
/**
 * This file implements the UI view for Users > User settings > Invitations
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

// Create result set:
$SQL = new SQL();
$SQL->SELECT( 'SQL_NO_CACHE org_ID, org_owner_user_ID, org_name, org_url, org_accept, org_perm_role, user_login, COUNT( uorg_user_ID ) AS members_count' );
$SQL->FROM( 'T_users__organization' );
$SQL->FROM_add( 'INNER JOIN T_users ON org_owner_user_ID = user_ID' );
$SQL->FROM_add( 'LEFT JOIN T_users__user_org ON uorg_org_ID = org_ID' );
$SQL->GROUP_BY( 'org_ID' );

$count_SQL = new SQL();
$count_SQL->SELECT( 'SQL_NO_CACHE COUNT( org_ID )' );
$count_SQL->FROM( 'T_users__organization' );
$count_SQL->FROM_add( 'INNER JOIN T_users ON org_owner_user_ID = user_ID' );

$Results = new Results( $SQL->get(), 'org_', '-D', $UserSettings->get( 'results_per_page' ), $count_SQL->get() );
$Results->Cache = get_OrganizationCache();

$Results->title = T_('Organizations').get_manual_link( 'organizations-tab' );

/*
 * Table icons:
 */
if( $current_User->check_perm( 'orgs', 'create', false ) )
{ // create new group link
	$Results->global_icon( T_('Create a new organization...'), 'new', '?ctrl=organizations&amp;action=new', T_('Add organization').' &raquo;', 3, 4, array( 'class' => 'action_icon btn-primary' ) );
}

$Results->cols[] = array(
		'th' => T_('ID'),
		'order' => 'org_ID',
		'th_class' => 'shrinkwrap',
		'td_class' => 'right',
		'td' => '$org_ID$',
	);

function org_td_name( & $Organization )
{
	global $current_User;

	if( $current_User->check_perm( 'orgs', 'view', false, $Organization ) )
	{
		global $admin_url;
		return '<a href="'.$admin_url.'?ctrl=organizations&amp;action=edit&amp;org_ID='.$Organization->ID.'&amp;filter=refresh"><b>'.$Organization->get( 'name' ).'</b></a>';
	}
	else
	{
		return $Organization->get( 'name' );
	}
}
$Results->cols[] = array(
		'th' => T_('Name'),
		'order' => 'org_name',
		'td' => '%org_td_name( {Obj} )%',
	);

function org_td_owner( & $Organization )
{
	$owner_User = & $Organization->get_owner_User();
	return $owner_User->get_identity_link();
}
$Results->cols[] = array(
		'th'       => T_('Owner'),
		'td'       => '%org_td_owner( {Obj} )%',
		'order'    => 'user_login',
		'th_class' => 'shrinkwrap',
	);

$Results->cols[] = array(
		'th' => T_('Url'),
		'order' => 'org_url',
		'td' => '~conditional( #org_url# == "", "&nbsp;", "<a href=\"#org_url#\">#org_url#</a>" )~',
	);

$Results->cols[] = array(
		'th'          => T_('Members'),
		'td'          => '$members_count$',
		'order'       => 'members_count',
		'default_dir' => 'D',
		'th_class'    => 'shrinkwrap',
		'td_class'    => 'right',
	);

function org_td_actions( & $Organization )
{
	global $current_User;

	$r = '';
	$perm_org_edit = $current_User->check_perm( 'orgs', 'edit', false, $Organization );

	if( $perm_org_edit )
	{
		$r .= action_icon( T_('Edit this organization...'), 'edit',
			regenerate_url( 'ctrl,action', 'ctrl=organizations&amp;org_ID='.$Organization->ID.'&amp;action=edit&amp;filter=refresh' ) );
	}
	if( $current_User->check_perm( 'orgs', 'create', false ) )
	{
		$r .= action_icon( T_('Duplicate this organization...'), 'copy',
			regenerate_url( 'ctrl,action', 'ctrl=organizations&amp;org_ID='.$Organization->ID.'&amp;action=new' ) );
	}
	if( $perm_org_edit )
	{
		$r .= action_icon( T_('Delete this organization!'), 'delete',
			regenerate_url( 'ctrl,action', 'ctrl=organizations&amp;org_ID='.$Organization->ID.'&amp;action=delete&amp;'.url_crumb('organization') ) );
	}

	return $r;
}

$Results->cols[] = array(
		'th' => T_('Actions'),
		'td_class' => 'shrinkwrap',
		'td' => '%org_td_actions( {Obj} )%',
	);

// Display results:
$Results->display();
?>