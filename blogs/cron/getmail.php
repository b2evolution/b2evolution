<?php
/**
 * pop3-2-b2 mail to blog
 *
 * modified for 2.4.1 by Stephan Knauss. Contact me by PM in {@link http://forums.b2evolution.net/} (user stephankn)
 * or send a mail to stephankn at users.sourceforge.net
 * 
 * Uses MIME E-mail message parser classes written by Manuel Lemos: ({@link http://www.phpclasses.org/browse/package/3169.html})
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * @author Stephan Knauss
 * 
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
 * This file built upon code from original b2 - http://cafelog.com/
 * @package htsrv
 */


/**
 * TODO: Things to test!
 * - check different encodings. only tested with iso-8859-1
 * - try more exotic email clients like mobile phones
 * - tested and working with thunderbird (text, html, signed), yahoo mail (text, html), outlook webmail, K800i
 */



$output_debugging_info = 1; # =1 if you want to output debugging info


/**
 * load b2evolution configuration
 */
require_once dirname( __FILE__ ) . '/../conf/_config.php';

require_once $inc_path . '_main.inc.php';
require_once $inc_path . 'items/model/_itemlist.class.php';
require_once $inc_path . 'files/model/_file.funcs.php';

require_once( 'rfc822_addresses.php' );
require_once( 'mime_parser.php' );


if ( !$Settings -> get( 'eblog_enabled' ) )
{
	echo T_( 'Blog by email feature is not enabled.' );
	debug_info();
	exit();
}


/**
 * this script could be called with parameter test to specify what should be done and what level
 * of debug output to generate.
 * <p>
 * <ul>
 * <li>level 0: default. Process everything, no debug output, no html (called by cronjob)
 * <li>level 1: Test only connection to server, do not process messages
 * <li>level 2: additionally process messaged, but do not post
 * <li>level 3: do everything with extended verbosity
 * </ul>
 * @global integer $test
 */
global $test;
param( 'test', 'integer', 0 );


/**
 * Subject of the current email message
 * @global string $subject
 */
global $subject;

/**
 * post date of current message
 * @global mixed $post_date
 */	
global $post_date;

/**
 * meassage content of current email that is going to be posted
 * @global string $content
 */
global $content;


/**
 * define colour constants for messages
 */
define( 'INFO', 'black' );
define( 'WARNING', 'orange' );
define( 'ERROR', 'red' );

$test_connection_only = false;
$str_failure = '';

$do_real_posting = ! $Settings->get( 'eblog_test_mode' ); 
if ( ! $do_real_posting )
{
	echo_message( T_('You configured test mode in the settings. Nothing will be posted to the database/mediastore nor will your inbox be altered.'), WARNING, 0, true );
}

// if it's not called by a logged in user override test settings
if ( !isset( $current_User ) )
{
	$test = 0;
}
elseif ( !$current_User -> check_perm( 'options', 'edit', true ) )
{
	$test = 0;
}

if ( $test > 0 )
{
	//error_reporting (0);
	
	// @TODO I don't find a header to include for this popup window. There should exist one in b2evo. So right now no valid HTML
	$page_title = T_( 'Blog by email' );
	echo '<html><head><title>' . $page_title . '</title></head><body>';
}

/**
 * print out a debugging message
 *
 * print out a debugging message with optional html colour added.
 *
 * @global integer the global test level in use
 * @param  string $strmessage The message to print
 * @param  string $color optional colour so use
 * @param  integer $level optional level to limit output to that level
 * @param  bool $newline insert a newline after message
 */
function echo_message( $strmessage , $color = '', $level = 0, $newline = false )
{
 	global $test;

	if ( $level <= $test )
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
		
		if ( $newline )
		{		
			echo "<br>\n";
		}
	}
}

/**
 * provide sys_get_temp_dir for older versions of PHP.
 * 
 * code posted on php.net by minghong at gmail dot com
 * Based on http://www.phpit.net/
 * article/creating-zip-tar-archives-dynamically-php/2/
 *
 * @return string path to system temporary directory
 */ 
