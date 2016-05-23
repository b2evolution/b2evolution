<?php
/**
 * This file implements the post by mail support functions.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Print out a debugging message with optional HTML color added
 *
 * @param string Message
 * @param string
 */
function pbm_msg( $message, $cron = false )
{
	global $is_web, $result_message, $pbm_messages;

	// Log all messages to $pbm_messages no matter if we are in cron mode or not
	// We may use this report later, display or send to the blog owner
	$pbm_messages[] = $message;

	if( $cron )
	{	// We are in cron mode, log the message
		if( $is_web )
			$message .= '<br />';

		$result_message .= $message."\n";
	}
}

/**
 * Connect to an IMAP or POP mail server
 *
 * @param boolean TRUE if script is executed by cron
 * @return resource $mbox
 */
function pbm_connect( $cron = false )
{
	if( ! extension_loaded( 'imap' ) )
	{	// Exit here if imap extension is not loaded:
		pbm_msg( '<b class="red">'.( 'IMAP extension is NOT loaded! ').'</b>', $cron );
		return false;
	}

	global $Settings;

	$host = $Settings->get('eblog_server_host').':'.$Settings->get('eblog_server_port');
	$mailserver = '{'.$host;

	pbm_msg( sprintf( ('Connecting and authenticating to mail server %s'), '<b>'.$host.'</b>' ), $cron );

	switch( $Settings->get('eblog_encrypt') )
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

	switch( $Settings->get('eblog_method') )
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

	if( $Settings->get('eblog_novalidatecert') )
	{
		$mailserver .= '/novalidate-cert';
	}

	$mailserver .= '}INBOX';

	// Connect to mail server (one retry)
	$mbox = @imap_open( $mailserver, $Settings->get('eblog_username'), $Settings->get('eblog_password'), NULL, 1 );

	if( is_null(@get_resource_type($mbox)) )
	{	// Not a resource
		$error = imap_errors();
		if( is_array($error) )
		{
			$error = implode( "<br />\n", $error );
		}

		pbm_msg( sprintf( ('Connection failed: %s'), $error ), $cron );
		return false;
	}
	pbm_msg( '<b class="green">'.('Successfully connected!').'</b>', $cron );

	@imap_errors();

	return $mbox;
}


/**
 * Read messages from server and create posts
 *
 * @param resource $mbox created by pbm_connect() (by reference)
 * @param integer the number of messages to process
 * @param boolean TRUE if script is executed by cron
 * @return boolean true on success
 */
