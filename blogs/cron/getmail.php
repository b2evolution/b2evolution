<?php
/**
 * pop3-2-b2 mail to blog
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 * This file built upon code from original b2 - http://cafelog.com/
 *
 * @package htsrv
 */

$output_debugging_info = 0;		# =1 if you want to output debugging info

/**
 * Initialize:
 */
require_once dirname(__FILE__).'/../conf/_config.php';

require_once $inc_path.'_main.inc.php';

if( !$Settings->get('eblog_enabled') )
{
	echo T_('Blog by email feature is not enabled.');
	debug_info();
	exit();
}


// Get test settings
param( 'test_type', 'integer', 0 );
$show_messages = false;
$test_connection_only = false;
$str_failure = '';

if ( $Settings->get('eblog_test_mode') )
{
	$test_type = 2;
}
elseif ( !isset( $current_User ) )
{
	$test_type = 0;
}
elseif ( !$current_User->check_perm( 'options', 'edit', true ) )
{
	$test_type = 0;
}

if ( $test_type > 0 )
{
	error_reporting (0);

	$page_title = T_('Blog by email');
	require_once( dirname(__FILE__).'/../_header.php' );
	$show_messages = true;
	$str_failure = ' <font color="red">[ ' . T_('Failed') . ' ]</font>';
	$str_warning = ' <font color="orange">[ ' . T_('Warning') . ' ]</font>';
}

//---------------------------------------
function echo_message( $strmessage , $color = '', $level = 0 )
{
	global $show_messages;
	global $test_type;

	if ( $show_messages )
	{
		if ($level <= $test_type)
		{
			if ( $color )
			{
				echo "<font color='$color'>";
			}

			echo $strmessage;

			if ( $color )
			{
			  echo "</font>";
			}
		}
	}
}
//---------------------------------------



