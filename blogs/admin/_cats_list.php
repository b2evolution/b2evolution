<?php
/**
 * This file implements the UI view for the recursive categories list.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

// Begin payload block:
$AdminUI->dispPayloadBegin();

if( isset($Blog ) )
{
	echo '<h2>'.T_('Categories for blog:'), ' ', $Blog->dget('name').'</h2>';
}

// ----------------- START RECURSIVE CAT LIST ----------------
cat_query( false );	// make sure the caches are loaded

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
	global $blog;

	$cat = get_the_category_by_ID( $cat_ID );
	$r = '<li>';
	$r .= "<a href=\"?action=edit&amp;cat_ID=".$cat_ID.'" title="'.T_('Edit category properties').'">';
	$r .= '<img src="img/properties.png" width="18" height="13" class="middle" alt="'.T_('Properties').'" />';
	$r .= ' <strong>'.$cat['cat_name'].'</strong></a>';
	$r .= ' <a href="?action=delete&amp;cat_ID='.$cat_ID.'&amp;blog='.$blog
				.'" onclick="return confirm(\''.TS_('Are you sure you want to delete?').'\')">';
	$r .= '<img src="img/xross.gif" width="13" height="13" class="middle" alt="'./* TRANS: Abbrev. for Delete */ T_('Del').'" />';
	$r .= '</a>';
	$r .= "<ul>\n";

	return $r;
}
/**
 * callback to display sublist element
 */
function cat_edit_after_each( $cat_ID, $level )
{	// callback to display sublist element
	global $blog;

	$r = '<li><a href="?action=new&amp;parent_cat_ID='.$cat_ID.'&amp;blog='.$blog.'">';
	$r .= '<img src="img/new.gif" width="13" height="13" class="middle" alt="" /> ';
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
echo cat_children( $cache_categories, $blog, NULL, 'cat_edit_before_first', 'cat_edit_before_each', 'cat_edit_after_each', 'cat_edit_after_last', 0 );
echo "<li><a href=\"?action=new&amp;blog=".$blog, '">';
echo '<img src="img/new.gif" width="13" height="13" class="middle" alt="" /> ';
echo T_('New category here'), "</a></li>\n";
echo "</ul>\n";
// ----------------- END RECURSIVE CAT LIST ----------------

Log::display( '', '', T_('<strong>Note:</strong> Deleting a category does not delete items from that category. It will just assign them to the parent category. When deleting a root category, items will be assigned to the oldest remaining category in the same collection (smallest category number).'), 'note' );

// End payload block:
$AdminUI->dispPayloadEnd();
?>
