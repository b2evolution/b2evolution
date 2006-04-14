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
//____________________ Callbacks functions to display categories list _____________________
 
global $list_title;

global $GenericElementCache;

global $line_class;

global $perm_name, $perm_level;

$line_class = 'odd';

/**
 * Get html generic category line
 *
 * @param GenericCategory generic category we want to display
 * @param int level of the category in the recursive tree
 * @return html generic category line
 */
function cat_line( $GenericCategory, $level )
{
	global $line_class, $result_fadeout, $perm_name, $perm_level, $current_User;
	
	$line_class = $line_class == 'even' ? 'odd' : 'even';
	
	$r = '<tr id="tr-'.$GenericCategory->ID.'"class="'.$line_class.
					// Fadeout? 
					( in_array( $GenericCategory->ID, $result_fadeout ) ? ' fadeout-ffff00': '' ).'">
					<td class="firstcol shrinkwrap">'.
						$GenericCategory->ID.'
					</td>';
	
	if( !isset( $perm_name ) || $current_User->check_perm( $perm_name, $perm_level, false ) )
	{	// We have permission permission to edit, so display action column:						
		$r .= '<td>
						<label style="padding-left: '.(2*$level).'em;">'.$GenericCategory->name.'</label>
					 </td>
					 <td class="lastcol shrinkwrap">'.
						 action_icon( T_('New...'), 'new', regenerate_url( 'action,ID,'.$GenericCategory->dbprefix.'parent_ID', $GenericCategory->dbprefix.'parent_ID='.$GenericCategory->ID.'&amp;action=new' ) ).
						 action_icon( T_('Edit...'), 'edit', regenerate_url( 'action,'.$GenericCategory->dbIDname, $GenericCategory->dbIDname.'='.$GenericCategory->ID.'&amp;action=edit' ) ).
						 action_icon( T_('Delete...'), 'delete', regenerate_url( 'action,'.$GenericCategory->dbIDname, $GenericCategory->dbIDname.'='.$GenericCategory->ID.'&amp;action=delete' ) ).'
					 </td>';
	}
	else 
	{
		$r .= '<td class="lastcol">
						 <label style="padding-left: '.(2*$level).'em;">'.$GenericCategory->name.'</label>
					 </td>';
	}
	
	
	$r .=	'</tr>';
	
	return $r;
}

function cat_no_children( $GenericCategory, $level )
{
	return '';
}

function cat_before_level( $level )
{
	return '';
}

function cat_after_level( $level )
{
	return '';
}


$callbacks = array(
	'line' 			 	 => 'cat_line',
	'no_children'  => 'cat_no_children',
	'before_level' => 'cat_before_level',
	'after_level'	 => 'cat_after_level'
);

//____________________________________ Display generic categories _____________________________________

echo '<table class="grouped" cellspacing="0">
			<tr>
					<th colspan="3" class="results_title">
						<div class="results_title">';
			

if( !isset( $perm_name ) || $current_User->check_perm( $perm_name, $perm_level, false ) )
{	// We have permission permission to edit, so display global icon to add nex genereic element:
	echo '<span class="right_icons">'
				.action_icon( T_('Add an element...'), 'new', regenerate_url( 'action,'.$GenericElementCache->dbIDname, 'action=new' ), T_('Add element'), 3, 4 ).'  
				</span>';
}

echo				$list_title.'
						</div>
					</th>
			</tr>
			<tr>
					<th class="firstcol shrinkwrap right">'.T_('ID').'</th>
					<th>'.T_('Name').'</th>';

if( !isset( $perm_name ) || $current_User->check_perm( $perm_name, $perm_level, false ) )
{	// We have permission permission to edit, so display action column:
	echo '<th class="lastcol">'.T_('Actions').'</th>';
}

echo '</tr>';

$GenericElementCache->Reveal_children();

echo $GenericElementCache->recurse( $callbacks );

echo '</table>';

?>