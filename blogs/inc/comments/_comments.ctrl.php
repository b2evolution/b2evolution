<?php
/**
 * This file implements the UI controller for managing comments.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @var AdminUI
 */
global $AdminUI;

/**
 * @var UserSettings
 */
global $UserSettings;

$action = param_action( 'list' );

/*
 * Init the objects we want to work on.
 */
switch( $action )
{
	case 'edit':
	case 'update':
	case 'publish':
	case 'deprecate':
	case 'delete_url':
	case 'update_publish':
	case 'delete':
		param( 'comment_ID', 'integer', true );
		$edited_Comment = & Comment_get_by_ID( $comment_ID );

		$edited_Comment_Item = & $edited_Comment->get_Item();
		set_working_blog( $edited_Comment_Item->get_blog_ID() );
		$BlogCache = & get_BlogCache();
		$Blog = & $BlogCache->get_by_ID( $blog );

		// Check permission:
		$current_User->check_perm( $edited_Comment->blogperm_name(), 'edit', true, $blog );

		// Where are we going to redirect to?
		param( 'redirect_to', 'string', url_add_param( $admin_url, 'ctrl=items&blog='.$blog.'&p='.$edited_Comment_Item->ID, '&' ) );
		break;

	case 'list':
	  // Check permission:
		$selected = autoselect_blog( 'blog_comments', 'edit' );
		if( ! $selected )
		{ // No blog could be selected
			$Messages->add( T_('You have no permission to edit comments.' ), 'error' );
			$action = 'nil';
		}
		elseif( set_working_blog( $selected ) )	// set $blog & memorize in user prefs
		{	// Selected a new blog:
			$BlogCache = & get_BlogCache();
			$Blog = & $BlogCache->get_by_ID( $blog );
		}
		break;

	default:
		debug_die( 'unhandled action 1' );
}


$AdminUI->breadcrumbpath_init();
$AdminUI->breadcrumbpath_add( T_('Contents'), '?ctrl=items&amp;blog=$blog$&amp;tab=full&amp;filter=restore' );
$AdminUI->breadcrumbpath_add( T_('Comments'), '?ctrl=comments&amp;blog=$blog$&amp;filter=restore' );

$AdminUI->set_path( 'items' );	// Sublevel may be attached below

/**
 * Perform action:
 */