function pbm_process_messages( & $mbox, $limit, $cron = false )
{
	global $Settings, $debug;
	global $pbm_item_files, $pbm_messages, $pbm_items, $post_cntr, $del_cntr, $is_cron_mode;

	// This may take a very long time if there are many messages; No execution time limit:
	set_max_execution_time(0);

	// Are we in test mode?
	$test_mode_on = $Settings->get('eblog_test_mode');

	$post_cntr = 0;
	$del_cntr = 0;
	for( $index = 1; $index <= $limit; $index++ )
	{	// Repeat for as many messages as allowed...
		pbm_msg( '<hr /><h3>'.sprintf( ('Processing message %s:'), '#'.$index ).'</h3>', $cron );

		$html_body = '';
		$strbody = '';
		$hasAttachment = false;
		$hasRelated = false;

		$pbm_item_files = array(); // reset the value for each new Item

		// Save email to a temporary file on hard drive, otherwise BIG attachments may take a lot of RAM:
		if( ! ($tmpMIME = tempnam( sys_get_temp_dir(), 'b2evoMail' )) )
		{
			pbm_msg( ('Could not create temporary file.'), $cron );
			continue;
		}
		// Save the whole body of a specific message from the mailbox:
		imap_savebody( $mbox, $tmpMIME, $index );

		// Create random temp directory for message parts:
		$tmpDirMIME = pbm_tempdir( sys_get_temp_dir(), 'b2evo_' );

		// Instanciate mime_parser.php library:
		$mimeParser = new mime_parser_class();
		$mimeParser->mbox = 0;						// Set to 0 for parsing a *single* RFC 2822 message
		$mimeParser->decode_headers = 1;			// Set to 1 if it is	necessary to decode message headers that may have non-ASCII	characters and use other character set encodings
		$mimeParser->ignore_syntax_errors = 1;	// ignore syntax errors in	malformed messages.
		$mimeParser->extract_addresses = 0;

		$MIMEparameters = array(
				'File' => $tmpMIME,
				'SaveBody' => $tmpDirMIME,	// Save message body parts to a directory
				'SkipBody' => 1,			// Do not retrieve or save message body parts
			);

		// Associative array to specify parameters for the messagedata parsing and decoding operation.
		$MIMEparameters = array(
				'File' => $tmpMIME,			// Name of the file from which the message data will be read.
				'SaveBody' => $tmpDirMIME,	// Save message body parts to a directory
				'SkipBody' => 1,				// 1 means the information about the message body part structure is returned in $decodedMIME below but it does not return any body data.
			);

		// STEP 1: Parse and decode message data and retrieve its structure:
		if( !$mimeParser->Decode( $MIMEparameters, $decodedMIME ) )
		{	// error:
			pbm_msg( sprintf( ('MIME message decoding error: %s at position %d.'), $mimeParser->error, $mimeParser->error_position ), $cron );
			rmdir_r( $tmpDirMIME );
			unlink( $tmpMIME );
			continue;
		}
		else
		{	// the specified message data was parsed successfully:
			pbm_msg( ('MIME message decoding successful'), $cron );

			// STEP 2: Analyze (the first) parsed message to describe its contents:
			if( ! $mimeParser->Analyze( $decodedMIME[0], $parsedMIME ) )
			{	// error:
				pbm_msg( sprintf( ('MIME message analyze error: %s'), $mimeParser->error ), $cron );
				rmdir_r( $tmpDirMIME );
				unlink( $tmpMIME );
				continue;
			}

			// Get message $subject and $post_date from headers (by reference)
			if( ! pbm_process_header( $parsedMIME, $subject, $post_date, $cron ) )
			{	// Couldn't process message headers:
				rmdir_r( $tmpDirMIME );
				unlink( $tmpMIME );
				continue;
			}

			// TODO: handle type == "message" recursively
			// sam2kb> For some reason imap_qprint() demages HTML text... needs more testing
			// yura> I replaced imap_qprint() with quoted_printable_decode() to avoid notices about invalid quoted-printable sequence
			// yura> imap_qprint() and quoted_printable_decode() do empty the message text, thus they were deleted.

			if( $parsedMIME['Type'] == 'html' )
			{	// Mail is HTML:
				if( $Settings->get('eblog_html_enabled') )
				{	// HTML posting enabled
					if( $debug )
					{	// Display this info only in debug mode:
						pbm_msg( sprintf( ('HTML message part saved as %s'), $parsedMIME['DataFile'] ), $cron );
					}
					$html_body = file_get_contents( $parsedMIME['DataFile'] );
				}

				foreach( $parsedMIME['Alternative'] as $alternative )
				{	// First try to get HTML alternative (when possible)
					if( $alternative['Type'] == 'html' && $Settings->get('eblog_html_enabled') )
					{	// HTML text:
						if( $debug )
						{	// Display this info only in debug mode:
							pbm_msg( sprintf( ('HTML alternative message part saved as %s'), $alternative['DataFile'] ), $cron );
						}
						$strbody = file_get_contents( $alternative['DataFile'] );
						break; // stop after first alternative
					}
					elseif( $alternative['Type'] == 'text' )
					{	// Plain text:
						if( $debug )
						{	// Display this info only in debug mode:
							pbm_msg( sprintf( ('Text alternative message part saved as %s'), $alternative['DataFile'] ), $cron );
						}
						$strbody = file_get_contents( $alternative['DataFile'] );
						break; // stop after first alternative
					}
				}
			}
			elseif( $parsedMIME['Type'] == 'text' )
			{	// Mail is plain text:
				if( $debug )
				{	// Display this info only in debug mode:
					pbm_msg( sprintf( ('Plain-text message part saved as %s'), $parsedMIME['DataFile'] ), $cron );
				}
				$strbody = file_get_contents( $parsedMIME['DataFile'] );
			}

			// Check for attachments:
			if( ! empty( $parsedMIME['Attachments'] ) )
			{
				$hasAttachment = true;
				foreach( $parsedMIME['Attachments'] as $file )
				{
					pbm_msg( sprintf( ('Attachment: %s stored as %s'), $file['FileName'], $file['DataFile'] ), $cron );
				}
			}

			// Check for inline images:
			if( ! empty( $parsedMIME['Related'] ) )
			{
				$hasRelated = true;
				foreach( $parsedMIME['Related'] as $file )
				{
					pbm_msg( sprintf( ('Related file with content ID: %s stored as %s'), $file['ContentID'], $file['DataFile'] ), $cron );
				}
			}

			if( count( $mimeParser->warnings ) > 0 )
			{
				pbm_msg( '<h4>'.sprintf( ('%d warnings during decode:'), count( $mimeParser->warnings ) ).'</h4>', $cron );
				foreach( $mimeParser->warnings as $k => $v )
				{
					pbm_msg( sprintf( ('Warning: %s at position %s'), $v, $k ), $cron );
				}
			}
		}
		unlink( $tmpMIME );

		if( empty( $html_body ) )
		{	// Plain-text message
			pbm_msg( sprintf( ('Message type: %s'), 'TEXT' ), $cron );
			pbm_msg( sprintf( ('Message body: %s'), '<pre style="font-size:10px">'.htmlspecialchars( $strbody ).'</pre>' ), $cron );

			// Process body. First fix different line-endings (dos, mac, unix), remove double newlines
			$content = str_replace( array( "\r", "\n\n" ), "\n", trim( $strbody ) );

			// First see if there's an <auth> tag with login and password
			if( ( $auth = pbm_get_auth_tag( $content ) ) === false )
			{	// No <auth> tag, let's detect legacy "username:password" on the first line
				$a_body = explode( "\n", $content, 2 );

				// tblue> splitting only into 2 parts allows colons in the user PW
				// Note: login and password cannot include '<' !
				$auth = explode( ':', strip_tags($a_body[0]), 2 );

				// Drop the first line with username and password
				$content = $a_body[1];
			}
		}
		else
		{	// HTML message
			pbm_msg( sprintf( ('Message type: %s'), 'HTML' ), $cron );

			if( ( $parsed_message = pbm_prepare_html_message( $html_body, $cron ) ) === false )
			{	// No 'auth' tag provided, skip to the next message
				rmdir_r( $tmpDirMIME );
				continue;
			}
			list($auth, $content) = $parsed_message;
		}

		// TODO: dh> should the password really get trimmed here?!
		$user_pass = isset($auth[1]) ? trim( remove_magic_quotes($auth[1]) ) : NULL;
		$user_login = trim( utf8_strtolower(remove_magic_quotes($auth[0])) );

		if( empty($user_login) || empty($user_pass) )
		{
			pbm_msg( sprintf( ('Please add username and password in message body in format %s.'),
						'"&lt;auth&gt;username:password&lt;/auth&gt;"' ), $cron );

			rmdir_r( $tmpDirMIME );
			continue;
		}

		// Authenticate user
		pbm_msg( ('Authenticating User').': &laquo;'.$user_login.'&raquo;', $cron );
		$pbmUser = & pbm_validate_user_password( $user_login, $user_pass );
		if( ! $pbmUser )
		{
			pbm_msg( sprintf( ( 'Authentication failed for user &laquo;%s&raquo;' ), htmlspecialchars( $user_login ) ), $cron );
			rmdir_r( $tmpDirMIME );
			continue;
		}

		$pbmUser->get_Group(); // Load group
		if( ! empty($is_cron_mode) )
		{	// Assign current User if we are in cron mode. This is needed in order to check user permissions
			global $current_User;
			$current_User = clone $pbmUser;
		}

		// Activate User's locale
		locale_activate( $pbmUser->get('locale') );

		pbm_msg( '<b class="green">'.('Success').'</b>', $cron );

		if( $post_categories = xmlrpc_getpostcategories( $content ) )
		{
			$main_cat_ID = array_shift($post_categories);
			$extra_cat_IDs = $post_categories;

			pbm_msg( ('Extra categories').': '.implode( ', ', $extra_cat_IDs ), $cron );
		}
		else
		{
			$main_cat_ID = $Settings->get('eblog_default_category');
			$extra_cat_IDs = array();
		}
		pbm_msg( ('Main category ID').': '.$main_cat_ID, $cron );

		$ChapterCache = & get_ChapterCache();
		$pbmChapter = & $ChapterCache->get_by_ID( $main_cat_ID, false, false );
		if( empty($pbmChapter) )
		{
			pbm_msg( sprintf( ('Requested category %s does not exist!'), $main_cat_ID ), $cron );
			rmdir_r( $tmpDirMIME );
			continue;
		}

		$blog_ID = $pbmChapter->blog_ID;
		pbm_msg( T_('Blog ID').': '.$blog_ID, $cron );

		$BlogCache = & get_BlogCache();
		$pbmBlog = & $BlogCache->get_by_ID( $blog_ID, false, false );
		if( empty($pbmBlog) )
		{
			pbm_msg( sprintf( ('Requested collection %s does not exist!'), $blog_ID ), $cron );
			rmdir_r( $tmpDirMIME );
			continue;
		}

		// Check permission:
		pbm_msg( sprintf( ('Checking permissions for User &laquo;%s&raquo; to post to Collection #%d'), $user_login, $blog_ID ), $cron );
		if( !$pbmUser->check_perm( 'blog_post!published', 'edit', false, $blog_ID ) )
		{
			pbm_msg( T_('Permission denied.'), $cron );
			rmdir_r( $tmpDirMIME );
			continue;
		}

		if( ($hasAttachment || $hasRelated) && !$pbmUser->check_perm( 'files', 'add', false, $blog_ID ) )
		{
			pbm_msg( ( 'You have no permission to add/upload files.' ), $cron );
			rmdir_r( $tmpDirMIME );
			continue;
		}
		pbm_msg( '<b class="green">'.('Success').'</b>', $cron );

		// Remove content after terminator
		$eblog_terminator = $Settings->get('eblog_body_terminator');
		if( !empty( $eblog_terminator ) && ($os_terminator = utf8_strpos( $content, $eblog_terminator )) !== false )
		{
			$content = utf8_substr( $content, 0, $os_terminator );
		}

		$post_title = pbm_get_post_title( $content, $subject );

		// Remove 'title' and 'category' tags
		$content = xmlrpc_removepostdata( $content );

		// Remove <br> tags from string start and end
		// We do it here because there might be extra <br> left after deletion of <auth>, <category> and <title> tags
		$content = preg_replace( array( '~^(\s*<br[\s/]*>\s*){1,}~i', '~(\s*<br[\s/]*>\s*){1,}$~i' ), '', $content );

		if( $hasAttachment || $hasRelated )
		{	// Handle attachments
			if( isset($GLOBALS['files_Module']) )
			{
				if( $mediadir = $pbmBlog->get_media_dir() )
				{
					if( $hasAttachment )
					{
						pbm_process_attachments( $content, $parsedMIME['Attachments'], $mediadir,
									$pbmBlog->get_media_url(), $Settings->get('eblog_add_imgtag'), 'attach', $cron );
					}
					if( $hasRelated )
					{
						pbm_process_attachments( $content, $parsedMIME['Related'], $mediadir,
									$pbmBlog->get_media_url(), true, 'related', $cron );
					}
				}
				else
				{
					pbm_msg( ('Unable to access media directory. No attachments processed.'), $cron );
				}
			}
			else
			{
				pbm_msg( ('Files module is disabled or missing!'), $cron );
			}
		}

		// CHECK and FORMAT content
		global $Plugins;
		$renderer_params = array( 'Blog' => & $pbmBlog, 'setting_name' => 'coll_apply_rendering' );
		$renderers = $Plugins->validate_renderer_list( $Settings->get('eblog_renderers'), $renderer_params );

		pbm_msg( sprintf( ('Applying the following text renderers: %s'), implode( ', ', $renderers ) ), $cron );

		// Do some optional filtering on the content
		// Typically stuff that will help the content to validate
		// Useful for code display
		// Will probably be used for validation also
		$Plugins_admin = & get_Plugins_admin();
		$params = array( 'object_type' => 'Item', 'object_Blog' => & $pbmBlog );
		$Plugins_admin->filter_contents( $post_title /* by ref */, $content /* by ref */, $renderers, $params );

		pbm_msg( sprintf( ('Filtered post content: %s'), '<pre style="font-size:10px">'.htmlspecialchars( $content ).'</pre>' ), $cron );

		$context = $Settings->get('eblog_html_tag_limit') ? 'commenting' : 'posting';
		$post_title = check_html_sanity( $post_title, $context, $pbmUser );
		$content = check_html_sanity( $content, $context, $pbmUser );

		global $Messages;
		if( $Messages->has_errors() )
		{
			// Make it easier for user to find and correct the errors
			pbm_msg( "\n".sprintf( ('Processing message: %s'), $post_title ), $cron );
			pbm_msg( $Messages->get_string( ('Cannot post, please correct these errors:'), 'error' ), $cron );

			$Messages->clear();
			rmdir_r( $tmpDirMIME );
			continue;
		}

		if( $test_mode_on )
		{	// Test mode
			pbm_msg( '<b class="green">'.('It looks like the post can be successfully saved in the database. However we will not do it in test mode.').'</b>', $cron );
		}
		else
		{
			load_class( 'items/model/_item.class.php', 'Item' );

			global $pbm_items, $DB, $localtimenow;

			$post_status = 'published';

			pbm_msg( '<h4>'.sprintf( ('Saving item "%s" in the database'), $post_title ).'</h4>', $cron );

			// INSERT NEW POST INTO DB:
			$edited_Item = new Item();

			$edited_Item->set_creator_User( $pbmUser );
			$edited_Item->set( $edited_Item->lasteditor_field, $pbmUser->ID );

			$edited_Item->set( 'title', $post_title );
			$edited_Item->set( 'content', $content );
			$edited_Item->set( 'datestart', $post_date );
			$edited_Item->set( 'datemodified', date('Y-m-d H:i:s',$localtimenow) );

			$edited_Item->set( 'main_cat_ID', $main_cat_ID );
			$edited_Item->set( 'extra_cat_IDs', $extra_cat_IDs );
			$edited_Item->set( 'status', $post_status );
			$edited_Item->set( 'locale', $pbmUser->locale );
			$edited_Item->set( 'renderers', $renderers );

			// INSERT INTO DB:
			$edited_Item->dbinsert();

			pbm_msg( sprintf( ('Item created?: %s'), ( isset( $edited_Item->ID ) ? 'yes' : 'no' ) ), $cron );

			// Execute or schedule notifications & pings:
			$edited_Item->handle_notifications( $pbmUser->ID, true );

			if( !empty($pbm_item_files) )
			{	// Attach files
				$FileCache = & get_FileCache();

				$order = 1;
				foreach( $pbm_item_files as $filename )
				{
					pbm_msg( sprintf( ('Saving file "%s" in the database'), $filename ), $cron );
					$pbmFile = & $FileCache->get_by_root_and_path( 'collection', $pbmBlog->ID, $filename );
					$pbmFile->meta = 'notfound'; // Save time and don't try to load meta from DB, it's not there anyway
					$pbmFile->dbsave();
					pbm_msg( sprintf( ('File saved?: %s'), ( isset( $pbmFile->ID ) ? 'yes' : 'no' ) ), $cron );

					pbm_msg( sprintf( ('Attaching file "%s" to the post'), $filename ), $cron );
					// Let's make the link!
					$pbmLink = new Link();
					$pbmLink->set( 'itm_ID', $edited_Item->ID );
					$pbmLink->set( 'file_ID', $pbmFile->ID );
					$pbmLink->set( 'position', 'aftermore' );
					$pbmLink->set( 'order', $order++ );
					$pbmLink->dbinsert();
					pbm_msg( sprintf( ('File attached?: %s'), ( isset( $pbmLink->ID ) ? 'yes' : 'no' ) ), $cron );
				}

				// Invalidate blog's media BlockCache
				BlockCache::invalidate_key( 'media_coll_ID', $edited_Item->get_blog_ID() );
			}

			// Save posted items sorted by author user for reports
			$pbm_items['user_'.$pbmUser->ID][] = $edited_Item;

			++$post_cntr;
		}

		pbm_msg( ('Message posting successful'), $cron );

		// Delete temporary directory
		rmdir_r( $tmpDirMIME );

		if( ! $test_mode_on && $Settings->get('eblog_delete_emails') )
		{
			pbm_msg( sprintf( ('Marking message for deletion from inbox: %s'), $index ), $cron );
			imap_delete( $mbox, $index );
			++$del_cntr;
		}
	}

	// Expunge messages marked for deletion
	imap_expunge($mbox);

	return true;
}

