<?php
$title = _('Categories');
/* <Categories> */

function add_magic_quotes($array) {
	foreach ($array as $k => $v) {
		if (is_array($v)) {
			$array[$k] = add_magic_quotes($v);
		} else {
			$array[$k] = addslashes($v);
		}
	}
	return $array;
} 

if (!get_magic_quotes_gpc()) {
	$HTTP_GET_VARS    = add_magic_quotes($HTTP_GET_VARS);
	$HTTP_POST_VARS   = add_magic_quotes($HTTP_POST_VARS);
	$HTTP_COOKIE_VARS = add_magic_quotes($HTTP_COOKIE_VARS);
}

$b2varstoreset = array('action','standalone','cat');
for ($i=0; $i<count($b2varstoreset); $i += 1) {
	$b2var = $b2varstoreset[$i];
	if (!isset($$b2var)) {
		if (empty($HTTP_POST_VARS["$b2var"])) {
			if (empty($HTTP_GET_VARS["$b2var"])) {
				$$b2var = '';
			} else {
				$$b2var = $HTTP_GET_VARS["$b2var"];
			}
		} else {
			$$b2var = $HTTP_POST_VARS["$b2var"];
		}
	}
}


switch($action) 
{
case "newcat":
	$standalone=0;
	require_once (dirname(__FILE__)."/b2header.php");

	if ($user_level < 3) 
	{
		die( _('You have no right to edit the categories.') );
	}

	echo "<div class=\"panelblock\">\n";

	$parent_cat_ID = $_GET['parent_cat_ID'];
	if( !empty($parent_cat_ID) )
	{	// We are creating a subcat
		$parent_cat_name = get_catname($parent_cat_ID);
		?>
	<h3><?php printf( _('New sub-category in category: %s'), $parent_cat_name ); ?></h3>
	<form name="addcat" action="b2categories.php" method="post">
		<input type="hidden" name="parent_cat_ID" value="<?php echo $parent_cat_ID ?>" />
		<?php 
	}
	else
	{ // We are creating a new base cat
		$blog_ID = $_GET['blog_ID'];
		$blogparams = get_blogparams_by_ID($blog_ID);
		?>
	<h3><?php printf( _('New category in blog: %s'), $blogparams->blog_name ); ?></h3>
	<form name="addcat" action="b2categories.php" method="post">
		<input type="hidden" name="cat_blog_ID" value="<?php echo $blog_ID ?>" />
		<?php 
	}
	?>
		<input type="hidden" name="action" value="addcat" />
		<p><?php echo _('New category name') ?>: <input type="text" name="cat_name" /></p>
		<input type="submit" name="submit" value="Create category" class="search" />
	</form>
	</div>
	<?php
	break;


case "addcat":
	/*
	 * INSERT new cat into db
	 */
	$standalone = 1;
	require_once(dirname(__FILE__)."/b2header.php");

	if ($user_level < 3)
	die ("Cheatin' uh ?");
	
	$cat_name=addslashes($_POST["cat_name"]);
	$parent_cat_ID = $_POST['parent_cat_ID'];
	$cat_blog_ID = $_POST['cat_blog_ID'];

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

	$standalone = 0;
	require_once(dirname(__FILE__)."/b2header.php");

	echo "<div class=\"panelinfo\">\n";
	echo '<h3>', _('Deleting category...'), "</h3>\n";
	
	$cat_ID = $_GET["cat_ID"];

	if ($user_level < 3) die ('Cheatin\' uh ?');
	
	// DELETE FROM DB:
	$result = cat_delete( $cat_ID ) or mysql_oops( $query );	
	if( $result !== 1 )
	{	// We got an error message!
		echo '<p class="error">', _('ERROR'), ': ', $result, "</p>\n";
	}
	else
	{
		echo _('Category deleted.');
	}
	echo "</div>\n";

	break;
	
	


case "Edit":

	require_once (dirname(__FILE__)."/b2header.php");
	$cat_ID = $_GET['cat_ID'];
	$cat_name = get_catname($cat_ID);
	$cat_name = addslashes($cat_name);
	$cat_blog = get_catblog($cat_ID);
	$cat_parent_ID = get_catparent($cat_ID);
	?>
	<div class="panelblock">
	<p><?php echo _('<strong>Old</strong> name') ?>: <?php echo $cat_name ?></p>
	<p>
	<form name="renamecat" action="b2categories.php" method="post">
		<?php echo _('<strong>New</strong> name') ?>:<br />
		<input type="hidden" name="action" value="editedcat" />
		<input type="hidden" name="cat_ID" value="<?php echo $cat_ID ?>" />
		<input type="text" name="cat_name" value="<?php echo $cat_name ?>" />
		<h3><?php echo _('New parent category') ?>:</h3>
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
		if( $cat_parent_ID == $curr_cat_ID ) echo ' &lt;= ', _('Old Parent');
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
	<input type="radio" id="cat_parent_none" name="cat_parent_ID" value="NULL" 
		<?php 
			if( ! $cat_parent_ID ) echo 'checked="checked"';
		?>
		/>
		<label for="cat_parent_none"><strong>Root (No parent)</strong></label>
		<?php	
		if( ! $cat_parent_ID ) echo ' &lt;= ', _('Old Parent');
		echo "</li>\n";


	// RECURSE:
	cat_children( $cache_categories, $cat_blog, NULL, cat_move_before_first, cat_move_before_each, cat_move_after_each, cat_move_after_last );

	// ----------------- END RECURSIVE CAT LIST ----------------
?>		
		<input type="submit" name="submit" value="Edit it !" class="search" />
	</form>
	</div>

	<?php

	break;

case "editedcat":

	$standalone = 1;
	require_once(dirname(__FILE__).'/b2header.php');

	if ($user_level < 3)
	die ("Cheatin' uh ?");
	
	$cat_name=addslashes($_POST['cat_name']);
	$cat_ID=$_POST['cat_ID'];
	$cat_parent_ID=$_POST['cat_parent_ID'];

	cat_update( $cat_ID, $cat_name, $cat_parent_ID ) or mysql_oops( $query );
		
	header("Location: b2categories.php");

break;

default:

	$standalone=0;
	require_once (dirname(__FILE__)."/b2header.php");
	if ($user_level < 3) 
	{
		die( 
		_('You have no right to edit the categories.') );
	}

break;
}
?>

<div class="panelblock">
<h2>Categories:</h2>
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
		echo " [<a href=\"?action=Edit&cat_ID=".$cat_ID.'">', _('Edit'), '</a>]';
		echo " [<a href=\"?action=Delete&cat_ID=", $cat_ID, 
			'" onClick="return confirm(\'Are you sure you want to delete?\')">', _('Delete'), '</a>]';
		echo "<ul>\n";
	}
	function cat_edit_after_each( $cat_ID, $level )
	{	// callback to display sublist element
		echo "<li>[<a href=\"?action=newcat&parent_cat_ID=".$cat_ID.'">', _('New sub-category here'), "</a>]</li>\n";
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
		echo "<li>[<a href=\"?action=newcat&blog_ID=".$i_blog->blog_ID, '">', _('New category here'), "</a>]</li>\n";
		echo "</ul>\n";
	}
	// ----------------- END RECURSIVE CAT LIST ----------------
?>
	<p><?php echo _('<strong>Note:</strong> Deleting a category does not delete posts from that category. It will just assign them to the parent category. When deleting a root category, posts will be assigned to the oldest remaining category in the same blog (smallest category number).') ?></p>
	</div>


<?php include($b2inc."/_footer.php"); ?>