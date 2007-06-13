<?php
/**
 * This file implements the UI controller for the dashboard.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * @copyright (c)2003-2007 by Francois PLANQUE - {@link http://fplanque.net/}
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

if( $blog )
{
	if( ! $current_User->check_perm( 'blog_ismember', '', false, $blog ) )
	{	// We don't have permission for the requested blog (may happen if we come to admin from a link on a different blog)
		set_working_blog( 0 );
		unset( $Blog );
	}
}

$blogListButtons = $AdminUI->get_html_collection_list( 'blog_ismember', 'view',
											regenerate_url( array('blog'), 'blog=%d' ),
											T_('Global'), regenerate_url( array('blog'), 'blog=0' ) );

$AdminUI->set_path( 'dashboard' );

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();


// fp> Note: don't bother with T_() yet. This is going to change too often.

if( $blog )
{	// We want to look at a specific blog:
	// Begin payload block:
	$AdminUI->disp_payload_begin();

	echo '<h2>'.$Blog->dget( 'name' ).'</h2>';

	echo '<div class="dashboard_sidebar">';
	echo '<h3>Shortcuts</h3>';
	echo '<ul>';
		echo '<li><a href="admin.php?ctrl=items&amp;action=new&amp;blog='.$Blog->ID.'">Write a new post &raquo;</a></li>';

 		echo '<li>Manage:<ul>';
		echo '<li><a href="admin.php?ctrl=items&tab=full&filter=restore&blog='.$Blog->ID.'">Posts (full) &raquo;</a></li>';
		echo '<li><a href="admin.php?ctrl=items&tab=list&filter=restore&blog='.$Blog->ID.'">Posts (list) &raquo;</a></li>';
		echo '<li><a href="admin.php?ctrl=comments&blog='.$Blog->ID.'">Comments &raquo;</a></li>';
		echo '</ul></li>';

		if( $current_User->check_perm( 'blog_properties', 'edit', false, $Blog->ID ) )
		{
			echo '<li>Customize blog:<ul>';
			echo '<li><a href="admin.php?ctrl=coll_settings&tab=general&blog='.$Blog->ID.'">Name &raquo;</a></li>';
			echo '<li><a href="admin.php?ctrl=coll_settings&tab=skin&blog='.$Blog->ID.'">Appearance (skin) &raquo;</a></li>';
			echo '<li><a href="admin.php?ctrl=coll_settings&tab=display&blog='.$Blog->ID.'">Post order &raquo;</a></li>';
			echo '<li><a href="admin.php?ctrl=coll_settings&tab=widgets&blog='.$Blog->ID.'">Widgets &raquo;</a></li>';
			echo '</ul></li>';
		}

		if( $current_User->check_perm( 'blog_genstatic', 'any', false, $Blog->ID ) )
		{
			echo '<li><a href="admin.php?ctrl=collections&amp;action=GenStatic&amp;blog='.$Blog->ID.'&amp;redir_after_genstatic='.rawurlencode(regenerate_url( '', '', '', '&' )).'">Generate static page!</a></li>';
		}

 		echo '<li><a href="'.$Blog->get('url').'" target="_blank">Open public blog page</a></li>';

		// TODO: dh> display link to "not-approved" (to be moderated) comments, if any. Therefor the comments list must be filterable.
	echo '</ul>';
	echo '</div>';

	echo '<h3>Latest posts</h3>';

	load_class( 'MODEL/items/_itemlist2.class.php' );

	// Create empty List:
	$ItemList = & new ItemList2( $Blog, NULL, NULL );

	// Filter list:
	$ItemList->set_filters( array(
			'visibility_array' => array( 'published', 'protected', 'private', 'draft', 'deprecated', 'redirected' ),
			'posts' => 5,
		) );

	// Get ready for display (runs the query):
	$ItemList->display_init();

	while( $Item = & $ItemList->get_item() )
	{
		?>
		<div class="dashboard_post" lang="<?php $Item->lang() ?>">
			<?php
			// We don't switch locales in the backoffice, since we use the user pref anyway
			// Load item's creator user:
			$Item->get_creator_User();

			// Display images that are linked to this post:
			$Item->images( array(
					'before' =>              '<div class="dashboard_thumbnails">',
					'before_image' =>        '',
					'before_image_legend' => NULL,	// No legend
					'after_image_legend' =>  NULL,
					'after_image' =>         '',
					'after' =>               '</div>',
					'image_size' =>          'fit-80x80'
				) );

			echo '<h3 class="dashboard_post_title">';
			echo '<a href="?ctrl=items&amp;blog='.$Blog->ID.'&amp;p='.$Item->ID.'">'.$Item->dget( 'title' ).'</a>';

			echo ' <span class="dashboard_post_details">';
			$Item->status();
			echo ' &bull; ';
			$Item->views();
			echo '</span>';
			echo '</h3>';

			echo '<div class="small">'.$Item->get_content_excerpt( 150 ).'</div>';

			echo '<div style="clear:left;">'.get_icon('pixel').'</div>'; // IE crap
			?>
		</div>
		<?php
	}


	// fp> TODO: drafts


	echo '<div class="clear"></div>';

	// End payload block:
	$AdminUI->disp_payload_end();
}
else
{	// We're on the GLOBAL tab...

	$AdminUI->disp_payload_begin();
	// Display blog list VIEW:
	$AdminUI->disp_view( 'collections/_blogs_list.php' );
	$AdminUI->disp_payload_end();

}


if( $current_User->check_perm( 'options', 'edit' ) )
{	// We have some serious admin privilege:
	// Begin payload block:
	$AdminUI->disp_payload_begin();

	echo '<h2>Administrative tasks</h2>';

	echo '<ul>';
		echo '<li><a href="admin.php?ctrl=users&amp;user_ID='.$current_User->ID.'">Edit my user profile &raquo;</a></li>';
		if( $current_User->check_perm( 'users', 'edit' ) )
		{
			echo '<li><a href="admin.php?ctrl=users&amp;action=new_user">Create new user &raquo;</a></li>';
		}
		if( $current_User->check_perm( 'blogs', 'create' ) )
		{
			echo '<li><a href="admin.php?ctrl=collections&amp;action=new">Create new blog &raquo;</a></li>';
		}
		// TODO: remember system date check and only remind every 3 months
		echo '<li><a href="admin.php?ctrl=system">Check if my blog server is secure &raquo;</a></li>';
	echo '</ul>';
	// End payload block:
	$AdminUI->disp_payload_end();
}


// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

/*
 * $Log$
 * Revision 1.19  2007/06/13 20:56:02  fplanque
 * minor
 *
 * Revision 1.18  2007/05/09 01:01:29  fplanque
 * permissions cleanup
 *
 * Revision 1.17  2007/04/26 00:11:15  fplanque
 * (c) 2007
 *
 * Revision 1.16  2007/03/11 23:57:07  fplanque
 * item editing: allow setting to 'redirected' status
 *
 * Revision 1.15  2007/03/05 04:48:15  fplanque
 * IE crap
 *
 * Revision 1.14  2007/03/05 02:13:25  fplanque
 * improved dashboard
 *
 * Revision 1.13  2007/01/28 23:31:57  blueyed
 * todo
 *
 * Revision 1.12  2007/01/19 08:20:57  fplanque
 * bugfix
 *
 * Revision 1.11  2007/01/14 22:43:29  fplanque
 * handled blog view perms.
 *
 * Revision 1.10  2006/12/17 02:42:22  fplanque
 * streamlined access to blog settings
 *
 * Revision 1.9  2006/12/15 22:53:26  fplanque
 * cleanup
 *
 * Revision 1.8  2006/12/12 21:19:31  fplanque
 * UI fixes
 *
 * Revision 1.7  2006/12/12 02:53:56  fplanque
 * Activated new item/comments controllers + new editing navigation
 * Some things are unfinished yet. Other things may need more testing.
 *
 * Revision 1.6  2006/12/11 17:26:21  fplanque
 * some cross-linking
 *
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