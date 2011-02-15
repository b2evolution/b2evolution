<?php
/**
 * This file implements the UI controller for Global Features.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 * Parts of this file are copyright (c)2005 by Halton STEWART - {@link http://hstewart.net/}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 *
 * Halton STEWART grants Francois PLANQUE the right to license
 * Halton STEWART's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author halton: Halton STEWART.
 * @author fplanque: Francois PLANQUE.
 * @author blueyed: Daniel HAHLER.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Check minimum permission:
$current_User->check_perm( 'options', 'view', true );


$AdminUI->set_path( 'options', 'features' );

param( 'action', 'string' );

switch( $action )
{
	case 'update':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'globalsettings' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// fp> Restore defaults has been removed because it's extra maintenance work and no real benefit to the user.

		// Online help
		param( 'webhelp_enabled', 'integer', 0 );
		$Settings->set( 'webhelp_enabled', $webhelp_enabled );

		// Outbound pinging:
 		param( 'outbound_notifications_mode', 'string', true );
		$Settings->set( 'outbound_notifications_mode',  get_param('outbound_notifications_mode') );

		// Blog by email
		param( 'eblog_enabled', 'boolean', 0 );
		$Settings->set( 'eblog_enabled', $eblog_enabled );

		param( 'eblog_method', 'string', true );
		$Settings->set( 'eblog_method', strtolower(trim($eblog_method)));

		param( 'eblog_encrypt', 'string', true );
		$Settings->set( 'eblog_encrypt', $eblog_encrypt );

		param( 'eblog_novalidatecert', 'boolean', 0 );
		$Settings->set( 'eblog_novalidatecert', $eblog_novalidatecert );

		param( 'eblog_server_host', 'string', true );
		$Settings->set( 'eblog_server_host', evo_strtolower(trim($eblog_server_host)));

		param( 'eblog_server_port', 'integer', true );
		$Settings->set( 'eblog_server_port', $eblog_server_port );

		param( 'eblog_username', 'string', true );
		$Settings->set( 'eblog_username', trim($eblog_username));

		param( 'eblog_password', 'string', true );
		$Settings->set( 'eblog_password', trim($eblog_password));

		param( 'eblog_default_category', 'integer', true );
		$Settings->set( 'eblog_default_category', $eblog_default_category );

		param( 'eblog_subject_prefix', 'string', true );
		$Settings->set( 'eblog_subject_prefix', trim($eblog_subject_prefix) );

		param( 'AutoBR', 'boolean', 0 );
		$Settings->set( 'AutoBR', $AutoBR );

		param( 'eblog_body_terminator', 'string', true );
		$Settings->set( 'eblog_body_terminator', trim($eblog_body_terminator) );

		param( 'eblog_test_mode', 'boolean', 0 );
		$Settings->set( 'eblog_test_mode', $eblog_test_mode );

		param( 'eblog_add_imgtag', 'boolean', 0 );
		$Settings->set( 'eblog_add_imgtag', $eblog_add_imgtag );

		/* tblue> this isn't used/implemented at the moment
		param( 'eblog_phonemail', 'integer', 0 );
		$Settings->set( 'eblog_phonemail', $eblog_phonemail );

		param( 'eblog_phonemail_separator', 'string', true );
		$Settings->set( 'eblog_phonemail_separator', trim($eblog_phonemail_separator) );*/


		// Hit & Session logs
		$Settings->set( 'log_public_hits', param( 'log_public_hits', 'integer', 0 ) );
		$Settings->set( 'log_admin_hits', param( 'log_admin_hits', 'integer', 0 ) );
		$Settings->set( 'log_spam_hits', param( 'log_spam_hits', 'integer', 0 ) );

		param( 'auto_prune_stats_mode', 'string', true );
		$Settings->set( 'auto_prune_stats_mode',  get_param('auto_prune_stats_mode') );

		// TODO: offer to set-up cron job if mode == 'cron' and to remove cron job if mode != 'cron'

		param( 'auto_prune_stats', 'integer', $Settings->get_default('auto_prune_stats'), false, false, true, false );
		$Settings->set( 'auto_prune_stats', get_param('auto_prune_stats') );


		// Categories:
		$Settings->set( 'allow_moving_chapters', param( 'allow_moving_chapters', 'integer', 0 ) );
		$Settings->set( 'chapter_ordering', param( 'chapter_ordering', 'string', 'alpha' ) );

		$Settings->set( 'cross_posting', param( 'cross_posting', 'integer', 0 ) );
		$Settings->set( 'cross_posting_blogs', param( 'cross_posting_blogs', 'integer', 0 ) );

		//XML-RPC
		$Settings->set( 'general_xmlrpc', param( 'general_xmlrpc', 'integer', 0 ) );

		param( 'xmlrpc_default_title', 'string', true );
		$Settings->set( 'xmlrpc_default_title', trim($xmlrpc_default_title) );

		if( ! $Messages->has_errors() )
		{
			$Settings->dbupdate();
			$Messages->add( T_('Settings updated.'), 'success' );
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=features', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		break;
}


