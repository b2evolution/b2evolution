<?php
/**
 * Editing the categories
 *
 * b2evolution - {@link http://b2evolution.net/}
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */
require_once( dirname(__FILE__) . '/_header.php' );
$title = T_('Categories for blog:');
param( 'action', 'string' );

/** 
 * Local use
 *
 * {@internal cats_display_blog_list(-) }}
 *
 * @access private 
 */
function cats_display_blog_list()
{
	global $blog, $current_User;
	$sep = '';
	for( $curr_blog_ID = blog_list_start();
				$curr_blog_ID != false;
				$curr_blog_ID = blog_list_next() )
	{ 
		if( ! $current_User->check_perm( 'blog_cats', '', false, $curr_blog_ID ) )
		{	// Current user is not allowed to edit cats...
			continue;
		}
		if( $blog == 0 )
		{	// If no selected blog yet, select this one:
			$blog = $curr_blog_ID;
		}
		echo $sep;
		if( $curr_blog_ID == $blog ) 
		{ // This is the blog being displayed on this page ?>
		<strong>[<a href="b2categories.php?blog=<?php echo $curr_blog_ID ?>"><?php blog_list_iteminfo('shortname') ?></a>]</strong>
		<?php 
		} 
		else 
		{ // This is another blog ?>
		<a href="b2categories.php?blog=<?php echo $curr_blog_ID ?>"><?php blog_list_iteminfo('shortname') ?></a>
		<?php 
		} 
		$sep = ' | ';
	}
}