if ( !function_exists( 'sys_get_temp_dir' ) )
{
	function sys_get_temp_dir()

	{
		// Try to get from environment variable
		if ( !empty( $_ENV['TMP'] ) )
		{
			return realpath( $_ENV['TMP'] );
		}
		else if ( !empty( $_ENV['TMPDIR'] ) )
		{
			return realpath( $_ENV['TMPDIR'] );
		}
		else if ( !empty( $_ENV['TEMP'] ) )
		{
			return realpath( $_ENV['TEMP'] );
		}

		// Detect by creating a temporary file
		else
		{
			// Try to use system's temporary directory
			// as random name shouldn't exist
			$temp_file = tempnam( md5( uniqid( rand(), true ) ), '' );
			if ( $temp_file )
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
 * @param  mixed $dir base path to new directory
 * @param  mixed $prefix prefix random number with this
 * @param  mixed $mode permissions to use
 * @return mixed path to created directory
 */
function tempdir( $dir, $prefix = 'tmp', $mode = 0700 )

{
	if ( substr( $dir, -1 ) != '/' ) $dir .= '/';

	do
	{
		$path = $dir . $prefix . mt_rand();
	} while ( !mkdir( $path, $mode ) );

	return $path;
}



/**
 * process Header information like subject and date of a mail
 *
 * @global mixed subject gets written to that
 * @global mixed the date of the mail is inserted here
 * @global GeneralSettings the global settings
 * @param  object $header header as returned by imap_headerinfo
 * @return bool true if valid subject prefix is detected
 */
function processHeader( $header )
{

	// write to these globals
	global $subject, $post_date;

	// read these globals
	global $Settings;

	$subject = utf8_decode( imap_utf8( $header -> subject ) );

	echo_message( T_( 'Subject' ) . ': ' . $subject, INFO, 3, true );
	if ( !preg_match( '/' . $Settings -> get( 'eblog_subject_prefix' ) . '/', $subject ) )
	{
		echo_message( '&#x2718; ' . T_( 'The subject prefix is not ' ) . '"' . $Settings -> get( 'eblog_subject_prefix' ) . '"', WARNING, 2, true );
		return false;
	}

	// todo: review the post_date code
	// of the form '20 Mar 2002 20:32:37'
	$ddate = trim( $header -> Date );
	if ( strpos( $ddate, ',' ) ){
		$ddate = trim( substr( $ddate, strpos( $ddate, ',' ) + 1, strlen( $ddate ) ) );
	}
	$date_arr = explode( ' ', $ddate );
	$date_time = explode( ':', $date_arr[3] );

	$ddate_H = $date_time[0];
	$ddate_i = $date_time[1];
	$ddate_s = $date_time[2];

	$ddate_m = $date_arr[1];
	$ddate_d = $date_arr[0];
	$ddate_Y = $date_arr[2];

	$dmonths = array( 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' );

	for ( $i = 0; $i < 12; $i++ ){

		if ( $ddate_m == $dmonths[$i] ){
			$ddate_m = $i + 1;
		}
	}
	$ddate_U = mktime( $ddate_H, $ddate_i, $ddate_s, $ddate_m, $ddate_d, $ddate_Y );
	$post_date = date( 'Y-m-d H:i:s', $ddate_U );

	return true;
}



/**
 * process attachments by saving into media directory and optionally creating image tag in post
 *
 * @global string message content that is optionally manipulated by adding image tags
 * @global bool do we really post?
 * @global object global Settings
 * @param  array $mailAttachments array containing path to attachment files
 * @param  string $mediadir path to media directory of blog as seen by file system
 * @param  string $media_url url to media directory as seen by user
 * @param  bool $add_img_tags should img tags be added
 * @return bool true for sucessfull execution
 */
function processAttachments( $mailAttachments, $mediadir, $media_url, $add_img_tags = true )
{

	global $content;
	global $do_real_posting;
	global $Settings;
	
	$return = true;

	echo_message( T_( 'Processing attachments' ), INFO, 3, true );
	
	foreach( $mailAttachments as $attachment )
	{
		$filename = strtolower( $attachment["FileName"] );
		if ( $filename == '' )
		{
			$filename = tempnam( $mediadir, 'upload' ) . "." . $attachment["SubType"];
			echo_message( '&#x279C; ' . T_( 'Attachment without name. Using ' ) . htmlspecialchars( $filename ), WARNING, 2, true );
		}
		$filename = preg_replace( '/[^a-z0-9\-_.]/', '-', $filename );

		// Check valid filename/extension: (includes check for locked filenames)
		if ( $error_filename = validate_filename( $filename, false ) )
		{
			echo_message( '&#x2718; ' . T_( 'Invalid filename' ), WARNING, 2, true );
			$return = false; // return: at least one error. try with next attachment
			continue;
		}

		// if file exists count up a number
		$cnt = 0;
		$checkName = $filename;
		while ( file_exists( $mediadir . $checkName ) )
		{
			$checkName = substr( $filename, 0, strrpos( $filename, "." ) ) . "-$cnt" . strrchr( $filename, "." );
			echo_message( '&#x2718; ' . T_( 'file already exists. Changing filename to: ' ) . $checkName , WARNING, 2, true );
			++$cnt;
		}
		$filename = $checkName;

		echo_message( '&#x279C; ' . T_( 'Saving file to: ') . htmlspecialchars( $mediadir . $filename  ), INFO, 3, true );
		if ( $do_real_posting )
		{
			if ( !rename( $attachment["DataFile"], $mediadir . $filename ) )
			{
				echo_message( '&#x2718; ' . T_( 'Problem saving upload to ') . htmlspecialchars( $mediadir . $filename ), WARNING, 2, true );
				$return = false; // return: at least one error. try with next attachment
				continue;
			}
	
			// chmod uploaded file:
			$chmod = $Settings -> get( 'fm_default_chmod_file' );
			@chmod( $mediadir . $filename, octdec( $chmod ) );
		}
		
		// TODO: think about config option to use a link insted of img tag. That could also handle other file types.
		$content .= "\n<img src=\"" . $media_url . $filename . "\"/>\n";
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
	$title =	xmlrpc_getposttitle( $content );
	if ( $title == '' )
	{
		$title = $alternate_title;
	}
	
	return $title;
}


// MAIN ROUTINE
switch ( $Settings -> get( 'eblog_method' ) )
{
	case 'pop3':
		echo T_( 'No longer supported!' ), "<br />\n";
		break;

	case 'pop3a':
		// --------------------------------------------------------------------
		// eblog_method = POP3 through IMAP extension (experimental)
		// --------------------------------------------------------------------
		if ( ! extension_loaded( 'imap' ) )
		{
			echo T_( 'The php_imap extension is not available to php on this server. Please configure a different email retrieval method on the Features tab.' );
			exit;
		}
		echo_message( T_( 'Connecting and authenticating to mail server' ), INFO, 1, true );

		// Prepare the connection string
		$port = $Settings -> get( 'eblog_server_port' ) ? $Settings -> get( 'eblog_server_port' ) : '110';

		// @TODO: add setting to configure SSL/TLS
		$mailserver = '{' . $Settings -> get( 'eblog_server_host' ) . ':' . $port . '/pop3/notls}INBOX';

		// Connect to mail server
		$mbox = imap_open( $mailserver, $Settings -> get( 'eblog_username' ), $Settings -> get( 'eblog_password' ) );
		if ( ! $mbox )
		{
			echo_message( '&#x2718; ' . T_( 'Connection failed: ' ) . imap_last_error(), $test > 0 ? ERROR : '', 0, true );
			exit();
		}
		@imap_errors();

		// damn gmail... grr
		// $mbox = imap_open ("{pop.gmail.com:995/pop3/ssl/novalidate-cert}INBOX", "xxx@gmail.com", "xxx") or die( T_('Connection failed: ') . imap_last_error() );

		echo_message( '&#x2714; ' . T_( 'Success' ), 'green', 1, true );
		if ( $test == 1 )
		{
			echo_message( T_( 'All Tests completed' ), INFO, 1, true );
			imap_close( $mbox );
			exit();
		}


		// Read messages from server
		echo_message( T_( 'Reading messages from server' ), INFO, 2, true );
		$imap_obj = imap_check( $mbox );
		echo_message( ' &#x279C; ' . $imap_obj -> Nmsgs . ' ' . T_( 'messages' ), INFO, 2, true );

		for ( $index = 1; $index <= $imap_obj -> Nmsgs; $index++ )
		{
			echo_message( '<b>' . T_( 'Message' ) . " #$index" . ':</b>', INFO, 2, true );
				
				
			// retrieve and process header. continue processing only if subject prefix is matching
			$imap_header = imap_headerinfo( $mbox, $index );
			if ( !processHeader( $imap_header ) )
			{
				continue; // skip to next message
			}
				
			$strbody = "";
			$hasAttachment = false;
			$postAttachments = array();
				
			// save mail to disk because with attachments could take up much RAM
			$tmpMIME = tempnam( '/tmp', 'b2evoMail' );
			imap_savebody( $mbox, $tmpMIME, $index );
				
			$tmpDirMIME = tempdir( sys_get_temp_dir(), 'b2evo' );
			$mimeParser = new mime_parser_class;
			$mimeParser -> mbox = 0; // Set to 0 for parsing a single message file
			$mimeParser -> decode_bodies = 1;
			$mimeParser -> ignore_syntax_errors = 1;
			$mimeParser -> extract_addresses = 0;
			$MIMEparameters = array(
				'File' => $tmpMIME,
				'SaveBody' => $tmpDirMIME, // Save the message body parts to a directory
				'SkipBody' => 1, // Do not retrieve or save message body parts
			);
				
			if ( !$mimeParser -> Decode( $MIMEparameters, $decodedMIME ) )
			{
				echo_message( '&#x2718; ' .T_( 'MIME message decoding error: ' ) . $mimeParser -> error . T_(' at position ' ) . $mimeParser -> error_position, $test > 0 ? ERROR : '', 0, true );
				rmdir_r( $tmpDirMIME );
				unlink( $tmpMIME );
				continue;
			}
			else
			{
				echo_message( '&#x2714; ' . T_( 'MIME message decoding successful.'), INFO, 3, true );
				if ( ! $mimeParser -> Analyze( $decodedMIME[0], $parsedMIME ) )
				{
					echo_message( '&#x2718; ' . T_('MIME message analyse error: ') . $mimeParser -> error, $test > 0 ? ERROR : '', 0, true );
					// var_dump($parsedMIME);
					rmdir_r( $tmpDirMIME );
					unlink( $tmpMIME );
					continue;
				}

				// the following helps me debugging
				if (false)
				{
					echo "<pre>\n";
					var_dump($decodedMIME[0]);
					var_dump($parsedMIME);
					echo "</pre>\n";
				}

				// TODO: handle type == "message" recursively

				// mail is html
				if ( $parsedMIME["Type"] == "html" ){
					foreach ( $parsedMIME["Alternative"] as $alternative ){
						if ( $alternative["Type"] == "text" ){
							echo_message( T_( 'HTML alternative message part saved as ' ) . $alternative["DataFile"], INFO, 3, true );
							$strbody = imap_qprint( file_get_contents( $alternative["DataFile"] ) );
							break; // stop after first alternative
						}
					}
				}

				// mail is plain text
				if ( $parsedMIME["Type"] == "text" )
				{
					echo_message( T_( 'Plain-text message part saved as ' ) . $parsedMIME["DataFile"], INFO, 3, true );
					$strbody = imap_qprint( file_get_contents( $parsedMIME["DataFile"] ) );
				}

				// Check for attachments
				if ( isset( $parsedMIME["Attachments"] ) )
				{
					foreach( $parsedMIME["Attachments"] as $attachment )
					{
						// TODO: handle other file types
						if ( $attachment["Type"] == "image" )
						{
							echo_message( T_( 'Attached image: ' ) . $attachment["FileName"] . T_( ' stored as ' ) . $attachment["DataFile"], INFO, 3, true );
							$hasAttachment = true;
							array_push( $postAttachments, $attachment );
						}
					}
				}

				$warning_count = count( $mimeParser->warnings ); 
				if ( $warning_count > 0 )
				{
					echo_message( '&#x2718; ' . $warning_count . T_( ' warnings during decode: ' ), WARNING, 2, true );
					for( $warning = 0, Reset( $mimeParser -> warnings ); $warning < $warning_count; Next( $mimeParser -> warnings ), $warning++ )
					{
						$w = Key( $mimeParser -> warnings );
						echo_message( '&#x2718; ' . T_( 'Warning: ' ) . $mimeParser -> warnings[$w] . T_( ' at position ' ) . $w, WARNING, 3, true );
					}
				}
			}
			unlink( $tmpMIME );
				
			// var_dump($strbody);
			// process body. First fix different line-endings (dos, mac, unix), remove double newlines
			$strbody = str_replace( "\r", "\n", $strbody );
			$strbody = str_replace( "\n\n", "\n", $strbody );
				
			$a_body = split( "\n", $strbody, 2 );
			$a_authentication = split( ':', $a_body[0] );
			$content = trim( $a_body[1] );
				
			echo_message( T_( 'Message content:' ) . '<code>' . htmlspecialchars( $content ) . '</code>', INFO, 3, true );
				
			$user_login = trim( $a_authentication[0] );
			$user_pass = @trim( $a_authentication[1] );
				
			echo_message( T_( 'Authenticating user' ) . ': ' . $user_login, INFO, 3, true );
			// authenticate user
			if ( !user_pass_ok( $user_login, $user_pass ) )
			{
				echo_message( '&#x2718; ' . T_( 'Authentication failed for user ' ) . htmlspecialchars( $user_login ), $test > 0 ? ERROR : '', 0, true );
				echo_message( '&#x2718; ' . T_( 'Wrong login or password.' ) . ' ' . T_( 'First line of text in email must be in the format "username:password"' ), ERROR, 3, true );
				rmdir_r( $tmpDirMIME );
				continue;
			}
			else
			{
				echo_message( '&#x2714; ' . T_( 'Success' ), 'green', 3, true );
			}
				
			$subject = trim( str_replace( $Settings -> get( 'eblog_subject_prefix' ), '', $subject ) );
				
			// remove content after terminator
			$eblog_terminator = $Settings -> get( 'eblog_body_terminator' );
			if ( !empty( $eblog_terminator ) )
			{
				$os_terminator = strpos( $content, $eblog_terminator );
				if ( $os_terminator )
				{
					$content = substr( $content, 0, $os_terminator );
				}
			}
				
			// check_html_sanity needs local user set.
			$UserCache = & get_Cache( 'UserCache' );
			$current_User = & $UserCache -> get_by_login( $user_login );
				
			$post_title = get_post_title( $content, $subject );
				
			if ( ! ( $post_category = xmlrpc_getpostcategory( $content ) ) )
			{
				$post_category = $Settings -> get( 'eblog_default_category' );
			}
			echo_message( T_( 'Category ID' ) . ': ' . htmlspecialchars( $post_category ), INFO, 3, true );
				
			$content = xmlrpc_removepostdata( $content );
			$blog_ID = get_catblog( $post_category ); // TODO: should not die, if cat does not exist!
			echo_message( T_( 'Blog ID' ) . ': ' . $blog_ID, INFO, 3, true );
				
			$BlogCache = & get_Cache( 'BlogCache' );
			$Blog = $BlogCache -> get_by_ID( $blog_ID, false, false );
				
			if ( empty( $Blog ) )
			{
				echo_message( '&#x2718; ' . T_( 'Blog not found: ' ) . htmlspecialchars( $blog_ID ), $test > 0 ? ERROR : '', 0, true );
				rmdir_r( $tmpDirMIME );
				continue;
			}
				
				
			// Check permission:
			echo_message( sprintf( T_( 'Checking permissions for user &laquo;%s&raquo; to post to Blog #%d' ), $user_login, $blog_ID ), INFO, 3, true );
			if ( !$current_User -> check_perm( 'blog_post!published', 'edit', false, $blog_ID )
			|| ( $hasAttachment && !$current_User -> check_perm( 'files', 'add', false ) )
			)
			{
				echo_message( '&#x2718; ' . T_( 'Permission denied' ), $test > 0 ? ERROR : '', 0, true );
				rmdir_r( $tmpDirMIME );
				continue;
			}
			else
			{
				echo_message( '&#x2714; ' . T_( 'Pass' ), 'green', 3, true );
			}
				
			// handle attachments
			if ( $hasAttachment )
			{
				$mediadir = $Blog->get_media_dir();
				if ( $mediadir )
				{
					processAttachments( $postAttachments, $mediadir, $Blog->get_media_url(), true );
				}
				else
				{
					echo_message( '&#x2718; ' . T_( 'Unable to access media directory. No attachments processed' ), $test > 0 ? ERROR : '', 0, true );
				}
			}
				
			// CHECK and FORMAT content
			$post_title = check_html_sanity( trim( $post_title ), 'posting', false );
			$content = check_html_sanity( trim( $content ), 'posting', $Settings -> get( 'AutoBR' ) );

			if ( $Messages -> display( T_( 'Cannot post, please correct these errors:' ), '', true, 'error' ) )
			{
				$Messages -> reset();
				rmdir_r( $tmpDirMIME );
				continue;
			}

			if ( $do_real_posting )
			{
				// INSERT NEW POST INTO DB:
				$edited_Item = & new Item();
	
				$post_ID = $edited_Item -> insert( $current_User -> ID, $post_title, $content, $post_date, $post_category, array(), 'published', $current_User -> locale );
	
				// Execute or schedule notifications & pings:
				$edited_Item -> handle_post_processing();
			}

			echo_message( '&#x2714; ' . T_( 'Message posting successfull.' ), 'green', 2, true );
			echo_message( '&#x279C; ' . T_( 'Post title: ' ) . htmlspecialchars( $post_title ), INFO, 3, true );
			echo_message( '&#x279C; ' . T_( 'Post content: ' ) . htmlspecialchars( $content ), INFO, 3, true );
				
			rmdir_r( $tmpDirMIME );
				
			echo_message( T_( 'Marking message for deletion' ) . ": $index", INFO, 3, true );
			imap_delete( $mbox, $index );
		}

		if ( $do_real_posting )
		{
			imap_expunge( $mbox );
			echo_message( T_( 'Deleting processed messages from inbox' ), INFO, 2, true );
		}

		imap_close( $mbox );

		break;


	default:
		echo T_( 'Blog by email feature not configured' );
		break;
}

if ( $test > 0 )
{
	// @TODO: I don't find a footer to include in this popup. b2evo should include one...
	echo '</body>';
}

?>
