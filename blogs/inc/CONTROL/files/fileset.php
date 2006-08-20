<?php
/**
 * This file implements the UI controller for file settings management.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
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


$AdminUI->set_path( 'options', 'files' );

param( 'action', 'string' );

switch( $action )
{
	case 'update':
		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		param( 'submit', 'array', array() );
		if( isset($submit['restore_defaults']) )
		{
			$Settings->delete_array( array(
					'fm_enabled',
					'fm_enable_roots_blog',
					// 'fm_enable_roots_group',
					'fm_enable_roots_user',
					'fm_enable_create_dir',
					'fm_enable_create_file',
					'upload_enabled',
					'upload_allowedext',
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
			param( 'fm_enabled', 'integer', 0 );
			$Settings->set( 'fm_enabled', $fm_enabled );

			param( 'fm_enable_roots_blog', 'integer', 0 );
			$Settings->set( 'fm_enable_roots_blog', $fm_enable_roots_blog );

			// param( 'fm_enable_roots_group', 'fm_enable_roots_group', 'integer', 0 );

			param( 'fm_enable_roots_user', 'integer', 0 );
			$Settings->set( 'fm_enable_roots_user', $fm_enable_roots_user );

			param( 'fm_enable_create_dir', 'integer', 0 );
			$Settings->set( 'fm_enable_create_dir', $fm_enable_create_dir );

			param( 'fm_enable_create_file', 'integer', 0 );
			$Settings->set( 'fm_enable_create_file', $fm_enable_create_file );

			// Upload
			param( 'upload_enabled', 'integer', 0 );
			$Settings->set( 'upload_enabled', $upload_enabled );

			param_integer_range( 'upload_maxkb', 1, $upload_maxmaxkb, T_('Maximum allowed filesize must be between %d and %d KB.') );
			$Settings->set( 'upload_maxkb', $upload_maxkb );

			// Advanced settings
			param( 'regexp_filename', 'string', '' );
			if( param_check_regexp( 'regexp_filename', T_('Valid filename pattern is not a regular expression!') ) )
			{
				$Settings->set( 'regexp_filename', $regexp_filename );
			}
			param( 'regexp_dirname', 'string', '' );
			if( param_check_regexp( 'regexp_dirname', T_('Valid dirname pattern is not a regular expression!') ) )
			{
				$Settings->set( 'regexp_dirname', $regexp_dirname );
			}

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

		break;
}

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

// Display VIEW:
$AdminUI->disp_view( 'files/_set_files.form.php' );

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>