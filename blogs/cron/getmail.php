<?php
/**
 * pop3-2-b2 mail to blog
 *
 * modified for 2.4.1 by Stephan Knauss. Contact me by PM in {@link http://forums.b2evolution.net/} (user stephankn)
 * or send a mail to stephankn at users.sourceforge.net
 *
 * Uses MIME E-mail message parser classes written by Manuel Lemos: {@link http://www.phpclasses.org/browse/package/3169.html}
 *
 * This script can be called with a parameter "test" to specify what
 * should be done and what level of debug output to generate:
 * <ul>
 * <li>level 0: default. Process everything, no debug output, no html (called by cronjob)</li>
 * <li>level 1: Test only connection to server, do not process messages</li>
 * <li>level 2: additionally process messages, but do not post</li>
 * <li>level 3: do everything with extended verbosity</li>
 * </ul>
 * Only messages for "level 0" should get marked for translation (using T_()).
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * @author Stephan Knauss
 * @author tblue246: Tilman Blumenbach
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
 * This file built upon code from original b2 - http://cafelog.com/
 * @package htsrv
 *
 * @todo check different encodings. only tested with iso-8859-1
 * @todo try more exotic email clients like mobile phones
 * @todo tested and working with thunderbird (text, html, signed), yahoo mail (text, html), outlook webmail, K800i
 * @todo Allow the user to choose whether to upload attachments to the blog media folder or to his user root.
 *
 * @version $Id$
 */

/**
 * load b2evolution configuration
 */
require_once dirname( __FILE__ ) . '/../conf/_config.php';
require_once $inc_path . '_main.inc.php';

load_class('items/model/_itemlist.class.php');
load_funcs('files/model/_file.funcs.php');
load_class( '_ext/mime_parser/rfc822_addresses.php' );
load_class( '_ext/mime_parser/mime_parser.php' );

if( !$Settings->get( 'eblog_enabled' ) )
{
	echo T_( 'Blog by email feature is not enabled.' );
	debug_info();
	exit();
}

param( 'test', 'integer', 0 );

/**
 * Subject of the current email message
 *
 * @global string $subject
 */
$subject = '';

/**
 * post date of current message
 *
 * @global string $post_date
 */
$post_date = '';

/**
 * message content of current email that is going to be posted
 *
 * @global string $content
 */
$content = '';

/**#@+
 * define colour constants for messages
 */
define( 'INFO', 'black' );
define( 'SUCCESS', 'green' );
define( 'WARNING', 'orange' );
define( 'ERROR', 'red' );
/**#@-*/

// if it's not called by a logged in user override test settings
if( !isset( $current_User ) || !$current_User->check_perm( 'options', 'edit', true ) )
{
	$test = 0;
}

/**
 * Whether to do real posting.
 *
 * It is set to true if the setting eblog_test_mode is set to false *and*
 * the test parameter is not set to 2.
 */
$do_real_posting = (!$Settings->get( 'eblog_test_mode' ) && $test != 2);
if( ! $do_real_posting )
{
	echo_message( T_('You configured test mode in the settings or set $test to 2. Nothing will be posted to the database/mediastore nor will your inbox be altered.'), WARNING, 0 );
}

if( $test > 0 )
{
	//error_reporting (0);

	// TODO: I don't find a header to include for this popup window.
	//	There should exist one in b2evo. So right now no valid HTML
	$page_title = T_( 'Blog by email' );
	echo '<html><head><title>' . $page_title . '</title></head><body>';
}

/**
 * Print out a debugging message with optional HTML color added.
 *
 * This function only outputs any additional HTML (colors, <br />) if
 * $test is greater than 0.
 *
 * @global integer The test level
 * @param  string $strmessage The message to print
 * @param  string $color optional colour so use
 * @param  integer $level optional level to limit output to that level
 */
function echo_message( $strmessage , $color = '', $level = 0 )
{
	global $test;

	if( $level <= $test )
	{
		if( $test > 0 && $color )
		{
			echo '<font color="'.$color.'">';
		}

		echo $strmessage;

		if( $test > 0 && $color )
		{
			echo '</font>';
		}

		if( $test > 0 )
		{
			echo '<br />';
		}
		echo "\n";
	}
}