switch( $action )
{
 	case 'nil':
		// Do nothing
		break;


	case 'edit':
		$AdminUI->title = $AdminUI->title_titlearea = T_('Editing comment').' #'.$edited_Comment->ID;
		break;


	case 'update_publish':
	case 'update':
		// fp> TODO: $edited_Comment->load_from_Request( true );

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );

		if( ! $edited_Comment->get_author_User() )
		{ // If this is not a member comment
			param( 'newcomment_author', 'string', true );
			param( 'newcomment_author_email', 'string' );
			param( 'newcomment_author_url', 'string' );
			param( 'comment_allow_msgform', 'integer', 0 /* checkbox */ );

			param_check_not_empty( 'newcomment_author', T_('Please enter and author name.'), '' );
			$edited_Comment->set( 'author', $newcomment_author );
			param_check_email( 'newcomment_author_email', false );
			$edited_Comment->set( 'author_email', $newcomment_author_email );
			param_check_url( 'newcomment_author_url', 'posting', '' ); // Give posting permissions here
			$edited_Comment->set( 'author_url', $newcomment_author_url );
			$edited_Comment->set( 'allow_msgform', $comment_allow_msgform );
		}

		// Move to different post
		if( param( 'moveto_post', 'string', false ) )
		{ // Move to post is set
			$comment_Item = & $edited_Comment->get_Item();
			if( $comment_Item->ID != $moveto_post )
			{ // Move to post was changed
				// Check destination post
				$ItemCache = & get_ItemCache();
				if( ( $dest_Item = $ItemCache->get_by_ID( $moveto_post, false, false) ) !== false )
				{ // the item exists

					$dest_Item_Blog = & $dest_Item->get_Blog();
					$dest_Item_Blog_User = & $dest_Item_Blog->get_owner_User();

					$comment_Item_Blog = & $comment_Item->get_Blog();
					$comment_Item_Blog_User = & $comment_Item_Blog->get_owner_User();

					if( $current_User->ID == $dest_Item_Blog_User->ID &&
						$current_User->ID == $comment_Item_Blog_User->ID )
					{ // current user has permission
						$edited_Comment->set_Item( $dest_Item );
					}
					else
					{
						$Messages->add( T_('Destination post blog owner is different!'), 'error' );
					}
				}
				else
				{ // the item doesn't exists
					$Messages->add( sprintf( T_('Post ID &laquo;%d&raquo; does not exist!'), $moveto_post ), 'error' );
				}
			}
		}

		// Content:
		param( 'content', 'html' );
		param( 'post_autobr', 'integer', ($comments_use_autobr == 'always') ? 1 : 0 );

		param_check_html( 'content', T_('Invalid comment text.'), '#', $post_autobr );	// Check this is backoffice content (NOT with comment rules)
		$edited_Comment->set( 'content', get_param( 'content' ) );

		if( $current_User->check_perm( 'edit_timestamp' ))
		{ // We use user date
			param_date( 'comment_issue_date', T_('Please enter a valid comment date.'), true );
			if( strlen(get_param('comment_issue_date')) )
			{ // only set it, if a date was given:
				param_time( 'comment_issue_time' );
				$edited_Comment->set( 'date', form_date( get_param( 'comment_issue_date' ), get_param( 'comment_issue_time' ) ) ); // TODO: cleanup...
			}
		}

		param( 'comment_rating', 'integer', NULL );
		$edited_Comment->set_from_Request( 'rating' );

		$comment_status = param( 'comment_status', 'string', 'published' );
		if( $action == 'update_publish' )
		{
			$comment_status = 'published';
		}
		$edited_Comment->set( 'status', $comment_status );

		param( 'comment_nofollow', 'integer', 0 );
		$edited_Comment->set_from_Request( 'nofollow' );

		if( $Messages->count('error') )
		{	// There have been some validation errors:
			break;
		}

		// UPDATE DB:
		$edited_Comment->dbupdate();	// Commit update to the DB

		$Messages->add( T_('Comment has been updated.'), 'success' );

		header_redirect( $redirect_to );
		/* exited */
		break;


	case 'publish':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );

		$edited_Comment->set('status', 'published' );

		$edited_Comment->dbupdate();	// Commit update to the DB

		$Messages->add( T_('Comment has been published.'), 'success' );

		header_redirect( $redirect_to );
		/* exited */
		break;


	case 'deprecate':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );

		$edited_Comment->set('status', 'deprecated' );

		$edited_Comment->dbupdate();	// Commit update to the DB

		$Messages->add( T_('Comment has been deprecated.'), 'success' );

		header_redirect( $redirect_to );
		/* exited */
		break;


	case 'delete_url':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );
		
		$edited_Comment->set('author_url', NULL );
		
		$edited_Comment->dbupdate();	// Commit update to the DB

		$Messages->add( T_('Comment url has been deleted.'), 'success' );

		header_redirect( $redirect_to );
		/* exited */
		break;


	case 'delete':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );

		// fp> TODO: non JS confirm

		// Delete from DB:
		$edited_Comment->dbdelete();

		$Messages->add( T_('Comment has been deleted.'), 'success' );

		header_redirect( $redirect_to );
		break;


	case 'list':
		/*
		 * Latest comments:
		 */
		$AdminUI->title = $AdminUI->title_titlearea = T_('Latest comments');

		// Generate available blogs list:
		$AdminUI->set_coll_list_params( 'blog_comments', 'edit',
						array( 'ctrl' => 'comments', 'filter' => 'restore' ), NULL, '' );

		/*
		 * Add sub menu entries:
		 * We do this here instead of _header because we need to include all filter params into regenerate_url()
		 */
		attach_browse_tabs();

		$AdminUI->append_path_level( 'comments' );

		// Set the third level tab
		param( 'tab3', 'string', 'fullview', true );
		$AdminUI->set_path( 'items', 'comments', $tab3 );

		/*
		 * List of comments to display:
		 */
		$CommentList = new CommentList2( $Blog );

		// Filter list:
		$CommentList->set_default_filters( array(
				'statuses' => array( 'published', 'draft', 'deprecated' ),
				'comments' => $UserSettings->get( 'results_per_page', $current_User->ID ),
			) );

		$CommentList->load_from_Request();

		break;


	default:
		debug_die( 'unhandled action 2' );
}


/*
 * Page navigation:
 */

$AdminUI->set_path( 'items', 'comments' );

require_css( 'rsc/css/blog_base.css', true );

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

/**
 * Display payload:
 */
switch( $action )
{
	case 'nil':
		// Do nothing
		break;


	case 'edit':
	case 'update_publish':
	case 'update':	// on error
		// Begin payload block:
		$AdminUI->disp_payload_begin();

		// Display VIEW:
		$AdminUI->disp_view( 'comments/views/_comment.form.php' );


		// End payload block:
		$AdminUI->disp_payload_end();
		break;


	case 'list':
	default:		
		// Begin payload block:
		$AdminUI->disp_payload_begin();

		echo '<table class="browse" cellspacing="0" cellpadding="0" border="0"><tr>';
		echo '<td class="browse_left_col">';
		// Display VIEW:
		if( $tab3 == 'fullview' )
		{
			$AdminUI->disp_view( 'comments/views/_browse_comments.view.php' );
		}
		else
		{
			$AdminUI->disp_view( 'comments/views/_comment_list_table.view.php' );
		}
		echo '</td>';
		
		echo '<td class="browse_right_col">';
			// Display VIEW:
			$AdminUI->disp_view( 'comments/views/_comments_sidebar.view.php' );
		echo '</td>';

		echo '</tr></table>';

		// End payload block:
		$AdminUI->disp_payload_end();
		break;
}

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();