/**
 * Process Header information like subject and date of a mail.
 *
 * @param array $header header as set by mime_parser_class::Analyze()
 * @param string message subject by reference
 * @param string message date by reference
 * @param boolean TRUE if script is executed by cron
 * @return bool true if valid subject prefix is detected
 */
function pbm_process_header( $header, & $subject, & $post_date, $cron = false )
{
	global $Settings;

	$subject = $header['Subject'];
	$ddate = $header['Date'];

	$prefix = $Settings->get( 'eblog_subject_prefix' );
	pbm_msg( T_('Subject').': '.$subject, $cron );

	if( utf8_substr($subject, 0, utf8_strlen($prefix)) !== $prefix )
	{
		pbm_msg( sprintf( ('Subject prefix is not "%s", skip this email'), $prefix ), $cron );
		return false;
	}

	$subject = utf8_substr($subject, utf8_strlen($prefix));

	// Parse Date
	if( !preg_match('#^(.{3}, )?(\d{2}) (.{3}) (\d{4}) (\d{2}):(\d{2}):(\d{2})#', $ddate, $match) )
	{
		$ddate_U = @strtotime($ddate);
		if( empty($ddate_U) || strlen($ddate_U) < 2 )
		{
			pbm_msg( sprintf( ('Could not parse date header "%s"'), $ddate ), $cron );
			return false;
		}
	}

	if( empty($ddate_U) )
	{
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
			pbm_msg( ('Invalid month name in message date string.'), $cron );
			return false;
		}
		$ddate_m = $dmonths[$match[3]];
		$ddate_d = $match[2];
		$ddate_Y = $match[4];

		$ddate_U = mktime( $ddate_H, $ddate_i, $ddate_s, $ddate_m, $ddate_d, $ddate_Y );
	}

	$post_date = date( 'Y-m-d H:i:s', $ddate_U );

	return true;
}