/**
 * Provide sys_get_temp_dir for older versions of PHP (< 5.2.1).
 *
 * code posted on php.net by minghong at gmail dot com
 * Based on {@link http://www.phpit.net/article/creating-zip-tar-archives-dynamically-php/2/}
 *
 * @return string path to system temporary directory
 */
if( !function_exists( 'sys_get_temp_dir' ) )
{
	function sys_get_temp_dir()
	{
		// Try to get from environment variable
		if( !empty( $_ENV['TMP'] ) )
		{
			return realpath( $_ENV['TMP'] );
		}
		else if( !empty( $_ENV['TMPDIR'] ) )
		{
			return realpath( $_ENV['TMPDIR'] );
		}
		else if( !empty( $_ENV['TEMP'] ) )
		{
			return realpath( $_ENV['TEMP'] );
		}

		// Detect by creating a temporary file
		else
		{
			// Try to use system's temporary directory
			// as random name shouldn't exist
			$temp_file = tempnam( md5( uniqid( rand(), true ) ), '' );
			if( $temp_file )
			{
				$temp_dir = realpath( dirname( $temp_file ) );
				unlink( $temp_file );
				return $temp_dir;
			}
			else
			{
				return false;
			}
		}
	}
}


/**
 * Create a new directory with unique name.
 * This creates a new directory below the given path with the given prefix and a random number.
 *
 * @param  string $dir base path to new directory
 * @param  string $prefix prefix random number with this
 * @param  integer $mode permissions to use
 * @return string path to created directory
 */
function tempdir( $dir, $prefix = 'tmp', $mode = 0700 )
{
	if( substr( $dir, -1 ) != '/' ) $dir .= '/';

	do
	{
		$path = $dir . $prefix . mt_rand();
	} while( !mkdir( $path, $mode ) );

	return $path;
}



/**
 * Process Header information like subject and date of a mail.
 *
 * @global string The subject of the current message (write)
 * @global string The post date of the current message (write)
 * @global object b2evo settings (read)
 * @global integer The test level (read)
 * @param  array $header header as set by mime_parser_class::Analyze()
 * @return bool true if valid subject prefix is detected
 */
function processHeader( &$header )
{
	// write to these globals
	global $subject, $post_date;

	// read these globals
	global $Settings, $test;

	$subject = $header['Subject'];
	$ddate = $header['Date'];

	$prefix = $Settings->get( 'eblog_subject_prefix' );
	echo_message( 'Subject: ' . $subject, INFO, 3 );

	if(substr($subject, 0, strlen($prefix)) !== $prefix)
	{
		echo_message( '&#x2718; The subject prefix is not ' . '"' . $prefix . '"', WARNING, 2 );
		return false;
	}

	// Parse Date.
	// TODO: dh> use strftime (after format validation)? or strptime (PHP>=5.1.0)
	// of the form '20 Mar 2002 20:32:37'
	if(!preg_match('#^(.{3}, )?(\d{2}) (.{3}) (\d{4}) (\d{2}):(\d{2}):(\d{2})#', $ddate, $match))
	{
		echo_message(T_('Could not parse date header!'), ERROR, 0);
		//pre_dump($ddate);
		return false;
	}

	$dmonths = array(
		'Jan' => 1,
		'Feb' => 2,
		'Mar' => 3,
		'Apr' => 4,
		'May' => 5,
		'Jun' => 6,
		'Jul' => 7,
		'Aug' => 8,
		'Sep' => 9,
		'Oct' => 10,
		'Nov' => 11,
		'Dec' => 12,
	);

	$ddate_H = $match[5];
	$ddate_i = $match[6];
	$ddate_s = $match[7];

	if( ! isset( $dmonths[$match[3]] ) )
	{
		echo_message( T_( 'Invalid month name in message date string.' ), ERROR, 0 );
		return false;
	}
	$ddate_m = $dmonths[$match[3]];
	$ddate_d = $match[2];
	$ddate_Y = $match[4];

	$ddate_U = mktime( $ddate_H, $ddate_i, $ddate_s, $ddate_m, $ddate_d, $ddate_Y );
	$post_date = date( 'Y-m-d H:i:s', $ddate_U );

	return true;
}



