<?php

function add_magic_quotes($array) {
	foreach ($array as $k => $v) {
		if (is_array($v)) {
			$array[$k] = add_magic_quotes($v);
		} else {
			$array[$k] = addslashes($v);
		}
	}
	return $array;
} 

if (!get_magic_quotes_gpc()) 
{
	$HTTP_GET_VARS    = add_magic_quotes($HTTP_GET_VARS);
	$HTTP_POST_VARS   = add_magic_quotes($HTTP_POST_VARS);
	$HTTP_COOKIE_VARS = add_magic_quotes($HTTP_COOKIE_VARS);
}

$b2varstoreset = array('action','safe_mode','withcomments','c','posts','poststart','postend','content','edited_post_title','comment_error','profile', 'trackback_url');
for ($i=0; $i<count($b2varstoreset); $i += 1) 
{
	$b2var = $b2varstoreset[$i];
	if (!isset($$b2var)) {
		if (empty($HTTP_POST_VARS["$b2var"])) 
		{
			if (empty($HTTP_GET_VARS["$b2var"])) 
			{
				$$b2var = '';
			} else 
			{
				$$b2var = $HTTP_GET_VARS["$b2var"];
			}
		} 
		else 
		{
			$$b2var = $HTTP_POST_VARS["$b2var"];
		}
	}
}



$default_blog = 2;



switch($action) 
{
case 'new':
	/*
	 * --------------------------------------------------------------------
	 * New post form
	 */
	$standalone = 1;
	$title = _('New post in blog:');
	require_once (dirname(__FILE__)."/b2header.php");
	include (dirname(__FILE__)."/$b2inc/_menutop.php");
	echo '<span class="menutopbloglist">';
	include (dirname(__FILE__)."/_edit_blogselect.php");
	echo '</span>';
	include (dirname(__FILE__)."/$b2inc/_menutop_end.php");

	if ($user_level > 0) 
	{
		$action='post';
		$post_lang = $default_language;
		$post_status = $default_post_status;		// 'published' or 'draft'
		$post_url = '';

		$extracats = array();
		include($b2inc."/_edit_form.php");
	} 
	else
	{
		?>
		<div class="panelblock">
		<?php printf( _('Since you\'re a newcomer, you\'ll have to wait for an admin to raise your level to 1, in order to be authorized to post.	You can also <a %s>e-mail the admin</a> to ask for a promotion. When you\'re promoted, just reload this page and you\'ll be able to blog. :)'), 'href="mailto:'.admin_email.'?subject=b2-promotion' ); ?>
		</div>
		<?php
	
	}
	break;


case "edit":
	/*
	 * --------------------------------------------------------------------
	 * Display post editing form
	 */
	$standalone = 1;
	require_once (dirname(__FILE__)."/b2header.php");

	dbconnect();
	set_param( "post", 'integer', true );
	$postdata = get_postdata($post) or die( _('Oops, no post with this ID.') );
	$post_lang = $postdata["Lang"];
	$cat = $postdata["Category"];
	$blog = get_catblog($cat); 

	$title = _('Editing post');
	include (dirname(__FILE__)."/$b2inc/_menutop.php");
	echo '<span class="menutopbloglist">';
  echo "#".$postdata["ID"]." in blog: ".$blogname;
	echo '</span>';
	include (dirname(__FILE__)."/$b2inc/_menutop_end.php");

	if ($user_level > 0) 
	{
		$authordata = get_userdata($postdata["Author_ID"]);
		if ($user_level < $authordata[13])
			die ("You don't have the right to edit <strong>".$authordata[1]."</strong>'s posts.");

		$content = $postdata["Content"];
		$autobr = $postdata["AutoBR"];
		$post_status = $postdata["Status"];
		$extracats = postcats_get_byID( $post );
		$content = format_to_edit($content);
		$edited_post_title = format_to_edit($postdata["Title"]);
		$post_url = format_to_edit($postdata["Url"]);

		include($b2inc."/_edit_form.php");
	} 
	else
	{
		printf( _('Since you\'re a newcomer, you\'ll have to wait for an admin to raise your level to 1, in order to be authorized to post.	You can also <a %s>e-mail the admin</a> to ask for a promotion. When you\'re promoted, just reload this page and you\'ll be able to blog. :)'), 'href="mailto:'.admin_email.'?subject=b2-promotion' );
	}

	break;


case "editcomment":
	/*
	 * --------------------------------------------------------------------
	 * Display comment in edit form
	 */
	$standalone=1;
	require_once (dirname(__FILE__)."/b2header.php");
	set_param( 'comment', 'integer', true );
	$commentdata = get_commentdata($comment,1) or die( _('Oops, no comment with this ID!') );

	$title = _('Editing comment');
	include (dirname(__FILE__)."/$b2inc/_menutop.php");
	echo '<span class="menutopbloglist">';
	echo "#".$commentdata["comment_ID"];
	echo '</span>';
	include (dirname(__FILE__)."/$b2inc/_menutop_end.php");

	get_currentuserinfo();

	if ($user_level == 0) 
	{
		die(_('Cheatin\' uh ?'));
	}

	$content = $commentdata['comment_content'];

	$content = format_to_edit($content);
	
	include($b2inc.'/_edit_form.php');

	break;

	
	
default:
	/*
	 * --------------------------------------------------------------------
	 * Display posts
	 */
	$standalone = 1;
	$title = _('Browse blog:');
	require_once (dirname(__FILE__)."/b2header.php");
	include (dirname(__FILE__)."/$b2inc/_menutop.php");
	echo '<span class="menutopbloglist">';
	include (dirname(__FILE__)."/_edit_blogselect.php");
	echo '</span>';
	include (dirname(__FILE__)."/$b2inc/_menutop_end.php");

	
	if ($user_level > 0) 
	{
		include $b2inc."/_edit_showposts.php";
	} 
	else
	{ 
	?>
		<div class="panelblock">
		<?php printf( _('Since you\'re a newcomer, you\'ll have to wait for an admin to raise your level to 1, in order to be authorized to post.	You can also <a %s>e-mail the admin</a> to ask for a promotion. When you\'re promoted, just reload this page and you\'ll be able to blog. :)'), 'href="mailto:'.admin_email.'?subject=b2-promotion' ); ?>
		</div>
	<?php
	}

}

include($b2inc."/_footer.php") ?>