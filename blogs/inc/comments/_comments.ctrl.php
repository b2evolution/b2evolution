<?php
/**
 * This file implements the UI controller for managing comments.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @package admin
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

param( 'action', 'string', 'list' );

/*
 * Init the objects we want to work on.
 */
switch( $action )
{
	case 'edit':
	case 'update':
	case 'publish':
	case 'deprecate':
	case 'delete':
		param( 'comment_ID', 'integer', true );
		$edited_Comment = Comment_get_by_ID( $comment_ID );

		$edited_Comment_Item = & $edited_Comment->get_Item();
		set_working_blog( $edited_Comment_Item->blog_ID );
		$BlogCache = & get_Cache( 'BlogCache' );
		$Blog = & $BlogCache->get_by_ID( $blog );

		// Check permission:
		$current_User->check_perm( 'blog_comments', 'edit', true, $blog );

		// Where are we going to redirect to?
		param( 'redirect_to', 'string', url_add_param( $admin_url, 'ctrl=items&blog='.$blog.'&p='.$edited_Comment->item_ID, '&' ) );
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
			$BlogCache = & get_Cache( 'BlogCache' );
			$Blog = & $BlogCache->get_by_ID( $blog );
		}
		break;

	default:
		debug_die( 'unhandled action 1' );
}


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


	case 'update':
		// fp> TODO: $edited_Comment->load_from_Request( true );

		if( ! $edited_Comment->get_author_User() )
		{ // If this is not a member comment
			param( 'newcomment_author', 'string', true );
			param( 'newcomment_author_email', 'string' );
			param( 'newcomment_author_url', 'string' );
			param_check_not_empty( 'newcomment_author', T_('Please enter and author name.'), '' );
			$edited_Comment->set( 'author', $newcomment_author );
			param_check_email( 'newcomment_author_email', false );
			$edited_Comment->set( 'author_email', $newcomment_author_email );
			param_check_url( 'newcomment_author_url', 'posting', '' ); // Give posting permissions here
			$edited_Comment->set( 'author_url', $newcomment_author_url );
		}

		// Content:
		param( 'content', 'html' );
		param( 'post_autobr', 'integer', ($comments_use_autobr == 'always') ? 1 : 0 );
		param_check_html( 'content', T_('Invalid comment text.') );	// Check this is backoffice content (NOT with comment rules)
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

		param( 'comment_status', 'string', 'published' );
		$edited_Comment->set_from_Request( 'status' );

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
		$edited_Comment->set('status', 'published' );

		$edited_Comment->dbupdate();	// Commit update to the DB

		$Messages->add( T_('Comment has been published.'), 'success' );

		header_redirect( $redirect_to );
		/* exited */
		break;


	case 'deprecate':
		$edited_Comment->set('status', 'deprecated' );

		$edited_Comment->dbupdate();	// Commit update to the DB

		$Messages->add( T_('Comment has been deprecated.'), 'success' );

		header_redirect( $redirect_to );
		/* exited */
		break;


	case 'delete':
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

		param( 'show_statuses', 'array', array(), true );	// Array of cats to restrict to

		// Generate available blogs list:
		$AdminUI->set_coll_list_params( 'blog_comments', 'edit',
						array( 'ctrl' => 'comments' ), NULL, '' );

		/*
		 * Add sub menu entries:
		 * We do this here instead of _header because we need to include all filter params into regenerate_url()
		 */
		attach_browse_tabs();

		$AdminUI->append_path_level( 'comments' );

		/*
		 * List of comments to display:
		 */
		$CommentList = & new CommentList( $Blog, "'comment','trackback','pingback'", $show_statuses, '',	'',	'DESC',	'',	20 );
		break;


	default:
		debug_die( 'unhandled action 2' );
}


/*
 * Page navigation:
 */

$AdminUI->set_path( 'items', 'comments' );

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

		// Display VIEW:
		$AdminUI->disp_view( 'comments/views/_browse_comments.view.php' );

		// End payload block:
		$AdminUI->disp_payload_end();
		break;
}

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();


/*
 * $Log$
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