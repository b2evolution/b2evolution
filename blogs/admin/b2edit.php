<?php
/**
 * This file implements the UI controller for editing posts.
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

$itemTypeCache = & new DataObjectCache( 'Element', true, 'T_posttypes', 'ptyp_', 'ptyp_ID' );
$itemStatusCache = & new DataObjectCache( 'Element', true, 'T_poststatuses', 'pst_', 'pst_ID' );

$AdminUI->setPath( 'new' );
param( 'action', 'string' );

// All statuses are allowed for display/acting on (including drafts and deprecated posts):
$show_statuses = array( 'published', 'protected', 'private', 'draft', 'deprecated' );


switch($action)
{
	case 'edit':
		/*
		 * --------------------------------------------------------------------
		 * Display post editing form
		 */
		param( 'post', 'integer', true );
		$edited_Item = $ItemCache->get_by_ID( $post );
		$post_locale = $edited_Item->get( 'locale' );
		$cat = $edited_Item->get( 'main_cat_ID' );
		$blog = get_catblog($cat);
		$Blog = Blog_get_by_ID( $blog );

		$admin_pagetitle = T_('Editing post').': '.$edited_Item->dget( 'title', 'htmlhead' );
		$admin_pagetitle_titlearea = sprintf( T_('Editing post #%d in blog: %s'), $edited_Item->ID, $Blog->get('name') );
		require (dirname(__FILE__). '/_menutop.php');

		$post_status = $edited_Item->get( 'status' );
		// Check permission:
		$current_User->check_perm( 'blog_post_statuses', $post_status, true, $blog );

		$post_title = $edited_Item->get( 'title' );
		$post_urltitle = $edited_Item->get( 'urltitle' );
		$post_url = $edited_Item->get( 'url' );
		$content = format_to_edit( $edited_Item->get( 'content' ) );
		$post_pingback = 0;
		$post_trackbacks = '';
		$post_comments = $edited_Item->get( 'comments' );
		$post_extracats = postcats_get_byID( $post );

		// Display edit form:
		$form_action = 'edit_actions.php';
		$next_action = 'update';
		require(dirname(__FILE__).'/_item.form.php');

		break;


	case 'editcomment':
		/*
		 * --------------------------------------------------------------------
		 * Display comment in edit form
		 */
		param( 'comment', 'integer', true );
		$edited_Comment = Comment_get_by_ID( $comment );

		$admin_pagetitle = T_('Editing comment').' #'.$edited_Comment->ID;
		require (dirname(__FILE__).'/_menutop.php');

		$blog = $edited_Comment->Item->blog_ID;
		$Blog = Blog_get_by_ID( $blog );

		// Check permission:
		$current_User->check_perm( 'blog_comments', 'any', true, $blog );

		require(dirname(__FILE__).'/_comment.form.php');

		break;


	default:
		/*
		 * --------------------------------------------------------------------
		 * New post form  (can be a bookmarklet form if mode == bookmarklet )
		 */
		param( 'blog', 'integer', 0 );

		$admin_pagetitle = $admin_pagetitle_titlearea = T_('New post in blog:');


		if( ($blog == 0) && $current_User->check_perm( 'blog_post_statuses', 'any', false, $default_to_blog ) )
		{ // Default blog is a valid choice
			$blog = $default_to_blog;
		}

		// ---------------------------------- START OF BLOG LIST ----------------------------------
		$blogListButtons = '';
		for( $curr_blog_ID = blog_list_start();
					$curr_blog_ID != false;
					$curr_blog_ID = blog_list_next() )
		{
			if( ! $current_User->check_perm( 'blog_post_statuses', 'any', false, $curr_blog_ID ) )
			{ // Current user is not a member of this blog...
				continue;
			}
			if( $blog == 0 )
			{ // If no selected blog yet, select this one:
				$blog = $curr_blog_ID;
			}
			// This is for when Javascript is not available:
			$blogListButtons .= '<a href="b2edit.php?blog='.$curr_blog_ID;
			if( !empty( $mode ) )
			{ // stay in mode
				$blogListButtons .= '&amp;mode='.$mode;
			}
			$blogListButtons .= '" ';
			if( ! blog_has_cats( $curr_blog_ID ) )
			{ // loop blog has no categories, you cannot post to it.
				$blogListButtons .= 'onclick="alert(\''
													.format_to_output( T_('Since this blog has no categories, you cannot post to it. You must create categories first.'), 'formvalue' )
													.'\'); return false;" title="'.format_to_output( T_('Since this blog has no categories, you cannot post to it. You must create categories first.'), 'formvalue' ).'"';
			}
			elseif( blog_has_cats( $blog ) )
			{ // loop blog AND current blog both have catageories, normal situation:
				$blogListButtons .= 'onclick="return edit_reload(this.ownerDocument.forms.namedItem(\'post\'), '
														.$curr_blog_ID.' )" title="'.T_('Switch to this blog (keeping your input if Javascript is active)').'"';
			}

			if( $curr_blog_ID == $blog )
			{
				$blogListButtons .= ' class="CurrentBlog"';
				$admin_pagetitle .= ' '.blog_list_iteminfo('shortname', false);
			}
			else
			{
				$blogListButtons .= ' class="OtherBlog"';
			}
			$blogListButtons .= '>'.blog_list_iteminfo('shortname', false).'</a> ';
		} // --------------------------------- END OF BLOG LIST ---------------------------------


		require (dirname(__FILE__).'/_menutop.php');

		if( $blog == 0 )
		{
			?>
			<div class="panelblock">
			<?php printf( T_('Since you\'re a newcomer, you\'ll have to wait for an admin to authorize you to post. You can also <a %s>e-mail the admin</a> to ask for a promotion. When you\'re promoted, just reload this page and you\'ll be able to blog. :)'), 'href="mailto:'.$admin_email.'?subject=b2-promotion"' ); ?>
			</div>
			<?php
			break;
		}

		$Blog = Blog_get_by_ID( $blog ); /* TMP: */ $blogparams = get_blogparams_by_ID( $blog );

		if( ! blog_has_cats( $blog ) )
		{
			?>
			<div class="panelinfo">
				<?php
				Log::display( '', '', T_('Since this blog has no categories, you cannot post to it. You must create categories first.'), 'error' );
				?>
			</div>
			<?php

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
		$content = format_to_edit( $content, false );
		param( 'post_title', 'html', $popuptitle );
		param( 'post_urltitle', 'string', '' );
		param( 'post_url', 'string', $popupurl );
		$post_url = format_to_edit( $post_url, false );
		param( 'item_issue_date', 'string' );
		param( 'item_issue_time', 'string' );
		param( 'post_comments', 'string', 'open' );		// 'open' or 'closed' or ...
		param( 'post_extracats', 'array', array() );
		param( 'post_locale', 'string', $default_locale );
		param( 'renderers', 'array', array( 'default' ) );

		param( 'edit_date', 'integer', 0 );


		// Display edit form:
		$form_action = 'edit_actions.php';
		$next_action = 'create';
		require(dirname(__FILE__).'/_item.form.php');
}

require( dirname(__FILE__).'/_footer.php' );

?>