/**
 * process attachments by saving into media directory and optionally creating image tag in post
 *
 * @global string message content that is optionally manipulated by adding image tags
 * @global bool do we really post?
 * @global object global b2evo settings
 * @param  array $mailAttachments array containing path to attachment files
 * @param  string $mediadir path to media directory of blog as seen by file system
 * @param  string $media_url url to media directory as seen by user
 * @param  bool $add_img_tags should img tags be added (instead of adding a normal link)
 * @return bool true for sucessfull execution
 */
function processAttachments( $mailAttachments, $mediadir, $media_url, $add_img_tags = true )
{
	global $content;
	global $do_real_posting;
	global $Settings;

	$return = true;

	echo_message( 'Processing attachments', INFO, 3 );

	foreach( $mailAttachments as $attachment )
	{
		$filename = strtolower( $attachment['FileName'] );
		if( $filename == '' )
		{
			$filename = tempnam( $mediadir, 'upload' ) . '.' . $attachment['SubType'];
			echo_message( '&#x279C; ' . sprintf('Attachment without name. Using "%s".', htmlspecialchars( $filename )), WARNING, 2 );
		}
		$filename = preg_replace( '/[^a-z0-9\-_.]/', '-', $filename );

		// Check valid filename/extension: (includes check for locked filenames)
		if( $error_filename = validate_filename( $filename, false ) )
		{
			echo_message( '&#x2718; ' . 'Invalid filename: '.$error_filename, WARNING, 2 );
			$return = false; // return: at least one error. try with next attachment
			continue;
		}

		// if file exists count up a number
		$cnt = 0;
		$prename = substr( $filename, 0, strrpos( $filename, '.' ) ).'-';
		$sufname = strrchr( $filename, '.' );
		while( file_exists( $mediadir . $filename ) )
		{
			$filename = $prename.$cnt.$sufname;
			echo_message( '&#x2718; file already exists. Changing filename to: ' . $filename , WARNING, 2 );
			++$cnt;
		}

		if( $do_real_posting )
		{
			echo_message( '&#x279C; Saving file to: ' . htmlspecialchars( $mediadir . $filename  ), INFO, 3 );
			if( !rename( $attachment['DataFile'], $mediadir . $filename ) )
			{
				echo_message( '&#x2718; Problem saving upload to ' . htmlspecialchars( $mediadir . $filename ), WARNING, 2 );
				$return = false; // return: at least one error. try with next attachment
				continue;
			}

			// chmod uploaded file:
			$chmod = $Settings->get( 'fm_default_chmod_file' );
			@chmod( $mediadir . $filename, octdec( $chmod ) );
		}

		$imginfo = @getimagesize($mediadir.$filename);
		echo_message('Attachment is an image: '.(is_array($imginfo) ? T_('yes') : T_('no')), INFO, 3 );

		$content .= "\n";
		if(is_array($imginfo) && $add_img_tags)
		{
			$content .= '<img src="'.$media_url.$filename.'" '.$imginfo[3].' />';
		}
		else
		{
			$content .= '<a href="'.$media_url.$filename.'">'.basename($filename).'</a>';
		}
		$content .= "\n";
	}

	return $return;
}

/**
 * look inside message to get title for posting.
 *
 * The message could contain a xml-tag <code><title>sample title</title></code> to specify a title for the posting.
 * If not tag is found there could be a global $post_default_title containing a global default title.
 * If none of these is found then the specified alternate title line is used.
 *
 * @param string $content message to search for title tag
 * @param string $alternate_title use this string if no title tag is found
 * @return string title of posting
 *
 * @see $post_default_title
 */
function get_post_title( $content, $alternate_title )
{
	$title = xmlrpc_getposttitle( $content );
	if( $title == '' )
	{
		$title = $alternate_title;
	}

	return $title;
}

if( ! extension_loaded( 'imap' ) )
{
	echo_message( T_( 'The php_imap extension is not available to PHP on this server. Please load it in php.ini or ask your hosting provider to do so.' ), ERROR, 0 );
	exit;
}
echo_message( 'Connecting and authenticating to mail server.', INFO, 1 );