$AdminUI->breadcrumbpath_init();
$AdminUI->breadcrumbpath_add( T_('Global settings'), '?ctrl=settings',
		T_('Global settings are shared between all blogs; see Blog settings for more granular settings.') );
$AdminUI->breadcrumbpath_add( T_('Features'), '?ctrl=features' );


// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

// Display VIEW:
$AdminUI->disp_view( 'settings/views/_features.form.php' );

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

/*
 * $Log$
 * Revision 1.19  2011/02/15 05:31:53  sam2kb
 * evo_strtolower mbstring wrapper for strtolower function
 *
 * Revision 1.18  2010/11/25 15:16:35  efy-asimo
 * refactor $Messages
 *
 * Revision 1.17  2010/06/24 07:03:11  efy-asimo
 * move the cross posting options to the bottom of teh Features tab & fix error message after moving post
 *
 * Revision 1.16  2010/02/08 17:53:55  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.15  2010/01/02 21:11:59  fplanque
 * fat reduction / cleanup
 *
 * Revision 1.14  2010/01/02 17:24:31  fplanque
 * Crumbs - Proof of concept
 *
 * Revision 1.13  2009/12/06 22:55:21  fplanque
 * Started breadcrumbs feature in admin.
 * Work in progress. Help welcome ;)
 * Also move file settings to Files tab and made FM always enabled
 *
 * Revision 1.12  2009/09/02 13:47:31  waltercruz
 * Setting the default title fot posts created through blogger API
 *
 * Revision 1.11  2009/08/31 15:56:39  waltercruz
 * Adding setting to enable/disable xmlrc
 *
 * Revision 1.10  2009/03/08 23:57:45  fplanque
 * 2009
 *
 * Revision 1.9  2009/01/28 21:23:23  fplanque
 * Manual ordering of categories
 *
 * Revision 1.8  2008/10/07 16:54:40  tblue246
 * Unset all settings if resetting to default values (some were missing)
 *
 * Revision 1.7  2008/10/06 18:11:58  tblue246
 * Further blog by email fixes
 *
 * Revision 1.6  2008/10/06 11:02:27  tblue246
 * Blog by mail now supports POP3 & IMAP, SSL & TLS
 *
 * Revision 1.5  2008/10/05 10:55:46  tblue246
 * Blog by mail: We've only one working method => removed the drop-down box and added automatical change to pop3a.
 * The default value for this setting was in the wrong file, moved.
 *
 * Revision 1.4  2008/10/04 14:25:25  tblue246
 * Code improvements in blog/cron/getmail.php, e. g. option to add <img> tags for image attachments.
 * All attachments now get added to the post if the filename is valid (validate_filename()). Not sure if this is secure, but should be.
 *
 * Revision 1.3  2008/02/19 11:11:19  fplanque
 * no message
 *
 * Revision 1.2  2008/01/21 09:35:34  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/06/25 11:01:12  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.19  2007/04/26 00:11:14  fplanque
 * (c) 2007
 *
 * Revision 1.18  2006/12/11 00:32:26  fplanque
 * allow_moving_chapters stting moved to UI
 * chapters are now called categories in the UI
 *
 * Revision 1.17  2006/12/07 00:55:52  fplanque
 * reorganized some settings
 *
 * Revision 1.16  2006/11/24 18:27:23  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 */
?>
