<?php
/**
 * This file implements the UI controller for the antispam management.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}.
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

param( 'display_mode', 'string' );

if( $display_mode != 'js' )
{
	$AdminUI->set_path( 'tools', 'antispam' );
}
else
{	// This is an Ajax response
	// fp> TODO: have a more systematic way of handling AJAX responses
	header_content_type( 'text/html', $io_charset );
}

param_action( '' );
param( 'confirm', 'string' );
param( 'keyword', 'string', '', true );
param( 'domain', 'string' );
param( 'filteron', 'string', '', true );
param( 'filter', 'array', array() );

$tab3 = param( 'tab3', 'string', '', true );

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
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'antispam' );

		// Check permission:
		$current_User->check_perm( 'spamblacklist', 'edit', true ); // TODO: This should become different for 'edit'/'add' perm level - check for 'add' here.

		$keyword = evo_substr( $keyword, 0, 80 );
		param( 'delhits', 'integer', 0 );
		param( 'deldraft', 'integer', 0 );
		param( 'delpublished', 'integer', 0 );
		param( 'deldeprecated', 'integer', 0 );
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

		$delcomments = $deldraft || $delpublished || $deldeprecated;
		if( $delcomments )
		{ // select banned comments
			$del_condition = blog_restrict( $deldraft, $delpublished, $deldeprecated );
			$keyword_cond = '(comment_author LIKE '.$DB->quote('%'.$keyword.'%').'
							OR comment_author_email LIKE '.$DB->quote('%'.$keyword.'%').'
							OR comment_author_url LIKE '.$DB->quote('%'.$keyword.'%').'
							OR comment_content LIKE '.$DB->quote('%'.$keyword.'%').')';
			// asimo> we don't need transaction here 
			if( $display_mode == 'js' )
			{
				$query = 'SELECT comment_ID FROM T_comments
							  WHERE '.$keyword_cond.$del_condition;
				$deleted_ids = implode( ',', $DB->get_col($query, 0, 'Get comment ids awaiting for delete') );
			};
			// asimo> If a comment whith this keyword content was inserted here, the user will not even observe that (This is good)
			$r = $DB->query('DELETE FROM T_comments
			                  WHERE '.$keyword_cond.$del_condition );
			$Messages->add( sprintf( T_('Deleted %d comments matching &laquo;%s&raquo;.'), $r, htmlspecialchars($keyword) ), 'success' );
		}

		if( $blacklist_locally )
		{ // Local blacklist:
			if( antispam_create( $keyword ) )
			{
				$Messages->add( sprintf( T_('The keyword &laquo;%s&raquo; has been blacklisted locally.'), htmlspecialchars($keyword) ), 'success' );
				// Redirect so that a reload doesn't write to the DB twice:
				if( $display_mode != 'js' )
				{
					header_redirect( '?ctrl=antispam', 303 ); // Will EXIT
					// We have EXITed already at this point!!
				}
			}
			else
			{ // TODO: message?
			}
		}

		if( $report )
		{ // Report this keyword as abuse:
			antispam_report_abuse( $keyword );
		}

		param( 'request', 'string', '' );
		if( $display_mode == 'js' && $request != 'checkban' )
		{
			if( $delcomments && $r ) // $r not null => means the commentlist was deleted successfully
			{
				send_javascript_message( array( 'refreshAfterBan' => array( $deleted_ids ), 'closeAntispamSettings' => array() ), true );
			}
			else
			{
				send_javascript_message( array( 'closeAntispamSettings' => array() ), true );
			}
		}

		// We'll ask the user later what to do, if no "sub-action" given.
		break;


	case 'remove':
		// Remove a domain from ban list:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'antispam' );

		// Check permission:
		$current_User->check_perm( 'spamblacklist', 'edit', true );

		param( 'hit_ID', 'integer', true );	// Required!
		$Messages->add( sprintf( T_('Removing entry #%d from the ban list...'), $hit_ID), 'note' );
		antispam_delete( $hit_ID );
		break;


	case 'report':
		// Report an entry as abuse to centralized blacklist:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'antispam' );

		// Check permission:
		$current_User->check_perm( 'spamblacklist', 'edit', true );

		// Report this keyword as abuse:
		antispam_report_abuse( $keyword );
		break;


	case 'poll':
		// request abuse list from central blacklist:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'antispam' );

		// Check permission:
		$current_User->check_perm( 'spamblacklist', 'edit', true );

		ob_start();
		antispam_poll_abuse();
		$Debuglog->add( ob_get_contents(), 'antispam_poll' );
		ob_end_clean();
		break;

	case 'update':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'antispam' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// fp> Restore defaults has been removed because it's extra maintenance work and no real benefit to the user.

		param_integer_range( 'antispam_threshold_publish', -100, 100, T_('The threshold must be between -100 and 100.') );
		$Settings->set( 'antispam_threshold_publish', $antispam_threshold_publish );

		param_integer_range( 'antispam_threshold_delete', -100, 100, T_('The threshold must be between -100 and 100.') );
		$Settings->set( 'antispam_threshold_delete', $antispam_threshold_delete );

		param( 'antispam_block_spam_referers', 'integer', 0 );
		$Settings->set( 'antispam_block_spam_referers', $antispam_block_spam_referers );

		param( 'antispam_report_to_central', 'integer', 0 );
		$Settings->set( 'antispam_report_to_central', $antispam_report_to_central );

		$changed_weight = false;
		param( 'antispam_plugin_spam_weight', 'array', array() );
		foreach( $antispam_plugin_spam_weight as $l_plugin_ID => $l_weight )
		{
			if( ! is_numeric($l_weight) )
			{
				continue;
			}
			if( $l_weight < 0 || $l_weight > 100 )
			{
				param_error( 'antispam_plugin_spam_weight['.$l_plugin_ID.']', T_('Spam weight has to be in the range of 0-100.') );
				continue;
			}
			if( $DB->query( '
					UPDATE T_plugins
						 SET plug_spam_weight = '.$DB->quote($l_weight).'
					 WHERE plug_ID = '.(int)$l_plugin_ID ) )
			{
				$changed_weight = true;
			}
		}
		if( $changed_weight )
		{ // Reload plugins table (for display):
			$Plugins->loaded_plugins_table = false;
			$Plugins->load_plugins_table();
		}


		if( ! $Messages->has_errors() )
		{
			$Settings->dbupdate();

			$Messages->add( T_('Settings updated.'), 'success' );
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=antispam&tab3=settings', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}

	case 'find_spam_comments':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'antispam' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		$keywords = $DB->get_col('SELECT aspm_string FROM T_antispam');
		$keywords = array_chunk( $keywords, 100 );
		$rows_affected = 0;

		@ignore_user_abort(true);
		set_max_execution_time(900);

		// Delete comments in chunks of 100 keywords per SQL query
		foreach( $keywords as $chunk )
		{
			$arr = array();
			foreach( $chunk as $word )
			{
				$arr[] = $DB->quote('%'.$word.'%');
			}

			$DB->query('DELETE FROM T_comments
						WHERE (comment_author LIKE '.implode(' OR comment_author LIKE ', $arr).')
						OR (comment_author_email LIKE '.implode(' OR comment_author_email LIKE ', $arr).')
						OR (comment_author_url LIKE '.implode(' OR comment_author_url LIKE ', $arr).')
						OR (comment_content LIKE '.implode(' OR comment_content LIKE ', $arr).')',
						'Delete spam comments');

			$rows_affected = $rows_affected + $DB->rows_affected;
		}
		$Messages->add( sprintf( T_('Deleted %d comments'), $rows_affected ), 'success' );
		break;

	case 'find_spam_referers':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'antispam' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		$keywords = $DB->get_col('SELECT aspm_string FROM T_antispam');
		$keywords = array_chunk( $keywords, 100 );
		$rows_affected = 0;

		@ignore_user_abort(true);
		set_max_execution_time(900);

		// Delete hits in chunks of 100 keywords per SQL query
		foreach( $keywords as $chunk )
		{
			$arr = array();
			foreach( $chunk as $word )
			{
				$arr[] = $DB->quote('%'.$word.'%');
			}

			$DB->query('DELETE FROM T_hitlog
						WHERE hit_referer LIKE '.implode(' OR hit_referer LIKE ', $arr),
						'Delete all banned hit-log entries' );

			$rows_affected = $rows_affected + $DB->rows_affected;
		}
		$Messages->add( sprintf( T_('Deleted %d logged hits'), $rows_affected ), 'success' );
		break;
}

if( $display_mode != 'js')
{
	$AdminUI->breadcrumbpath_init( false );  // fp> I'm playing with the idea of keeping the current blog in the path here...
	$AdminUI->breadcrumbpath_add( T_('Tools'), '?ctrl=crontab' );
	$AdminUI->breadcrumbpath_add( T_('Antispam'), '?ctrl=antispam' );

	if( empty($tab3) )
	{
		$tab3 = 'blacklist';
	}
	switch( $tab3 )
	{
		case 'settings':
			$AdminUI->breadcrumbpath_add( T_('Settings'), '?ctrl=antispam&amp;tab3='.$tab3 );
			break;

		case 'tools':
			$AdminUI->breadcrumbpath_add( T_('Tools'), '?ctrl=antispam&amp;tab3='.$tab3 );
			break;

		case 'blacklist':
			$AdminUI->breadcrumbpath_add( T_('Blacklist'), '?ctrl=antispam' );
			break;
	}

	if( !empty($tab3) )
	{
		$AdminUI->append_path_level( $tab3 );
	}

	// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
	$AdminUI->disp_html_head();
	
	// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
	$AdminUI->disp_body_top();

	// Begin payload block:
	$AdminUI->disp_payload_begin();
}

switch( $tab3 )
{
	case 'settings':
		$AdminUI->disp_view( 'antispam/views/_antispam_settings.form.php' );
		break;

	case 'tools':
		$AdminUI->disp_view( 'antispam/views/_antispam_tools.view.php' );
		break;

	case 'blacklist':
	default:
		if( $action == 'ban' && !$Messages->has_errors() && !( $delhits || $delcomments || $blacklist_locally || $report ) )
		{	// Nothing to do, ask user:
			$AdminUI->disp_view( 'antispam/views/_antispam_ban.form.php' );
		}
		else
		{	// Display blacklist:
			$AdminUI->disp_view( 'antispam/views/_antispam_list.view.php' );
		}
		break;
}

// End payload block:
if( $display_mode != 'js')
{
	$AdminUI->disp_payload_end();

	// Display body bottom, debug info and close </html>:
	$AdminUI->disp_global_footer();
}


/*
 * $Log$
 * Revision 1.2  2011/09/05 14:20:21  sam2kb
 * minor / version update
 *
 * Revision 1.22  2011/09/05 14:17:26  sam2kb
 * Refactor antispam controller
 *
 * Revision 1.21  2011/02/10 23:07:21  fplanque
 * minor/doc
 *
 * Revision 1.20  2010/11/25 15:16:34  efy-asimo
 * refactor $Messages
 *
 * Revision 1.19  2010/10/18 15:29:35  efy-asimo
 * ajax calls charset - fix
 *
 * Revision 1.18  2010/06/01 11:33:19  efy-asimo
 * Split blog_comments advanced permission (published, deprecated, draft)
 * Use this new permissions (Antispam tool,when edit/delete comments)
 *
 * Revision 1.17  2010/05/14 08:16:04  efy-asimo
 * antispam tool ban form - create seperate table for different comments
 *
 * Revision 1.16  2010/03/05 09:22:25  efy-asimo
 * modify refresh comments visual effect on dashboard
 *
 * Revision 1.15  2010/03/03 15:59:46  fplanque
 * minor/doc
 *
 * Revision 1.14  2010/03/02 11:59:11  efy-asimo
 * refresh icon for dashboard comment list
 *
 * Revision 1.13  2010/02/28 23:38:39  fplanque
 * minor changes
 *
 * Revision 1.12  2010/02/26 08:34:33  efy-asimo
 * dashboard -> ban icon should be javascripted task
 *
 * Revision 1.11  2010/02/08 17:52:06  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.10  2010/01/17 04:14:45  fplanque
 * minor / fixes
 *
 * Revision 1.9  2010/01/16 14:27:03  efy-yury
 * crumbs, fadeouts, redirect, action_icon
 *
 * Revision 1.8  2010/01/03 17:56:05  fplanque
 * crumbs & stuff
 *
 * Revision 1.7  2009/12/06 22:55:22  fplanque
 * Started breadcrumbs feature in admin.
 * Work in progress. Help welcome ;)
 * Also move file settings to Files tab and made FM always enabled
 *
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