<?php
/**
 * This file implements the decode the returned emails support functions.
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
 * Log a result message in global variables to use them later
 *     on cron CLI/Web screens or in back-office "Returned emails" test tool
 *
 * @param string Message text
 * @param boolean TRUE if it is called from cron
 */
function dre_msg( $message, $cron = false )
{
	global $is_web, $result_message, $dre_messages;

	if( ! is_array( $dre_messages ) )
	{	// Initialize global array first time:
		$dre_messages = array();
	}

	// Log all messages to global array $dre_messages no matter if we are in cron mode or not
	// We may use this report later, display or send to the blog owner
	$dre_messages[] = $message;

	if( $cron )
	{	// We are in cron mode
		if( $is_web )
		{	// Separate a message with newline when we call a cron from browser:
			$message .= '<br />';
		}

		// Log the message in global variable $result_message:
		$result_message .= $message."\n";
	}
}


/**
 * Connect to an IMAP or POP mail server
 *
 * @param boolean TRUE if script is executed by cron
 * @param boolean TRUE to print out all folders of host
 * @return resource $mbox
 */
function dre_connect( $cron = false, $print_out_folders = false )
{
	if( ! extension_loaded( 'imap' ) )
	{	// Exit here if imap extension is not loaded:
		dre_msg( '<b class="red">'.( 'IMAP extension is NOT loaded! ').'</b>', $cron );
		return false;
	}

	global $Settings;

	$host = $Settings->get('repath_server_host').':'.$Settings->get('repath_server_port');
	$mailserver = '{'.$host;

	dre_msg( sprintf( ('Connecting and authenticating to mail server %s'), '<b>'.$host.'</b>' ), $cron );

	switch( $Settings->get('repath_encrypt') )
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

	switch( $Settings->get('repath_method') )
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

	if( $Settings->get('repath_novalidatecert') )
	{	// Don't validate certificate:
		$mailserver .= '/novalidate-cert';
	}

	$mailserver .= '}';

	if( ! $print_out_folders )
	{	// Select messages only from this IMAP folder:
		// Don't filter by folder when we request to display all folders:
		$mailserver .= $Settings->get( 'repath_imap_folder' );
	}

	// Connect to mail server (one retry)
	$mbox = @imap_open( $mailserver, $Settings->get('repath_username'), $Settings->get('repath_password'), NULL, 1 );

	if( is_null(@get_resource_type($mbox)) )
	{	// Not a resource
		$error = imap_errors();
		if( is_array($error) )
		{
			$error = implode( "<br />\n", $error );
		}

		dre_msg( sprintf( ('Connection failed: %s'), $error ), $cron );
		return false;
	}
	dre_msg( '<b class="green">'.('Successfully connected!').'</b>', $cron );

	@imap_errors();

	if( $print_out_folders )
	{	// Print out all possible folders of host:
		$server_folders = imap_list( $mbox, $mailserver, '*' );
		dre_msg( '<b>'.T_('Mail server has the following folders:').'</b>', $cron );
		$folders_html = '<ol>';
		foreach( $server_folders as $server_folder )
		{
			$folders_html .= '<li>'.preg_replace( '#^\{[^\}]+\}#', '', $server_folder ).'</li>';
		}
		$folders_html .= '</ol>';
		dre_msg( $folders_html, $cron );
	}

	return $mbox;
}


/**
 * Read messages from server and save returned emails into DB
 *
 * @param resource $mbox created by dre_connect() (by reference)
 * @param integer the number of messages to process
 * @param boolean TRUE if script is executed by cron
 * @return boolean true on success
 */