switch ( $Settings->get('eblog_method') )
{
	case 'pop3':
		//--------------------------------------------------------------------
		// eblog_method = POP3 (original)
		//--------------------------------------------------------------------

		// error_reporting( E_ALL );

		require_once $inc_path.'_misc/ext/_pop3.class.php';

		$pop3 = new POP3();
		$port = $Settings->get('eblog_server_port') ? $Settings->get('eblog_server_port') : '110';

		echo T_('Connecting to pop server...'), "<br />\n";
		if( !$pop3->connect( $Settings->get('eblog_server_host'), $port ) )
		{
			echo T_('Connection failed: ').$pop3->ERROR." <br />\n";
			exit;
		}

		echo T_('Logging into pop server...'), "<br />\n";
		$Count = $pop3->login( $Settings->get('eblog_username'), $Settings->get('eblog_password') );
		if( (!$Count) || ($Count == -1) )
		{
			echo T_('No mail or Login Failed:'), " $pop3->ERROR <br />\n";
			$pop3->quit();
			exit;
		}

		if ( $test_type == 1 )
		{
			echo '<br /><br />' . T_('All Tests complete');
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
						if ( $Settings->get('eblog_phonemail') )
						{
							$subject = explode( $Settings->get('eblog_phonemail_separator'), $subject );
							$subject = trim($subject[0]);
						}
						if (!ereg($Settings->get('eblog_subject_prefix'), $subject))
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
						$post_date = date('Y-m-d H:i:s', $ddate_U);
					}
				}
			}

			$ddate_today = $localtimenow;
			$ddate_difference_days = ($ddate_today - $ddate_U) / 86400;


			# starts buffering the output
			ob_start();

			if ($ddate_difference_days > 14)
			{
				echo T_('Too old'), '<br />';
				continue;
			}

			if( !preg_match('/'.$Settings->get('eblog_subject_prefix').'/', $subject))
			{
				echo T_('Subject prefix does not match').'.<br />';
				continue;
			}

			$userpassstring = '';

			echo '<div style="border: 1px dashed #999; padding: 10px; margin: 10px;">';
			echo "<p><strong>$iCount</strong></p><p><strong>Subject: </strong>$subject</p>\n";

			$subject = trim(str_replace($Settings->get('eblog_subject_prefix'), '', $subject));

			if ($content_type == 'multipart/alternative') {
				$content = explode('--'.$boundary, $content);
				$content = $content[2];
				$content = explode('Content-Transfer-Encoding: quoted-printable', $content);
				$content = strip_tags($content[1], '<img><p><br><i><b><u><em><strong><strike><font><span><div>');
			}
			$content = trim($content);

			echo "<p><strong>Content-type:</strong> $content_type, <strong>boundary:</strong> $boundary</p>\n";
			echo '<p><strong>', T_('Raw content:'), '</strong><br /><xmp>', $content, '</xmp></p>';

			$btpos = strpos( $content, $Settings->get('eblog_body_terminator') );
			if ($btpos) {
				$content = substr($content, 0, $btpos);
			}
			$content = trim($content);

			$blah = explode("\n", $content);
			$firstline = $blah[0];

			if ( $Settings->get('eblog_phonemail') )
			{
				$btpos = strpos($firstline, $Settings->get('eblog_phonemail_separator') );
				if ($btpos) {
					$userpassstring = trim(substr($firstline, 0, $btpos));
					$content = trim(substr($content, $btpos+strlen($Settings->get('eblog_phonemail_separator')), strlen($content)));
					$btpos = strpos($content, $Settings->get('eblog_phonemail_separator') );
					if ($btpos) {
						$userpassstring = trim(substr($content, 0, $btpos));
						$content = trim(substr($content, $btpos+strlen($Settings->get('eblog_phonemail_separator')), strlen($content)));
					}
				}
				$contentfirstline = $blah[1];
			}
			else
			{
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

			$loop_User = & $UserCache->get_by_login( $user_login );

			// --- get infos from content -----------
			$post_title = xmlrpc_getposttitle($content);
			if ($post_title == '')
			{
				$post_title = $subject;
			}

			if( ! ($post_category = xmlrpc_getpostcategory($content) ) )
			{
				$post_category = $Settings->get('eblog_default_category');
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

			if (!$Settings->get('eblog_test_mode'))
			{
				// CHECK and FORMAT content
				$post_title = format_to_post( trim($post_title), 0, 0 );
				$content = format_to_post( trim($content), $Settings->get('AutoBR'), 0);

				if( $Messages->display( T_('Cannot post, please correct these errors:'), '', true, 'error' ) )
				{
					$Messages->reset();
					echo '</div>';
					continue;
				}

				// INSERT NEW POST INTO DB:
				$edited_Item = & new Item();
				$post_ID = $edited_Item->insert( $loop_User->ID, $post_title, $content, $post_date, $post_category,	array(), 'published', $loop_User->locale, '',	$Settings->get('AutoBR'), true );

				$blogparams = get_blogparams_by_ID( $blog_ID );
				pingback( true, $content, $post_title, '', $post_ID, $blogparams, true);

				// Send email notifications now!
				$edited_Item->send_email_notifications( false );

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

	break;


	case 'pop3a':
		//--------------------------------------------------------------------
		// eblog_method = POP3 through IMAP extension (experimental)
		//--------------------------------------------------------------------

		if( ! extension_loaded('imap') )
		{
			echo T_('The php_imap extension is not available to php on this server. Please configure a different email retrieval method on the Features tab.');
			exit;
		}

		echo_message( '&bull; ' . T_('Connecting and authenticating to mail server') );

		// Prepare the connection string
		$port = $Settings->get('eblog_server_port') ? $Settings->get('eblog_server_port') : '110';

		$mailserver = '{' . $Settings->get('eblog_server_host') . ':' . $port . '/pop3}INBOX';

		// Connect to mail server
		$mbox = imap_open( $mailserver, $Settings->get('eblog_username'), $Settings->get('eblog_password') )
			or die( $str_failure . '<div class="action_messages"><div class="log_error">' . T_('Connection failed: ') . imap_last_error() . '</div></div>' );

		// damn gmail... grr
		//$mbox = imap_open ("{pop.gmail.com:995/pop3/ssl/novalidate-cert}INBOX", "xxx@gmail.com", "xxx") or die( T_('Connection failed: ') . imap_last_error() );

		echo_message( ' [ ' . T_('Success') . ' ]<br />' , 'green' );
		if ( $test_type == 1 )
		{
			echo '<br /><br />' . T_('All Tests complete');
			imap_close($mbox);
			exit();
		}


		// Read messages from server
		echo_message( '&bull; ' . T_('Reading messages from server') );
		$imap_obj = imap_check($mbox);
		echo_message( ' [ ' . $imap_obj->Nmsgs . ' ' . T_('messages') .' ] <br />', 'green' );

		for ( $index=1; $index<= $imap_obj->Nmsgs; $index++ )
		{
			echo_message( '<br /><b>' . T_('Message') . " #$index" . '</b><br />' );


			//retrieve and process header
			$imap_header = imap_headerinfo($mbox,$index);
			$subject = $imap_header->subject;

			//echo_message( '<b>' . T_('Subject') . ':</b>' . $subject . '<br />', "green");
			echo_message('&bull;<b>' . T_('Subject') . ':</b>' . $subject );
			if( !preg_match( '/'.$Settings->get('eblog_subject_prefix') .'/', $subject ) )
			{
				echo_message( ' [ ' . T_('Warning') . ' ] <br />', 'orange' );
				echo_message( '&bull; ' . T_('The subject prefix is not ') . '"' . $Settings->get('eblog_subject_prefix') . '"<br/>', 'orange' );
				continue;
			}
			else
			{
				echo_message( ' [ ' . T_('Pass') . ' ] <br />', 'green' );
			}

			// todo: review the post_date code
			// of the form '20 Mar 2002 20:32:37'
			$ddate = trim($imap_header->Date);
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

			$dmonths = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');

			for ($i=0; $i<12; $i++) {

				if ($ddate_m == $dmonths[$i]) {
					$ddate_m = $i+1;
				}
			}
			$ddate_U = mktime($ddate_H, $ddate_i, $ddate_s, $ddate_m, $ddate_d, $ddate_Y);
			$post_date = date('Y-m-d H:i:s', $ddate_U);


			//fetch structure
			$imap_structure = imap_fetchstructure ($mbox, $index);
			//echo var_dump($imap_structure), "<br /><br />\n\n";

			$strbody = "";

			/* part types
			0 text
			1 multipart
			2 message
			3 application
			4 audio
			5 image
			6 video
			7 other
			*/

			if ( $imap_structure->type == 1 )
			{
				// multipart message
				for ( $ix=0; $ix< count($imap_structure->parts); $ix++ )
				{
					//foreach ( $imap_structure->parts as $part )
					switch ( $imap_structure->parts[$ix]->type )
					{
						case 0 : // text
						  $strbody = imap_fetchbody($mbox,$index,$ix);
						break;
						case 5 : // image
							// awww yeah ;)
							// todo: add code to save attachments *safely*.  refer to files.php case 'file_upload'

						break;
						default:
							echo_message( '&bull; ' . T_('Unhandled email part type') . "\n" . var_dump($part) . "\n<br/>", 'orange');
						break;
					}

				}
			}
			else
			{
				// single part
				$strbody = imap_fetchbody ($mbox, $index,1);
			}

			// process body
			$a_body = split(chr(13),$strbody,2);
			$a_authentication = split(':',$a_body[0]);
			$content = $a_body[1];
			$user_login = trim($a_authentication[0]);
			$user_pass = @trim($a_authentication[1]);

			echo_message('&bull;<b>' . T_('Authenticating User') . ":</b> $user_login ");
			// authenticate user
			if( !user_pass_ok( $user_login, $user_pass ) )
			{
				echo_message('[ ' . T_('Fail') .' ]<br />','orange');
				echo_message( '&bull; ' . T_('Wrong login or password.') . ' ' . T_('First line of text in email must be in the format "username:password"') . '<br />','orange');
				continue;
			}
			else
			{
				echo_message('[ ' . T_('Pass') .' ]<br />','green');
			}

			$subject = trim(str_replace($Settings->get('eblog_subject_prefix'), '', $subject));

			// remove content after terminator
			$eblog_terminator = $Settings->get('eblog_body_terminator');
			if ( !empty( $eblog_terminator ) )
			{
				$os_terminator = strpos( $content, $Settings->get($eblog_terminator) );
				if ($os_terminator)
				{
					$content = substr($content, 0, $os_terminator);
				}
			}
			$content = trim($content);


			$loop_User = & $UserCache->get_by_login( $user_login );

			// --- get infos from content -----------
			$post_title = xmlrpc_getposttitle($content);
			if ($post_title == '')
			{
				$post_title = $subject;
			}

			if( ! ($post_category = xmlrpc_getpostcategory($content) ) )
			{
				$post_category = $Settings->get('eblog_default_category');
			}
			echo_message( '&bull;<b>' . T_('Category ID') . ':</b> ' . $post_category . '<br />','',3);

			$content = xmlrpc_removepostdata( $content );

			$blog_ID = get_catblog($post_category); // TODO: should not die, if cat does not exist!
			echo_message( '&bull;<b>' . T_('Blog ID') . ':</b> ' . $blog_ID . '<br />','',3);

			// Check permission:
			echo_message( '&bull;'.sprintf( T_('Checking permissions for user &laquo;%s&raquo; to post to Blog #%d'), $user_login, $blog_ID ).' ' );
			if(  !$loop_User->check_perm( 'blog_post_statuses', 'published', false, $blog_ID ) )
			{
				echo_message( '[ ' . T_('Permission denied') . ' ]','red' );
				continue;
			}
			else
			{
				echo_message( '[ ' . T_('Pass') . ' ]<br />' , 'green');
			}

			// todo: finish this last section
			if ( !$test_type > 0 )
			{
				// CHECK and FORMAT content
				$post_title = format_to_post( trim($post_title), 0, 0 );
				$content = format_to_post( trim($content), $Settings->get('AutoBR'), 0);

				if( $Messages->display( T_('Cannot post, please correct these errors:'), '', true, 'error' ) )
				{
					$Messages->reset();
					continue;
				}

				// INSERT NEW POST INTO DB:
				$edited_Item = & new Item();
				$post_ID = $edited_Item->insert( $loop_User->ID, $post_title, $content, $post_date, $post_category,	array(), 'published', $loop_User->locale, '',	$Settings->get('AutoBR'), true );

				$blogparams = get_blogparams_by_ID( $blog_ID );
				pingback( true, $content, $post_title, '', $post_ID, $blogparams, true);

				// Send email notifications now!
				$edited_Item->send_email_notifications( false );

				//pingb2evonet( $blogparams, $post_ID, $post_title);
				//pingWeblogs($blogparams);
				//pingBlogs($blogparams);
				//pingTechnorati($blogparams);
			}
			echo_message( '&bull;<b>' . T_('Post title') . ":</b> $post_title<br/>",'',3 );
			echo_message( '&bull;<b>' . T_('Post content') . ":</b> $content<br/>",'',3 );
			echo_message( '&bull;<b>' . T_('Blog by Email'). ':</b> ');
			echo_message( '<b>[ ' . T_('Success') . ' ]</b><br/>', 'green');

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

		}
		echo '</div>';

		imap_close($mbox);

	break;


	default:
		echo T_('Blog by email feature not configured');
	break;
}

?>