<?php
/**
 * Editing posts
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */
require_once (dirname(__FILE__). '/_header.php');
$admin_tab = 'edit';	// Exception to this below (new post)
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
		$postdata = get_postdata( $post ) or die( T_('Oops, no post with this ID.') );
		$edited_Item = Item_get_by_ID( $post );
		$post_locale = $edited_Item->get( 'locale' );
		$cat = $edited_Item->get( 'main_cat_ID' );
		$blog = get_catblog($cat);
		$Blog = Blog_get_by_ID( $blog );

		$admin_pagetitle = T_('Editing post');
		require (dirname(__FILE__). '/_menutop.php');

		printf( T_('#%d in blog: %s'), $edited_Item->ID, get_bloginfo( 'name' ) );

		require (dirname(__FILE__). '/_menutop_end.php');

		$post_status = $edited_Item->get( 'scope' );
		// Check permission:
		$current_User->check_perm( 'blog_post_statuses', $post_status, true, $blog );

		$post_title = $edited_Item->get( 'title' );
		$post_urltitle = $edited_Item->get( 'urltitle' );
		$post_url = $edited_Item->get( 'url' );
		$content = format_to_edit( $edited_Item->get( 'content' ), $edited_Item->get( 'autobr' ) );
		$post_autobr = $edited_Item->get( 'autobr' );
		$post_pingback = 0;
		$post_trackbacks = '';
		$post_comments = $edited_Item->get( 'comments' );
		$post_extracats = postcats_get_byID( $post );
		$edit_date = 0;
		$post_issue_date = $edited_Item->get( 'issue_date' );
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

		$admin_pagetitle = T_('Editing comment');
		require (dirname(__FILE__).'/_menutop.php');
		echo "#".$commentdata['comment_ID'];
		require (dirname(__FILE__).'/_menutop_end.php');

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
		/*
		 * --------------------------------------------------------------------
		 * New post form  (can be a bookmarklet form if mode == bookmarklet )
		 */
		$admin_tab = 'new';
		$admin_pagetitle = T_('New post in blog:');
		require (dirname(__FILE__).'/_menutop.php');

		if( ($blog == 0) && $current_User->check_perm( 'blog_post_statuses', 'any', false, $default_to_blog ) )
		{	// Default blog is a valid choice
			$blog = $default_to_blog;
		}

		// ---------------------------------- START OF BLOG LIST ----------------------------------
		$sep = '';
		for( $curr_blog_ID = blog_list_start();
					$curr_blog_ID != false;
					$curr_blog_ID = blog_list_next() )
		{
			if( ! $current_User->check_perm( 'blog_post_statuses', 'any', false, $curr_blog_ID ) )
			{	// Current user is not a member of this blog...
				continue;
			}
			if( $blog == 0 )
			{	// If no selected blog yet, select this one:
				$blog = $curr_blog_ID;
			}
			echo $sep;
			if( $curr_blog_ID == $blog ) echo '<strong>';
			// This is for when Javascript is not available:
			echo '[<a href="b2edit.php?blog=', $curr_blog_ID;
			if( !empty( $mode ) ) echo '&amp;mode=', $mode;
			echo '" ';
			if( ! blog_has_cats( $curr_blog_ID ) )
			{	// loop blog has no categories, you cannot post to it.
				echo 'onClick="alert(\'', T_('Since this blog has no categories, you cannot post to it. You must create categories first.'), '\'); return false;" title="', T_('Since this blog has no categories, you cannot post to it. You must create categories first.'), '"';
			}
			elseif( blog_has_cats( $blog ) )
			{	// loop blog AND current blog both have catageories, normal situation:
				echo 'onClick="return edit_reload(this.ownerDocument.forms.namedItem(\'post\'), ', $curr_blog_ID,' )" title="', T_('Switch to this blog (keeping your input if Javascript is active)'), '"';
			}
			echo '>';
			blog_list_iteminfo('shortname');
			echo '</a>]';
			if( $curr_blog_ID == $blog ) echo '</strong>';
			$sep = ' | ';
		} // --------------------------------- END OF BLOG LIST ---------------------------------

		require (dirname(__FILE__).'/_menutop_end.php');

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
			die( T_('Since this blog has no categories, you cannot post to it. You must create categories first.') );
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

		param( 'post_autobr', 'integer', get_settings('AutoBR') );  // Use default if nothing provided
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