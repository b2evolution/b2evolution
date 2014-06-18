<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009-2014 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
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
 * The Evo Factory grants Francois PLANQUE the right to license
 * The Evo Factory's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author evofactory-test
 * @author fplanque: Francois Planque.
 *
 * @version  $Id: _userfields.view.php 6135 2014-03-08 07:54:05Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'users/model/_userfield.class.php', 'Userfield' );

global $dispatcher;

// query which groups have user field definitions (in order to prevent deletion of groups which have user field definitions)
global $usedgroups;	// We need this in a callback below
$usedgroups = $DB->get_col( 'SELECT ufgp_ID
			FROM T_users__fieldgroups INNER JOIN T_users__fielddefs ON ufdf_ufgp_ID = ufgp_ID
			GROUP BY ufgp_ID' );

// Get IDs of userfields that first/last in the own group, to hide action icon "move up/down"
global $userfields_group_sides;
$userfields_group_sides = array();
$userfields_group_sides['first'] = $DB->get_col( 'SELECT ufdf_ID
			FROM T_users__fielddefs f1
			WHERE ufdf_order =
					( SELECT MIN(f2.ufdf_order)
						FROM T_users__fielddefs f2
						WHERE f2.ufdf_ufgp_ID = f1.ufdf_ufgp_ID )
			GROUP BY ufdf_ufgp_ID' );
$userfields_group_sides['last'] = $DB->get_col( 'SELECT ufdf_ID
			FROM T_users__fielddefs f1
			WHERE ufdf_order =
					( SELECT MAX(f2.ufdf_order)
						FROM T_users__fielddefs f2
						WHERE f2.ufdf_ufgp_ID = f1.ufdf_ufgp_ID )
			GROUP BY ufdf_ufgp_ID' );

// Get params from request
$s = param( 's', 'string', '', true );
$s_type = param( 's_type', 'string', '', true );

// Create query
$SQL = new SQL();
$SQL->SELECT( '*' );
$SQL->FROM( 'T_users__fielddefs RIGHT JOIN T_users__fieldgroups ON ufdf_ufgp_ID = ufgp_ID' );

$where_clause = '';

if( !empty($s) )
{	// We want to filter on search keyword:
	// Note: we use CONCAT_WS (Concat With Separator) because CONCAT returns NULL if any arg is NULL
	$where_clause = 'CONCAT_WS( " ", ufdf_name ) LIKE "%'.$DB->escape($s).'%"';
}

if( !empty( $s_type ) )
{	// We want to filter on user field type:
	$where_clause = 'ufdf_type LIKE "%'.$DB->escape($s_type).'%"';
}

if( $where_clause != '' )
{
	$SQL->WHERE_and( $where_clause );
}
$SQL->GROUP_BY( 'ufdf_ID, ufgp_ID' );
$SQL->ORDER_BY( 'ufgp_order, ufgp_name, ufdf_order' );

$count_sql = 'SELECT COUNT(*)
							  FROM T_users__fielddefs';
if( $where_clause != '' )
{
	$count_sql .= ' WHERE '.$where_clause;
}

// Create result set:
$Results = new Results( $SQL->get(), 'ufdf_', 'A', NULL, $count_sql );

$Results->title = T_('User fields').get_manual_link('user-fields-list');

/**
 * Callback to enumerate possible user field types
 *
 */
function enumerate_types( $selected = '' ) {
	$options = '<option value="">All</option>';
	foreach( Userfield::get_types() as $type_code => $type_name ) {
		$options .= '<option value="'.$type_code.'" ';
		if( $type_code == $selected ) $options .= '"selected" ';
		$options .= '>'.$type_name.'</option>';
	}
	return $options;
}

/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_userfields( & $Form )
{
	$Form->text( 's', get_param('s'), 30, T_('Search'), '', 255 );
	$Form->select( 's_type', get_param( 's_type' ), 'enumerate_types', T_('Type'), '', ''  );
}

$Results->filter_area = array(
	'callback' => 'filter_userfields',
	'presets' => array(
		'all' => array( T_('All'), '?ctrl=userfields' ),
		)
	);


/*
 * Grouping params:
 */
$Results->group_by = 'ufgp_ID';
$Results->ID_col = 'ufdf_ID';


/*
 * Group columns:
 */
$group_td_colspan = $current_User->check_perm( 'users', 'edit', false ) ? -2 : 0;
$Results->grp_cols[] = array(
						'td_colspan' => $group_td_colspan,
						'td' => '<a href="?ctrl=userfieldsgroups&amp;action=edit&amp;ufgp_ID=$ufgp_ID$">$ufgp_name$</a>',
					);
if( $current_User->check_perm( 'users', 'edit', false ) )
{	// We have permission to modify:
	$Results->grp_cols[] = array(
							'td' => '$ufgp_order$',
							'td_class' => 'center',
						);

	function grp_actions( & $row )
	{
		global $usedgroups, $current_User;

		$r = '';
		if( $current_User->check_perm( 'users', 'edit', false ) )
		{
			$r = action_icon( T_('Edit this group...'), 'edit', regenerate_url( 'ctrl,action', 'ctrl=userfieldsgroups&amp;action=edit&amp;ufgp_ID='.$row->ufgp_ID ) )
					.action_icon( T_('Duplicate this group...'), 'copy', regenerate_url( 'ctrl,action', 'ctrl=userfieldsgroups&amp;action=new&amp;ufgp_ID='.$row->ufgp_ID ) );

			if( !in_array( $row->ufgp_ID, $usedgroups ) )
			{ // delete
				$r .= action_icon( T_('Delete this group!'), 'delete', regenerate_url( 'ctrl,action', 'ctrl=userfieldsgroups&amp;action=delete&amp;ufgp_ID='.$row->ufgp_ID.'&amp;'.url_crumb('userfieldgroup') ) );
			}
			else
			{
				$r .= get_icon( 'delete', 'noimg' );
			}
		}
		return $r;
	}
	$Results->grp_cols[] = array(
							'td_class' => 'shrinkwrap',
							'td' => '%grp_actions( {row} )%',
						);
}


/*
 * Data columns:
 */
$Results->cols[] = array(
		'th' => T_('Name'),
		'td' => '<a href="%regenerate_url( \'action\', \'ufdf_ID=$ufdf_ID$&amp;action=edit\' )%"><strong>%T_(#ufdf_name#)%</strong></a>',
	);

$Results->cols[] = array(
	'th' => T_('Type'),
	'td' => '%T_(#ufdf_type#)%',
);

$Results->cols[] = array(
		'th' => T_('Required?'),
		'td' => '%get_userfield_required( #ufdf_required# )%',
		'td_class' => 'center',
	);

$Results->cols[] = array(
		'th' => T_('Multiple values'),
		'td' => '$ufdf_duplicated$',
		'td_class' => 'center',
	);

if( $current_User->check_perm( 'users', 'edit', false ) )
{	// We have permission to modify:
	function order_actions( & $row )
	{
		global $userfields_group_sides;

		$r = '';

		if( in_array( $row->ufdf_ID, $userfields_group_sides['first'] ) )
		{	// First record, no change ordering, print blank icon
			$r .= get_icon( 'move_down', 'noimg' );
		}
		else
		{
			$r .= action_icon( T_('Move up'), 'move_up',
					regenerate_url( 'ctrl,action', 'ctrl=userfields&amp;ufdf_ID='.$row->ufdf_ID.'&amp;action=move_up&amp;'.url_crumb('userfield') ) );
		}

		if( in_array( $row->ufdf_ID, $userfields_group_sides['last'] ) )
		{	// Last record, no change ordering, print blank icon
			$r .= get_icon( 'move_down', 'noimg' );
		}
		else
		{
			$r .= action_icon( T_('Move down'), 'move_down',
					regenerate_url( 'ctrl,action', 'ctrl=userfields&amp;ufdf_ID='.$row->ufdf_ID.'&amp;action=move_down&amp;'.url_crumb('userfield') ) );
		}

		return $r;
	}

	$Results->cols[] = array(
			'th' => T_('Order'),
			'td' => '%order_actions( {row} )%',
			'td_class' => 'shrinkwrap',
		);

	function fld_actions( & $row )
	{
		$r = action_icon( T_('Edit this user field...'), 'edit',
					regenerate_url( 'ctrl,action', 'ctrl=userfields&amp;ufdf_ID='.$row->ufdf_ID.'&amp;action=edit') )
				.action_icon( T_('Duplicate this user field...'), 'copy',
					regenerate_url( 'ctrl,action', 'ctrl=userfields&amp;ufdf_ID='.$row->ufdf_ID.'&amp;action=new') )
				.action_icon( T_('Delete this user field!'), 'delete',
					regenerate_url( 'ctrl,action', 'ctrl=userfields&amp;ufdf_ID='.$row->ufdf_ID.'&amp;action=delete&amp;'.url_crumb('userfield') ) );

		return $r;
	}

	$Results->cols[] = array(
							'th' => T_('Actions'),
							'td_class' => 'shrinkwrap',
							'td' => '%fld_actions( {row} )%',
						);

	$Results->global_icon( T_('Create a new user field...'), 'new',
				'?ctrl=userfields&action=new', T_('New user field').' &raquo;', 3, 4 );
	$Results->global_icon( T_('Create a new user field group...'), 'new',
				'?ctrl=userfieldsgroups&action=new', T_('New user field group').' &raquo;', 3, 4 );
}


// Display results:
$Results->display();

?>