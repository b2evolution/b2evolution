<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003-2004 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 */
require_once (dirname(__FILE__).'/_header.php');
$title = T_('Categories');

param( 'action', 'string' );

switch($action) 
{
case 'newcat':
	require(dirname(__FILE__).'/_menutop.php');
	require(dirname(__FILE__).'/_menutop_end.php');

	if( $user_level < 3 ) 
	{
		die( '<p>'.T_('You have no right to edit the categories.').'</p>' );
	}

	echo "<div class=\"panelblock\">\n";

	param( 'parent_cat_ID', 'integer' );
	if( !empty($parent_cat_ID) )
	{	// We are creating a subcat
		$parent_cat_name = get_catname($parent_cat_ID);
		?>
	<h3><?php printf( T_('New sub-category in category: %s'), $parent_cat_name ); ?></h3>
	<form name="addcat" action="b2categories.php" method="post">
		<input type="hidden" name="parent_cat_ID" value="<?php echo $parent_cat_ID ?>" />
		<?php 
	}
	else
	{ // We are creating a new base cat
		param( 'blog_ID', 'integer' );
		$blogparams = get_blogparams_by_ID($blog_ID);
		?>
	<h3><?php printf( T_('New category in blog: %s'), $blogparams->blog_name ); ?></h3>
	<form name="addcat" action="b2categories.php" method="post">
		<input type="hidden" name="cat_blog_ID" value="<?php echo $blog_ID ?>" />
		<?php 
	}
	?>
		<input type="hidden" name="action" value="addcat" />
		<p><?php echo T_('New category name') ?>: <input type="text" name="cat_name" /></p>
		<input type="submit" name="submit" value="<?php echo T_('Create category') ?>" class="search" />
	</form>
	</div>
	<?php
	break;


case 'addcat':
	/*
	 * INSERT new cat into db
	 */
	if( $user_level < 3 ) 
	{
		die( '<p>'.T_('You have no right to edit the categories.').'</p>' );
	}
	
	param( 'cat_name', 'string' );
	param( 'parent_cat_ID', 'integer' );
	param( 'cat_blog_ID', 'integer' );

	if( !empty($parent_cat_ID) )
	{	// We are creating a subcat
		// INSERT INTO DB
		$new_cat_ID = cat_create( $cat_name, $parent_cat_ID ) or mysql_oops( $query );
	}
	else
	{ // We are creating a new base cat
		// INSERT INTO DB
		$new_cat_ID = cat_create( $cat_name, 'NULL', $cat_blog_ID ) or mysql_oops( $query );
	}
	
	header("Location: b2categories.php");

	break;


case "Delete":
	require(dirname(__FILE__).'/_menutop.php');
	require(dirname(__FILE__).'/_menutop_end.php');

	if( $user_level < 3 ) 
	{
		die( '<p>'.T_('You have no right to edit the categories.').'</p>' );
	}

	echo "<div class=\"panelinfo\">\n";
	echo '<h3>', T_('Deleting category...'), "</h3>\n";
	
	param( 'cat_ID', 'integer' );

	// DELETE FROM DB:
	$result = cat_delete( $cat_ID ) or mysql_oops( $query );	
	if( $result !== 1 )
	{	// We got an error message!
		echo '<p class="error">', T_('ERROR'), ': ', $result, "</p>\n";
	}
	else
	{
		echo T_('Category deleted.');
	}
	echo "</div>\n";

	break;
	
	
case 'Edit':
	require(dirname(__FILE__).'/_menutop.php');
	require(dirname(__FILE__).'/_menutop_end.php');

	if( $user_level < 3 ) 
	{
		die( '<p>'.T_('You have no right to edit the categories.').'</p>' );
	}

	param( 'cat_ID', 'integer' );
	$cat_name = get_catname($cat_ID);
	$cat_name = addslashes($cat_name);
	$cat_blog = get_catblog($cat_ID);
	$cat_parent_ID = get_catparent($cat_ID);
	?>
	<div class="panelblock">
	<p><?php echo T_('<strong>Old</strong> name') ?>: <?php echo $cat_name ?></p>
	<p>
	<form name="renamecat" action="b2categories.php" method="post">
		<?php echo T_('<strong>New</strong> name') ?>:<br />
		<input type="hidden" name="action" value="editedcat" />
		<input type="hidden" name="cat_ID" value="<?php echo $cat_ID ?>" />
		<input type="text" name="cat_name" value="<?php echo $cat_name ?>" />
		<h3><?php echo T_('New parent category') ?>:</h3>
	<?php		
	// ----------------- START RECURSIVE CAT LIST ----------------
	cat_query();	// make sure the caches are loaded
	function cat_move_before_first( $parent_cat_ID, $level )
	{	// callback to start sublist
		echo "\n<ul>\n";
	}
	function cat_move_before_each( $curr_cat_ID, $level )
	{	// callback to display sublist element
		global $cat_ID;	// This is the category being currently edited !!
		global $cat_parent_ID;	// This is the old parent ID
		if( $curr_cat_ID == $cat_ID )
		{	// We have reached current category.
			// This branch cannot become a parent!
			return -1;
		}
		$cat = get_the_category_by_ID( $curr_cat_ID );
		echo "<li>"; ?>
		<input type="radio" id="cat_parent_ID<?php echo $curr_cat_ID; ?>" name="cat_parent_ID" value="<?php echo $curr_cat_ID; ?>" 
		<?php 
			if( $cat_parent_ID == $curr_cat_ID ) echo 'checked="checked"';
		?>
		/>
		<label for="cat_parent_ID<?php echo $curr_cat_ID; ?>"><strong><?php echo $cat['cat_name']; ?></strong></label>
		<?php	
		if( $cat_parent_ID == $curr_cat_ID ) echo ' &lt;= ', T_('Old Parent');
	}
	function cat_move_after_each( $curr_cat_ID, $level )
	{	// callback after each sublist element
		echo "</li>\n";
	}
	function cat_move_after_last( $parent_cat_ID, $level )
	{	// callback to end sublist
		echo "</ul>\n";
	}

	?>
	<input type="radio" id="cat_parent_none" name="cat_parent_ID" value="0" 
		<?php 
			if( ! $cat_parent_ID ) echo 'checked="checked"';
		?>
		/>
		<label for="cat_parent_none"><strong><?php echo T_('Root (No parent)') ?></strong></label>
		<?php	
		if( ! $cat_parent_ID ) echo ' &lt;= ', T_('Old Parent');
		echo "</li>\n";


	// RECURSE:
	cat_children( $cache_categories, $cat_blog, NULL, 'cat_move_before_first', 'cat_move_before_each', 'cat_move_after_each', 'cat_move_after_last' );

	// ----------------- END RECURSIVE CAT LIST ----------------
?>		
		<input type="submit" name="submit" value="<?php echo T_('Edit category!') ?>" class="search" />
	</form>
	</div>

	<?php

	break;


case 'editedcat':
	if( $user_level < 3 ) 
	{
		die( '<p>'.T_('You have no right to edit the categories.').'</p>' );
	}
	
	param( 'cat_name', 'string' );
	param( 'cat_parent_ID', 'integer' );
	param( 'cat_ID', 'integer' );

	cat_update( $cat_ID, $cat_name, $cat_parent_ID ) or mysql_oops( $query );

	header("Location: b2categories.php");

break;

default:

	require(dirname(__FILE__).'/_menutop.php');
	require(dirname(__FILE__).'/_menutop_end.php');
	if( $user_level < 3 && ! $demo_mode ) 
	{
		die( '<p>'.T_('You have no right to edit the categories.').'</p>' );
	}

break;
}
?>

