<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003-2004 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * Display posts for editing
 */
require_once (dirname(__FILE__).'/_header.php');

if( ($blog == 0) && $current_User->is_blog_member( $default_to_blog ) )
{	// Default blog is a valid choice
	$blog = $default_to_blog;
}

$title = T_('Browse blog:');
require (dirname(__FILE__).'/_menutop.php');

// ---------------------------------- START OF BLOG LIST ----------------------------------
$sep = '';
for( $curr_blog_ID=blog_list_start(); 
			$curr_blog_ID!=false; 
			 $curr_blog_ID=blog_list_next() ) 
	{ 
		if( ! $current_User->is_blog_member( $curr_blog_ID ) )
		{	// Current user is not a member of this blog...
			continue;
		}
		if( $blog == 0 )
		{	// If no selected blog yet, select this one:
			$blog = $curr_blog_ID;
		}
		echo $sep;
		if( $curr_blog_ID == $blog ) 
		{ // This is the blog being displayed on this page ?>
		<strong>[<a href="<?php echo $pagenow ?>?blog=<?php echo $curr_blog_ID ?>"><?php blog_list_iteminfo('shortname') ?></a>]</strong>
		<?php 
		} 
		else 
		{ // This is another blog ?>
		<a href="<?php echo $pagenow ?>?blog=<?php echo $curr_blog_ID ?>"><?php blog_list_iteminfo('shortname') ?></a>
		<?php 
		} 
		$sep = ' | ';
	} // --------------------------------- END OF BLOG LIST --------------------------------- 

	require (dirname(__FILE__).'/_menutop_end.php');

	get_blogparams();
	
	if( ($user_level == 0) || ! $current_User->is_blog_member( $blog ) )
	{	
		die( 'Permission denied.');
	}

	require dirname(__FILE__).'/_edit_showposts.php';

	require( dirname(__FILE__).'/_footer.php' ); 

?>