<?php
/**
 * This file implements the decode the returned emails support functions.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
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
function dre_msg( $message, $cron = false )
{
	global $is_web, $result_message, $dre_messages;

	// Log all messages to $dre_messages no matter if we are in cron mode or not
	// We may use this report later, display or send to the blog owner
	$dre_messages[] = $message;

	if( $cron )
	{	// We are in cron mode, log the message
		if( $is_web )
			$message .= '<br />';

		$result_message .= $message."\n";
	}
}

/**
 * Connect to a mail server
 *
 * @param string Message
 * @return resource $mbox
 */
function dre_connect()
{
	if( !extension_loaded( 'imap' ) )
	{	// Exit here if imap extension is not loaded
		dre_msg('<b class="red">IMAP extension is NOT loaded!</b>');
		return false;
	}

	global $Settings;

	$host = $Settings->get('repath_server_host').':'.$Settings->get('repath_server_port');
	$mailserver = '{'.$host;

	dre_msg('Connecting and authenticating to mail server <b>'.$host.'</b>');

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
	{
		$mailserver .= '/novalidate-cert';
	}

	$mailserver .= '}INBOX';

	// Connect to mail server (one retry)
	$mbox = @imap_open( $mailserver, $Settings->get('repath_username'), $Settings->get('repath_password'), NULL, 1 );

	if( is_null(@get_resource_type($mbox)) )
	{	// Not a resource
		$error = imap_errors();
		if( is_array($error) )
		{
			$error = implode( "<br />\n", $error );
		}

		dre_msg( sprintf( /* TRANS: %s is the error message */ T_('Connection failed: %s'), $error), true );
		return false;
	}
	dre_msg('<b class="green">Successfully connected!</b>');

	@imap_errors();

	return $mbox;
}


/**
 * Read messages from server and save returned emails into DB
 *
 * @param resource $mbox created by dre_connect() (by reference)
 * @param integer the number of messages to process
 * @return boolean true on success
 */
