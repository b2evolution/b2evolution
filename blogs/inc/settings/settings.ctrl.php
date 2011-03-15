<?php
/**
 * This file implements the UI controller for settings management.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
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
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Check minimum permission:
$current_User->check_perm( 'options', 'view', true );


$AdminUI->set_path( 'options', 'general' );

param( 'action', 'string' );

switch( $action )
{
	case 'update':
		// UPDATE general settings:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'globalsettings' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		if( param( 'default_blog_ID', 'integer', NULL ) !== NULL )
		{
			$Settings->set( 'default_blog_ID', $default_blog_ID );
		}

		// Session timeout
		$timeout_sessions = param_duration( 'timeout_sessions' );

		if( $timeout_sessions < 300 )
		{ // lower than 5 minutes: not allowed
			param_error( 'timeout_sessions', sprintf( T_( 'You cannot set a session timeout below %d seconds.' ), 300 ) );
		}
		elseif( $timeout_sessions < 86400 )
		{ // lower than 1 day: notice/warning
			$Messages->add( sprintf( T_( 'Warning: your session timeout is just %d seconds. Your users may have to re-login often!' ), $timeout_sessions ), 'note' );
		}
		$Settings->set( 'timeout_sessions', $timeout_sessions );

		// Reload page timeout
		$reloadpage_timeout = param_duration( 'reloadpage_timeout' );

		if( $reloadpage_timeout > 99999 )
		{
			param_error( 'reloadpage_timeout', sprintf( T_( 'Reload-page timeout must be between %d and %d seconds.' ), 0, 99999 ) );
		}
		$Settings->set( 'reloadpage_timeout', $reloadpage_timeout );

		$new_cache_status = param( 'general_cache_enabled', 'integer', 0 );
		if( ! $Messages->has_errors() )
		{
			load_funcs( 'collections/model/_blog.funcs.php' );
			$result = set_cache_enabled( 'general_cache_enabled', $new_cache_status, NULL, false );
			if( $result != NULL )
			{ // general cache setting was changed
				list( $status, $message ) = $result;
				$Messages->add( $message, $status );
			}
		}

		$Settings->set( 'newblog_cache_enabled', param( 'newblog_cache_enabled', 'integer', 0 ) );
		$Settings->set( 'newblog_cache_enabled_widget', param( 'newblog_cache_enabled_widget', 'integer', 0 ) );

		if( ! $Messages->has_errors() )
		{
			$Settings->dbupdate();
			$Messages->add( T_('General settings updated.'), 'success' );
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=settings', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		break;
}


$AdminUI->breadcrumbpath_init();
$AdminUI->breadcrumbpath_add( T_('Global settings'), '?ctrl=settings',
		T_('Global settings are shared between all blogs; see Blog settings for more granular settings.') );
$AdminUI->breadcrumbpath_add( T_('General'), '?ctrl=settings' );


// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

// Display VIEW:
$AdminUI->disp_view( 'settings/views/_general.form.php' );

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();


/*
 * $Log$
 * Revision 1.25  2011/03/15 09:34:05  efy-asimo
 * have checkboxes for enabling caching in new blogs
 * refactorize cache create/enable/disable
 *
 * Revision 1.24  2010/11/25 15:16:35  efy-asimo
 * refactor $Messages
 *
 * Revision 1.23  2010/06/24 07:03:11  efy-asimo
 * move the cross posting options to the bottom of teh Features tab & fix error message after moving post
 *
 * Revision 1.22  2010/05/22 12:22:49  efy-asimo
 * move $allow_cross_posting in the backoffice
 *
 * Revision 1.21  2010/02/08 17:53:55  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.20  2010/01/30 18:55:34  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.19  2010/01/02 21:11:59  fplanque
 * fat reduction / cleanup
 *
 * Revision 1.18  2009/12/06 22:55:21  fplanque
 * Started breadcrumbs feature in admin.
 * Work in progress. Help welcome ;)
 * Also move file settings to Files tab and made FM always enabled
 *
 * Revision 1.17  2009/10/28 10:56:34  efy-maxim
 * param_duration
 *
 * Revision 1.16  2009/10/27 23:06:46  fplanque
 * doc
 *
 * Revision 1.15  2009/10/27 13:27:49  efy-maxim
 * 1. months and seconds fields in duration field
 * 2. duration fields instead simple text fields
 *
 * Revision 1.14  2009/09/26 12:00:43  tblue246
 * Minor/coding style
 *
 * Revision 1.13  2009/09/16 05:35:47  efy-bogdan
 * Require country checkbox added
 *
 * Revision 1.12  2009/09/15 22:33:20  efy-bogdan
 * Require country checkbox added
 *
 * Revision 1.11  2009/09/15 09:20:47  efy-bogdan
 * Moved the "email validation" and the "security options" blocks to the Users -> Registration tab
 *
 * Revision 1.10  2009/09/14 13:41:44  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.9  2009/09/14 11:54:21  efy-bogdan
 * Moved Default user permissions under a new tab
 *
 * Revision 1.8  2009/09/03 15:51:52  tblue246
 * Doc, "refix", use "0" instead of an empty string for the "No blog" option.
 *
 * Revision 1.7  2009/09/02 23:27:20  fplanque
 * != works, doesn't it?
 * Tblue> No, it does _not_. If you select "No blog", the setting does
 * not get set to 0 because comparing 0 and NULL using the != operator
 * gives false.
 *
 * Revision 1.6  2009/09/02 18:01:51  tblue246
 * minor
 *
 * Revision 1.5  2009/09/02 17:47:25  fplanque
 * doc/minor
 */
?>