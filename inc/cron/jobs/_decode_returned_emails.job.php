<?php
/**
 * This file implements the return path inbox cron job
 *
 * Uses MIME E-mail message parser classes written by Manuel Lemos: {@link http://www.phpclasses.org/browse/package/3169.html}
 *
 * @author Stephan Knauss
 * @author tblue246: Tilman Blumenbach
 * @author sam2kb: Alex
 *
 * TODO:
 * - Try more exotic email clients like mobile phones
 * - TODO Tested and working with thunderbird (text, html, signed), yahoo mail (text, html), outlook webmail, K800i
 * - Allow the user to choose whether to upload attachments to the blog media folder or to his user root.
 * - Create a copy of check_html_sanity function and clean up dangerous HTML code
 * - Add support for shortcodes instead of <tags> similar to:
 *	[title Your post title]
 *	[category x,y,z]
 *	[excerpt]some excerpt[/excerpt]
 *	[tags x,y,z]
 *	[delay +1 hour]
 *	[comments on | off]
 *	[status publish | pending | draft | private]
 *	[slug some-url-name]
 *	[end] - everything after this shortcode is ignored (i.e. signatures)
 *	[more] - more tag
 *	[nextpage] - pagination
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Settings, $DB;
global $dre_messages, $dre_emails, $email_cntr, $del_cntr, $is_cron_mode;

// Are we in cron job mode?
$is_cron_mode = 'yes';

load_funcs( 'cron/model/_decode_returned_emails.funcs.php' );

if( ! $Settings->get( 'repath_enabled' ) )
{
	dre_msg( T_('Return path processing feature is not enabled.'), true );
	return 2; // error
}

if( ! extension_loaded('imap') )
{
	dre_msg( T_('The php_imap extension is not available to PHP on this server. Please load it in php.ini or ask your hosting provider to do so.'), true );
	return 2; // error
}

load_class( '_ext/mime_parser/rfc822_addresses.php', 'rfc822_addresses_class' );
load_class( '_ext/mime_parser/mime_parser.php', 'mime_parser_class' );

if( isset($GLOBALS['files_Module']) )
{
	load_funcs( 'files/model/_file.funcs.php');
}

if( ! $mbox = dre_connect( true ) )
{	// We couldn't connect to the mail server
	return 2; // error
}

// Read messages from server
dre_msg( T_('Reading messages from server'), true );
$imap_obj = imap_check( $mbox );
dre_msg( sprintf( T_('Found %d messages'), intval( $imap_obj->Nmsgs ) ), true );

if( $imap_obj->Nmsgs == 0 )
{
	dre_msg( T_('There are no messages in the mailbox'), true );
	imap_close( $mbox );
	return 1; // success
}

// Create posts
dre_process_messages( $mbox, $imap_obj->Nmsgs, true );

if( count( $del_cntr ) > 0 )
{	// We want to delete processed emails from server
	imap_expunge( $mbox );
	dre_msg( sprintf( T_('Deleted %d processed message(s) from inbox.'), $del_cntr ), true );
}

imap_close( $mbox );

// Show reports
if( $email_cntr > 0 )
{
	dre_msg( sprintf( T_('New emails saved: %d'), $email_cntr ), true );
}

return 1; // success

?>