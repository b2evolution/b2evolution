<?php
/**
 * This file implements the generic recrusive editor list.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
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
 * @version $Id: _generic_recursive_list.inc.php 6135 2014-03-08 07:54:05Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );
//____________________ Callbacks functions to display categories list _____________________

global $list_title;

global $GenericCategoryCache;

global $line_class;

global $permission_to_edit;

global $subset_ID;

$line_class = 'odd';


/**
 * Generate category line when it has children
 *
 * @param GenericCategory generic category we want to display
 * @param int level of the category in the recursive tree
 * @return string HTML
 */
function cat_line( $GenericCategory, $level )
{
	global $line_class, $result_fadeout, $permission_to_edit, $current_User;

	$line_class = $line_class == 'even' ? 'odd' : 'even';

	$r = '<tr id="tr-'.$GenericCategory->ID.'"class="'.$line_class.
					// Fadeout?
					( in_array( $GenericCategory->ID, $result_fadeout ) ? ' fadeout-ffff00': '' ).'">
					<td class="firstcol shrinkwrap">'.
						$GenericCategory->ID.'
					</td>';

	if( $permission_to_edit )
	{	// We have permission permission to edit, so display action column:
		$edit_url = regenerate_url( 'action,'.$GenericCategory->dbIDname, $GenericCategory->dbIDname.'='.$GenericCategory->ID.'&amp;action=edit' );
		$r .= '<td>
						<label style="padding-left: '.($level).'em;"><a href="'.$edit_url.'" title="'.T_('Edit...').'">'.$GenericCategory->name.'</a></label>
					 </td>
					 <td class="lastcol shrinkwrap">'.
						 action_icon( T_('New...'), 'new', regenerate_url( 'action,'.$GenericCategory->dbIDname.','.$GenericCategory->dbprefix.'parent_ID', $GenericCategory->dbprefix.'parent_ID='.$GenericCategory->ID.'&amp;action=new' ) ).
						 action_icon( T_('Edit...'), 'edit', $edit_url ).
						 action_icon( T_('Delete...'), 'delete', regenerate_url( 'action,'.$GenericCategory->dbIDname, $GenericCategory->dbIDname.'='.$GenericCategory->ID.'&amp;action=delete&amp;'.url_crumb('element') ) ).'
					 </td>';
	}
	else
	{
		$r .= '<td class="lastcol">
						 <label style="padding-left: '.($level).'em;">'.$GenericCategory->name.'</label>
					 </td>';
	}


	$r .=	'</tr>';

	return $r;
}


/**
 * Generate category line when it has no children
 *
 * @param GenericCategory generic category we want to display
 * @param int level of the category in the recursive tree
 * @return string HTML
 */
function cat_no_children( $GenericCategory, $level )
{
	return '';
}


/**
 * Generate code when entering a new level
 *
 * @param int level of the category in the recursive tree
 * @return string HTML
 */
function cat_before_level( $level )
{
	return '';
}

/**
 * Generate code when exiting from a level
 *
 * @param int level of the category in the recursive tree
 * @return string HTML
 */
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


if( $permission_to_edit )
{	// We have permission permission to edit, so display global icon to add nex genereic element:
	echo '<span class="right_icons">'
				.action_icon( T_('Create a new element...'), 'new', regenerate_url( 'action,'.$GenericCategoryCache->dbIDname, 'action=new' ), T_('Add element').' &raquo;', 3, 4 ).'
				</span>';
}

echo				$list_title.'
						</div>
					</th>
			</tr>
			<tr>
					<th class="firstcol shrinkwrap right">'.T_('ID').'</th>
					<th>'.T_('Name').'</th>';

if( $permission_to_edit )
{	// We have permission permission to edit, so display action column:
	echo '<th class="lastcol">'.T_('Actions').'</th>';
}

echo '</tr>';

echo $GenericCategoryCache->recurse( $callbacks, $subset_ID );

echo '</table>';

?>