/**
 * Prepare the connection string.
 */
$mailserver = '{' . $Settings->get( 'eblog_server_host' ) . ':' . $Settings->get( 'eblog_server_port' );
switch($Settings->get('eblog_encrypt'))
{
	case 'ssl':
		$mailserver .= '/ssl';
		break;
	case 'tls':
		$mailserver .= '/tls';
		break;
	case 'none':
	default:
		$mailserver .= '/notls';
		break;
}
switch($Settings->get('eblog_method'))
{
	case 'pop3':
	case 'pop3a':
		$mailserver .= '/pop3';
		break;
	case 'imap':
	default:
		// imap needs no additional options
		break;
}
if($Settings->get('eblog_novalidatecert'))
{
	$mailserver .= '/novalidate-cert';
}
$mailserver .= '}INBOX';
//pre_dump($mailserver);

// Connect to mail server
$mbox = @imap_open( $mailserver, $Settings->get( 'eblog_username' ), $Settings->get( 'eblog_password' ) );
if( ! $mbox )
{
    echo_message( sprintf( /* TRANS: %s is the error message */ T_( 'Connection failed: %s' ), imap_last_error()), ERROR, 0 );
	exit();
}
@imap_errors();

echo_message( '&#x2714; Success', SUCCESS, 1 );
if( $test == 1 )
{
	echo_message( 'All tests completed.', INFO, 1 );
	imap_close( $mbox );
	exit();
}


// Read messages from server
echo_message( 'Reading messages from server.', INFO, 2 );
$imap_obj = imap_check( $mbox );
echo_message( ' &#x279C; ' . $imap_obj->Nmsgs . ' ' . 'messages', INFO, 2 );

