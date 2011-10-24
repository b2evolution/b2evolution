<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @version  $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'users/model/_userfield.class.php', 'Userfield' );

global $dispatcher;

// query which groups have user field definitions (in order to prevent deletion of groups which have user field definitions)
global $usedgroups;	// We need this in a callback below
$usedgroups = $DB->get_col( 'SELECT ufgp_ID
			FROM T_users__fieldgroups INNER JOIN T_users__fielddefs ON ufdf_ufgp_ID = ufgp_ID
			GROUP BY ufgp_ID');

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
$SQL->ORDER_BY( 'ufgp_ID, *' );

$count_sql = 'SELECT COUNT(*)
							  FROM T_users__fielddefs';
if( $where_clause != '' )
{
	$count_sql .= ' WHERE '.$where_clause;
}

// Create result set:
$Results = new Results( $SQL->get(), 'ufdf_', 'A', NULL, $count_sql );

$Results->title = T_('User fields');

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
$Results->grp_cols[] = array(
						'td_colspan' => -1,  // nb_colds - 1
						'td' => '<a href="?ctrl=userfieldsgroups&amp;action=edit&amp;ufgp_ID=$ufgp_ID$">$ufgp_name$</a>',
					);

function grp_actions( & $row )
{
	global $usedgroups, $current_User;

	$r = '';
	if( $current_User->check_perm( 'users', 'edit', false ) )
	{
		$r = action_icon( T_('Edit this group...'), 'edit', regenerate_url( 'ctrl,action', 'ctrl=userfieldsgroups&amp;action=edit&amp;ufgp_ID='.$row->ufgp_ID ) );
	
		$r .= action_icon( T_('Duplicate this group...'), 'copy', regenerate_url( 'ctrl,action', 'ctrl=userfieldsgroups&amp;action=new&amp;ufgp_ID='.$row->ufgp_ID ) );
	
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


/*
 * Data columns:
 */
$Results->cols[] = array(
		'th' => T_('ID'),
		'order' => 'ufdf_ID',
		'td_class' => 'center',
		'td' => '$ufdf_ID$',
	);

$Results->cols[] = array(
	'th' => T_('Type'),
	'order' => 'ufdf_type',
	'td' => '%T_(#ufdf_type#)%',
);

$Results->cols[] = array(
		'th' => T_('Name'),
		'order' => 'ufdf_name',
		'td' => '<a href="%regenerate_url( \'action\', \'ufdf_ID=$ufdf_ID$&amp;action=edit\' )%">%T_(#ufdf_name#)%</a>',
	);

$Results->cols[] = array(
		'th' => T_('Required?'),
		'order' => 'ufdf_required',
		'td' => '%get_userfield_required( #ufdf_required# )%',
		'td_class' => 'center',
	);

if( $current_User->check_perm( 'users', 'edit', false ) )
{ // We have permission to modify:
	$Results->cols[] = array(
							'th' => T_('Actions'),
							'th_class' => 'shrinkwrap',
							'td' => action_icon( T_('Edit this user field...'), 'edit',
										'%regenerate_url( \'action\', \'ufdf_ID=$ufdf_ID$&amp;action=edit\')%' )
									.action_icon( T_('Duplicate this user field...'), 'copy',
										'%regenerate_url( \'action\', \'ufdf_ID=$ufdf_ID$&amp;action=new\')%' )
									.action_icon( T_('Delete this user field!'), 'delete',
										'%regenerate_url( \'action\', \'ufdf_ID=$ufdf_ID$&amp;action=delete&amp;'.url_crumb('userfield').'\')%' ),
						);

	$Results->global_icon( T_('Create a new user field...'), 'new',
				'?ctrl=userfields&action=new', T_('New user field').' &raquo;', 3, 4 );
	$Results->global_icon( T_('Create a new group for user fields...'), 'new',
				'?ctrl=userfieldsgroups&action=new', T_('New group for user fields').' &raquo;', 3, 4 );
}


// Display results:
$Results->display();

/*
 * $Log$
 * Revision 1.18  2011/10/24 18:32:35  efy-yurybakh
 * Groups for user fields
 *
 * Revision 1.17  2011/10/20 17:22:21  efy-yurybakh
 * clickable Names on ?ctrl=userfields
 *
 * Revision 1.16  2011/09/27 17:31:19  efy-yurybakh
 * User additional info fields
 *
 * Revision 1.15  2011/09/11 00:04:45  fplanque
 * doc
 *
 * Revision 1.14  2011/09/10 22:48:41  fplanque
 * doc
 *
 * Revision 1.13  2011/08/29 08:51:14  efy-james
 * Default / mandatory additional fields
 *
 * Revision 1.12  2010/05/07 08:07:14  efy-asimo
 * Permissions check update (User tab, Global Settings tab) - bugfix
 *
 * Revision 1.11  2010/02/26 22:15:48  fplanque
 * whitespace/doc/minor
 *
 * Revision 1.9  2010/01/03 13:10:57  fplanque
 * set some crumbs (needs checking)
 *
 * Revision 1.8  2009/09/20 00:27:08  fplanque
 * cleanup/doc/simplified
 *
 * Revision 1.6  2009/09/16 18:22:57  fplanque
 * Readded with -kkv option
 *
 * Revision 1.1  2009/09/11 18:34:05  fplanque
 * userfields editing module.
 * needs further cleanup but I think it works.
 *
 */
?>
