<?php 
/**
 * Displays list of cats for editing
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */
?>
<div class="panelblock">
<h2><?php echo T_('Categories for blog:'), ' ', get_bloginfo('name'); ?></h2>
<?php 
	
	// ----------------- START RECURSIVE CAT LIST ----------------
	cat_query();	// make sure the caches are loaded
	function cat_edit_before_first( $parent_cat_ID, $level )
	{	// callback to start sublist
		
	}
	function cat_edit_before_each( $cat_ID, $level )
	{	// callback to display sublist element
		$cat = get_the_category_by_ID( $cat_ID );
		echo '<li>';
		echo "<a href=\"?action=Edit&cat_ID=".$cat_ID.'">';
		echo '<img src="img/properties.png" width="18" height="13" class="middle" alt="', T_('Properties'), '" />';
		echo '</a> <strong>'.$cat['cat_name'].'</strong>';
		echo " <a href=\"?action=Delete&cat_ID=", $cat_ID, 
			'" onClick="return confirm(\''.T_('Are you sure you want to delete?').'\')">';
		echo '<img src="img/xross.gif" width="13" height="13" class="middle" alt="', /* TRANS: Abbrev. for Delete */ T_('Del'), '" />';
		echo '</a>';
		echo "
<ul>\n";
	}
	function cat_edit_after_each( $cat_ID, $level )
	{	// callback to display sublist element
		echo "<li><a href=\"?action=newcat&parent_cat_ID=".$cat_ID.'">';
		echo '<img src="img/new.png" width="13" height="12" class="middle" alt="', T_('Create'), '" /> ';
		echo T_('New sub-category here'), "</a></li>\n";
		echo "</ul>\n";
		echo "</li>\n";
	}
	function cat_edit_after_last( $parent_cat_ID, $level )
	{	// callback to end sublist
		if( $level > 0 )
		{
		}
	}

	// run recursively through the cats
	echo "<ul>\n";
	cat_children( $cache_categories, $blog, NULL, 'cat_edit_before_first', 'cat_edit_before_each', 'cat_edit_after_each', 'cat_edit_after_last', 0 );
	echo "<li><a href=\"?action=newcat&blog=".$blog, '">';
	echo '<img src="img/new.png" width="13" height="12" class="middle" alt="', T_('Create'), '" /> ';
	echo T_('New category here'), "</a></li>\n";
	echo "</ul>\n";
	// ----------------- END RECURSIVE CAT LIST ----------------
?>
	<p><?php echo T_('<strong>Note:</strong> Deleting a category does not delete posts from that category. It will just assign them to the parent category. When deleting a root category, posts will be assigned to the oldest remaining category in the same blog (smallest category number).') ?></p>
</div>
