<?php
/**
 * This file implements the UI controller for editing posts.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @var AdminUI
 */
global $AdminUI;

// Get tab ("simple" or "expert") from Request or UserSettings:
$UserSettings->param_Request( 'tab', 'string', 'expert', true /* memorize */ );

$AdminUI->set_path( 'new', $tab );

$Request->param( 'action', 'string', 'new', true );

// All statuses are allowed for display/acting on (including drafts and deprecated posts):
$show_statuses = array( 'published', 'protected', 'private', 'draft', 'deprecated' );

/*
 * Load editable objects:
 */
if( param( 'link_ID', 'integer', NULL, false, false, false ) )
{
	if( ($edited_Link = & $LinkCache->get_by_ID( $link_ID, false )) === false )
	{	// We could not find the linke to edit:
		$Messages->head = T_('Cannot edit link!');
		$Messages->add( T_('Requested link does not exist any longer.'), 'error' );
		unset( $edited_Link );
		unset( $link_ID );
	}
}


switch($action)
{
	case 'delete_link':
		// Delete a link:

		// TODO: check perm!

		// Unlink File from Item:
		if( isset( $edited_Link ) )
		{
			// TODO: get Item from Link to check perm

			// TODO: check item EDIT permissions!

			// Delete from DB:
			$msg = sprintf( T_('Link from &laquo;%s&raquo; deleted.'), $edited_Link->Item->dget('title') );
			$edited_Link->dbdelete( true );
			unset($edited_Link);
			$Messages->add( $msg, 'success' );
		}
		// This will eventually boil down to editing, so we need to prepare...


	case 'edit':
		/*
		 * --------------------------------------------------------------------
		 * Display post editing form
		 */
		param( 'post', 'integer', true, true );
		$edited_Item = & $ItemCache->get_by_ID( $post );
		$post_locale = $edited_Item->get( 'locale' );
		$cat = $edited_Item->get( 'main_cat_ID' );
		$blog = get_catblog($cat);
		$Blog = Blog_get_by_ID( $blog );

		$AdminUI->title = T_('Editing post').': '.$edited_Item->dget( 'title', 'htmlhead' );
		$AdminUI->title_titlearea = sprintf( T_('Editing post #%d in blog: %s'), $edited_Item->ID, $Blog->get('name') );

		$post_status = $edited_Item->get( 'status' );
		// Check permission:
		$current_User->check_perm( 'blog_post_statuses', $post_status, true, $blog );
		break;


	case 'editcomment':
		/*
		 * --------------------------------------------------------------------
		 * Display comment in edit form
		 */
		param( 'comment', 'integer', true );
		$edited_Comment = Comment_get_by_ID( $comment );

		$AdminUI->title = T_('Editing comment').' #'.$edited_Comment->ID;

		$edited_Comment_Item = & $edited_Comment->get_Item();
		$blog = $edited_Comment_Item->blog_ID;
		$Blog = Blog_get_by_ID( $blog );

		// Check permission:
		$current_User->check_perm( 'blog_comments', 'any', true, $blog );

		break;


	default:
		/*
		 * --------------------------------------------------------------------
		 * New post form  (can be a bookmarklet form if mode == bookmarklet )
		 */
		param( 'blog', 'integer', 0 );

		$AdminUI->title = $AdminUI->title_titlearea = T_('New post in blog:');

		$blog = autoselect_blog( $blog, 'blog_post_statuses', 'any' );

		// Generate available blogs list:
		$blogListButtons = $AdminUI->get_html_collection_list( 'blog_post_statuses', 'any', $pagenow.'?blog=%d', NULL, '',
												( blog_has_cats( $blog ) ? 'return b2edit_reload(this.ownerDocument.forms.namedItem(\'item_checkchanges\'), \''.$pagenow.'\', %d )'
												: '' /* Current blog has no cats, we can't be posting */ ), 'switch_to_%d_nocheckchanges' );
		// TODO: b2edit_reload params handling is far from complete..
		// dh> what do you mean?

		if( !$blog )
		{
			$Messages->add( sprintf( T_('Since you\'re a newcomer, you\'ll have to wait for an admin to authorize you to post. You can also <a %s>e-mail the admin</a> to ask for a promotion. When you\'re promoted, just reload this page and you\'ll be able to blog. :)'),
											'href="mailto:'.$admin_email.'?subject=b2-promotion"' ), 'error' );
			$action = 'nil';
			break;
		}

		$Blog = Blog_get_by_ID( $blog ); /* TMP: */ $blogparams = get_blogparams_by_ID( $blog );

		if( ! blog_has_cats( $blog ) )
		{
			$Messages->add( T_('Since this blog has no categories, you cannot post to it. You must create categories first.'), 'error' );
			$action = 'nil';
			break;
		}

		// Check permission:
		$current_User->check_perm( 'blog_post_statuses', 'any', true, $blog );

		// Okay now we know we can use at least one status,
		// but we need to make sure the requested/default one is ok...
		param( 'post_status', 'string',  $default_post_status );		// 'published' or 'draft' or ...
		if( ! $current_User->check_perm( 'blog_post_statuses', $post_status, false, $blog ) )
		{ // We need to find another one:
			if( $current_User->check_perm( 'blog_post_statuses', 'published', false, $blog ) )
				$post_status = 'published';
			elseif( $current_User->check_perm( 'blog_post_statuses', 'protected', false, $blog ) )
				$post_status = 'protected';
			elseif( $current_User->check_perm( 'blog_post_statuses', 'private', false, $blog ) )
				$post_status = 'private';
			elseif( $current_User->check_perm( 'blog_post_statuses', 'draft', false, $blog ) )
				$post_status = 'draft';
			else
				$post_status = 'deprecated';
		}

		$edited_Item = & new Item();
		$edited_Item->blog_ID = $blog;

		// These are bookmarklet params:
		param( 'popuptitle', 'string', '' );
		param( 'popupurl', 'string', '' );
		param( 'text', 'html', '' );

		// Params used when switching pages:
		$edited_Item->assign_to( param( 'item_assigned_user_ID', 'integer', 0 ) );
		param( 'post_pingback', 'integer', 0 );
		param( 'trackback_url', 'string' );
		$post_trackbacks = & $trackback_url;
		param( 'content', 'html', $text );
		param( 'post_title', 'html', $popuptitle );
		param( 'post_urltitle', 'string', '' );
		param( 'post_url', 'string', $popupurl );
		param( 'item_issue_date', 'string' );
		param( 'item_issue_time', 'string' );
		param( 'post_comment_status', 'string', 'open' );		// 'open' or 'closed' or ...
		param( 'post_extracats', 'array', array() );
		param( 'post_locale', 'string', $default_locale );
		param( 'renderers', 'array', array( 'default' ) );

		param( 'edit_date', 'integer', 0 );
}


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
		break;

	case 'delete_link':
	case 'edit':
		/*
		 * --------------------------------------------------------------------
		 * Display post editing form
		 */
		$post_title = $edited_Item->get( 'title' );
		$post_urltitle = $edited_Item->get( 'urltitle' );
		$post_url = $edited_Item->get( 'url' );
		$content = $edited_Item->get( 'content' );
		$post_pingback = 0;
		$post_trackbacks = '';
		$post_comment_status = $edited_Item->get( 'comment_status' );
		$post_extracats = postcats_get_byID( $post );

		// Display edit form:
		$form_action = '?ctrl=editactions';
		$next_action = 'update';
		// Display VIEW:
		$AdminUI->disp_view( 'items/_item.form.php' );

		break;

	case 'editcomment':
		/*
		 * --------------------------------------------------------------------
		 * Display comment in edit form
		 */
		// Display VIEW:
		$AdminUI->disp_view( 'comments/_comment.form.php' );

		break;

	default:
		/*
		 * --------------------------------------------------------------------
		 * New post form  (can be a bookmarklet form if mode == bookmarklet )
		 */
		// Display edit form:
		$form_action = '?ctrl=editactions';
		$next_action = 'create';
		// Display VIEW:
		switch( $tab )
		{
			case 'simple':
				$AdminUI->disp_view( 'items/_item_simple.form.php' );
				break;

			case 'expert':
			default:
				$AdminUI->disp_view( 'items/_item.form.php' );
				break;
		}
}

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>