<div class="panelblock">
<h2><?php echo T_('Categories') ?>:</h2>
<?php 
	
	// ----------------- START RECURSIVE CAT LIST ----------------
	cat_query();	// make sure the caches are loaded
	function cat_edit_before_first( $parent_cat_ID, $level )
	{	// callback to start sublist
		
	}
	function cat_edit_before_each( $cat_ID, $level )
	{	// callback to display sublist element
		$cat = get_the_category_by_ID( $cat_ID );
		echo "<li><strong>".$cat['cat_name'].'</strong>';
		echo " [<a href=\"?action=Edit&cat_ID=".$cat_ID.'">', T_('Edit'), '</a>]';
		echo " [<a href=\"?action=Delete&cat_ID=", $cat_ID, 
			'" onClick="return confirm(\'Are you sure you want to delete?\')">', T_('Delete'), '</a>]';
		echo "<ul>\n";
	}
	function cat_edit_after_each( $cat_ID, $level )
	{	// callback to display sublist element
		echo "<li>[<a href=\"?action=newcat&parent_cat_ID=".$cat_ID.'">', T_('New sub-category here'), "</a>]</li>\n";
		echo "</ul>\n";
		echo "</li>\n";
	}
	function cat_edit_after_last( $parent_cat_ID, $level )
	{	// callback to end sublist
		if( $level > 0 )
		{
		}
	}
	foreach( $cache_blogs as $i_blog )
	{ // run recursively through the cats
		echo "<h3>".$i_blog->blog_name."</h3>\n";
		echo "<ul>\n";
		cat_children( $cache_categories, $i_blog->blog_ID, NULL, 'cat_edit_before_first', 'cat_edit_before_each', 'cat_edit_after_each', 'cat_edit_after_last', 0 );
		echo "<li>[<a href=\"?action=newcat&blog_ID=".$i_blog->blog_ID, '">', T_('New category here'), "</a>]</li>\n";
		echo "</ul>\n";
	}
	// ----------------- END RECURSIVE CAT LIST ----------------
?>
	<p><?php echo T_('<strong>Note:</strong> Deleting a category does not delete posts from that category. It will just assign them to the parent category. When deleting a root category, posts will be assigned to the oldest remaining category in the same blog (smallest category number).') ?></p>
	</div>


<?php require( dirname(__FILE__).'/_footer.php' ); 
 ?>