$delMsgs = 0;
for ( $index = 1; $index <= $imap_obj->Nmsgs; $index++ )
{
	echo_message( '<strong>Message #' . $index . ':</strong>', INFO, 2 );

	$strbody = '';
	$hasAttachment = false;

	// save mail to disk because with attachments could take up much RAM
	if(!($tmpMIME = tempnam( sys_get_temp_dir(), 'b2evoMail' )))
	{
		echo_message( T_('Could not create temporary file.'), ERROR, 0 );
		continue;
	}
	imap_savebody( $mbox, $tmpMIME, $index );

	$tmpDirMIME = tempdir( sys_get_temp_dir(), 'b2evo' );
	$mimeParser = new mime_parser_class;
	$mimeParser->mbox = 0; // Set to 0 for parsing a single message file
	$mimeParser->decode_bodies = 1;
	$mimeParser->ignore_syntax_errors = 1;
	$mimeParser->extract_addresses = 0;
	$MIMEparameters = array(
		'File' => $tmpMIME,
		'SaveBody' => $tmpDirMIME, // Save the message body parts to a directory
		'SkipBody' => 1, // Do not retrieve or save message body parts
	);

	if( !$mimeParser->Decode( $MIMEparameters, $decodedMIME ) )
	{
		echo_message( sprintf(T_('MIME message decoding error: %s at position %d.'), $mimeParser->error, $mimeParser->error_position), ERROR, 0 );
		rmdir_r( $tmpDirMIME );
		unlink( $tmpMIME );
		continue;
	}
	else
	{
		echo_message( '&#x2714; MIME message decoding successful.', INFO, 3 );
		if( ! $mimeParser->Analyze( $decodedMIME[0], $parsedMIME ) )
		{
			echo_message( sprintf(T_('MIME message analyse error: %s'), $mimeParser->error), ERROR, 0 );
			// var_dump($parsedMIME);
			rmdir_r( $tmpDirMIME );
			unlink( $tmpMIME );
			continue;
		}

		// the following helps me debugging
		//pre_dump($decodedMIME[0], $parsedMIME);

		if(!processHeader($parsedMIME))
		{
			rmdir_r( $tmpDirMIME );
			unlink($tmpMIME);
			continue;
		}

		// TODO: handle type == "message" recursively

		// mail is html
		if( $parsedMIME['Type'] == 'html' ){
			foreach ( $parsedMIME['Alternative'] as $alternative ){
				if( $alternative['Type'] == 'text' ){
					echo_message( 'HTML alternative message part saved as ' . $alternative['DataFile'], INFO, 3 );
					$strbody = imap_qprint( file_get_contents( $alternative['DataFile'] ) );
					break; // stop after first alternative
				}
			}
		}

		// mail is plain text
		elseif( $parsedMIME['Type'] == 'text' )
		{
			echo_message( 'Plain-text message part saved as ' . $parsedMIME['DataFile'], INFO, 3 );
			$strbody = imap_qprint( file_get_contents( $parsedMIME['DataFile'] ) );
		}

		// Check for attachments
		if( isset( $parsedMIME['Attachments'] ) && count($parsedMIME['Attachments']) )
		{
			$hasAttachment = true;
			foreach( $parsedMIME['Attachments'] as $attachment )
			{
				echo_message( 'Attachment: ' . $attachment['FileName'] . ' stored as ' . $attachment['DataFile'], INFO, 3 );
			}
		}

		$warning_count = count( $mimeParser->warnings );
		if( $warning_count > 0 )
		{
			echo_message( '&#x2718; ' . $warning_count . ' warnings during decode: ', WARNING, 2 );
			foreach ($mimeParser->warnings as $k => $v)
			{
				echo_message( '&#x2718; ' . 'Warning: ' . $v . ' at position ' . $k, WARNING, 3 );
			}
		}
	}
	unlink( $tmpMIME );

	// var_dump($strbody);
	// process body. First fix different line-endings (dos, mac, unix), remove double newlines
	$strbody = str_replace( array("\r", "\n\n"), "\n", $strbody );

	$a_body = explode( "\n", $strbody, 2 );

	// tblue> splitting only into 2 parts allows colons in the user PW
	$a_authentication = explode( ':', $a_body[0], 2 );
	$content = trim( $a_body[1] );

	echo_message( 'Message content:' . ' <code>' . htmlspecialchars( $content ) . '</code>', INFO, 3 );

	$user_login = trim( $a_authentication[0] );
	// TODO: dh> should the password really get trimmed here?!
	$user_pass = isset($a_authentication[1]) ? trim($a_authentication[1]) : null;

	// authenticate user
	echo_message( 'Authenticating user: ' . $user_login, INFO, 3 );
	if( !user_pass_ok( $user_login, $user_pass ) )
	{
		echo_message( sprintf(T_( 'Authentication failed for user %s.' ), htmlspecialchars($user_login)), ERROR, 0 );
		echo_message( '&#x2718; Wrong login or password. First line of text in email must be in the format "username:password".', ERROR, 3 );
		rmdir_r( $tmpDirMIME );
		continue;
	}
	else
	{
		echo_message( '&#x2714; Success.', SUCCESS, 3 );
	}

	$subject = trim( substr($subject, strlen($Settings->get( 'eblog_subject_prefix' ))) );

	// remove content after terminator
	$eblog_terminator = $Settings->get( 'eblog_body_terminator' );
	if( !empty( $eblog_terminator ) &&
		 ($os_terminator = strpos( $content, $eblog_terminator )) !== false)
	{
		$content = substr( $content, 0, $os_terminator );
	}

	// check_html_sanity needs local user set.
	$UserCache = & get_Cache( 'UserCache' );
	$current_User = & $UserCache->get_by_login( $user_login );

	$post_title = get_post_title( $content, $subject );

	if( ! ( $post_category = xmlrpc_getpostcategory( $content ) ) )
	{
		$post_category = $Settings->get( 'eblog_default_category' );
	}
	echo_message( 'Category ID: ' . htmlspecialchars( $post_category ), INFO, 3 );

	$content = xmlrpc_removepostdata( $content );
	$blog_ID = get_catblog( $post_category ); // TODO: should not die, if cat does not exist!
	echo_message( 'Blog ID: ' . $blog_ID, INFO, 3 );

	$BlogCache = & get_Cache( 'BlogCache' );
	$Blog = $BlogCache->get_by_ID( $blog_ID, false, false );

	if( empty( $Blog ) )
	{
		echo_message( sprintf( T_('Blog #%d not found!'), $blog_ID ), ERROR, 0 );
		rmdir_r( $tmpDirMIME );
		continue;
	}


	// Check permission:
	echo_message( sprintf( 'Checking permissions for user &laquo;%s&raquo; to post to Blog #%d', $user_login, $blog_ID ), INFO, 3 );
	if( !$current_User->check_perm( 'blog_post!published', 'edit', false, $blog_ID )
	|| ( $hasAttachment && !$current_User->check_perm( 'files', 'add', false ) )
	)
	{
		echo_message( T_( 'Permission denied.' ), ERROR, 0 );
		rmdir_r( $tmpDirMIME );
		continue;
	}
	else
	{
		echo_message( '&#x2714; Pass.', SUCCESS, 3 );
	}

	// handle attachments
	if( $hasAttachment )
	{
		$mediadir = $Blog->get_media_dir();
		if( $mediadir )
		{
			processAttachments( $parsedMIME['Attachments'], $mediadir, $Blog->get_media_url(), $Settings->get('eblog_add_imgtag') );
		}
		else
		{
			echo_message( T_( 'Unable to access media directory. No attachments processed.' ), ERROR, 0 );
		}
	}

	// CHECK and FORMAT content
	$post_title = check_html_sanity( trim( $post_title ), 'posting', false );
	$content = check_html_sanity( trim( $content ), 'posting', $Settings->get( 'AutoBR' ) );

	if( ( $error = $Messages->get_string( T_( 'Cannot post, please correct these errors:' ), '', 'error' ) ) )
	{
		echo_message( $error, ERROR, 0 );
		$Messages->clear( 'error' );
		rmdir_r( $tmpDirMIME );
		continue;
	}

	if( $do_real_posting )
	{
		// INSERT NEW POST INTO DB:
		$edited_Item = & new Item();

		$post_ID = $edited_Item->insert( $current_User->ID, $post_title, $content, $post_date, $post_category, array(), 'published', $current_User->locale );

		// Execute or schedule notifications & pings:
		$edited_Item->handle_post_processing();
	}

	echo_message( '&#x2714; Message posting successful.', SUCCESS, 2 );
	echo_message( '&#x279C; Post title: ' . htmlspecialchars( $post_title ), INFO, 3 );
	echo_message( '&#x279C; Post content: ' . htmlspecialchars( $content ), INFO, 3 );

	rmdir_r( $tmpDirMIME );

	echo_message( 'Marking message for deletion: '.$index, INFO, 3 );
	imap_delete( $mbox, $index );
	++$delMsgs;
}