function dre_process_messages( & $mbox, $limit, $cron = false )
{
	global $Settings, $debug;
	global $dre_messages, $dre_emails, $email_cntr, $del_cntr, $is_cron_mode;

	// This may take a very long time if there are many messages; No execution time limit:
	set_max_execution_time(0);

	if( $Settings->get( 'repath_ignore_read' ) )
	{	// Read status info of all messages in order to know which have already been read:
		$msg_statuses = imap_fetch_overview( $mbox, '1:'.$limit );
	}

	$email_cntr = 0;
	$del_cntr = 0;
	for( $index = 1; $index <= $limit; $index++ )
	{	// Repeat for as many messages as allowed...

		dre_msg( '<hr /><h3>'.sprintf( ('Processing message %s:'), '#'.$index ).'</h3>', $cron );

		if( $Settings->get( 'repath_ignore_read' ) )
		{	// Check if we can read this message or we should skip this:
			if( isset( $msg_statuses[ $index - 1 ] ) && $msg_statuses[ $index - 1 ]->seen == 1 )
			{	// Skip this message because it has already been read:
				dre_msg( ('Ignoring this message because it has aleady been read.'), $cron );
				continue;
			}
			else
			{	// Mark this message as "Seen" in order to don't read it twice:
				imap_setflag_full( $mbox, $index, '\\Seen' );
			}
		}

		$html_body = '';
		$strbody = '';
		$hasAttachment = false;
		$hasRelated = false;

		// Save email to a temporary file on hard drive, otherwise BIG attachments may take a lot of RAM:
		if( ! ($tmpMIME = tempnam( sys_get_temp_dir(), 'b2evoMail' )) )
		{
			dre_msg( ('Could not create temporary file.'), $cron );
			continue;
		}
		// Save the whole body of a specific message from the mailbox:
		imap_savebody( $mbox, $tmpMIME, $index );

// fp> TODO: soemwhere here we should skip messages that already have the "seen" flag. This should be optional but should be the default.
// This will allow to keep the emails in the INBOX without reprocessing them but to easily try them again my marking them unread.

		// Create random temp directory for message parts:
		$tmpDirMIME = dre_tempdir( sys_get_temp_dir(), 'b2evo_' );

		// Instanciate mime_parser.php library:
		$mimeParser = new mime_parser_class();
		$mimeParser->mbox = 0;						// Set to 0 for parsing a *single* RFC 2822 message
		$mimeParser->decode_headers = 1;			// Set to 1 if it is	necessary to decode message headers that may have non-ASCII	characters and use other character set encodings
		$mimeParser->ignore_syntax_errors = 1;	// ignore syntax errors in	malformed messages.
		$mimeParser->extract_addresses = 0;

		// Associative array to specify parameters for the messagedata parsing and decoding operation.
		$MIMEparameters = array(
				'File' => $tmpMIME,			// Name of the file from which the message data will be read.
				'SaveBody' => $tmpDirMIME,	// Save message body parts to a directory
				'SkipBody' => 1,				// 1 means the information about the message body part structure is returned in $decodedMIME below but it does not return any body data.
			);

		// STEP 1: Parse and decode message data and retrieve its structure:
		if( !$mimeParser->Decode( $MIMEparameters, /* BY REF */ $decodedMIME ) )
		{	// error:
			dre_msg( sprintf( ('MIME message decoding error: %s at position %d.'), $mimeParser->error, $mimeParser->error_position ), $cron );
			rmdir_r( $tmpDirMIME );
			unlink( $tmpMIME );
			continue;
		}
		else
		{	// the specified message data was parsed successfully:
			dre_msg( ('MIME message decoding successful'), $cron );

			// STEP 2: Analyze (the first) parsed message to describe its contents:
			if( ! $mimeParser->Analyze( $decodedMIME[0], /* BY REF */ $parsedMIME ) )
			{	// error:
				dre_msg( sprintf( ('MIME message analyze error: %s'), $mimeParser->error ), $cron );
				rmdir_r( $tmpDirMIME );
				unlink( $tmpMIME );
				continue;
			}

			// Get message $subject and $post_date from headers (by reference)
			if( ! dre_process_header( $parsedMIME, /* BY REF */ $subject, /* BY REF */ $post_date, $cron ) )
			{	// Couldn't process message headers:
				rmdir_r( $tmpDirMIME );
				unlink( $tmpMIME );
				continue;
			}

			// TODO: handle type == "message" recursively
// fp> where is type == "message" ???
// yura> I don't find the type == 'message' in dump of $decodedMIME and $parsedMIME

			// sam2kb> For some reason imap_qprint() demages HTML text... needs more testing
			// yura> I replaced imap_qprint() with quoted_printable_decode() to avoid notices about invalid quoted-printable sequence
			// yura> imap_qprint() and quoted_printable_decode() do empty the message text, thus they were deleted.

			dre_msg( ('Email Type').': '.$parsedMIME['Type'], $cron );

			if( $parsedMIME['Type'] == 'html' )
			{	// Mail is HTML:
				if( $debug )
				{	// Display this info only in debug mode:
					dre_msg( sprintf( ('HTML message part saved as %s'), $parsedMIME['DataFile'] ), $cron );
				}
				$html_body = file_get_contents( $parsedMIME['DataFile'] );

				if( empty( $html_body ) )
				{	// Try to get a body text from alternative parts if main html body is empty:
					foreach( $parsedMIME['Alternative'] as $alternative )
					{	// First try to get HTML alternative (when possible)
						if( $alternative['Type'] == 'html' )
						{	// HTML text
							if( $debug )
							{	// Display this info only in debug mode:
								dre_msg( sprintf( ('HTML alternative message part saved as %s'), $alternative['DataFile'] ), $cron );
							}
							$strbody = file_get_contents( $alternative['DataFile'] );
							break; // stop after first alternative
						}
						elseif( $alternative['Type'] == 'text' )
						{	// Plain text
							if( $debug )
							{	// Display this info only in debug mode:
								dre_msg( sprintf( ('Text alternative message part saved as %s'), $alternative['DataFile'] ), $cron );
							}
							$strbody = file_get_contents( $alternative['DataFile'] );
							break; // stop after first alternative
						}
					}
				}
			}
			elseif( $parsedMIME['Type'] == 'text' )
			{	// Mail is plain text:
				if( $debug )
				{	// Display this info only in debug mode:
					dre_msg( sprintf( ('Plain-text message part saved as %s'), $parsedMIME['DataFile'] ), $cron );
				}
				$strbody = file_get_contents( $parsedMIME['DataFile'] );
			}
			elseif( $parsedMIME['Type'] == 'delivery-status' )
			{	// Mail is delivery-status:
				$strbody = $parsedMIME['Response'];
			}


			if( count($mimeParser->warnings) > 0 )
			{ // Record potential warnings:
				dre_msg( '<h4>'.sprintf( ('%d warnings during decode:'), count( $mimeParser->warnings ) ).'</h4>', $cron );
				foreach( $mimeParser->warnings as $k => $v )
				{
					dre_msg( sprintf( ('Warning: %s at position %s'), $v, $k ), $cron );
				}
			}
		}
		unlink( $tmpMIME );


		if( empty( $html_body ) )
		{	// Plain-text message
			dre_msg( sprintf( ('Message type: %s'), 'TEXT' ), $cron );

			// Process body. First fix different line-endings (dos, mac, unix), remove double newlines
			$content = str_replace( array( "\r", "\n\n" ), "\n", trim( $strbody ) );

			dre_msg( sprintf( ('Message body: %s'), '<pre style="font-size:10px">'.htmlspecialchars( $strbody ).'</pre>' ), $cron );
		}
		else
		{	// HTML message
			dre_msg( sprintf( ('Message type: %s'), 'HTML' ), $cron );
			dre_msg( sprintf( ('Message body (original): %s'), '<pre style="font-size:10px">'.htmlspecialchars( $html_body ).'</pre>', $cron ) );

			// Prepare html message body text:
			$content = dre_prepare_html_message( $html_body );

			dre_msg( sprintf( ('Message body (processed): %s'), '<pre style="font-size:10px">'.htmlspecialchars( $content ).'</pre>', $cron ) );
		}


		dre_msg( '<b class="green">'.('MIME Decoding Successful').'</b>', $cron );

		$message_text = $content;

		// Remove content after terminators
		$content = dre_limit_by_terminators( $content );

		global $Messages;
		if( $Messages->has_errors() )
		{
			// Make it easier for user to find and correct the errors
			dre_msg( "\n".sprintf( ('Processing message: %s'), $post_title ), $cron );
			dre_msg( $Messages->get_string( ('Cannot post, please correct these errors:'), 'error' ), $cron );

			$Messages->clear();
			rmdir_r( $tmpDirMIME );
			continue;
		}

		global $dre_emails, $DB, $localtimenow;

		dre_msg( '<h4>'.('Saving the returned email in the database').'</h4>', $cron );

		// Get Headers from Decoded MIME Data:
		$email_headers = dre_get_headers( $decodedMIME );

		// Get data of the returned email:
		$email_data = dre_get_email_data( $content, $message_text, $email_headers );

		dre_msg( ('Email Address').': '.$email_data['address'], $cron );
		dre_msg( ('Error Type').': '.dre_decode_error_type( $email_data['errtype'] ), $cron );
		dre_msg( ('Error Message').': '.$email_data['errormsg'], $cron );

		// Insert a returned email's data into DB
		if( dre_insert_returned_email( $email_data ) )
		{
			++$email_cntr;
		}

		// Delete temporary directory:
		rmdir_r( $tmpDirMIME );

		// Mark message to be deleted:
		if( $Settings->get('repath_delete_emails') )
		{
			dre_msg( sprintf( ('Marking message for deletion from inbox: %s'), $index ), $cron );
			imap_delete( $mbox, $index );
			++$del_cntr;
		}
	}

	// Expunge messages marked for deletion
	imap_expunge( $mbox );

	return true;
}


