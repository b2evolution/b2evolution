<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003-2004 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * pop3-2-b2 mail to blog
 * This file built upon code from original b2 - http://cafelog.com/
 */
$output_debugging_info = 0;		# =1 if you want to output debugging info

require_once(dirname(__FILE__).'/../conf/_config.php');
require_once(dirname(__FILE__)."/$htsrv_dirout/$core_subdir/_main.php");
require_once(dirname(__FILE__)."/$htsrv_dirout/$core_subdir/_class_pop3.php");

if( $use_phoneemail )
{ // if you're using phone email, the email will already be in your timezone
	$Settings->set('time_difference', 0);
}

// error_reporting( E_ALL );


$pop3 = new POP3();

echo T_('Connecting to pop server...'), "<br />\n";
if( !$pop3->connect($mailserver_url, $mailserver_port) )
{
	echo T_('Connection failed: ').$pop3->ERROR." <br />\n";
	exit;
}

echo T_('Logging into pop server...'), "<br />\n";
$Count = $pop3->login( $mailserver_login, $mailserver_pass );
if( (!$Count) || ($Count == -1) )
{
	echo T_('No mail or Login Failed:'), " $pop3->ERROR <br />\n";
	$pop3->quit();
	exit;
}


// ONLY USE THIS IF YOUR PHP VERSION SUPPORTS IT! (PHP >= 3.0.4)
#register_shutdown_function( $pop3->quit() );

