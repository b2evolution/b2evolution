<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 */
require_once (dirname(__FILE__).'/_header.php');

param( 'action', 'string' );

switch($action) 
{
case 'new':
	/*
	 * --------------------------------------------------------------------
	 * New post form  (can be a bookmarklet form if mode == bookmarklet )
	 */
	$title = T_('New post in blog:');
	require (dirname(__FILE__).'/_menutop.php');
	echo '<span class="menutopbloglist">';
	require (dirname(__FILE__).'/_edit_blogselect.php');
	echo '</span>';
	require (dirname(__FILE__).'/_menutop_end.php');

	if ($user_level > 0) 
	{
		if( ! blog_has_cats( $blog ) )
		{
			die( 'Since this blog has no categories, you cannot post to it. You must create categories first.' );
		}

		$action='post';
		param( 'popuptitle', 'string', '' );
		$edited_post_title = format_to_edit( $popuptitle );
		param( 'popupurl', 'string', '' );
		$post_url = format_to_edit( $popupurl );
		param( 'text', 'html', '' );
		$content = format_to_edit( $text );
		$post_lang = $default_language;
		$post_status = $default_post_status;		// 'published' or 'draft' or ...

		$extracats = array();
		require(dirname(__FILE__).'/_edit_form.php');
	} 
	else
	{
		?>
		<div class="panelblock">
		<?php printf( T_('Since you\'re a newcomer, you\'ll have to wait for an admin to raise your level to 1, in order to be authorized to post.	You can also <a %s>e-mail the admin</a> to ask for a promotion. When you\'re promoted, just reload this page and you\'ll be able to blog. :)'), 'href="mailto:'.admin_email.'?subject=b2-promotion' ); ?>
		</div>
		<?php
	
	}
	break;


case "edit":
	/*
	 * --------------------------------------------------------------------
	 * Display post editing form
	 */
	param( "post", 'integer', true );
	$postdata = get_postdata($post) or die( T_('Oops, no post with this ID.') );
	$post_lang = $postdata["Lang"];
	$cat = $postdata["Category"];
	$blog = get_catblog($cat); 

	$title = T_('Editing post');
	require (dirname(__FILE__).'/_menutop.php');
	echo '<span class="menutopbloglist">';
  echo "#".$postdata["ID"]." in blog: ".$blogname;
	echo '</span>';
	require (dirname(__FILE__).'/_menutop_end.php');

	if ($user_level > 0) 
	{
		$authordata = get_userdata($postdata["Author_ID"]);
		if ($user_level < $authordata[13])
			die("You don't have the right to edit <strong>".$authordata[1]."</strong>'s posts.");

		$edited_post_title = format_to_edit($postdata["Title"]);
		$post_url = format_to_edit( $postdata["Url"] );
		$content = format_to_edit( $postdata["Content"] );
		$autobr = $postdata["AutoBR"];
		$post_status = $postdata["Status"];
		$extracats = postcats_get_byID( $post );

		require(dirname(__FILE__).'/_edit_form.php');
	} 
	else
	{
		printf( T_('Since you\'re a newcomer, you\'ll have to wait for an admin to raise your level to 1, in order to be authorized to post.	You can also <a %s>e-mail the admin</a> to ask for a promotion. When you\'re promoted, just reload this page and you\'ll be able to blog. :)'), 'href="mailto:'.admin_email.'?subject=b2-promotion' );
	}

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
	echo '<span class="menutopbloglist">';
	echo "#".$commentdata["comment_ID"];
	echo '</span>';
	require (dirname(__FILE__).'/_menutop_end.php');

	if ($user_level == 0) 
	{
		die(T_('Cheatin\' uh ?'));
	}

	$content = $commentdata['comment_content'];

	$content = format_to_edit($content);
	
	require(dirname(__FILE__).'/_edit_form.php');

	break;

	
	
default:
	/*
	 * --------------------------------------------------------------------
	 * Display posts
	 */
	$title = T_('Browse blog:');
	require (dirname(__FILE__).'/_menutop.php');
	echo '<span class="menutopbloglist">';
	require (dirname(__FILE__).'/_edit_blogselect.php');
	echo '</span>';
	require (dirname(__FILE__).'/_menutop_end.php');

	
	if ($user_level > 0) 
	{
		require dirname(__FILE__).'/_edit_showposts.php';
	} 
	else
	{ 
	?>
		<div class="panelblock">
		<?php printf( T_('Since you\'re a newcomer, you\'ll have to wait for an admin to raise your level to 1, in order to be authorized to post.	You can also <a %s>e-mail the admin</a> to ask for a promotion. When you\'re promoted, just reload this page and you\'ll be able to blog. :)'), 'href="mailto:'.admin_email.'?subject=b2-promotion' ); ?>
		</div>
	<?php
	}

}

require( dirname(__FILE__).'/_footer.php' ); 

?>