/**
 * Simulate a message processing and save email into DB
 *
 * @param string Message text
 * @return boolean true on success
 */
function dre_simulate_message( $message_text )
{
	global $Settings;
	global $dre_messages, $is_cron_mode, $DB, $localtimenow;

	$content = $message_text;

	dre_msg( '<hr /><h3>'.sprintf( ('Working with message %s:'), '#1' ).'</h3>' );

	dre_msg( sprintf( ('Message body: %s'), '<pre style="font-size:10px">'.htmlspecialchars( $content ).'</pre>' ) );

	dre_msg( '<b class="green">'.('(No MIME decoding is done in simulation mode)').'</b>' );

	// Remove content after terminators
	$content = dre_limit_by_terminators( $content );

	dre_msg( '<h4>'.('Saving the returned email in the database').'</h4>' );

	// Get data of the returned email:
	$email_data = dre_get_email_data( $content, $message_text, 'Empty headers' );

	dre_msg( ('Email Address').': '.$email_data['address'] );
	dre_msg( ('Error Type').': '.dre_decode_error_type( $email_data['errtype'] ) );
	dre_msg( ('Error Message').': '.$email_data['errormsg'] );

	// Insert a returned email's data into DB:
	return dre_insert_returned_email( $email_data );
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
function dre_tempdir( $dir, $prefix = 'tmp', $mode = 0700 )
{
	// Add trailing slash
	$dir = trailing_slash($dir);

	do { $path = $dir.$prefix.mt_rand(); } while( ! evo_mkdir( $path, $mode ) );

	return $path;
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
function dre_process_header( $header, & $subject, & $post_date, $cron = false )
{
	global $Settings;

	$subject = $header['Subject'];
	$ddate = $header['Date'];

	dre_msg( T_('Subject').': '.$subject, $cron );

	// Check subject to match in titles to identify return path emails
	$subject_is_correct = false;
	$repath_subjects = explode( "\n", str_replace( array( '\r\n', '\n\n' ), '\n', $Settings->get( 'repath_subject' ) ) );
	foreach( $repath_subjects as $repath_subject )
	{
		if( strpos( $subject, $repath_subject ) !== false )
		{
			$subject_is_correct = true;
			break;
		}
	}

	if( !$subject_is_correct )
	{ // Subject is not match to identify return email
		dre_msg( sprintf( ('Subject prefix is not "%s", skip this email'), implode( '", "', $repath_subjects ) ), $cron );
		return false;
	}

	// Parse Date
	if( !preg_match( '#^(.{3}, )?(\d{2}) (.{3}) (\d{4}) (\d{2}):(\d{2}):(\d{2})#', $ddate, $match ) )
	{
		$ddate_U = @strtotime($ddate);
		if( empty($ddate_U) || strlen($ddate_U) < 2 )
		{
			dre_msg( sprintf( ('Could not parse date header "%s"'), $ddate ), $cron );
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
			dre_msg( ('Invalid month name in message date string.'), $cron );
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
 * Extract emails from a message body
 *
 * @param string Message body
 * @param integer Max count emails
 * @param string Delimeter between emails
 * @return string Emails separated by delimeter
 */
function dre_get_emails( $content, $max_count = 1, $delimeter = ', ' )
{
	if( preg_match_all( '/([a-z0-9&\-_.]+?@[\w\-]+\.([\w\-\.]+\.)?[\w]+)/is', $content, $emails ) )
	{	// Get the returned emails
		global $Settings;

		$emails = array_unique( $emails[1] );
		foreach( $emails as $e => $email )
		{
			if( in_array( $email, array( $Settings->get( 'notification_sender_email' ), $Settings->get( 'notification_return_path' ) ) ) )
			{	// Exclude Sender email & Return path
				unset( $emails[$e] );
			}
		}
		// Limit by max count emails
		$emails = array_slice( $emails, 0, $max_count );

		return implode( $delimeter, $emails );
	}

	return '';
}


/**
 * Prepare html message
 *
 * @param string Message
 * @param boolean TRUE if script is executed by cron
 * @return string Content
 */
function dre_prepare_html_message( $message, $cron = false )
{
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
	$content = str_replace( array( "\r", "\n\n" ), "\n", trim( $content ) );

	// Decode 'category', 'title' and 'auth' tags
	$content = preg_replace( '~&lt;(/)?(category|title|auth)&gt;~i', '<\\1\\2>', $content );

	// Balance tags
	$content = balance_tags( $content );

	// Remove markup that cause validator errors
	$patterns = array(
		'~ moz-do-not-send="true"~',			// Thunderbird inline image with absolute "src"
		'~ class="moz-signature" cols="\d+"~',	// Thunderbird signature in HTML message
		'~ goomoji="[^"]+"~',					// Gmail smilies
	);
	$content = preg_replace( $patterns, '', $content );

	return $content;
}


/**
 * Get Headers from Decoded MIME Data
 *
 * @param array Decoded MIME Data
 * @return string Headers
 */
function dre_get_headers( $decodedMIME )
{
	$headers = array();
	foreach( $decodedMIME[0]['Headers'] as $field => $value )
	{
		if( is_array( $value ) )
		{
			$value = implode( "\r\n", $value );
		}
		$headers[] = ucfirst( $field ).' '.$value;
	}

	return implode( "\r\n", $headers );
}


/**
 * Get content after email and before terminator line
 *
 * @param mixed $message
 * @param mixed $emails
 * @return string Content
 */
function dre_get_processing_content( $content, $emails )
{
	$error_text = '';

	$emails = explode( ', ', $emails );
	if( count( $emails ) > 0 )
	{	// If emails exist
		// Get last email
		$email = $emails[ count( $emails ) - 1 ];
		if( !empty( $email ) )
		{	// Get error text after last email address
			$error_text = trim( utf8_substr( $content, utf8_strpos( $content, $email ) + utf8_strlen( $email ) ) );
		}
		if( empty( $error_text ) )
		{	// If error text is empty we should get all content before email OR full content if no email address in content
			$error_text = empty( $email ) ? $content : trim( utf8_substr( $content, 0, utf8_strpos( $content, $email ) ) );
		}
	}
	else
	{	// If no emails - get full content as error text
		$error_text = $content;
	}

	if( !empty( $error_text ) )
	{	// Replace all new line sumbols with space symbol
		$error_text = str_replace( array( "\r\n\r\n", "\r\n", "\n\n", "\n" ), " ", $error_text );
	}

	return $error_text;
}

/**
 * Get Error from Message
 *
 * @param string Message
 * @return string Error text
 */
function dre_get_error_message( $message )
{
	$error_text = '';

	if( preg_match( '#[\s;]{1}(5[0-9][0-9][\s\d\.\-]+([^\n]+))#i', $message, $errors ) )
	{	// Get first found error
		$error_text = trim( $errors[1] );
	}
	else
	{	// If no errors - use full content(between email and body terminator)
		$error_text = $message;
	}

	// Return error text limited by DB field length
	return utf8_substr( $error_text, 0, 255 );
}


/**
 * Get Error from Message by defined patterns ($Settings->get( 'repath_errtype' ))
 *
 * @param string Content
 * @return array ( 'Error Type', 'Error Mask' )
 */
function dre_get_error_by_pattern( $content )
{
	global $Settings;

	$error_types = trim( $Settings->get( 'repath_errtype' ) );

	if( empty( $error_types ) )
	{	// Error types are not defined
		return false;
	}

	$error_types = explode( "\n", str_replace( array( "\r", "\n\n" ), "\n", $error_types ) );

	foreach( $error_types as $error_type )
	{
		list( $error_type, $error_mask ) = explode( ' ', $error_type, 2 );
		if( preg_match( '#'.$error_mask.'#i', $content ) )
		{
			return array( $error_type, $error_mask );
		}
	}

	// Not found error
	return false;
}


/**
 * Get Error info
 *
 * @param string Content
 * @return array Error info:
 *              'text' => Error text
 *              'type' => Error type code
 */
function dre_get_error_info( $content )
{
	$error_info = array();

	// Get error by patterns from the Setting 'repath_errtype'
	$error_pattern = dre_get_error_by_pattern( $content );

	// Get full error message from content
	$error_full_text = dre_get_error_message( $content );

	if( !empty( $error_pattern ) )
	{
		if( preg_match( '#'.$error_pattern[1].'#i', $error_full_text ) )
		{	// If error pattern is contained in the full error text
			$error_info['text'] = $error_full_text;
			$error_info['type'] = $error_pattern[0];
		}
	}

	if( empty( $error_info ) )
	{	// Set error info from full error text If error info is not defined by some reason yet
		$error_info['text'] = $error_full_text;
		$error_info['type'] = dre_get_error_type( $error_full_text );
	}

	return $error_info;
}


/**
 * Get Error type
 *
 * @param string Error
 * @return string Error type
 */
function dre_get_error_type( $error )
{
	global $Settings;

	$error_types = trim( $Settings->get( 'repath_errtype' ) );

	if( empty( $error_types ) )
	{	// Error types are not defined
		return 'U';
	}

	$error_types = explode( "\n", str_replace( array( "\r", "\n\n" ), "\n", $error_types ) );

	foreach( $error_types as $error_type )
	{
		list( $error_type, $error_mask ) = explode( ' ', $error_type, 2 );
		if( preg_match( '#'.$error_mask.'#i', $error ) )
		{
			return $error_type;
		}
	}

	// Not found error type
	return 'U';
}


/**
 * Decode error type to error title
 *
 * @param string Error type
 * @return string Error title
 */
function dre_decode_error_type( $error_type )
{
	$titles = array(
		''  => T_('Unknown error'),
		'S' => T_('Spam suspicion'),
		'P' => T_('Permanent error'),
		'T' => T_('Temporary error'),
		'C' => T_('Configuration error')
	);

	if( isset( $titles[ $error_type ] ) )
	{
		return $titles[ $error_type ];
	}
	else
	{	// Unknown error
		return $titles[ '' ];
	}
}


/**
 * Remove content after body terminators
 *
 * @param string Source content
 * @retrun string Limited content
 */
function dre_limit_by_terminators( $content )
{
	global $Settings;

	$repath_terminators = $Settings->get('repath_body_terminator');
	if( !empty( $repath_terminators ) )
	{
		$repath_terminators = explode( "\n", str_replace( array( "\r", "\n\n" ), "\n", $repath_terminators ) );
		foreach( $repath_terminators as $repath_terminator )
		{	// Limit by each terminator
			$repath_terminator = trim( $repath_terminator );
			if( empty( $repath_terminator ) )
			{	// Skip empty string
				continue;
			}
			if( !empty( $repath_terminator ) && ($os_terminator = utf8_strpos( $content, $repath_terminator )) !== false )
			{	// Remove text after terminator string
				$content = utf8_substr( $content, 0, $os_terminator );
			}
		}
	}

	return $content;
}


/**
 * Get data of returned email
 *
 * @param string Prepared message text (without text after body terminator)
 * @param string Full message text
 * @param string Headers
 * @return array ( 'address', 'errormsg', 'message', 'headers', 'errtype' )
 */
function dre_get_email_data( $content, $message_text, $headers )
{
	global $servertimenow;

	// Extract emails from content:
	$emails = utf8_strtolower( dre_get_emails( $content ) );

	// Get content between email and body terminator:
	$content = dre_get_processing_content( $content, $emails );

	// Get Error info:
	$error_info = dre_get_error_info( $content );

	$email_returned = array(
			'address'   => $emails,
			'errormsg'  => $error_info['text'],
			'timestamp' => date2mysql( $servertimenow ),
			'message'   => htmlspecialchars( utf8_clean( $message_text ) ),
			'headers'   => $headers,
			'errtype'   => $error_info['type']
		);

	return $email_returned;
}


/**
 * Insert a returned email's data into DB
 *
 * @param array Data of an returned email ( 'address', 'errormsg', 'message', 'headers', 'errtype' )
 * @return boolean TRUE on successful insertion
 */
function dre_insert_returned_email( $email_data )
{
	global $DB, $dre_emails;

	// INSERT RETURNED DATA INTO DB:
	$DB->query( 'INSERT INTO T_email__returns ( emret_address, emret_errormsg, emret_timestamp, emret_message, emret_headers, emret_errtype )
		VALUES ( '.$DB->quote( $email_data ).' )',
		'Insert info of the returned email' );

	if( $DB->insert_id > 0 )
	{
		// Save the data for the returned email address into DB:
		dre_save_email_address_data( $email_data );

		// Save the saved emails for reports:
		$dre_emails[] = $email_data;

		return true;
	}
	else
	{
		return false;
	}
}

/**
 * Insert/Update the data of email address into DB
 *
 * @param array Data of returned email:
 *               'address'
 *               'errormsg'
 *               'message'
 *               'headers'
 *               'errtype'
 */
function dre_save_email_address_data( $email_returned )
{
	global $DB;

	if( empty( $email_returned['address'] ) )
	{	// No emails, Exit here
		return;
	}

	$EmailAddressCache = & get_EmailAddressCache();
	// Get an existing email address to update if it exist
	$EmailAddress = & $EmailAddressCache->get_by_name( $email_returned['address'], false );
	if( !$EmailAddress )
	{	// Insert new email address
		$EmailAddress = new EmailAddress();
		$EmailAddress->set( 'address', $email_returned['address'] );
	}

	switch( $email_returned['errtype'] )
	{	// Error type of the returned email:
		case 'P':	// Permanent error
			$EmailAddress->increase_counter( 'prmerror' );
			// Update only the adresses with NOT spammer statuses
			$EmailAddress->set_status( 'prmerror' );
			break;

		case 'T':	// Temporary error
			if( in_array( $EmailAddress->get( 'status' ), array( 'suspicious1', 'suspicious2', 'suspicious3' ) ) )
			{ // If current status already is defined as 'suspicious1', 'suspicious2' or 'suspicious3'
				if( $EmailAddress->get( 'sent_last_returnerror' ) <= 1 )
				{
					if( $EmailAddress->get( 'status' ) == 'suspicious1' )
					{	// Increase status from suspicious1 to suspicious2
						$EmailAddress->set( 'status', 'suspicious2' );
					}
					elseif( $EmailAddress->get( 'status' ) == 'suspicious2' )
					{	// Increase status from suspicious2 to suspicious3
						$EmailAddress->set( 'status', 'suspicious3' );
					}
				}
			}
			elseif( $EmailAddress->get( 'status' ) == 'redemption' )
			{ // IF current status is 'redemption' we should set it as 'suspicious3'
				$EmailAddress->set_status( 'suspicious3' );
			}
			else
			{ // Update only the email addresses with level status less then Suspicious 1
				$EmailAddress->set_status( 'suspicious1' );
			}
			$EmailAddress->increase_counter( 'tmperror' );
			break;

		case 'S':	// Spam suspicion
			$EmailAddress->increase_counter( 'spamerror' );
			// Update only the email addresses with 'unknown' status
			$EmailAddress->set_status( 'warning' );
			break;

		default:	// Other errors
			$EmailAddress->increase_counter( 'othererror' );
			// Update only the email addresses with 'unknown' status
			$EmailAddress->set_status( 'warning' );
			break;
	}

	// Insert/Update an email address
	$EmailAddress->dbsave();
}

?>