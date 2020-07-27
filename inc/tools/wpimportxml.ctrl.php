<?php
/**
 * This file implements the UI controller for Wordpress XML importer.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 * @author fplanque: Francois PLANQUE.
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Check permission:
check_user_perm( 'admin', 'normal', true );
check_user_perm( 'options', 'edit', true );

load_funcs( 'tools/model/_wp.funcs.php' );
load_class( 'tools/model/_wordpressimport.class.php', 'WordpressImport' );

/**
 * @var action
 *
 * values:
 * 1) 'file'
 * 2) 'confirm'
 *   2.1) 'delete_extract'
 *   2.2) 'use_existing_folder'
 * 3) 'import'
 */
param_action();

if( !empty( $action ) )
{	// Try to obtain some serious time to do some serious processing (15 minutes)
	set_max_execution_time( 900 );
	// Turn off the output buffering to do the correct work of the function flush()
	@ini_set( 'output_buffering', 'off' );
}

if( param( 'wp_blog_ID', 'integer', 0, true ) > 0 )
{	// Save last import collection in Session:
	$Session->set( 'last_import_coll_ID', get_param( 'wp_blog_ID' ) );

	// Save last used import controller in Session:
	$Session->set( 'last_import_controller_'.get_param( 'wp_blog_ID' ), 'xml' );
}

switch( $action )
{
	case 'confirm':
	case 'delete_extract':
	case 'use_existing_folder':
	case 'import':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'wpxml' );

		$WordpressImport = new WordpressImport();

		// Load import data from request:
		if( ! $WordpressImport->load_from_Request() )
		{	// Don't import if errors have been detected:
			$action = ( $action == 'confirm' ? 'file' : 'confirm' );
		}

		if( in_array( $action, array( 'confirm', 'delete_extract', 'use_existing_folder' ) ) )
		{	// Don't log into file for the confirm screen before start importing:
			$WordpressImport->log_file = false;
		}
		break;
}


// Highlight the requested tab (if valid):
$AdminUI->set_path( 'options', 'misc', 'import' );

$AdminUI->breadcrumbpath_init( false );
$AdminUI->breadcrumbpath_add( 'System', $admin_url.'?ctrl=system' );
$AdminUI->breadcrumbpath_add( 'Maintenance', $admin_url.'?ctrl=tools' );
$AdminUI->breadcrumbpath_add( 'Import', $admin_url.'?ctrl=tools&amp;tab3=import' );
$AdminUI->breadcrumbpath_add( 'WordPress XML Importer', $admin_url.'?ctrl=wpimportxml' );

// Set an url for manual page:
$AdminUI->set_page_manual_link( 'xml-importer' );


// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

switch( $action )
{
	case 'confirm':	// Step 2
	case 'delete_extract': // Delete and extract again
	case 'use_existing_folder': // Continue with existing folder
		$AdminUI->disp_view( 'tools/views/_wpxml_confirm.form.php' );
		break;

	case 'import':	// Step 3
		$AdminUI->disp_view( 'tools/views/_wpxml_import.form.php' );
		break;

	case 'file':	// Step 1
	default:
		$AdminUI->disp_view( 'tools/views/_wpxml_file.form.php' );
		break;
}


// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>