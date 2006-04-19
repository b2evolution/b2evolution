<?php
/**
 * This file implements the element list editor list.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * PROGIDISTRI S.A.S. grants Francois PLANQUE the right to license
 * PROGIDISTRI S.A.S.'s contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
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

echo $GenericElementCache->recurse( $callbacks );

echo '</table>';

?>