switch($action) 
{
	case 'newcat':
		// New category form:
		param( 'parent_cat_ID', 'integer' );
		if( !empty($parent_cat_ID) )
		{
			$blog = get_catblog($parent_cat_ID);
		}
		
		require(dirname(__FILE__).'/_menutop.php');
		cats_display_blog_list();
		require(dirname(__FILE__).'/_menutop_end.php');
	
		// check permissions:
		$current_User->check_perm( 'blog_cats', '', true, $blog );
	
		echo "<div class=\"panelblock\">\n";
	
		if( !empty($parent_cat_ID) )
		{	// We are creating a subcat
			$parent_cat_name = get_catname($parent_cat_ID);
			?>
		<h2><?php printf( T_('New sub-category in category: %s'), $parent_cat_name ); ?></h2>
		<form name="addcat" action="b2categories.php" method="post">
			<input type="hidden" name="parent_cat_ID" value="<?php echo $parent_cat_ID ?>" />
			<?php 
		}
		else
		{ // We are creating a new base cat
			$blogparams = get_blogparams_by_ID($blog);
			?>
		<h2><?php printf( T_('New category in blog: %s'), $blogparams->blog_name ); ?></h2>
		<form name="addcat" action="b2categories.php" method="post">
			<input type="hidden" name="cat_blog_ID" value="<?php echo $blog ?>" />
			<?php 
		}
		?>
			<input type="hidden" name="action" value="addcat" />
			<p><?php echo T_('New category name') ?>: <input type="text" name="cat_name" /></p>
			<input type="submit" name="submit" value="<?php echo T_('Create category') ?>" class="search" />
		</form>
		</div>
		<?php
		// List the cats:
		require( dirname(__FILE__).'/_cats_list.php' ); 
		break;
	
	
	case 'addcat':
		// INSERT new cat into db
		param( 'cat_name', 'string', true );
		param( 'parent_cat_ID', 'integer' );
		if( !empty($parent_cat_ID) )
		{	// We are creating a subcat
			$cat_blog_ID = get_catblog($parent_cat_ID);
		}
		else
		{
			param( 'cat_blog_ID', 'integer', true );
		}

		// check permissions:
		$current_User->check_perm( 'blog_cats', '', true, $cat_blog_ID );
	
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
		
		header("Location: b2categories.php?blog=$cat_blog_ID");
	
		break;
	
	
	case 'Delete':
		// Delete cat from DB:
		param( 'cat_ID', 'integer' );
		$blog = get_catblog($cat_ID);

		require(dirname(__FILE__).'/_menutop.php');
		cats_display_blog_list();
		require(dirname(__FILE__).'/_menutop_end.php');
	
		// check permissions:
		$current_User->check_perm( 'blog_cats', '', true, $blog );
	
		$cat_name = get_catname($cat_ID);

		echo "<div class=\"panelinfo\">\n";
		echo '<h3>', sprintf( T_('Deleting category #%d : %s ...') ,$cat_ID, format_to_output( $cat_name, 'htmlbody') ), "</h3>\n";
			
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
		// List the cats:
		require( dirname(__FILE__).'/_cats_list.php' ); 
	
		break;
		
		
	case 'Edit':
		// Cat edit form:
		param( 'cat_ID', 'integer' );
		$blog = get_catblog($cat_ID);

		require(dirname(__FILE__).'/_menutop.php');
		cats_display_blog_list();
		require(dirname(__FILE__).'/_menutop_end.php');
	
		// check permissions:
		$current_User->check_perm( 'blog_cats', '', true, $blog );
	
		$cat_name = get_catname($cat_ID);
		$cat_parent_ID = get_catparent($cat_ID);
		?>
		<div class="panelblock">
		<h2><?php echo T_('Properties for category:'), ' ', format_to_output( $cat_name, 'htmlbody' ) ?></h2>
		<p>
		<form name="renamecat" action="b2categories.php" method="post">
			<?php echo T_('Name') ?>:
			<input type="hidden" name="action" value="editedcat" />
			<input type="hidden" name="cat_ID" value="<?php echo $cat_ID ?>" />
			<input type="text" name="cat_name" value="<?php echo format_to_output( $cat_name, 'formvalue' ) ?>" />
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
			<input type="radio" id="cat_parent_ID<?php echo $curr_cat_ID; ?>" name="cat_parent_ID" value="<?php echo $curr_cat_ID ?>" 
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
		cat_children( $cache_categories, $blog, NULL, 'cat_move_before_first', 'cat_move_before_each', 'cat_move_after_each', 'cat_move_after_last' );
	
		// ----------------- END RECURSIVE CAT LIST ----------------
	?>		
			<input type="submit" name="submit" value="<?php echo T_('Edit category!') ?>" class="search" />
		</form>
		</div>
	
		<?php
		// List the cats:
		require( dirname(__FILE__).'/_cats_list.php' ); 
		break;
	
	
	case 'editedcat':
		// Update cat in db:
		param( 'cat_name', 'string', true );
		param( 'cat_parent_ID', 'integer', true );
		param( 'cat_ID', 'integer', true );
		//echo $cat_ID; 
		$cat_blog_ID = get_catblog($cat_ID);
		if( $cat_parent_ID != 0 )
		{	// Check that parent is in same blog
			$parent_cat_blog_ID = get_catblog($cat_parent_ID);
			if( $cat_blog_ID != $parent_cat_blog_ID )
			{
				die( 'Cat and parent must be in the same blog!' );
			}
		}
		
		// check permissions:
		$current_User->check_perm( 'blog_cats', '', true, $cat_blog_ID );

		cat_update( $cat_ID, $cat_name, $cat_parent_ID ) or mysql_oops( $query );
	
		header("Location: b2categories.php?blog=$cat_blog_ID");
		break;
	
	default:
		// Just display cat list for this blog
		require(dirname(__FILE__) . '/_menutop.php');
		cats_display_blog_list();
		require(dirname(__FILE__) . '/_menutop_end.php');

		if( $blog == 0 )
		{	// No blog could be selected
			?>
			<div class="panelblock">
			<?php printf( T_('Since you\'re a newcomer, you\'ll have to wait for an admin to authorize you to post. You can also <a %s>e-mail the admin</a> to ask for a promotion. When you\'re promoted, just reload this page and you\'ll be able to blog. :)'), 'href="mailto:'.$admin_email.'?subject=b2-promotion"' ); ?>
			</div>
			<?php
			break;
		}

		// check permissions:
		$current_User->check_perm( 'blog_cats', '', true, $blog );

		// List the cats:
		require( dirname(__FILE__).'/_cats_list.php' ); 
}

require( dirname(__FILE__).'/_footer.php' ); 

?>