/*
 * $Log$
 * Revision 1.31  2010/07/19 09:35:02  efy-asimo
 * Fix messaging permission setup
 * Update comments number per page
 *
 * Revision 1.30  2010/06/24 08:54:05  efy-asimo
 * PHP 4 compatibility
 *
 * Revision 1.29  2010/06/23 09:30:55  efy-asimo
 * Comments display and Antispam ban form modifications
 *
 * Revision 1.28  2010/06/01 11:33:19  efy-asimo
 * Split blog_comments advanced permission (published, deprecated, draft)
 * Use this new permissions (Antispam tool,when edit/delete comments)
 *
 * Revision 1.27  2010/05/10 14:26:17  efy-asimo
 * Paged Comments & filtering & add comments listview
 *
 * Revision 1.26  2010/03/30 11:14:01  efy-asimo
 * move comments from one post to another
 *
 * Revision 1.25  2010/03/15 17:12:09  efy-asimo
 * Add filters to Comment page
 *
 * Revision 1.24  2010/03/11 10:34:33  efy-asimo
 * Rewrite CommentList to CommentList2 task
 *
 * Revision 1.23  2010/02/08 17:52:09  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.22  2010/01/31 17:39:51  efy-asimo
 * delete url from comments in dashboard and comments form
 *
 * Revision 1.21  2010/01/29 23:07:01  efy-asimo
 * Publish Comment button
 *
 * Revision 1.20  2010/01/23 00:30:09  fplanque
 * no message
 *
 * Revision 1.19  2010/01/13 22:09:44  fplanque
 * normalized
 *
 * Revision 1.18  2010/01/13 19:49:45  efy-yury
 * update comments: crumbs
 *
 * Revision 1.17  2009/12/06 22:55:20  fplanque
 * Started breadcrumbs feature in admin.
 * Work in progress. Help welcome ;)
 * Also move file settings to Files tab and made FM always enabled
 *
 * Revision 1.16  2009/09/26 12:00:42  tblue246
 * Minor/coding style
 *
 * Revision 1.15  2009/09/25 07:32:52  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.14  2009/08/26 23:37:00  tblue246
 * Backoffice comment editing: Allow changing of "Allow message form" setting for guest comments
 *
 * Revision 1.13  2009/03/08 23:57:42  fplanque
 * 2009
 *
 * Revision 1.12  2009/02/25 22:17:53  blueyed
 * ItemLight: lazily load blog_ID and main_Chapter.
 * There is more, but I do not want to skim the diff again, after
 * "cvs ci" failed due to broken pipe.
 *
 * Revision 1.11  2009/01/25 18:57:56  blueyed
 * phpdoc: fix multiple package tags error
 *
 * Revision 1.10  2008/02/09 16:19:31  fplanque
 * fixed commenting bugs
 *
 * Revision 1.9  2008/01/21 09:35:27  fplanque
 * (c) 2008
 *
 * Revision 1.8  2008/01/20 18:20:23  fplanque
 * Antispam per group setting
 *
 * Revision 1.7  2008/01/19 15:45:28  fplanque
 * refactoring
 *
 * Revision 1.6  2008/01/18 15:53:42  fplanque
 * Ninja refactoring
 *
 * Revision 1.5  2008/01/05 02:28:17  fplanque
 * enhanced blog selector (bloglist_buttons)
 *
 * Revision 1.4  2007/12/18 23:51:33  fplanque
 * nofollow handling in comment urls
 *
 * Revision 1.3  2007/11/02 01:51:55  fplanque
 * comment ratings
 *
 * Revision 1.2  2007/09/04 19:51:27  fplanque
 * in-context comment editing
 *
 * Revision 1.1  2007/06/25 10:59:39  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.9  2007/05/13 18:49:54  fplanque
 * made autoselect_blog() more robust under PHP4
 *
 * Revision 1.8  2007/05/09 01:01:32  fplanque
 * permissions cleanup
 *
 * Revision 1.7  2007/04/26 00:11:09  fplanque
 * (c) 2007
 *
 * Revision 1.6  2007/03/07 02:37:52  fplanque
 * OMG I decided that pregenerating the menus was getting to much of a PITA!
 * It's a zillion problems with the permissions.
 * This will be simplified a lot. Enough of these crazy stuff.
 *
 * Revision 1.5  2006/12/26 00:55:58  fplanque
 * wording
 *
 * Revision 1.4  2006/12/18 03:20:41  fplanque
 * _header will always try to set $Blog.
 * controllers can use valid_blog_requested() to make sure we have one
 * controllers should call set_working_blog() to change $blog, so that it gets memorized in the user settings
 *
 * Revision 1.3  2006/12/17 23:42:38  fplanque
 * Removed special behavior of blog #1. Any blog can now aggregate any other combination of blogs.
 * Look into Advanced Settings for the aggregating blog.
 * There may be side effects and new bugs created by this. Please report them :]
 *
 * Revision 1.2  2006/12/12 02:47:47  fplanque
 * Completed comment controller
 *
 * Revision 1.1  2006/12/12 02:01:52  fplanque
 * basic comment controller
 *
 */
?>
