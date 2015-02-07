<?php
/**
 * This file implements the UI controller for managing comments.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _comments.ctrl.php 8027 2015-01-19 11:58:16Z yura $
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
	case 'switch_view':
	case 'publish':
	case 'restrict':
	case 'deprecate':
	case 'delete_url':
	case 'update_publish':
	case 'delete':
		if( $action != 'edit' && $action != 'switch_view' )
		{ // Stop a request from the blocked IP addresses or Domains
			antispam_block_request();
		}

		param( 'comment_ID', 'integer', true );
		$edited_Comment = & Comment_get_by_ID( $comment_ID );

		$edited_Comment_Item = & $edited_Comment->get_Item();
		set_working_blog( $edited_Comment_Item->get_blog_ID() );
		$BlogCache = & get_BlogCache();
		$Blog = & $BlogCache->get_by_ID( $blog );

		// Some users can delete & change a status of comments in their own posts, set corresponding permlevel
		if( $action == 'publish' || $action == 'update_publish' )
		{ // Load the new comment status from publish request and set perm check values
			$publish_status = param( 'publish_status', 'string', '' );
			$check_permname = 'comment!'.$publish_status;
			$check_permlevel = ( $action == 'publish' ) ? 'moderate' : 'edit';
		}
		elseif( $action == 'deprecate' )
		{ // set perm check values
			$check_permname = 'comment!deprecated';
			$check_permlevel = 'moderate';
		}
		else
		{ // set default perm check values
			$comment_status = param( 'comment_status', 'string', 'CURSTATUS' );
			$check_permname = 'comment!'.$comment_status;
			$check_permlevel = ( $action == 'delete' ) ? 'delete' : 'edit';
		}
		// Check permission:
		$current_User->check_perm( $check_permname, $check_permlevel, true, $edited_Comment );

		$comment_title = '';
		$comment_content = htmlspecialchars_decode( $edited_Comment->content );

		// Format content for editing, if we were not already in editing...
		$Plugins_admin = & get_Plugins_admin();
		$params = array( 'object_type' => 'Comment', 'object_Blog' => & $Blog );
		$Plugins_admin->unfilter_contents( $comment_title /* by ref */, $comment_content /* by ref */, $edited_Comment->get_renderers_validated(), $params );

		// Where are we going to redirect to?
		param( 'redirect_to', 'url', url_add_param( $admin_url, 'ctrl=items&blog='.$blog.'&p='.$edited_Comment_Item->ID, '&' ) );
		break;

	case 'elevate':
		// Stop a request from the blocked IP addresses or Domains
		antispam_block_request();

		global $blog;
		load_class( 'items/model/_item.class.php', 'Item' );

		param( 'comment_ID', 'integer', true );
		$edited_Comment = & Comment_get_by_ID( $comment_ID );

		$BlogCache = & get_BlogCache();
		$Blog = & $BlogCache->get_by_ID( $blog );

		// Check permission:
		$current_User->check_perm( 'blog_post!draft', 'edit', true, $blog );
		break;

	case 'trash_delete':
		param( 'blog_ID', 'integer', 0 );

		// Check permission:
		$current_User->check_perm( 'blogs', 'editall', true );
		break;

	case 'emptytrash':
		// Check permission:
		$current_User->check_perm( 'blogs', 'all', true );
		break;

	case 'list':
	case 'mass_delete':
		if( $action == 'mass_delete' )
		{ // Check permission:
			$current_User->check_perm( 'blogs', 'all', true );
		}

		// Check permission:
		$selected = autoselect_blog( 'blog_comments', 'edit' );
		if( ! $selected )
		{ // No blog could be selected
			$Messages->add( T_('You have no permission to edit comments.' ), 'error' );
			$action = 'nil';
		}
		elseif( set_working_blog( $selected ) )	// set $blog & memorize in user prefs
		{ // Selected a new blog:
			$BlogCache = & get_BlogCache();
			$Blog = & $BlogCache->get_by_ID( $blog );
		}
		break;

	case 'spam':
		// Used for quick SPAM vote of comments
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );

		param( 'comment_ID', 'integer', true );
		$edited_Comment = & Comment_get_by_ID( $comment_ID );

		$edited_Comment_Item = & $edited_Comment->get_Item();
		set_working_blog( $edited_Comment_Item->get_blog_ID() );
		$BlogCache = & get_BlogCache();
		$Blog = & $BlogCache->get_by_ID( $blog );

		// Check permission for spam voting
		$current_User->check_perm( 'blog_vote_spam_comments', 'edit', true, $Blog->ID );

		if( $edited_Comment !== false )
		{ // The comment still exists
			if( $current_User->ID != $edited_Comment->author_user_ID )
			{ // Do not allow users to vote on their own comments
				$edited_Comment->set_vote( 'spam', param( 'value', 'string' ) );
				$edited_Comment->dbupdate();
			}
		}

		// Where are we going to redirect to?
		param( 'redirect_to', 'url', url_add_param( $admin_url, 'ctrl=comments&blog='.$blog.'&filter=restore', '&' ) );

		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( $redirect_to, 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;

	default:
		debug_die( 'unhandled action 1' );
}