function dre_process_messages( & $mbox, $limit )
{
	//return; // Exit, in development...


	global $Settings;
	global $dre_messages, $dre_emails, $email_cntr, $del_cntr, $is_cron_mode;

	// No execution time limit
	set_max_execution_time(0);

	$email_cntr = 0;
	$del_cntr = 0;
	for( $index = 1; $index <= $limit; $index++ )
	{
		dre_msg('<hr /><h3>Processing message #'.$index.':</h3>');

		$html_body = '';
		$strbody = '';
		$hasAttachment = false;
		$hasRelated = false;

		// Save email to hard drive, otherwise attachments may take a lot of RAM
		if( ! ($tmpMIME = tempnam( sys_get_temp_dir(), 'b2evoMail' )) )
		{
			dre_msg( T_('Could not create temporary file.'), true );
			continue;
		}
		imap_savebody( $mbox, $tmpMIME, $index );

		// Create random temp directory for message parts
		$tmpDirMIME = dre_tempdir( sys_get_temp_dir(), 'b2evo_' );

		$mimeParser = new mime_parser_class;
		$mimeParser->mbox = 0;				// Set to 0 for parsing a single message file
		$mimeParser->decode_headers = 1;
		$mimeParser->ignore_syntax_errors = 1;
		$mimeParser->extract_addresses = 0;

		$MIMEparameters = array(
				'File' => $tmpMIME,
				'SaveBody' => $tmpDirMIME,	// Save message body parts to a directory
				'SkipBody' => 1,			// Do not retrieve or save message body parts
			);

		if( !$mimeParser->Decode( $MIMEparameters, $decodedMIME ) )
		{
			dre_msg( sprintf( 'MIME message decoding error: %s at position %d.', $mimeParser->error, $mimeParser->error_position), true );
			rmdir_r( $tmpDirMIME );
			unlink( $tmpMIME );
			continue;
		}
		else
		{
			dre_msg('MIME message decoding successful');

			if( ! $mimeParser->Analyze( $decodedMIME[0], $parsedMIME ) )
			{
				dre_msg( sprintf( 'MIME message analyse error: %s', $mimeParser->error), true );
				rmdir_r( $tmpDirMIME );
				unlink( $tmpMIME );
				continue;
			}

			// Get message $subject and $post_date from headers (by reference)
			if( ! dre_process_header( $parsedMIME, $subject, $post_date ) )
			{	// Couldn't process message headers
				rmdir_r( $tmpDirMIME );
				unlink($tmpMIME);
				continue;
			}

			// TODO: handle type == "message" recursively
			// sam2kb> For some reason imap_qprint() demages HTML text... needs more testing

			if( $parsedMIME['Type'] == 'html' )
			{	// Mail is HTML
				dre_msg( 'HTML message part saved as '.$parsedMIME['DataFile'] );
				$html_body = file_get_contents($parsedMIME['DataFile']);

				foreach( $parsedMIME['Alternative'] as $alternative )
				{	// First try to get HTML alternative (when possible)
					if( $alternative['Type'] == 'html' )
					{	// HTML text
						dre_msg('HTML alternative message part saved as '.$alternative['DataFile']);
						// sam2kb> TODO: we may need to use $html_body here instead
						$strbody = file_get_contents($alternative['DataFile']);
						break; // stop after first alternative
					}
					elseif( $alternative['Type'] == 'text' )
					{	// Plain text
						dre_msg('Text alternative message part saved as '.$alternative['DataFile']);
						$strbody = imap_qprint( file_get_contents($alternative['DataFile']) );
						break; // stop after first alternative
					}
				}
			}
			elseif( $parsedMIME['Type'] == 'text' )
			{	// Mail is plain text
				dre_msg('Plain-text message part saved as '.$parsedMIME['DataFile']);
				$strbody = imap_qprint( file_get_contents($parsedMIME['DataFile']) );
			}
			elseif( $parsedMIME['Type'] == 'delivery-status' )
			{	// Mail is delivery-status
				$strbody = '';
				foreach( $decodedMIME[0]['Parts'] as $part )
				{
					$strbody .= imap_qprint( file_get_contents( $part['BodyFile'] ) );
				}
			}

			if( count($mimeParser->warnings) > 0 )
			{
				dre_msg( sprintf('<h4>%d warnings during decode:</h4>', count($mimeParser->warnings)) );
				foreach( $mimeParser->warnings as $k => $v )
				{
					dre_msg('Warning: '.$v.' at position '.$k);
				}
			}
		}
		unlink( $tmpMIME );

		if( empty($html_body) )
		{	// Plain-text message
			dre_msg('Message type: TEXT');
			dre_msg('Message body: <pre style="font-size:10px">'.htmlspecialchars($strbody).'</pre>');

			// Process body. First fix different line-endings (dos, mac, unix), remove double newlines
			$content = str_replace( array("\r", "\n\n"), "\n", trim($strbody) );
		}
		else
		{	// HTML message
			dre_msg('Message type: HTML');

			if( ($parsed_message = dre_prepare_html_message( $html_body )) === false )
			{	// No 'auth' tag provided, skip to the next message
				rmdir_r( $tmpDirMIME );
				continue;
			}
			list($auth, $content) = $parsed_message;
		}

		dre_msg('<b class="green">Success</b>');

		$message_text = $content;

		// Remove content after terminators
		$content = dre_limit_by_terminators( $content );

		global $Messages;
		if( $Messages->has_errors() )
		{
			// Make it easier for user to find and correct the errors
			dre_msg( "\n".sprintf( T_('Processing message: %s'), $post_title ), true );
			dre_msg( $Messages->get_string( T_('Cannot post, please correct these errors:'), 'error' ), true );

			$Messages->clear();
			rmdir_r( $tmpDirMIME );
			continue;
		}

		global $dre_emails, $DB, $localtimenow;

		dre_msg( sprintf('<h4>Saving the returned email in the database</h4>' ) );

		// Insert a returned email's data into DB
		if( $returned_email = dre_insert_returned_email( $content, $message_text, dre_get_headers( $decodedMIME ) ) )
		{
			dre_msg( 'Error Type: '.dre_decode_error_type( $returned_email['errtype'] ) );
			dre_msg( 'Error Message: '.$returned_email['errormsg'] );

			++$email_cntr;
		}

		// Delete temporary directory
		rmdir_r( $tmpDirMIME );

		if( $Settings->get('repath_delete_emails') )
		{
			dre_msg( 'Marking message for deletion from inbox: '.$index );
			imap_delete( $mbox, $index );
			++$del_cntr;
		}
	}

	// Expunge messages market for deletion
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

	dre_msg('<hr /><h3>Processing message:</h3>');

	dre_msg('Message body: <pre style="font-size:10px">'.htmlspecialchars( $content ).'</pre>');

	dre_msg('<b class="green">Success</b>');

	// Remove content after terminators
	$content = dre_limit_by_terminators( $content );

	dre_msg( sprintf('<h4>Saving the returned email in the database</h4>' ) );

	// Insert a returned email's data into DB
	if( $returned_email = dre_insert_returned_email( $content, $message_text, 'Empty headers' ) )
	{
		dre_msg( 'Error Type: '.dre_decode_error_type( $returned_email['errtype'] ) );
		dre_msg( 'Error Message: '.$returned_email['errormsg'] );
		return true;
	}
	else
	{
		return false;
	}
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
 * @return bool true if valid subject prefix is detected
 */
function dre_process_header( $header, & $subject, & $post_date )
{
	global $Settings;

	$subject = $header['Subject'];
	$ddate = $header['Date'];

	dre_msg('Subject: '.$subject);

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
	{	// Subject is not match to identify return email
		dre_msg( 'Subject prefix is not  "'.implode( '", "', $repath_subjects ).'", skip this email' );
		return false;
	}

	// Parse Date
	if( !preg_match('#^(.{3}, )?(\d{2}) (.{3}) (\d{4}) (\d{2}):(\d{2}):(\d{2})#', $ddate, $match) )
	{
		$ddate_U = @strtotime($ddate);
		if( empty($ddate_U) || strlen($ddate_U) < 2 )
		{
			dre_msg( sprintf( T_('Could not parse date header "%s"'), $ddate ), true );
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
			dre_msg( T_('Invalid month name in message date string.'), true );
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
 * @return string Content
 */
function dre_prepare_html_message( $message )
{
	dre_msg('Message body (original): <pre style="font-size:10px">'.htmlspecialchars($message).'</pre>');

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

	// Balance tags
	$content = balance_tags($content);

	// Remove markup that cause validator errors
	$patterns = array(
		'~ moz-do-not-send="true"~',			// Thunderbird inline image with absolute "src"
		'~ class="moz-signature" cols="\d+"~',	// Thunderbird signature in HTML message
		'~ goomoji="[^"]+"~',					// Gmail smilies
	);
	$content = preg_replace( $patterns, '', $content );

	dre_msg('Message body (processed): <pre style="font-size:10px">'.htmlspecialchars($content).'</pre>');

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
		'P' => T_('Permament error'),
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
 * Insert a returned email's data into DB
 *
 * @param string Prepared message text (without text after body terminator)
 * @param string Full message text
 * @param string Headers
 * @return array|boolean Data of returned email| False
 */
function dre_insert_returned_email( $content, $message_text, $headers )
{
	global $DB, $dre_emails;

	// Extract emails from content
	$emails = utf8_strtolower( dre_get_emails( $content ) );

	// Get content between email and body terminator
	$content = dre_get_processing_content( $content, $emails );

	// Get Error info
	$error_info = dre_get_error_info( $content );

	$email_returned = array(
			'address'  => $emails,
			'errormsg' => $error_info['text'],
			'message'  => $message_text,
			'headers'  => $headers,
			'errtype'  => $error_info['type']
		);

	// INSERT RETURNED DATA INTO DB
	$DB->query( 'INSERT INTO T_email__returns ( emret_address, emret_errormsg, emret_message, emret_headers, emret_errtype )
		VALUES ( '.$DB->quote( $email_returned ).' )' );

	if( $DB->insert_id > 0 )
	{
		// Save a blocked email's data
		dre_save_blocked_email( $email_returned );

		// Save saved emails for reports
		$dre_emails[] = $email_returned;

		return $email_returned;
	}
	else
	{
		return false;
	}
}

/**
 * Insert/Update a blocked email's data into DB
 *
 * @param array Data of returned email:
 *               'address'
 *               'errormsg'
 *               'message'
 *               'headers'
 *               'errtype'
 */
function dre_save_blocked_email( $email_returned )
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