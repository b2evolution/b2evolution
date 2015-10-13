<?php
/**
 * This file implements the UI view for Users > User settings > Invitations
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $blog, $admin_url, $UserSettings;

// Create result set:
$SQL = new SQL();
$SQL->SELECT( 'SQL_NO_CACHE ivc_ID, ivc_code, ivc_expire_ts, ivc_source, ivc_grp_ID, grp_name, grp_level' );
$SQL->FROM( 'T_users__invitation_code' );
$SQL->FROM_add( 'INNER JOIN T_groups ON grp_ID = ivc_grp_ID' );

$count_SQL = new SQL();
$count_SQL->SELECT( 'SQL_NO_CACHE COUNT( ivc_ID )' );
$count_SQL->FROM( 'T_users__invitation_code' );

$Results = new Results( $SQL->get(), 'ivc_', '-D', $UserSettings->get( 'results_per_page' ), $count_SQL->get() );

$Results->title = T_('Invitation codes').get_manual_link( 'invitation-codes-list' );

/*
 * Table icons:
 */
if( $current_User->check_perm( 'users', 'edit', false ) )
{ // create new group link
	$Results->global_icon( T_('Create a new invitation code...'), 'new', '?ctrl=invitations&amp;action=new', T_('Add invitation code').' &raquo;', 3, 4, array( 'class' => 'action_icon btn-primary' ) );
}

$Results->cols[] = array(
		'th' => T_('ID'),
		'order' => 'ivc_ID',
		'th_class' => 'shrinkwrap',
		'td_class' => 'right',
		'td' => '$ivc_ID$',
	);

$Results->cols[] = array(
		'th' => T_('Expires'),
		'order' => 'ivc_expire_ts',
		'td_class' => 'shrinkwrap',
		'td' => '$ivc_expire_ts$',
	);

$Results->cols[] = array(
		'th' => T_('Group'),
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
		'order' => 'grp_name',
		'td' => '$grp_name$ ($grp_level$)',
	);

$Results->cols[] = array(
		'th' => T_('Code'),
		'order' => 'ivc_code',
		'td' => $current_User->check_perm( 'users', 'edit', false )
			? '<a href="'.$admin_url.'?ctrl=invitations&amp;action=edit&amp;ivc_ID=$ivc_ID$"><b>$ivc_code$</b></a>'
			: '$ivc_code$',
	);

$Results->cols[] = array(
		'th' => T_('Code'),
		'order' => 'ivc_code',
		'td' => '<a href="'.get_secure_htsrv_url().'register.php?invitation=$ivc_code$">'.T_('Link').'</a>',
	);

$Results->cols[] = array(
		'th' => T_('Source'),
		'order' => 'ivc_source',
		'td' => '$ivc_source$',
	);

if( $current_User->check_perm( 'users', 'edit', false ) )
{
	function ivc_actions( & $row )
	{
		$r = action_icon( T_('Edit this invitation code...'), 'edit',
					regenerate_url( 'ctrl,action', 'ctrl=invitations&amp;ivc_ID='.$row->ivc_ID.'&amp;action=edit') )
				.action_icon( T_('Duplicate this invitation code...'), 'copy',
					regenerate_url( 'ctrl,action', 'ctrl=invitations&amp;ivc_ID='.$row->ivc_ID.'&amp;action=new') )
				.action_icon( T_('Delete this invitation code!'), 'delete',
					regenerate_url( 'ctrl,action', 'ctrl=invitations&amp;ivc_ID='.$row->ivc_ID.'&amp;action=delete&amp;'.url_crumb('invitation') ) );

		return $r;
	}

	$Results->cols[] = array(
			'th' => T_('Actions'),
			'td_class' => 'shrinkwrap',
			'td' => '%ivc_actions( {row} )%',
		);
}

// Display results:
$Results->display();
?>