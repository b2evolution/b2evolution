<?php
/**
 * This file implements the UI view for Users > Groups
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
 * @version $Id: _group.view.php 349 2011-11-18 11:18:14Z yura $
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
$SQL->SELECT( 'SQL_NO_CACHE grp_ID, grp_name' );
$SQL->FROM( 'T_groups' );

$count_SQL = new SQL();
$count_SQL->SELECT( 'SQL_NO_CACHE COUNT(grp_ID)' );
$count_SQL->FROM( 'T_groups' );

$Results = new Results( $SQL->get(), 'grp_', 'A', $UserSettings->get( 'results_per_page' ), $count_SQL->get() );

$Results->title = T_('User groups');

/*
 * Table icons:
 */
if( $current_User->check_perm( 'users', 'edit', false ) )
{	// create new group link
	$Results->global_icon( T_('Create a new group...'), 'new', '?ctrl=groups&amp;action=new', T_('Add group').' &raquo;', 3, 4 );
}

$Results->cols[] = array(
		'th' => T_('ID'),
		'order' => 'grp_ID',
		'th_class' => 'shrinkwrap',
		'td_class' => 'right',
		'td' => '$grp_ID$',
	);

if( $current_User->check_perm( 'users', 'edit', false ) )
{	// User can edit this group
	$Results->cols[] = array(
			'th' => T_('Name'),
			'order' => 'grp_name',
			'td' => '<a href="'.$admin_url.'?ctrl=groups&amp;action=edit&amp;grp_ID=$grp_ID$"><b>$grp_name$</b></a>',
		);
}
else
{	// No permission to edit group
	$Results->cols[] = array(
			'th' => T_('Name'),
			'order' => 'grp_name',
			'td' => '$grp_name$',
		);
}

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
		'th_class' => 'shrinkwrap small',
		'td_class' => 'shrinkwrap',
		'td' => '%grp_actions( {row} )%',
	);

// Display results:
$Results->display();

?>