/**
 * process attachments by saving into media directory and optionally creating image tag in post
 *
 * @param string message content that is optionally manipulated by adding image tags (by reference)
 * @param  array $mailAttachments array containing path to attachment files
 * @param  string $mediadir path to media directory of blog as seen by file system
 * @param  string $media_url url to media directory as seen by user
 * @param  bool $add_img_tags should img tags be added to the post (instead of linking through the file manager)
 * @param  string $type defines attachment type: 'attach' or 'related'
 * @param boolean TRUE if script is executed by cron
 */
function pbm_process_attachments( & $content, $mailAttachments, $mediadir, $media_url, $add_img_tags = true, $type = 'attach', $cron = false )
{
	global $Settings, $pbm_item_files, $filename_max_length;

	pbm_msg( '<h4>'.('Processing attachments').'</h4>', $cron );

	foreach( $mailAttachments as $attachment )
	{
		if( isset($attachment['FileName']) )
		{
			$filename = trim( utf8_strtolower($attachment['FileName']) );
		}
		else
		{	// Related attachments may not have file name, we'll generate one below
			$filename = '';
		}

		if( $filename == '' )
		{
			$filename = 'upload_'.uniqid().'.'.$attachment['SubType'];
			pbm_msg( sprintf( ('Attachment without name. Using "%s".'), htmlspecialchars( $filename ) ), $cron );
		}

		// Check valid filename/extension: (includes check for locked filenames)
		if( $error_filename = process_filename( $filename, true ) )
		{
			pbm_msg( ('Invalid filename').': '.$error_filename, $cron );
			syslog_insert( sprintf( 'The posted by mail file %s has an unrecognized extension', '[['.$filename.']]' ), 'warning', 'file' );
			continue;
		}

		// If file exists count up a number
		$cnt = 0;
		$prename = substr( $filename, 0, strrpos( $filename, '.' ) ).'-';
		$sufname = strrchr( $filename, '.' );
		$error_in_filename = false;

		while( file_exists( $mediadir.$filename ) )
		{
			$filename = $prename.$cnt.$sufname;
			if( strlen( $filename ) > $filename_max_length )
			{ // This is a special case, when the filename is longer then the maximum allowed
				// Cut as many characters as required before the counter on the file name
				$filename = fix_filename_length( $filename, strlen( $prename ) - 1 );
				if( $error_in_filename = process_filename( $filename, true ) )
				{ // The file name is not valid, this is an unexpected situation, because the file name was already validated before
					pbm_msg( ('Invalid filename').': '.$error_filename, $cron );
					syslog_insert( sprintf( 'The posted by mail file %s has an unrecognized extension', '[['.$filename.']]' ), 'warning', 'file' );
					break;
				}
			}
			++$cnt;
		}
		if( $error_in_filename )
		{ // Don't create file with invalid file name
			continue;
		}
		pbm_msg( sprintf( ('New file name is %s'), '<b>'.$filename.'</b>' ), $cron );

		$imginfo = NULL;
		if( ! $Settings->get('eblog_test_mode') )
		{
			pbm_msg( sprintf( ('Saving file to: %s'), htmlspecialchars( $mediadir.$filename ) ), $cron );
			if( !copy( $attachment['DataFile'], $mediadir.$filename ) )
			{
				pbm_msg( sprintf( ('Unable to copy uploaded file to %s'), htmlspecialchars( $mediadir.$filename ) ), $cron );
				continue;
			}

			// chmod uploaded file:
			$chmod = $Settings->get('fm_default_chmod_file');
			@chmod( $mediadir.$filename, octdec( $chmod ) );

			$imginfo = @getimagesize($mediadir.$filename);
			pbm_msg( sprintf( ('Is this an image?: %s'), ( is_array( $imginfo ) ? 'yes' : 'no' ) ), $cron );
		}

		if( $type == 'attach' )
		{
			$content .= "\n";
			if( is_array($imginfo) && $add_img_tags )
			{
				$content .= '<img src="'.$media_url.$filename.'" '.$imginfo[3].' />';
			}
			else
			{
				pbm_msg( sprintf( ('The file %s will be attached to the post later, after we save the post in the database.'), '<b>'.$filename.'</b>' ), $cron );
				$pbm_item_files[] = $filename;
			}
			$content .= "\n";
		}
		elseif( !empty($attachment['ContentID']) )
		{	// Replace relative "cid:xxxxx" URIs with absolute URLs to media files
			$content = str_replace( 'cid:'.$attachment['ContentID'], $media_url.$filename, $content );
		}
	}
}


