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
$admin_tab = 'new';
param( 'action', 'string' );

// All statuses are allowed for display/acting on (including drafts and deprecated posts):
$show_statuses = array( 'published', 'protected', 'private', 'draft', 'deprecated' );

// Page conf settings:
$use_filemanager = true;

switch($action)
{
	case 'edit':
		/*
		 * --------------------------------------------------------------------
		 * Display post editing form
		 */
		param( 'post', 'integer', true );
		$postdata = get_postdata( $post ) or die( T_('Oops, no post with this ID.') );
		$edited_Item = Item_get_by_ID( $post );
		$post_locale = $edited_Item->get( 'locale' );
		$cat = $edited_Item->get( 'main_cat_ID' );
		$blog = get_catblog($cat);
		$Blog = Blog_get_by_ID( $blog );

		$admin_pagetitle = T_('Editing post').': '.$edited_Item->dget( 'title', 'htmlhead' );
		$admin_pagetitle_titlearea = sprintf( T_('Editing post #%d in blog: %s'), $edited_Item->ID, get_bloginfo( 'name' ) );
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
		$edit_date = 0;
		$post_issue_date = $edited_Item->get( 'issue_date' );
		$renderers = $edited_Item->get( 'renderers' );
		$aa = mysql2date('Y', $post_issue_date  );
		$mm = mysql2date('m', $post_issue_date  );
		$jj = mysql2date('d', $post_issue_date  );
		$hh = mysql2date('H', $post_issue_date  );
		$mn = mysql2date('i', $post_issue_date  );
		$ss = mysql2date('s', $post_issue_date  );

		$form_action = 'editpost';
		require(dirname(__FILE__).'/_edit_form.php');

		break;


	case 'editcomment':
		/*
		 * --------------------------------------------------------------------
		 * Display comment in edit form
		 */
		param( 'comment', 'integer', true );
		$commentdata = get_commentdata($comment,1) or die( T_('Oops, no comment with this ID!') );
		$edited_Comment = Comment_get_by_ID( $comment );

		$admin_pagetitle = T_('Editing comment').' #'.$commentdata['comment_ID'];
		require (dirname(__FILE__).'/_menutop.php');

		$comment_post_ID = $commentdata['comment_post_ID'];
		$comment_postdata = get_postdata( $comment_post_ID );
		$blog = get_catblog( $comment_postdata['Category'] );
		$Blog = Blog_get_by_ID( $blog );

		// Check permission:
		$current_User->check_perm( 'blog_comments', 'any', true, $blog );

		$content = $commentdata['comment_content'];
		$content = format_to_edit($content, ($comments_use_autobr == 'always' || $comments_use_autobr == 'opt-out') );
		$post_autobr = ($comments_use_autobr == 'always' || $comments_use_autobr == 'opt-out');
		$edit_date = 0;
		$aa = mysql2date('Y', $commentdata['comment_date']);
		$mm = mysql2date('m', $commentdata['comment_date']);
		$jj = mysql2date('d', $commentdata['comment_date']);
		$hh = mysql2date('H', $commentdata['comment_date']);
		$mn = mysql2date('i', $commentdata['comment_date']);
		$ss = mysql2date('s', $commentdata['comment_date']);

		$form_action = 'editedcomment';
		require(dirname(__FILE__).'/_edit_form.php');

		break;


	default:
		param( 'blog', 'integer', 0 );
		/*
		 * --------------------------------------------------------------------
		 * New post form  (can be a bookmarklet form if mode == bookmarklet )
		 */
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
			<div class="panelinfo"><p>
			<?php echo T_('Since this blog has no categories, you cannot post to it. You must create categories first.') ?>
			</p></div>
			<?php
			break;
		}

		// Check permission:
		$current_User->check_perm( 'blog_post_statuses', 'any', true, $blog );

		// Okay now we know we can use at least one status,
		// but we need to make sure the requested/default one is ok...
		param( 'post_status', 'string',  $default_post_status );		// 'published' or 'draft' or ...
		if( ! $current_User->check_perm( 'blog_post_statuses', $post_status, false, $blog ) )
		{	// We need to find another one:
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

		$action = 'post';

		// These are bookmarklet params:
		param( 'popuptitle', 'string', '' );
		param( 'popupurl', 'string', '' );
		param( 'text', 'html', '' );

		param( 'post_autobr', 'integer', 0 );
		param( 'post_pingback', 'integer', 0 );
		param( 'trackback_url', 'string' );
		$post_trackbacks = & $trackback_url;
		param( 'content', 'html', $text );
		$content = format_to_edit( $content, false );
		param( 'post_title', 'html', $popuptitle );
		param( 'post_urltitle', 'string', '' );
		param( 'post_url', 'string', $popupurl );
		$post_url = format_to_edit( $post_url, false );
		param( 'post_comments', 'string', 'open' );		// 'open' or 'closed' or ...
		param( 'post_extracats', 'array', array() );
		param( 'post_locale', 'string', $default_locale );
		param( 'renderers', 'array', array( 'default' ) );

		param( 'edit_date', 'integer', 0 );
		param( 'aa', 'string', date( 'Y', $localtimenow) );
		param( 'mm', 'string', date( 'm', $localtimenow) );
		param( 'jj', 'string', date( 'd', $localtimenow) );
		param( 'hh', 'string', date( 'H', $localtimenow) );
		param( 'mn', 'string', date( 'i', $localtimenow) );
		param( 'ss', 'string', date( 's', $localtimenow) );

		$form_action = 'post';
		require(dirname(__FILE__).'/_edit_form.php');
}

require( dirname(__FILE__).'/_footer.php' );

?>