// Set the third level tab
param( 'tab3', 'string', '', true );

$AdminUI->breadcrumbpath_init( true, array( 'text' => T_('Contents'), 'url' => '?ctrl=items&amp;blog=$blog$&amp;tab=full&amp;filter=restore' ) );
$AdminUI->breadcrumbpath_add( T_('Comments'), '?ctrl=comments&amp;blog=$blog$&amp;filter=restore' );
switch( $tab3 )
{
	case 'listview':
		$AdminUI->breadcrumbpath_add( T_('List view'), '?ctrl=comments&amp;blog=$blog$&amp;tab3='.$tab3.'&amp;filter=restore' );
		break;

	case 'fullview':
		$AdminUI->breadcrumbpath_add( T_('Full text view'), '?ctrl=comments&amp;blog=$blog$&amp;tab3='.$tab3.'&amp;filter=restore' );
		break;
}

$AdminUI->set_path( 'items' );	// Sublevel may be attached below

if( in_array( $action, array( 'edit', 'elevate', 'update_publish', 'update', 'switch_view' ) ) )
{ // Page with comment edit form
	// Set an url for manual page
	$AdminUI->set_page_manual_link( 'editing-comments' );
}

/**
 * Perform action:
 */
switch( $action )
{
	case 'nil':
		// Do nothing
		break;


	case 'edit':
		$AdminUI->title_titlearea = T_('Editing comment').' #'.$edited_Comment->ID;

		// Generate available blogs list:
		$AdminUI->set_coll_list_params( 'blog_comments', 'edit',
						array( 'ctrl' => 'comments', 'filter' => 'restore' ), NULL, '' );

		/*
		 * Add sub menu entries:
		 * We do this here instead of _header because we need to include all filter params into regenerate_url()
		 */
		attach_browse_tabs( false );

		$AdminUI->set_path( 'items', 'comments' );

		$AdminUI->breadcrumbpath_add( sprintf( T_('Comment #%s'), $edited_Comment->ID ), '?ctrl=comments&amp;comment_ID='.$edited_Comment->ID.'&amp;action=edit' );
		$AdminUI->breadcrumbpath_add( T_('Edit'), '?ctrl=comments&amp;comment_ID='.$edited_Comment->ID.'&amp;action=edit' );
		break;


	case 'update_publish':
	case 'update':
	case 'switch_view':
		// fp> TODO: $edited_Comment->load_from_Request( true );

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );

		if( ! $edited_Comment->get_author_User() )
		{ // If this is not a member comment
			param( 'newcomment_author', 'string', true );
			param( 'newcomment_author_email', 'string' );
			param( 'newcomment_author_url', 'string' );
			param( 'comment_allow_msgform', 'integer', 0 /* checkbox */ );

			param_check_not_empty( 'newcomment_author', T_('Please enter an author name.'), '' );
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

					if( ($current_User->ID == $dest_Item_Blog_User->ID &&
						$current_User->ID == $comment_Item_Blog_User->ID ) ||
						( $current_User->check_perm( 'blog_admin', 'edit', false, $dest_Item_Blog->ID ) &&
						$current_User->check_perm( 'blog_admin', 'edit', false, $comment_Item_Blog->ID ) ) )
					{ // current user is the owner of both the source and the destination blogs or current user is admin for both blogs
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

		$edited_Comment_Item = $edited_Comment->get_Item();
		$edited_Comment_Item->load_Blog();
		if( $edited_Comment_Item->Blog->get_setting( 'allow_html_comment' ) )
		{	// HTML is allowed for this comment
			$text_format = 'html';
		}
		else
		{	// HTML is disallowed for this comment
			$text_format = 'htmlspecialchars';
		}

		// Content:
		param( 'content', $text_format );
		// Don't allow the hidden text in comment content
		$content = str_replace( '<!', '&lt;!', $content );

		// Renderers:
		if( param( 'renderers_displayed', 'integer', 0 ) )
		{ // use "renderers" value only if it has been displayed (may be empty)
			global $Plugins;
			$renderers = $Plugins->validate_renderer_list( param( 'renderers', 'array/string', array() ), array( 'Comment' => & $edited_Comment ) );
			$edited_Comment->set_renderers( $renderers );
		}

		if( $edited_Comment_Item->Blog->get_setting( 'threaded_comments' ) &&
		    ( param( 'in_reply_to_cmt_ID', 'integer', NULL, false, false, false ) !== false ) )
		{ // Change a field "In reply to comment ID" if threaded comments are enabled for the blog
			$in_reply_to_cmt_ID = get_param( 'in_reply_to_cmt_ID' );
			$CommentCache = & get_CommentCache();
			if( $in_reply_to_cmt_ID > 0 )
			{ // Check new entered comment ID
				if( ! empty( $edited_Comment->ID ) && $in_reply_to_cmt_ID == $edited_Comment->ID )
				{ // Restrict such brake case
					$Messages->add( T_('This comment cannot be a reply to itself.'), 'error' );
				}
				elseif( ! ( $Comment = & $CommentCache->get_by_ID( $in_reply_to_cmt_ID, false, false ) ) )
				{ // No comment exists
					$Messages->add( T_('The ID of the parent comment you entered does not exist.'), 'error' );
				}
				elseif( $Comment->item_ID != $edited_Comment_Item->ID )
				{ // Item of new reply comment is not same
					$Messages->add( T_('The ID of the parent comment must belong to the same post.'), 'error' );
				}
			}
			else
			{ // Deny wrong comment ID
				$in_reply_to_cmt_ID = NULL;
			}
			$edited_Comment->set( 'in_reply_to_cmt_ID', $in_reply_to_cmt_ID, true );
		}

		// Trigger event: a Plugin could add a $category="error" message here..
		// This must get triggered before any internal validation and must pass all relevant params.
		// The OpenID plugin will validate a given OpenID here (via redirect and coming back here).
		$Plugins->trigger_event( 'CommentFormSent', array(
				'dont_remove_pre' => true,
				'comment_item_ID' => $edited_Comment_Item->ID,
				'comment' => & $content,
				'renderers' => $edited_Comment->get_renderers(),
			) );

		param_check_html( 'content', T_('Invalid comment text.') );	// Check this is backoffice content (NOT with comment rules)
		param_check_not_empty( 'content', T_('Empty comment content is not allowed.') );
		$edited_Comment->set( 'content', get_param( 'content' ) );

		if( $current_User->check_perm( 'blog_edit_ts', 'edit', false, $Blog->ID ) )
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
			$comment_status = $publish_status;
		}
		$old_comment_status = $edited_Comment->get( 'status' );
		$edited_Comment->set( 'status', $comment_status );

		param( 'comment_nofollow', 'integer', 0 );
		$edited_Comment->set_from_Request( 'nofollow' );

		if( $Messages->has_errors() )
		{	// There have been some validation errors:
			break;
		}

		if( $old_comment_status != $comment_status )
		{ // Comment moderation is done, handle moderation "secret"
			$edited_Comment->handle_qm_secret();
		}

		// If action is switch_view then don't save the edited Comment yet, only change the edit view
		if( $action != 'switch_view' )
		{ // UPDATE DB:
			$edited_Comment->dbupdate();	// Commit update to the DB

			if( $edited_Comment->status == 'published' )
			{ // comment status was set to published or it was already published, needs to handle notifications
				$edited_Comment->handle_notifications( false, $current_User->ID );
			}

			$Messages->add( T_('Comment has been updated.'), 'success' );

			header_redirect( $redirect_to );
			/* exited */
		}

		break;


	case 'publish':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );

		$edited_Comment->set( 'status', $publish_status );
		// Comment moderation is done, handle moderation "secret"
		$edited_Comment->handle_qm_secret();

		$edited_Comment->dbupdate();	// Commit update to the DB

		// comment status was set to published, needs to handle notifications
		$edited_Comment->handle_notifications( false, $current_User->ID );

		// Set the success message corresponding for the new status
		switch( $edited_Comment->status )
		{
			case 'published':
				$success_message = T_('Comment has been published.');
				break;
			case 'community':
				$success_message = T_('The comment is now visible by the community.');
				break;
			case 'protected':
				$success_message = T_('The comment is now visible by the members.');
				break;
			case 'review':
				$success_message = T_('The comment is now visible by moderators.');
				break;
			default:
				$success_message = T_('Comment has been updated.');
				break;
		}
		$Messages->add( $success_message, 'success' );

		header_redirect( $redirect_to );
		/* exited */
		break;


	case 'restrict':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );

		$edited_Comment->set( 'status', $comment_status );
		// Comment moderation is done, handle moderation "secret"
		$edited_Comment->handle_qm_secret();

		$edited_Comment->dbupdate();	// Commit update to the DB

		$Messages->add( T_('Comment has been restricted.'), 'success' );

		header_redirect( $redirect_to );
		/* exited */
		break;


	case 'deprecate':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );

		$edited_Comment->set('status', 'deprecated' );
		// Comment moderation is done, handle moderation "secret"
		$edited_Comment->handle_qm_secret();

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
		$success_message = ( $edited_Comment->status == 'trash' ) ? T_('Comment has been deleted.') : T_('Comment has been recycled.');

		// Delete from DB:
		$edited_Comment->dbdelete();

		$Messages->add( $success_message, 'success' );

		header_redirect( $redirect_to );
		break;


	case 'trash_delete':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );

		if( isset( $blog_ID ) && ( $blog_ID != 0 ) )
		{ // delete by comment ids
			$query = 'SELECT comment_ID
					FROM T_blogs LEFT OUTER JOIN T_categories ON blog_ID = cat_blog_ID
						LEFT OUTER JOIN T_items__item ON cat_ID = post_main_cat_ID
						LEFT OUTER JOIN T_comments ON post_ID = comment_item_ID
					WHERE comment_status = "trash" AND blog_ID = '.$blog_ID;
			$comment_ids = $DB->query->get_col( $query, 'get trash comment ids' );
			$result = comments_delete_where( NULL, $comment_ids );
		}
		else
		{ // delete by where clause
			$result = comments_delete_where( 'comment_status = "trash"' );
		}

		if( $result !== false )
		{
			$Messages->add( T_('Recycle bin contents were successfully deleted.'), 'success' );
		}
		else
		{
			$Messages->add( T_('Could not empty recycle bin.'), 'error' );
		}

		header_redirect( regenerate_url( 'action', 'action=list', '', '&' ) );
		break;

	case 'emptytrash':
		/*
		 * Trash comments:
		 */
		$AdminUI->title_titlearea = T_('Comment recycle bins');

		/*
		 * Add sub menu entries:
		 * We do this here instead of _header because we need to include all filter params into regenerate_url()
		 */
		attach_browse_tabs( false );

		$AdminUI->set_path( 'items', 'comments' );

		$AdminUI->breadcrumbpath_add( T_('Comment recycle bins'), '?ctrl=comments&amp;action=emptytrash' );
		break;

	case 'elevate':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );

		$item_content = $edited_Comment->get_author_name().' '.T_( 'wrote' ).': <blockquote>'.$edited_Comment->get_content().'</blockquote>';
		$new_Item = new Item();
		$new_Item->set( 'status', 'draft' );
		$new_Item->set_creator_by_login( $current_User->login );
		$new_Item->set( 'main_cat_ID', $Blog->get_default_cat_ID() );
		$new_Item->set( 'title', T_( 'Elevated from comment' ) );
		$new_Item->set( 'content', $item_content );

		if( ! $new_Item->dbinsert() )
		{
			$Messages->add( T_( 'Unable to create the new post!' ), 'error' );
			break;
		}

		// Deprecate the comment after elevating
		$edited_Comment->set( 'status', 'deprecated' );
		$edited_Comment->dbupdate();

		// Move all child comments to new created post
		move_child_comments_to_item( $edited_Comment->ID, $new_Item->ID );

		header_redirect( url_add_param( $admin_url, 'ctrl=items&blog='.$blog.'&action=edit&p='.$new_Item->ID, '&' ) );
		break;

	case 'list':
	case 'mass_delete':
		/*
		 * Latest comments:
		 */
		$AdminUI->title_titlearea = T_('Latest comments');

		// Generate available blogs list:
		$AdminUI->set_coll_list_params( 'blog_comments', 'edit',
						array( 'ctrl' => 'comments', 'filter' => 'restore' ), NULL, '' );

		/*
		 * Add sub menu entries:
		 * We do this here instead of _header because we need to include all filter params into regenerate_url()
		 */
		attach_browse_tabs();

		$AdminUI->append_path_level( 'comments' );

		if( empty( $tab3 ) )
		{
			$tab3 = 'fullview';
		}

		$AdminUI->set_path( 'items', 'comments', $tab3 );

		$comments_list_param_prefix = 'cmnt_';
		if( !empty( $tab3 ) )
		{	// Use different param prefix for each tab
			$comments_list_param_prefix .= $tab3.'_';
		}
		/*
		 * List of comments to display:
		 */
		$CommentList = new CommentList2( $Blog, NULL, 'CommentCache', $comments_list_param_prefix, $tab3 );

		// Filter list:
		$CommentList->set_default_filters( array(
				'statuses' => get_visibility_statuses( 'keys', array( 'redirected', 'trash' ) ),
				//'comments' => $UserSettings->get( 'results_per_page' ),
			) );

		$CommentList->load_from_Request();

		/**
		 * Mass delete comments
		 */
		param( 'mass_type', 'string', '' );
		if( $action == 'mass_delete' && !empty( $mass_type ) )
		{
			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'comment' );

			// Init the comment list query, but don't execute it
			$CommentList->query_init();
			// Set sql query to get deletable comment ids
			$deletable_comments_query = 'SELECT DISTINCT '.$CommentList->Cache->dbIDname.' '
					.$CommentList->CommentQuery->get_from()
					.$CommentList->CommentQuery->get_where();

			// Set an action param to display a correct template
			$process_action = $action;
			unset( $_POST['actionArray'] );
			set_param( 'action', 'list' );

			// Try to obtain some serious time to do some serious processing (15 minutes)
			set_max_execution_time( 10000 );
		}

		break;

	default:
		debug_die( 'unhandled action 2' );
}