/**
 * Look inside message to get title for posting
 *
 * The message could contain a xml-tag <code><title>sample title</title></code> to specify a title for the posting.
 * If no tag is found we will try to get the default title from settings.
 * If none of these is found then the specified alternate title line is used.
 *
 * @param string $content message to search for title tag
 * @param string $alternate_title use this string if no title tag is found
 * @return string title of posting
 *
 */
function pbm_get_post_title( $content, $alternate_title )
{
	global $Settings;

	if( preg_match('~<title>(.+?)</title>~is', $content, $matchtitle) )
	{
		$title = $matchtitle[1];
	}
	else
	{
		$title = $Settings->get('eblog_default_title');
	}

	if( $title == '' )
	{
		$title = $alternate_title;
	}
	return $title;
}


/**
 * Extract <auth> tag with login and password
 *
 * @param string Message body (by reference)
 * @return array login and password
 */
function pbm_get_auth_tag( & $content )
{
	if( preg_match( '~<(auth)>(.+?)</\\1>~is', $content, $match ) )
	{
		// tblue> splitting only into 2 parts allows colons in the user PW
		// Note: login and password cannot include '<' !
		$auth = explode( ':', strip_tags($match[2]), 2 );

		// Delete 'auth' tag from post content
		$content = preg_replace( '~<(auth)>(.+?)</\\1>~is', '', $content );

		return $auth;
	}
	return false;
}


