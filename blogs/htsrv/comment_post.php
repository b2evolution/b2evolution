<?php

require_once(dirname(__FILE__)."/../conf/b2evo_config.php");
require_once(dirname(__FILE__)."/$b2inc/_functions_template.php");
require_once(dirname(__FILE__)."/$b2inc/_vars.php");
require_once(dirname(__FILE__)."/$b2inc/_functions.php");

dbconnect();

function add_magic_quotes($array) 
{
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

set_param( 'comment_post_ID', 'integer', true ); // required
set_param( 'author', 'string' );
set_param( 'email', 'string' );
set_param( 'url', 'string' );
set_param( 'comment' , 'html', true );	// mandatory
$original_comment = $comment;
set_param( 'comment_autobr', 'integer', ($comments_use_autobr == 'always')?1:0 );
set_param( 'comment_cookies', 'integer', 0 );

if ($require_name_email && (empty($author)) )
{ 
	errors_add( T_('Please fill in the name field') );
}
if ($require_name_email && (empty($email)) )
{ 
	errors_add( T_('Please fill in the email field') );
}
if( (!empty($email)) && (!is_email($email)) )
{
	errors_add( T_('Supplied email address is invalid') );
}
$url = ((!stristr($url, '://')) && ($url != '')) ? 'http://'.$url : $url;
if (strlen($url) < 7) {
	$url = '';
}
if( ! validate_url( $url, $comments_allowed_uri_scheme) )
{
	errors_add( T_('Supplied URL is invalid') );
}

$user_ip = $REMOTE_ADDR;
$user_domain = gethostbyaddr($user_ip);
$time_difference = get_settings("time_difference");
$now = date("Y-m-d H:i:s",(time() + ($time_difference * 3600)));


// CHECK and FORMAT content
//echo 'allowed tags:',htmlspecialchars($comment_allowed_tags);	
$comment = strip_tags($comment, $comment_allowed_tags);
$comment = format_to_post($comment, $comment_autobr, 1);

$comment_author = $author;
$comment_author_email = $email;
$comment_author_url = $url;

$author = addslashes($author);
$email = addslashes($email);
$url = addslashes($url);

/* flood-protection */
$query = "SELECT * FROM $tablecomments WHERE comment_author_IP='$user_ip' ORDER BY comment_date DESC LIMIT 1";
$result = mysql_query($query);
$ok=1;
if (!empty($result)) 
{
	while($row = mysql_fetch_object($result)) 
	{
		$then=$row->comment_date;
	}
	$time_lastcomment=mysql2date("U","$then");
	$time_newcomment=mysql2date("U","$now");
	if (($time_newcomment - $time_lastcomment) < 30)
		$ok=0;
}
if( ! $ok ) 
{
	errors_add( T_('You can only post a new comment every 30 seconds.') );
}

if( errors_display( T_('Cannot post comment, please correct these errors:'), 
	'[<a href="javascript:history.go(-1)">'.T_('Back to comment editing').'</a>]' ) )
{
	exit();
}

/* end flood-protection */

$query = "INSERT INTO $tablecomments( comment_post_ID, comment_type, comment_author, comment_author_email, comment_author_url, comment_author_IP, comment_date, comment_content)  VALUES( $comment_post_ID, 'comment', '$author','$email','$url','$user_ip','$now','$comment' )";
$result = mysql_query($query) or mysql_oops( $query );

if ($comments_notify) 
{	
	$postdata = get_postdata($comment_post_ID);
	$authordata = get_userdata($postdata["Author_ID"]);
	$recipient = $authordata["user_email"];
	$subject = sprintf( NT_('New comment on your post #%d "%s"'), $comment_post_ID, $postdata['Title'] );
	// fplanque added:
	$comment_blogparams = get_blogparams_by_ID( $postdata['Blog'] );

	// Not translated because sent to someone else...
	$notify_message  = sprintf( NT_('New comment on your post #%d "%s"'), $comment_post_ID, $postdata['Title'] )."\n";
	$notify_message .= $comment_blogparams->blog_siteurl."/".$comment_blogparams->blog_filename."?p=".$comment_post_ID."&c=1\n\n";
	$notify_message .= NT_('Author')." : $comment_author (IP: $user_ip , $user_domain)\n";
	$notify_message .= NT_('Email')."  : $comment_author_email\n";
	$notify_message .= NT_('Url')."    : $comment_author_url\n";
	$notify_message .= NT_('Comment').": \n".stripslashes($original_comment)."\n";

	// echo "Sending notification to $recipient :<pre>$notify_message</pre>";

	if( empty( $comment_author_email ) )
		$mail_from = $notify_from;
	else
		$mail_from = "\"$comment_author\" <$comment_author_email>";

	@mail($recipient, $subject, $notify_message, "From: $mail_from\nX-Mailer: b2evolution $b2_version - PHP/".phpversion());
}


/*
 * Handle cookies
 */
if( $comment_cookies )
{	// Set cookies:
	if ($email == "") 
	{
		$email = " "; // this to make sure a cookie is set for 'no email'
	}
	if ($url == "") 
	{
		$url = " "; // this to make sure a cookie is set for 'no url'
	}
	// fplanque: made cookies available for whole site
	setcookie( $cookie_name, $author, $cookie_expires, $cookie_path, $cookie_domain);
	setcookie( $cookie_email, $email, $cookie_expires, $cookie_path, $cookie_domain);
	setcookie( $cookie_url, $url, $cookie_expires, $cookie_path, $cookie_domain);
}
else
{	// Erase cookies:
	if( !empty($_COOKIE[$cookie_name]) ) 
	{	
		// echo "del1<br />";
		setcookie("comment_author","", $cookie_expired, '/');
		setcookie("comment_author","", $cookie_expired, $cookie_path, $cookie_domain);
		setcookie( $cookie_name, '', $cookie_expired, $cookie_path, $cookie_domain);
	}
	if( !empty($_COOKIE['comment_author_email']) )
	{	
		// echo "del2<br />";
		setcookie("comment_author_email","", $cookie_expired, '/');
		setcookie("comment_author_email","", $cookie_expired, $cookie_path, $cookie_domain);
		setcookie( $cookie_email, '', $cookie_expired, $cookie_path, $cookie_domain);
	}
	if( !empty($_COOKIE['comment_author_url']) )
	{	
		// echo "del3<br />";
		setcookie("comment_author_url","", $cookie_expired, '/');
		setcookie("comment_author_url","", $cookie_expired, $cookie_path, $cookie_domain);
		setcookie( $cookie_url, '', $cookie_expired, $cookie_path, $cookie_domain);
	}
}

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
$location = (!empty($_POST['redirect_to'])) ? $_POST['redirect_to'] : $_SERVER["HTTP_REFERER"];
header("Location: $location");

?>