for( $iCount = 1; $iCount <= $Count; $iCount++)
{
	printf( T_('Getting message #%d...')."<br />\n", $iCount );
	$MsgOne = $pop3->get($iCount);
	if((!$MsgOne) || (gettype($MsgOne) != 'array'))
	{
		echo $pop3->ERROR, "<br />\n";
		$pop3->quit();
		exit;
	}

	echo T_('Processing...'), "<br />\n";
	$content = '';
	$content_type = '';
	$boundary = '';
	$bodysignal = 0;
	$dmonths = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
	
	while( list( $lineNum, $line ) = each ($MsgOne) )
	{
		if( strlen($line) < 3 ) {
			$bodysignal = 1;
		}
		if ($bodysignal) {
			$content .= $line;
		} else {
			if (preg_match('/Content-Type: /', $line)) {
				$content_type = trim($line);
				$content_type = substr($content_type, 14, strlen($content_type)-14);
				$content_type = explode(';', $content_type);
				$content_type = $content_type[0];
			}
			if (($content_type == 'multipart/alternative') && (preg_match('/boundary="/', $line)) && ($boundary == ''))
			{
				$boundary = trim($line);
				$boundary = explode('"', $boundary);
				$boundary = $boundary[1];
			}
			if (preg_match('/Subject: /', $line))
			{
				$subject = trim($line);
				$subject = substr($subject, 9, strlen($subject)-9);
				if ($use_phoneemail)
				{
					$subject = explode($phoneemail_separator, $subject);
					$subject = trim($subject[0]);
				}
				if (!ereg($subjectprefix, $subject))
				{
					continue;
				}
			}
			if (preg_match('/Date: /', $line))
			{ // of the form '20 Mar 2002 20:32:37'
				$ddate = trim($line);
				$ddate = str_replace('Date: ', '', $ddate);
				if (strpos($ddate, ',')) {
					$ddate = trim(substr($ddate, strpos($ddate, ',')+1, strlen($ddate)));
				}
				$date_arr = explode(' ', $ddate);
				$date_time = explode(':', $date_arr[3]);

				$ddate_H = $date_time[0];
				$ddate_i = $date_time[1];
				$ddate_s = $date_time[2];

				$ddate_m = $date_arr[1];
				$ddate_d = $date_arr[0];
				$ddate_Y = $date_arr[2];
				for ($i=0; $i<12; $i++) {
					if ($ddate_m == $dmonths[$i]) {
						$ddate_m = $i+1;
					}
				}
				$ddate_U = mktime($ddate_H, $ddate_i, $ddate_s, $ddate_m, $ddate_d, $ddate_Y);
				$ddate_U = $ddate_U + ($Settings->get('time_difference') * 3600);
				$post_date = date('Y-m-d H:i:s', $ddate_U);
			}
		}
	}

	$ddate_today = time() + ($Settings->get('time_difference') * 3600);
	$ddate_difference_days = ($ddate_today - $ddate_U) / 86400;


	# starts buffering the output
	ob_start();

	if ($ddate_difference_days > 14)
	{
		echo T_('Too old'), '<br />';
		continue;
	}

	if( !preg_match('/'.$subjectprefix.'/', $subject))
	{
		echo T_('Subject prefix does not match').'.<br />';
		continue;
	}

	$userpassstring = '';

	echo '<div style="border: 1px dashed #999; padding: 10px; margin: 10px;">';
	echo "<p><strong>$iCount</strong></p><p><strong>Subject: </strong>$subject</p>\n";

	$subject = trim(str_replace($subjectprefix, '', $subject));

	if ($content_type == 'multipart/alternative') {
		$content = explode('--'.$boundary, $content);
		$content = $content[2];
		$content = explode('Content-Transfer-Encoding: quoted-printable', $content);
		$content = strip_tags($content[1], '<img><p><br><i><b><u><em><strong><strike><font><span><div>');
	}
	$content = trim($content);

	echo "<p><strong>Content-type:</strong> $content_type, <strong>boundary:</strong> $boundary</p>\n";
	echo '<p><strong>', T_('Raw content:'), '</strong><br /><xmp>', $content, '</xmp></p>';

	$btpos = strpos($content, $bodyterminator);
	if ($btpos) {
		$content = substr($content, 0, $btpos);
	}
	$content = trim($content);

	$blah = explode("\n", $content);
	$firstline = $blah[0];

	if ($use_phoneemail)
	{
		$btpos = strpos($firstline, $phoneemail_separator);
		if ($btpos) {
			$userpassstring = trim(substr($firstline, 0, $btpos));
			$content = trim(substr($content, $btpos+strlen($phoneemail_separator), strlen($content)));
			$btpos = strpos($content, $phoneemail_separator);
			if ($btpos) {
				$userpassstring = trim(substr($content, 0, $btpos));
				$content = trim(substr($content, $btpos+strlen($phoneemail_separator), strlen($content)));
			}
		}
		$contentfirstline = $blah[1];
	} else {
		$userpassstring = $firstline;
		$contentfirstline = '';
	}

	$blah = explode(':', $userpassstring);
	$user_login = trim($blah[0]);
	$user_pass = @trim($blah[1]);

	$content = $contentfirstline.str_replace($firstline, '', $content);
	$content = trim($content);

	echo '<p><strong>', T_('Login:'), '</strong> ', $user_login, ', <strong>', T_('Pass:'), '</strong> ', $user_pass, '</p>';

	if( !user_pass_ok( $user_login, $user_pass ) )
	{
		echo '<p><strong>', T_('Wrong login or password.'), '</strong></p></div>';
		continue;
	}

	$userdata = get_userdatabylogin($user_login);
	$loop_User = & new User( $userdata );
	$post_author = $userdata['ID'];

	// --- get infos from content -----------
	$post_title = xmlrpc_getposttitle($content);
	if ($post_title == '')
	{
		$post_title = $subject;
	}

	$post_category = xmlrpc_getpostcategory($content);
	if ($post_category == '')
	{
		$post_category = $default_category;
	}
	echo '<p><strong>', T_('Category ID'), ':</strong> ',$post_category,'</p>';

	$content = xmlrpc_removepostdata( $content );
	
	$blog_ID = get_catblog($post_category); // TODO: should not die, if cat does not exist!
	echo '<p><strong>', T_('Blog ID'), ':</strong> ',$blog_ID,'</p>';

	// Check permission:
	if( ! $loop_User->check_perm( 'blog_post_statuses', 'published', false, $blog_ID ) )
	{
		echo "\n", T_('Permission denied.'), '<br />';
		continue;
	}

	if (!$thisisforfunonly)
	{
		// CHECK and FORMAT content
		$post_title = format_to_post( trim($post_title), 0, 0 );
		$content = format_to_post( trim($content), $Settings->get('AutoBR'), 0);

		if( errors_display( T_('Cannot post, please correct these errors:'), '' ) )
		{
			$errors = array();
			echo '</div>';
			continue;
		}

		// INSERT NEW POST INTO DB:
		$post_ID = bpost_create( $post_author, $post_title, $content, $post_date, $post_category,	array(), 'published', $default_locale, '',	$Settings->get('AutoBR'), true ) or mysql_oops($query);

		if (isset($sleep_after_edit) && $sleep_after_edit > 0)
		{
			sleep($sleep_after_edit);
		}

		$blogparams = get_blogparams_by_ID( $blog_ID );
		pingback( true, $content, $post_title, '', $post_ID, $blogparams, true);
		pingb2evonet( $blogparams, $post_ID, $post_title);
		pingWeblogs($blogparams);
		pingBlogs($blogparams);
		pingTechnorati($blogparams);
	}
	echo "\n<p><strong>", T_('Posted title'), ':</strong> ', $post_title, '<br />';
	echo "\n<strong>", T_('Posted content'), ':</strong><br /><xmp>', $content, '</xmp></p>';

	if(!$pop3->delete($iCount))
	{
		echo '<p>', $pop3->ERROR, '</p></div>';
		$pop3->reset();
		exit;
	}
	else
	{
		echo '<p>', T_('Mission complete, message deleted.'), '</p>';
	}

	echo '</div>';
	if ($output_debugging_info)
	{
		ob_end_flush();
	}
	else
	{
		ob_end_clean();
	}
}

echo T_('OK.'), "<br />\n";

$pop3->quit();

timer_stop($output_debugging_info);
exit;

?>
