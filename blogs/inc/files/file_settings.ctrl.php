<?php
/**
 * This file implements the UI controller for file settings management.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
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
 * PROGIDISTRI S.A.S. grants Francois PLANQUE the right to license
 * PROGIDISTRI S.A.S.'s contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 * @author blueyed: Daniel HAHLER.
 * @author mbruneau: Marc BRUNEAU / PROGIDISTRI
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Check minimum permission:
$current_User->check_perm( 'options', 'view', true );


$AdminUI->set_path( 'files', 'settings' );

param( 'action', 'string' );

switch( $action )
{
	case 'update':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'file' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		param( 'submit', 'array', array() );
		if( isset($submit['restore_defaults']) )
		{
			$Settings->delete_array( array(
					'fm_enable_roots_blog',
					'fm_enable_roots_user',
					'fm_enable_roots_shared',
					'fm_enable_roots_skins',
					'fm_enable_create_dir',
					'fm_default_chmod_dir',
					'fm_enable_create_file',
					'fm_default_chmod_file',
					'upload_enabled',
					'upload_maxkb',
					'regexp_filename' ) );
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
			// Filemanager
			param( 'fm_enable_roots_blog', 'integer', 0 );
			$Settings->set( 'fm_enable_roots_blog', $fm_enable_roots_blog );

			param( 'fm_enable_roots_user', 'integer', 0 );
			$Settings->set( 'fm_enable_roots_user', $fm_enable_roots_user );

			param( 'fm_enable_roots_shared', 'integer', 0 );
			$Settings->set( 'fm_enable_roots_shared', $fm_enable_roots_shared );

			param( 'fm_enable_roots_skins', 'integer', 0 );
			$Settings->set( 'fm_enable_roots_skins', $fm_enable_roots_skins );

			param( 'fm_enable_create_dir', 'integer', 0 );
			$Settings->set( 'fm_enable_create_dir', $fm_enable_create_dir );

			// Default dir CHMOD:
			if( param( 'fm_default_chmod_dir', 'string', NULL ) !== NULL )
			{
				if( ! preg_match('~^[0-7]{3}$~', $fm_default_chmod_dir) )
				{
					param_error('fm_default_chmod_dir', T_('Invalid CHMOD value. Use 3 digits.'));
				}

				$Settings->set( 'fm_default_chmod_dir', $fm_default_chmod_dir );
			}

			param( 'fm_enable_create_file', 'integer', 0 );
			$Settings->set( 'fm_enable_create_file', $fm_enable_create_file );

			// Default files CHMOD:
			if( param( 'fm_default_chmod_file', 'string', NULL ) !== NULL )
			{
				if( ! preg_match('~^[0-7]{3}$~', $fm_default_chmod_file) )
				{
					param_error('fm_default_chmod_file', T_('Invalid CHMOD value. Use 3 digits.'));
				}

				$Settings->set( 'fm_default_chmod_file', $fm_default_chmod_file );
			}

			// Upload
			param( 'upload_enabled', 'integer', 0 );
			$Settings->set( 'upload_enabled', $upload_enabled );

			param_integer_range( 'upload_maxkb', 1, $upload_maxmaxkb, T_('Maximum allowed filesize must be between %d and %d KB.') );
			$Settings->set( 'upload_maxkb', $upload_maxkb );

			// Advanced settings
			param( 'regexp_filename', 'string', '' );
			if( param_check_isregexp( 'regexp_filename', T_('Valid filename pattern is not a regular expression!') ) )
			{
				$Settings->set( 'regexp_filename', $regexp_filename );
			}
			param( 'regexp_dirname', 'string', '' );
			if( param_check_isregexp( 'regexp_dirname', T_('Valid dirname pattern is not a regular expression!') ) )
			{
				$Settings->set( 'regexp_dirname', $regexp_dirname );
			}
			param( 'evocache_foldername', 'string', '');
			$Settings->set( 'evocache_foldername', $evocache_foldername );

			if( ! $Messages->count('error') )
			{
				if( $Settings->dbupdate() )
				{
					$Messages->add( T_('File settings updated.'), 'success' );
				}
				else
				{
					$Messages->add( T_('Settings have not changed.'), 'note' );
				}
			}
		}
		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( '?ctrl=fileset', 303 ); // Will EXIT
		// We have EXITed already at this point!!

		break;
}

file_controller_build_tabs();

// fp> TODO: this here is a bit sketchy since we have Blog & fileroot not necessarilly in sync. Needs investigation / propositions.
// Note: having both allows to post from any media dir into any blog.
$AdminUI->breadcrumbpath_init();
$AdminUI->breadcrumbpath_add( T_('Files'), '?ctrl=files&amp;blog=$blog$' );
$AdminUI->breadcrumbpath_add( T_('Settings'), '?ctrl=fileset' );


// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

// Display VIEW:
$AdminUI->disp_view( 'files/views/_file_settings.form.php' );

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

/*
 * $Log$
 * Revision 1.9  2010/03/12 10:52:52  efy-asimo
 * Set EvoCache  folder names - task
 *
 * Revision 1.8  2010/02/08 17:52:14  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.7  2010/01/17 04:14:41  fplanque
 * minor / fixes
 *
 * Revision 1.6  2010/01/16 14:27:03  efy-yury
 * crumbs, fadeouts, redirect, action_icon
 *
 * Revision 1.5  2009/12/06 22:55:18  fplanque
 * Started breadcrumbs feature in admin.
 * Work in progress. Help welcome ;)
 * Also move file settings to Files tab and made FM always enabled
 *
 * Revision 1.4  2009/03/08 23:57:42  fplanque
 * 2009
 *
 * Revision 1.3  2008/09/23 06:18:34  fplanque
 * File manager now supports a shared directory (/media/shared/global/)
 *
 * Revision 1.2  2008/01/21 09:35:28  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/06/25 10:59:51  fplanque
 * MODULES (refactored MVC)
 *
 */
?>