<?php
/**
 * Editing posts
 *
 * b2evolution - {@link http://b2evolution.net/}
 *
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */
require_once (dirname(__FILE__).'/_header.php');

param( 'action', 'string' );

switch($action) 
{
	case "edit":
		/*
		 * --------------------------------------------------------------------
		 * Display post editing form
		 */
		param( 'post', 'integer', true );
		$postdata = get_postdata($post) or die( T_('Oops, no post with this ID.') );
		$post_lang = $postdata['Lang'];
		$cat = $postdata['Category'];
		$blog = get_catblog($cat); 
	
		$title = T_('Editing post');
		require (dirname(__FILE__).'/_menutop.php');
	
		printf( T_('#%d in blog: %s'), $postdata['ID'], get_bloginfo( 'name' ) );
		
		require (dirname(__FILE__).'/_menutop_end.php');
	
		$post_status = $postdata['Status'];
		// Check permission:
		$current_User->check_perm( 'blog_post_statuses', $post_status, true, $blog );
		// Check level:
		$authordata = get_userdata($postdata['Author_ID']);
		if ($user_level < $authordata['user_level'])
			die("You don't have the right to edit <strong>".$authordata['user_login']."</strong>'s posts.");
	
		$edited_post_title = format_to_edit($postdata['Title']);
		$post_url = format_to_edit( $postdata['Url'] );
		$autobr = $postdata['AutoBR'];
		$content = format_to_edit( $postdata['Content'], $autobr );
		$post_pingback = 0;
		$post_trackbacks = '';
		$post_comments = $postdata['comments'];
		$post_extracats = postcats_get_byID( $post );
		$edit_date = 0;
		$aa = mysql2date('Y', $postdata['Date']);
		$mm = mysql2date('m', $postdata['Date']);
		$jj = mysql2date('d', $postdata['Date']);
		$hh = mysql2date('H', $postdata['Date']);
		$mn = mysql2date('i', $postdata['Date']);
		$ss = mysql2date('s', $postdata['Date']);
	
		$form_action = 'editpost';
		require(dirname(__FILE__).'/_edit_form.php');
	
		break;
	
	
	case "editcomment":
		/*
		 * --------------------------------------------------------------------
		 * Display comment in edit form
		 */
		param( 'comment', 'integer', true );
		$commentdata = get_commentdata($comment,1) or die( T_('Oops, no comment with this ID!') );
	
		$title = T_('Editing comment');
		require (dirname(__FILE__).'/_menutop.php');
		echo "#".$commentdata["comment_ID"];
		require (dirname(__FILE__).'/_menutop_end.php');
	
		// Check permission:
		$current_User->check_perm( 'blog_post_statuses', 'any', true, $blog );
	
		$content = $commentdata['comment_content'];
		$content = format_to_edit($content, ($comments_use_autobr == 'always' || $comments_use_autobr == 'opt-out') );
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
		$title = T_('New post in blog:');
		require (dirname(__FILE__).'/_menutop.php');
	
		if( ($blog == 0) && $current_User->check_perm( 'blog_post_statuses', 'any', false, $default_to_blog ) )
		{	// Default blog is a valid choice
			$blog = $default_to_blog;
		}
	
		// ---------------------------------- START OF BLOG LIST ----------------------------------
		$sep = '';
		for( $curr_blog_ID=blog_list_start(); 
					$curr_blog_ID!=false; 
					 $curr_blog_ID=blog_list_next() ) 
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
			echo '[<a href="b2edit.php?blog=', $curr_blog_ID, '" ';
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
			<?php printf( T_('Since you\'re a newcomer, you\'ll have to wait for an admin to authorize you to post. You can also <a %s>e-mail the admin</a> to ask for a promotion. When you\'re promoted, just reload this page and you\'ll be able to blog. :)'), 'href="mailto:'.admin_email.'?subject=b2-promotion"' ); ?>
			</div>
			<?php
			break;
		}
		
		get_blogparams();
	
		// Check permission:
		$current_User->check_perm( 'blog_post_statuses', 'any', true, $blog );
	
		if ($user_level > 0) 
		{
			if( ! blog_has_cats( $blog ) )
			{
				die( T_('Since this blog has no categories, you cannot post to it. You must create categories first.') );
			}
	
			$action='post';
			
			// These are bookmarklet params:
			param( 'popuptitle', 'string', '' );
			param( 'popupurl', 'string', '' );
			param( 'text', 'html', '' );
	
			param( 'editing', 'integer', 0 );
			param( 'post_autobr', 'integer', ($editing ? 0 : $autobr ) );	// Use real default only if we weren't already editing
			$autobr = $post_autobr;
			param( 'post_pingback', 'integer', 0 );
			param( 'trackback_url', 'string' );
			$post_trackbacks = & $trackback_url;
			param( 'content', 'html', $text );
			$content = format_to_edit( $content, false );
			param( 'post_title', 'html', $popuptitle );
			$edited_post_title = format_to_edit( $post_title, false );
			param( 'post_url', 'string', $popupurl );
			$post_url = format_to_edit( $post_url, false );
			param( 'post_status', 'string',  $default_post_status );		// 'published' or 'draft' or ...
			param( 'post_comments', 'string',  'open' );		// 'open' or 'closed' or ...
			param( 'post_extracats', 'array', array() );
			param( 'post_lang', 'string', $default_language );
	
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
		else
		{
			?>
			<div class="panelblock">
			<?php printf( T_('Since you\'re a newcomer, you\'ll have to wait for an admin to raise your level to 1, in order to be authorized to post.	You can also <a %s>e-mail the admin</a> to ask for a promotion. When you\'re promoted, just reload this page and you\'ll be able to blog. :)'), 'href="mailto:'.admin_email.'?subject=b2-promotion"' ); ?>
			</div>
			<?php
		
		}
}

require( dirname(__FILE__).'/_footer.php' ); 

?>