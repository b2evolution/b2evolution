<?php
/**
 * Browsing posts for editing
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */

/**
 * Includes:
 */
require_once (dirname(__FILE__). '/_header.php');
$admin_tab = 'edit';
$admin_pagetitle = T_('Browse blog:');
param( 'blog', 'integer', 0 );

if( ($blog == 0) && $current_User->check_perm( 'blog_ismember', 1, false, $default_to_blog ) )
{	// Default blog is a valid choice
	$blog = $default_to_blog;
}
require (dirname(__FILE__). '/_menutop.php');

// ---------------------------------- START OF BLOG LIST ----------------------------------
$sep = '';
for( $curr_blog_ID = blog_list_start();
			$curr_blog_ID != false;
			$curr_blog_ID = blog_list_next() )
	{
		if( ! $current_User->check_perm( 'blog_ismember', 1, false, $curr_blog_ID ) )
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

	require (dirname(__FILE__). '/_menutop_end.php');

	if( $blog == 0 )
	{	// No blog could be selected
		?>
		<div class="panelblock">
		<?php printf( T_('Since you\'re a newcomer, you\'ll have to wait for an admin to authorize you to post. You can also <a %s>e-mail the admin</a> to ask for a promotion. When you\'re promoted, just reload this page and you\'ll be able to blog. :)'), 'href="mailto:'. $admin_email. '?subject=b2-promotion"' ); ?>
		</div>
		<?php
	}
	else
	{	// We could select a blog:
		$Blog = Blog_get_by_ID( $blog ); /* TMP: */ $blogparams = get_blogparams_by_ID( $blog );

		// Check permission:
		$current_User->check_perm( 'blog_ismember', 1, true, $blog );

		// Show the posts:
		require dirname(__FILE__). '/_edit_showposts.php';
	}

	require( dirname(__FILE__). '/_footer.php' );

?>