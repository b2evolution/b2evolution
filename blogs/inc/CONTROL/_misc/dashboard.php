<?php
/**
 * This file implements the UI controller for the dashboard.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var User
 */
global $current_User;



$blogListButtons = '<a href="'.regenerate_url( array('blog'), 'blog=0' ).'" class="'.(( 0 == $blog ) ? 'CurrentBlog' : 'OtherBlog').'">'.T_('Global').'</a> ';
for( $curr_blog_ID = blog_list_start();
			$curr_blog_ID != false;
			$curr_blog_ID = blog_list_next() )
{
	$blogListButtons .= '<a href="'.regenerate_url( array('blog'), 'blog='.$curr_blog_ID ).'" class="'.(( $curr_blog_ID == $blog ) ? 'CurrentBlog' : 'OtherBlog').'">'.blog_list_iteminfo('shortname',false).'</a> ';
}



$AdminUI->set_path( 'dashboard' );

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

// fp> Note: don't bother with T_() yet. This is going to change too often.

echo '<p>Welcome to b2evolution '.$app_version.'. Work in progress...</p>';

echo '<p>This page is supposed to show you the most important things you will need on a daily basis.</p>';

if( $blog )
{
	$BlogCache = & get_Cache( 'BlogCache' );
	$Blog = & $BlogCache->get_by_ID($blog); // "Exit to blogs.." link

	echo '<h2>'.$Blog->dget( 'name' ).'</h2>';

	load_class( 'MODEL/items/_itemlist2.class.php' );

	// Create empty List:
	$ItemList = & new ItemList2( $Blog, NULL, NULL );

	$ItemList->set_default_filters( array(
			'visibility_array' => array( 'published', 'protected', 'private', 'draft', 'deprecated' ),
			'posts' => 5
		) );

	// Init filter params:
	if( ! $ItemList->load_from_Request() )
	{ // If we could not init a filterset from request
		// typically happens when we could no fall back to previously saved filterset...
		// echo ' no filterset!';
	}

	// Display VIEW:
	$AdminUI->disp_view( 'items/_browse_posts_list2.view.php' );



	// fp> TODO: drafts


	echo '<h3>Shortcuts</h3>';
	echo '<ul>';
		echo '<li><a href="admin.php?ctrl=edit&blog='.$Blog->ID.'">Write a new post...</a></li>';
		echo '<li><a href="'.$Blog->get('url').'" target="_blank">Open public blog page</a></li>';
		if( $current_User->check_perm( 'blog_properties', 'edit', false, $Blog->ID ) )
		{
			echo '<li><a href="admin.php?ctrl=coll_settings&tab=general&blog='.$Blog->ID.'">Change blog name...</a></li>';
			echo '<li><a href="admin.php?ctrl=coll_settings&tab=skin&blog='.$Blog->ID.'">Change blog skin...</a></li>';
		}
	echo '</ul>';
}


if( $current_User->check_perm( 'options', 'edit' ) )
{	// We have some serious admin privilege:
	echo '<h2>Administrative tasks</h2>';

	echo '<ul>';
		// TODO: remember system date check and only remind every 3 months
		echo '<li><a href="admin.php?ctrl=system">Check if your system is secure...</a></li>';
	echo '</ul>';
}

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

/*
 * $Log$
 * Revision 1.5  2006/12/09 02:01:48  fplanque
 * temporary / minor
 *
 * Revision 1.4  2006/12/07 23:59:31  fplanque
 * basic dashboard stuff
 *
 * Revision 1.3  2006/12/07 23:21:00  fplanque
 * dashboard blog switching
 *
 * Revision 1.2  2006/12/07 23:13:10  fplanque
 * @var needs to have only one argument: the variable type
 * Otherwise, I can't code!
 *
 * Revision 1.1  2006/12/07 22:29:26  fplanque
 * reorganized menus / basic dashboard
 *
 */
?>