/**
 * Prepare html message
 *
 * @param string Message
 * @param boolean TRUE if script is executed by cron
 * @return string Content
 */
function pbm_prepare_html_message( $message, $cron = false )
{
	pbm_msg( sprintf( ('Message body (original): %s'), '<pre style="font-size:10px">'.htmlspecialchars( $message ).'</pre>' ), $cron );

	$marker = 0;
	if( preg_match( '~<body[^>]*>(.*?)</body>~is', $message, $result ) )
	{	// First see if we can get contents of <body> tag
		$content = $result[1];
		$marker = 1;
	}
	elseif( preg_match( '~<html[^>]*>(.*?)</html>~is', $message, $result ) )
	{	// <body> was not found, use <html> contents and delete <head> section from it
		$content = preg_replace( '~<head[^>]*>(.*?)</head>~is', '', $result[1] );
		$marker = 1;
	}

	if( empty($marker) )
	{	// None of the above methods worked, just use the original message body
		$content = $message;
	}

	// First fix different line-endings (dos, mac, unix), remove double newlines
	$content = str_replace( array("\r", "\n\n"), "\n", trim($content) );

	// Decode 'category', 'title' and 'auth' tags
	$content = preg_replace( '~&lt;(/)?(category|title|auth)&gt;~i', '<\\1\\2>', $content );

	if( ($auth = pbm_get_auth_tag($content)) === false )
	{	// No 'auth' tag provided, exit
		pbm_msg( sprintf( ('&lt;auth&gt; tag not found! Please add username and password in message body in format %s.'),
					'"&lt;auth&gt;username:password&lt;/auth&gt;"' ), $cron );
		return false;
	}

	// Balance tags
	$content = balance_tags($content);

	// Remove markup that cause validator errors
	$patterns = array(
		'~ moz-do-not-send="true"~',			// Thunderbird inline image with absolute "src"
		'~ class="moz-signature" cols="\d+"~',	// Thunderbird signature in HTML message
		'~ goomoji="[^"]+"~',					// Gmail smilies
	);
	$content = preg_replace( $patterns, '', $content );

	pbm_msg( sprintf( ('Message body (processed): %s'), '<pre style="font-size:10px">'.htmlspecialchars( $content ).'</pre>' ), $cron );

	return array( $auth, $content );
}


function pbm_validate_user_password( $user_login, $user_pass )
{
	$UserCache = & get_UserCache();
	$User = & $UserCache->get_by_login( $user_login );
	if( ! $User )
	{
		return false;
	}

	// First check unhashed password
	if( ! $User->check_password( $user_pass, false ) )
	{
		if( preg_match( '~^[a-f0-9]{32}$~i', $user_pass ) )
		{	// This is a hashed password, see if it's valid
			// We check it here because some crazy user may use a real 32-chars password!
			if( $User->check_password( $user_pass, true ) )
			{	// Valid password
				return $User;
			}
		}
		return false;
	}
	return $User;
}


/**
 * Create a new directory with unique name
 * This creates a new directory below the given path with the given prefix and a random number
 *
 * @param  string $dir base path to new directory
 * @param  string $prefix prefix random number with this
 * @param  integer $mode permissions to use
 * @return string path to created directory
 */
function pbm_tempdir( $dir, $prefix = 'tmp', $mode = 0700 )
{
	// Add trailing slash
	$dir = trailing_slash($dir);

	do { $path = $dir.$prefix.mt_rand(); } while( ! evo_mkdir( $path, $mode ) );

	return $path;
}

?>