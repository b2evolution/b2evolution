<?php
/**
 * This file implements the UI controller for the browsing posts.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */

/**
 * Includes:
 */
require_once (dirname(__FILE__). '/_header.php');

$itemTypeCache = & new DataObjectCache( 'Element', true, 'T_posttypes', 'ptyp_', 'ptyp_ID' );
$itemStatusCache = & new DataObjectCache( 'Element', true, 'T_poststatuses', 'pst_', 'pst_ID' );

$AdminUI->setPath( 'edit' );
$AdminUI->title = $AdminUI->title_titlearea = T_('Browse blog:');
param( 'blog', 'integer', 0 );

if( $blog == 0 )
{ // No blog is selected so far...
	if( $current_User->check_perm( 'blog_ismember', 1, false, $default_to_blog ) )
	{ // Default blog is a valid choice
		$blog = $default_to_blog;
	}
	else
	{ // Let's try to find another one:
		for( $curr_blog_ID = blog_list_start();
					$curr_blog_ID != false;
					$curr_blog_ID = blog_list_next() )
		{
			if( $current_User->check_perm( 'blog_ismember', 1, false, $curr_blog_ID ) )
			{ // Current user is a member of this blog... let's select it:
				$blog = $curr_blog_ID;
				break;
			}
		}
	}
}

if( $blog != 0 )
{ // We could select a blog:
	$Blog = Blog_get_by_ID( $blog ); /* TMP: */ $blogparams = get_blogparams_by_ID( $blog );
	$admin_pagetitle .= ' '.$Blog->dget( 'shortname' );
}

// Generate available blogs list:
$blogListButtons = $AdminUI->getCollectionList( 'blog_ismember', 1, $pagenow.'?blog=%d' );

require( dirname(__FILE__).'/_menutop.php' );

if( $blog == 0 )
{ // No blog could be selected
	?>
	<div class="panelblock">
	<?php printf( T_('Since you\'re a newcomer, you\'ll have to wait for an admin to authorize you to post. You can also <a %s>e-mail the admin</a> to ask for a promotion. When you\'re promoted, just reload this page and you\'ll be able to blog. :)'), 'href="mailto:'. $admin_email. '?subject=b2-promotion"' ); ?>
	</div>
	<?php
}
else
{ // We could select a blog:

	// Check permission:
	$current_User->check_perm( 'blog_ismember', 1, true, $blog );

	// Show the posts:
	$add_item_url = 'b2edit.php?blog='.$blog;
	$edit_item_url = 'b2edit.php?action=edit&amp;post=';
	$delete_item_url = 'edit_actions.php?action=delete&amp;post=';
	$objType = 'Item';
	$dbtable = 'T_posts';
	$dbprefix = 'post_';
	$dbIDname = 'ID';

	require dirname(__FILE__).'/_edit_showposts.php';
}

require( dirname(__FILE__).'/_footer.php' );
?>