/*
 * Page navigation:
 */

$AdminUI->set_path( 'items', 'comments' );

if( $tab3 == 'fullview' )
{ // Load jquery UI to animate background color on change comment status and to transfer a comment to recycle bin
	require_js( '#jqueryUI#' );
}

if( in_array( $action, array( 'edit', 'update_publish', 'update', 'elevate' ) ) )
{ // Initialize date picker for _comment.form.php
	init_datepicker_js();
}

require_css( 'rsc/css/blog_base.css', true ); // Default styles for the blog navigation
require_js( 'communication.js' ); // auto requires jQuery
// Colorbox (a lightweight Lightbox alternative) allows to zoom on images and do slideshows with groups of images:
require_js_helper( 'colorbox' );

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
	case 'elevate':
	case 'update_publish':
	case 'update':	// on error
	case 'switch_view':
		// Begin payload block:
		$AdminUI->disp_payload_begin();

		// Display VIEW:
		$AdminUI->disp_view( 'comments/views/_comment.form.php' );


		// End payload block:
		$AdminUI->disp_payload_end();
		break;

	case 'emptytrash':
		// Begin payload block:
		$AdminUI->disp_payload_begin();

		// Display VIEW:
		$AdminUI->disp_view( 'comments/views/_trash_comments.view.php' );

		// End payload block:
		$AdminUI->disp_payload_end();
		break;

	case 'list':
	default:
		// Begin payload block:
		$AdminUI->disp_payload_begin();

		$table_browse_template = $AdminUI->get_template( 'table_browse' );
		echo $table_browse_template['table_start'];

		echo $table_browse_template['left_col_start'];

		if( ! empty( $process_action ) && $process_action == 'mass_delete' && !empty( $mass_type ) )
		{ // Mass deleting of the comments
			comment_mass_delete_process( $mass_type, $deletable_comments_query );
			$CommentList->reset();
		}

		// Display VIEW:
		if( $tab3 == 'fullview' )
		{
			$AdminUI->disp_view( 'comments/views/_browse_comments.view.php' );
		}
		else
		{
			$AdminUI->disp_view( 'comments/views/_comment_list_table.view.php' );
		}
		echo $table_browse_template['left_col_end'];

		echo $table_browse_template['right_col_start'];
			// Display VIEW:
			$AdminUI->disp_view( 'comments/views/_comments_sidebar.view.php' );
		echo $table_browse_template['right_col_end'];

		echo $table_browse_template['table_end'];

		// End payload block:
		$AdminUI->disp_payload_end();
		break;
}

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>
