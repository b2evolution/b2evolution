<?php
/**
 * This file implements the UI controller for Global Features.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 * Parts of this file are copyright (c)2005 by Halton STEWART - {@link http://hstewart.net/}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
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
		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		param( 'submit', 'array', array() );
		if( isset($submit['restore_defaults']) )
		{
			/*
			// TODO: insert some default settings rather than just delete them all, as per original configuration in the _advanced.php file:
			# mailserver settings
			$mailserver_url = 'mail.example.com';
			$mailserver_login = 'login@example.com';
			$mailserver_pass = 'password';
			$mailserver_port = 110;
			# by default posts will have this category
			$default_category = 1;
			# subject prefix
			$subjectprefix = 'blog:';
			# body terminator string (starting from this string, everything will be ignored, including this string)
			$bodyterminator = "___";
			# set this to 1 to run in test mode
			$thisisforfunonly = 0;
			### Special Configuration for some phone email services
			# some mobile phone email services will send identical subject & content on the same line
			# if you use such a service, set $use_phoneemail to 1, and indicate a separator string
			# when you compose your message, you'll type your subject then the separator string
			# then you type your login:password, then the separator, then content
			$use_phoneemail = 0;
			$phoneemail_separator = ':::';
			*/

			$Settings->delete_array( array(
				'eblog_enabled', 'eblog_method', 'eblog_server_host', 'eblog_server_port', 'eblog_username', 'eblog_password', 'eblog_default_category', 'eblog_subject_prefix',
				'log_public_hits', 'log_admin_hits', 'auto_prune_stats_mode', 'auto_prune_stats',
				'timeout_sessions',
				'webhelp_enabled' ) );

			if( $Settings->dbupdate() )
			{
				$Messages->add( T_('Restored default values.'), 'success' );
			}
			else
			{
				$Messages->add( T_('Settings have not changed.'), 'note' );
			}
		}
		else
		{
			// Online help
			$Request->param( 'webhelp_enabled', 'integer', 0 );
			$Settings->set( 'webhelp_enabled', $webhelp_enabled );


			// Blog by email
			$Request->param( 'eblog_enabled', 'integer', 0 );
			$Settings->set( 'eblog_enabled', $eblog_enabled );

			$Request->param( 'eblog_method', 'string', true );
			$Settings->set( 'eblog_method', strtolower(trim($eblog_method)));

			$Request->param( 'eblog_server_host', 'string', true );
			$Settings->set( 'eblog_server_host', strtolower(trim($eblog_server_host)));

			$Request->param( 'eblog_server_port', 'integer', 0 );
			$Settings->set( 'eblog_server_port', $eblog_server_port );

			$Request->param( 'eblog_username', 'string', true );
			$Settings->set( 'eblog_username', trim($eblog_username));

			$Request->param( 'eblog_password', 'string', true );
			$Settings->set( 'eblog_password', trim($eblog_password));

			$Request->param( 'eblog_default_category', 'integer', 0 );
			$Settings->set( 'eblog_default_category', $eblog_default_category );

			$Request->param( 'eblog_subject_prefix', 'string', true );
			$Settings->set( 'eblog_subject_prefix', trim($eblog_subject_prefix) );

			$Request->param( 'eblog_body_terminator', 'string', true );
			$Settings->set( 'eblog_body_terminator', trim($eblog_body_terminator) );

			$Request->param( 'eblog_test_mode', 'integer', 0 );
			$Settings->set( 'eblog_test_mode', $eblog_test_mode );

			$Request->param( 'eblog_phonemail', 'integer', 0 );
			$Settings->set( 'eblog_phonemail', $eblog_phonemail );

			$Request->param( 'eblog_phonemail_separator', 'string', true );
			$Settings->set( 'eblog_phonemail_separator', trim($eblog_phonemail_separator) );


			// Hit & Session logs
			$Settings->set( 'log_public_hits', $Request->param( 'log_public_hits', 'integer', 0 ) );
			$Settings->set( 'log_admin_hits', $Request->param( 'log_admin_hits', 'integer', 0 ) );

			$Request->param( 'auto_prune_stats_mode', 'string', true );
			$Settings->set( 'auto_prune_stats_mode',  get_param('auto_prune_stats_mode') );

			// TODO: offer to set-up cron job if mode == 'cron' and to remove cron job if mode != 'cron'

			$Request->param( 'auto_prune_stats', 'integer', $Settings->get_default('auto_prune_stats'), false, false, true, false );
			$Settings->set( 'auto_prune_stats', get_param('auto_prune_stats') );


			// Sessions
			$timeout_sessions = $Request->param( 'timeout_sessions', 'integer', $Settings->get_default('timeout_sessions') );
			if( $timeout_sessions < 300 )
			{ // lower than 5 minutes: not allowed
				$timeout_sessions = 300;
				$Messages->add( sprintf( T_( 'You cannot set a session timeout below %d seconds.' ), 300 ), 'error' );
			}
			elseif( $timeout_sessions < 86400 )
			{ // lower than 1 day: notice/warning
				$Messages->add( sprintf( T_( 'Warning: your session timeout is just %d seconds. Your users may have to re-login often!' ), $timeout_sessions ), 'note' );
			}
			$Settings->set( 'timeout_sessions', $timeout_sessions );


			if( ! $Messages->count('error') )
			{
				if( $Settings->dbupdate() )
				{
					$Messages->add( T_('Settings updated.'), 'success' );
				}
				else
				{
					$Messages->add( T_('Settings have not changed.'), 'note' );
				}
			}
		}
		break;
}


// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

// Display VIEW:
$AdminUI->disp_view( 'settings/_set_features.form.php' );

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

/*
 * $Log$
 * Revision 1.13  2006/08/20 20:12:32  fplanque
 * param_() refactoring part 1
 *
 * Revision 1.12  2006/08/07 22:29:32  fplanque
 * minor / doc
 *
 * Revision 1.11  2006/08/07 18:28:04  blueyed
 * Allow shorter session timeout values.
 *
 * Revision 1.10  2006/08/05 18:14:14  blueyed
 * Fixed eblog_server_port param.
 *
 * Revision 1.9  2006/07/06 19:59:08  fplanque
 * better logs, better stats, better pruning
 *
 * Revision 1.8  2006/06/15 17:53:38  fplanque
 * minor
 *
 * Revision 1.7  2006/06/01 18:36:09  fplanque
 * no message
 *
 * Revision 1.6.2.1  2006/05/19 15:06:23  fplanque
 * dirty sync
 *
 */
?>