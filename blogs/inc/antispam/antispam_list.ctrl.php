<?php
/**
 * This file implements the UI controller for the antispam management.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004 by Vegar BERG GULDAL - {@link http://funky-m.com/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * Vegar BERG GULDAL grants Francois PLANQUE the right to license
 * Vegar BERG GULDAL's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * Halton STEWART grants Francois PLANQUE the right to license
 * Halton STEWART's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 * @author vegarg: Vegar BERG GULDAL.
 * @author halton: Halton STEWART.
 *
 * @todo Allow applying / re-checking of the known data, not just after an update!
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

$AdminUI->set_path( 'tools', 'antispam' );

param_action( '' );
param( 'confirm', 'string' );
param( 'keyword', 'string', '', true );
param( 'domain', 'string' );
param( 'filteron', 'string', '', true );
param( 'filter', 'array', array() );

if( isset($filter['off']) )
{
	unset( $filteron );
	forget_param( 'filteron' );
}

// Check permission:
$current_User->check_perm( 'spamblacklist', 'view', true );

switch( $action )
{
	case 'ban': // only an action if further "actions" given
		// Check permission:
		$current_User->check_perm( 'spamblacklist', 'edit', true ); // TODO: This should become different for 'edit'/'add' perm level - check for 'add' here.

		$keyword = evo_substr( $keyword, 0, 80 );
		param( 'delhits', 'integer', 0 );
		param( 'delcomments', 'integer', 0 );
		param( 'blacklist_locally', 'integer', 0 );
		param( 'report', 'integer', 0 );

		// Check if the string is too short,
		// it has to be a minimum of 5 characters to avoid being too generic
		if( evo_strlen($keyword) < 5 )
		{
			$Messages->add( sprintf( T_('The keyword &laquo;%s&raquo; is too short, it has to be a minimum of 5 characters!'), htmlspecialchars($keyword) ), 'error' );
			break;
		}

		if( $delhits )
		{ // Delete all banned hit-log entries
			$r = $DB->query('DELETE FROM T_hitlog
												WHERE hit_referer LIKE '.$DB->quote('%'.$keyword.'%'),
												'Delete all banned hit-log entries' );

			$Messages->add( sprintf( T_('Deleted %d logged hits matching &laquo;%s&raquo;.'), $r, htmlspecialchars($keyword) ), 'success' );
		}

		if( $delcomments )
		{ // Then all banned comments
			$r = $DB->query('DELETE FROM T_comments
			                  WHERE comment_author LIKE '.$DB->quote('%'.$keyword.'%').'
			                     OR comment_author_email LIKE '.$DB->quote('%'.$keyword.'%').'
			                     OR comment_author_url LIKE '.$DB->quote('%'.$keyword.'%').'
			                     OR comment_content LIKE '.$DB->quote('%'.$keyword.'%') );
			$Messages->add( sprintf( T_('Deleted %d comments matching &laquo;%s&raquo;.'), $r, htmlspecialchars($keyword) ), 'success' );
		}

		if( $blacklist_locally )
		{ // Local blacklist:
			if( antispam_create( $keyword ) )
			{
				$Messages->add( sprintf( T_('The keyword &laquo;%s&raquo; has been blacklisted locally.'), htmlspecialchars($keyword) ), 'success' );
			}
			else
			{ // TODO: message?
			}
		}

		if( $report )
		{ // Report this keyword as abuse:
			antispam_report_abuse( $keyword );
		}

		// We'll ask the user later what to do, if no "sub-action" given.
		break;


	case 'remove':
		// Remove a domain from ban list:

		// Check permission:
		$current_User->check_perm( 'spamblacklist', 'edit', true );

		param( 'hit_ID', 'integer', true );	// Required!
		$Messages->add( sprintf( T_('Removing entry #%d from the ban list...'), $hit_ID), 'note' );
		antispam_delete( $hit_ID );
		break;


	case 'report':
		// Report an entry as abuse to centralized blacklist:

		// Check permission:
		$current_User->check_perm( 'spamblacklist', 'edit', true );

		// Report this keyword as abuse:
		antispam_report_abuse( $keyword );
		break;


	case 'poll':
		// request abuse list from central blacklist:

		// Check permission:
		$current_User->check_perm( 'spamblacklist', 'edit', true );

		ob_start();
		antispam_poll_abuse();
		$Debuglog->add( ob_get_contents(), 'antispam_poll' );
		ob_end_clean();
		break;
}


// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();


if( $action == 'ban' && !$Messages->count('error') && !( $delhits || $delcomments || $blacklist_locally || $report ) )
{ // Nothing to do, ask user:
	$AdminUI->disp_view( 'antispam/views/_antispam_ban.form.php' );
}
else
{	// Display blacklist:
	$AdminUI->disp_view( 'antispam/views/_antispam_list.view.php' );
}

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();


/*
 * $Log$
 * Revision 1.6  2009/07/08 02:38:55  sam2kb
 * Replaced strlen & substr with their mbstring wrappers evo_strlen & evo_substr when needed
 *
 * Revision 1.5  2009/03/08 23:57:41  fplanque
 * 2009
 *
 * Revision 1.4  2008/04/04 17:02:24  fplanque
 * cleanup of global settings
 *
 * Revision 1.3  2008/01/21 09:35:25  fplanque
 * (c) 2008
 *
 * Revision 1.2  2007/09/04 14:56:19  fplanque
 * antispam cleanup
 *
 * Revision 1.1  2007/06/25 10:59:23  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.10  2007/04/26 00:11:14  fplanque
 * (c) 2007
 *
 * Revision 1.9  2007/03/01 02:42:03  fplanque
 * prevent miserable failure when trying to delete heavy spam.
 *
 * Revision 1.8  2006/12/07 21:16:55  fplanque
 * killed templates
 */
?>