if( $do_real_posting )
{
	imap_expunge( $mbox );
	echo_message( sprintf('Deleted %d processed message(s) from inbox.', $delMsgs), INFO, 3 );
}

imap_close( $mbox );

if( $test > 0 )
{
	// TODO: I don't find a footer to include in this popup. b2evo should include one...
	echo '</body>';
}


/*
 * $Log$
 * Revision 1.35  2009/03/05 19:20:30  blueyed
 * getmail.php: drop T_ usage of uncommon messages (where level>0). This saves the translators 42(!) translations. Also fixed translations to use sprintf and markers for variables.
 *
 * Revision 1.34  2009/03/03 20:01:41  blueyed
 * getmail.php: minor: whitespace fixes, mostly coding style.
 *
 * Revision 1.33  2009/03/03 20:00:27  blueyed
 * getmail.php: doc, minor cleanup, TODOs
 *
 * Revision 1.32  2009/01/23 22:52:29  tblue246
 * Blog by mail: Ensure the month name in the "Date" header is valid.
 *
 * Revision 1.31  2008/12/31 16:04:04  tblue246
 * Moving external classes needed by the blog by mail feature to inc/_ext.
 *
 * Revision 1.30  2008/12/31 15:21:24  blueyed
 * TODO: please move ext. libs
 *
 * Revision 1.29  2008/12/18 01:04:17  blueyed
 * getmail.php: drop $newline param for echo_message, which was true always. Small translation fix. Whitespace fixes.
 *
 * Revision 1.28  2008/10/06 11:02:27  tblue246
 * Blog by mail now supports POP3 & IMAP, SSL & TLS
 *
 */
?>
