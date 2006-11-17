<?php
/**
 * This file implements the UI view for the recursive categories list.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * }}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $Blog;

global $cache_categories;

// Begin payload block:
$this->disp_payload_begin();

if( isset( $Blog ) )
{
	echo '<h2>'.T_('Categories for blog:'), ' ', $Blog->dget('name').'</h2>';
}

// ----------------- START RECURSIVE CAT LIST ----------------
cat_load_cache();	// make sure the caches are loaded

/**
 * callback to start sublist
 */
function cat_edit_before_first( $parent_cat_ID, $level )
{	// callback to start sublist

}
/**
 * callback to display sublist element
 */
function cat_edit_before_each( $cat_ID, $level )
{	// callback to display sublist element
	global $Blog;

	$cat = get_the_category_by_ID( $cat_ID );
	$r = '<li>'.action_icon( T_('Edit category properties'), 'properties', '?ctrl=chapters&amp;action=edit&amp;cat_ID='.$cat_ID );
	$r .= '<a href="?ctrl=chapters&amp;action=edit&amp;cat_ID='.$cat_ID.'"><strong>'.$cat['cat_name'].'</strong></a>';
	$r .= ' <a href="?ctrl=chapters&amp;action=delete&amp;cat_ID='.$cat_ID.'&amp;blog='.$Blog->ID
				.'" onclick="return confirm(\''.TS_('Are you sure you want to delete?').'\')">';
	$r .= get_icon( 'delete' );
	$r .= '</a>';
	$r .= "<ul>\n";

	return $r;
}
/**
 * callback to display sublist element
 */
function cat_edit_after_each( $cat_ID, $level )
{	// callback to display sublist element
	global $Blog;

	$r = '<li>'.action_icon( T_('New sub-category here'), 'new', '?ctrl=chapters&amp;action=new&amp;parent_cat_ID='.$cat_ID.'&amp;blog='.$Blog->ID );
	$r .= T_('New sub-category here')."</a></li>\n";
	$r .= "</ul>\n";
	$r .= "</li>\n";

	return $r;
}
/**
 * callback to end sublist
 */
function cat_edit_after_last( $parent_cat_ID, $level )
{	// callback to end sublist
	if( $level > 0 )
	{
	}
	return '';
}

// run recursively through the cats
echo "<ul>\n";
echo cat_children( $cache_categories, $Blog->ID, NULL, 'cat_edit_before_first', 'cat_edit_before_each', 'cat_edit_after_each', 'cat_edit_after_last', 0 );
echo '<li>'.action_icon( T_('New category here'), 'new', '?ctrl=chapters&amp;action=new&amp;blog='.$Blog->ID );
echo T_('New category here'), "</a></li>\n";
echo "</ul>\n";
// ----------------- END RECURSIVE CAT LIST ----------------

Log::display( '', '', T_('<strong>Note:</strong> Deleting a category does not delete items from that category. It will just assign them to the parent category. When deleting a root category, items will be assigned to the oldest remaining category in the same collection (smallest category number).'), 'note' );

// End payload block:
$this->disp_payload_end();

/*
 * $Log$
 * Revision 1.5  2006/11/17 23:29:54  blueyed
 * Replaced cat_query() calls with cat_load_cache()
 *
 * Revision 1.4  2006/04/19 20:13:50  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 */
?>