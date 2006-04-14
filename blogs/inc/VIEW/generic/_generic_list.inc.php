<?php
/**
 * This file implements the element list editor list.
 *
 * @copyright (c)2004-2005 by PROGIDISTRI - {@link http://progidistri.com/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * @package admin
 *
 * @author mbruneau: Marc BRUNEAU / PROGIDISTRI
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $perm_name, $perm_level;

global $result_fadeout;

global $GenericElementCache;

global $list_title, $default_col_order, $form_below_list;


// EXPERIMENTAL
if ( !isset( $default_col_order ) )
{ // The default order column is not set, so the default is the name column
	$default_col_order = '-A-';
}

// Create result set:
$sql = "SELECT $GenericElementCache->dbIDname, {$GenericElementCache->dbprefix}name
  			 	FROM $GenericElementCache->dbtablename";

$Results = & new Results(	$sql, $GenericElementCache->dbprefix, $default_col_order );

if( isset( $list_title ) )
{
	$Results->title = $list_title;
}

$Results->cols[] = array(
		'th' => T_('ID'),
		'order' => $GenericElementCache->dbIDname,
		'th_start' => '<th class="firstcol shrinkwrap">',
		'td_start' => '<td class="firstcol shrinkwrap">',
		'td' => "\$$GenericElementCache->dbIDname\$",
	);


function link_name( $title , $ID )
{
	global $GenericElementCache;
	
	global $locked_IDs, $perm_name, $perm_level, $current_User;
	
	if( ( empty( $locked_IDs ) || !in_array( $ID, $locked_IDs ) )
			&& ( !isset( $perm_name ) || $current_User->check_perm( $perm_name, $perm_level, false ) ) )
	{	// The element is not locked and we have permission permission to edit:
		return '<strong><a href="'.regenerate_url( 'action,ID', $GenericElementCache->dbIDname.'='.$ID.'&amp;action=edit' ).'">'.$title.'</a></strong>';
	}
	else
	{
		return '<strong>'.$title.'</strong>';
	}
}
$Results->cols[] = array(
		'th' => T_('Name'),
		'order' => $GenericElementCache->dbprefix.'name',
 		'td' => '%link_name( #'.$GenericElementCache->dbprefix.'name#, #'.$GenericElementCache->dbIDname.'# )%',
	);


if( !isset( $perm_name ) || $current_User->check_perm( $perm_name, $perm_level, false ) )
{	// We have permission permission to edit:

	function edit_actions( $ID )
	{
		global $locked_IDs, $GenericElementCache;

		$r = action_icon( T_('Duplicate...'), 'copy', regenerate_url( 'action,'.$GenericElementCache->dbIDname, $GenericElementCache->dbIDname.'='.$ID.'&amp;action=copy' ) );

		if( empty( $locked_IDs ) || !in_array( $ID, $locked_IDs ) )
		{ // This element is NOT locked:
			$r = action_icon( T_('Edit...'), 'edit', regenerate_url( 'action,'.$GenericElementCache->dbIDname, $GenericElementCache->dbIDname.'='.$ID.'&amp;action=edit' ) )
						.$r
						.action_icon( T_('Delete!'), 'delete', regenerate_url( 'action,'.$GenericElementCache->dbIDname, $GenericElementCache->dbIDname.'='.$ID.'&amp;action=delete' ) );

		}

		return $r;
	}

	$Results->cols[] = array(
			'th' => T_('Actions'),
			'td_start' => '<td class="shrinkwrap lastcol">',
			'td' => '%edit_actions( #'.$GenericElementCache->dbIDname.'# )%',
		);

}

if( !$form_below_list )
{	// Need to dispaly global icon to add new geenric element:
	if( !isset( $perm_name ) || $current_User->check_perm( $perm_name, $perm_level, false ) )
	{	// We have permission permission to edit:
		$Results->global_icon( T_('Add an element...'), 'new', regenerate_url( 'action,'.$GenericElementCache->dbIDname, 'action=new' ), T_('Add element'), 3, 4 );
	}
}

// EXPERIMENTAL
// $Results->display();
$Results->display( NULL, $result_fadeout );

?>