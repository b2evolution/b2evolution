<?php 
require_once(dirname(__FILE__)."/../conf/b2evo_config.php");
require_once(dirname(__FILE__)."/$b2inc/_functions_template.php");
require_once(dirname(__FILE__)."/$b2inc/_vars.php");
require_once(dirname(__FILE__)."/$b2inc/_functions.php");

param( 'tb_id', 'integer' );
param( 'url', 'string' );
param( 'title', 'string' );
param( 'excerpt', 'html' );
param( 'blog_name', 'string' );
if(empty($tb_id))
{	// No parameter for ID, get if from URL:
	$path_elements = explode( '/', $_SERVER['REQUEST_URI'], 30 );	
	$tb_id = intval( $path_elements[count($path_elements)-1] );
}

if ((strlen(''.$tb_id)) && (empty($HTTP_GET_VARS['__mode'])) && (strlen(''.$url))) 
{
	@header('Content-Type: text/xml');

	if (!$use_trackback) 
	{
		trackback_response(1, 'Sorry, this weblog does not allow you to trackback its posts.');
	}

	dbconnect();

	$url = addslashes($url);
	$title = strip_tags($title);
	$title = (strlen($title) > 255) ? substr($title, 0, 252).'...' : $title;
	$excerpt = strip_tags($excerpt);
	$excerpt = (strlen($excerpt) > 255) ? substr($excerpt, 0, 252).'...' : $excerpt;
	$blog_name = htmlspecialchars($blog_name);
	$blog_name = (strlen($blog_name) > 255) ? substr($blog_name, 0, 252).'...' : $blog_name;

	$comment = "<strong>$title</strong><br />$excerpt";

	$author = addslashes($blog_name);
	$email = '';
	$original_comment = $comment;
	$comment_post_ID = $tb_id;

	$user_ip = $HTTP_SERVER_VARS['REMOTE_ADDR'];
	$user_domain = gethostbyaddr($user_ip);
	$time_difference = get_settings('time_difference');
	$now = date('Y-m-d H:i:s',(time() + ($time_difference * 3600)));

	// CHECK and FORMAT content	
	if( ! validate_url( $url, $comments_allowed_uri_scheme) )
	{
		errors_add( 'Supplied URL is invalid' );
	}
	$comment = format_to_post($comment,1,1);

	if( $errstring = errors_string( 'Cannot insert trackback, please correct these errors:', '' ) )
	{
		trackback_response(2, $errstring);	// TODO: check that error code 2 is ok
		die();
	}

	$comment_author = $author;
	$comment_author_email = $email;
	$comment_author_url = $url;

	$author = addslashes($author);

	$query = "INSERT INTO $tablecomments( comment_post_ID, comment_type, comment_author, comment_author_email, comment_author_url, comment_author_IP, comment_date, comment_content) VALUES ( $comment_post_ID,'trackback','$author','$email','$url','$user_ip','$now','$comment' )";
	$result = mysql_query($query);
	if (!$result) 
	{
		trackback_response(2, "There is an error with the database, it can't store your comment...<br />Contact the <a href=\"mailto:$admin_email\">webmaster</a>");	// TODO: check that error code 2 is ok
		die ();
	}
	

		if ($comments_notify)
		{
			// echo 'notify';
			$postdata = get_postdata($comment_post_ID);
			$authordata = get_userdata($postdata["Author_ID"]);
			$recipient = $authordata["user_email"];
			$subject = sprintf( T_('New trackback on your post #%d "%s"', $default_locale), $comment_post_ID, $postdata['Title'] );
			// fplanque added:
			$comment_blogparams = get_blogparams_by_ID( $postdata['Blog'] );

			$notify_message  = sprintf( T_('New trackback on your post #%d "%s"', $default_locale), $comment_post_ID, $postdata['Title'] )."\n";
			$notify_message .= $comment_blogparams->blog_siteurl."/".$comment_blogparams->blog_filename."?p=".$comment_post_ID."&tb=1\n\n";
			$notify_message .= T_('Website', $default_locale).": $comment_author (IP: $user_ip , $user_domain)\n";
			$notify_message .= T_('Url', $default_locale).": $comment_author_url\n";
			$notify_message .= T_('Excerpt', $default_locale).": \n".stripslashes($original_comment)."\n";

			@mail($recipient, $subject, $notify_message, "From: $notify_from\nX-Mailer: b2evolution $b2_version - PHP/".phpversion() );
			
		}

		trackback_response(0,'ok');


}


?>