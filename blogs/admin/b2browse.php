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

$blog = autoselect_blog( param( 'blog', 'integer', 0 ), 'blog_ismember', 1 );

if( $blog != 0 )
{ // We could select a blog:
	$Blog = Blog_get_by_ID( $blog ); /* TMP: */ $blogparams = get_blogparams_by_ID( $blog );
	$AdminUI->title .= ' '.$Blog->dget( 'shortname' );
}
else
{ // No blog could be selected
	$Messages->add( sprintf( T_('Since you\'re a newcomer, you\'ll have to wait for an admin to authorize you to post. You can also <a %s>e-mail the admin</a> to ask for a promotion. When you\'re promoted, just reload this page and you\'ll be able to blog. :)'), 'href="mailto:'. $admin_email. '?subject=b2-promotion"' ) );
}


// Generate available blogs list:
$blogListButtons = $AdminUI->getCollectionList( 'blog_ismember', 1, $pagenow.'?blog=%d' );

require dirname(__FILE__).'/_menutop.php';

if( $blog )
{ // We could select a valid blog which we have permission to access:
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

require dirname(__FILE__).'/_footer.php';
?>