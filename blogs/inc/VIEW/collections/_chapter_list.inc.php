<?php
/**
 * This file implements the recursive chapter list.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );
//____________________ Callbacks functions to display categories list _____________________

global $Blog;

global $GenericCategoryCache;

global $line_class;

global $permission_to_edit;

global $subset_ID;

$line_class = 'odd';


/**
 * Generate category line when it has children
 *
 * @param Chapter generic category we want to display
 * @param int level of the category in the recursive tree
 * @return string HTML
 */
function cat_line( $Chapter, $level )
{
	global $line_class, $result_fadeout, $permission_to_edit, $current_User, $allow_moving_chapters;


	$line_class = $line_class == 'even' ? 'odd' : 'even';

	$r = '<tr id="tr-'.$Chapter->ID.'"class="'.$line_class.
					// Fadeout?
					( in_array( $Chapter->ID, $result_fadeout ) ? ' fadeout-ffff00': '' ).'">
					<td class="firstcol shrinkwrap">'.
						$Chapter->ID.'
				</td>';

	if( $permission_to_edit )
	{	// We have permission permission to edit:
		$edit_url = regenerate_url( 'action,'.$Chapter->dbIDname, $Chapter->dbIDname.'='.$Chapter->ID.'&amp;action=edit' );
		$r .= '<td>
						<label style="padding-left: '.($level).'em;"><a href="'.$edit_url.'" title="'.T_('Edit...').'">'.$Chapter->dget('name').'</a></label>
					 </td>';
	}
	else
	{
		$r .= '<td>
						 <label style="padding-left: '.($level).'em;">'.$Chapter->dget('name').'</label>
					 </td>';
	}

	$r .= '<td>'.$Chapter->dget('urlname').'</td>';


	$r .= '<td class="lastcol shrinkwrap">';
	if( $permission_to_edit )
	{	// We have permission permission to edit, so display action column:
		$r .=  action_icon( T_('New...'), 'new', regenerate_url( 'action,cat_ID,cat_parent_ID', 'cat_parent_ID='.$Chapter->ID.'&amp;action=new' ) )
					.action_icon( T_('Edit...'), 'edit', $edit_url );
		if( $allow_moving_chapters )
		{ // If moving cats between blogs is allowed:
			$r .= action_icon( T_('Move to a different blog...'), 'file_move', regenerate_url( 'action,cat_ID', 'cat_ID='.$Chapter->ID.'&amp;action=move' ), T_('Move') );
		}
		$r .= action_icon( T_('Delete...'), 'delete', regenerate_url( 'action,cat_ID', 'cat_ID='.$Chapter->ID.'&amp;action=delete' ) );
	}
	$r .= '</td>';
	$r .=	'</tr>';

	return $r;
}


/**
 * Generate category line when it has no children
 *
 * @param Chapter generic category we want to display
 * @param int level of the category in the recursive tree
 * @return string HTML
 */
function cat_no_children( $Chapter, $level )
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
				<th colspan="4" class="results_title">
					<div class="results_title">';


if( $permission_to_edit )
{	// We have permission permission to edit, so display global icon to add nex genereic element:
	echo '<span class="right_icons">'
				.action_icon( T_('Add an element...'), 'new', regenerate_url( 'action,'.$GenericCategoryCache->dbIDname, 'action=new' ), T_('Add element'), 3, 4 ).'
				</span>';
}

echo T_('Categories for blog:').' '.$Blog->dget('name').'
					</div>
				</th>
			</tr>
			<tr>
				<th class="firstcol shrinkwrap right">'.T_('ID').'</th>
				<th>'.T_('Name').'</th>
				<th>'.T_('URL Name').'</th>';

if( $permission_to_edit )
{	// We have permission permission to edit, so display action column:
	echo '<th class="lastcol">'.T_('Actions').'</th>';
}

echo '</tr>';

echo $GenericCategoryCache->recurse( $callbacks, $subset_ID );

echo '</table>';

echo '<p class="note">'.T_('<strong>Note:</strong> Deleting a category does not delete items from that category. It will just assign them to the parent category. When deleting a root category, items will be assigned to the oldest remaining category in the same collection (smallest category number).').'</p>';

global $allow_moving_chapters;
if( !$allow_moving_chapters )
{
	echo '<p class="note">'.T_('<strong>Note:</strong> Moving categories across blogs is disabled.').'</p> ';
}


/*
 * $Log$
 * Revision 1.6  2006/12/10 01:52:27  fplanque
 * old cats are now officially dead :>
 *
 * Revision 1.5  2006/12/09 17:59:34  fplanque
 * started "moving chapters accross blogs" feature
 *
 * Revision 1.4  2006/12/09 02:37:44  fplanque
 * Prevent user from creating loops in the chapter tree
 * (still needs a check before writing to DB though)
 *
 * Revision 1.3  2006/11/24 18:27:25  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.2  2006/09/11 19:34:34  fplanque
 * fully powered the ChapterCache
 *
 * Revision 1.1  2006/09/10 19:32:32  fplanque
 * completed chapter URL name editing
 *
 */
?>