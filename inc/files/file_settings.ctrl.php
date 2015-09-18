<?php
/**
 * This file implements the UI controller for file settings management.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Check minimum permission:
$current_User->check_perm( 'options', 'view', true );


param( 'action', 'string' );

if( $demo_mode && !empty($action) )
{
	$Messages->add( 'You cannot make any edits on this screen while in demo mode.', 'error' );
	$action = '';
}

switch( $action )
{
	case 'update':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'file' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		param( 'submit', 'array:string', array() );
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
					'regexp_filename',
					'exif_orientation',
					'fm_resize_enable',
					'fm_resize_width',
					'fm_resize_height',
					'fm_resize_quality' ) );
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
			$old_foldername = $Settings->get( 'evocache_foldername' );
			if( $old_foldername != $evocache_foldername)
			{ // ?evocache folder name has changed
				if( rename_cachefolders( $old_foldername, $evocache_foldername ) )
				{
					$Messages->add( sprintf( T_( 'All %s folders have been renamed to %s' ), $old_foldername, $evocache_foldername ), 'success' );
				}
				else
				{
					$Messages->add( sprintf( T_( 'Some %s folders could not be renamed to %s' ), $old_foldername, $evocache_foldername ), 'warning' );
				}
				$Settings->set( 'evocache_foldername', $evocache_foldername );
			}

			// Save Image options
			param( 'exif_orientation', 'integer', 0 );
			$Settings->set( 'exif_orientation', $exif_orientation );
			param( 'fm_resize_enable', 'integer', 0 );
			$Settings->set( 'fm_resize_enable', $fm_resize_enable );
			param( 'fm_resize_width', 'integer', 0 );
			$Settings->set( 'fm_resize_width', $fm_resize_width );
			param( 'fm_resize_height', 'integer', 0 );
			$Settings->set( 'fm_resize_height', $fm_resize_height );
			param_integer_range( 'fm_resize_quality', 0, 100, T_('The compression value must be between %d and %d.') );
			$Settings->set( 'fm_resize_quality', $fm_resize_quality );

			if( ! $Messages->has_errors() )
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

/**
 * We need make this call to build menu for all modules
 */
$AdminUI->set_path( 'files' );

file_controller_build_tabs();

$AdminUI->set_path( 'files', 'settings', 'settings' );

// fp> TODO: this here is a bit sketchy since we have Blog & fileroot not necessarilly in sync. Needs investigation / propositions.
// Note: having both allows to post from any media dir into any blog.
$AdminUI->breadcrumbpath_init( false );
$AdminUI->breadcrumbpath_add( T_('Files'), '?ctrl=files&amp;blog=$blog$' );
$AdminUI->breadcrumbpath_add( T_('Settings'), '?ctrl=fileset' );

// Set an url for manual page:
$AdminUI->set_page_manual_link( 'file-settings' );

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

?>