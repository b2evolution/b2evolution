<?php
/**
 * This file implements the UI controller for Wordpress XML importer.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 * @author fplanque: Francois PLANQUE.
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Check permission:
$current_User->check_perm( 'admin', 'normal', true );
$current_User->check_perm( 'options', 'edit', true );

load_funcs( 'tools/model/_wp.funcs.php' );

/**
 * @var action
 *
 * values:
 * 1) 'file'
 * 2) 'import'
 */
param( 'action', 'string' );

if( !empty( $action ) )
{	// Try to obtain some serious time to do some serious processing (15 minutes)
	set_max_execution_time( 900 );
	// Turn off the output buffering to do the correct work of the function flush()
	@ini_set( 'output_buffering', 'off' );
}


switch( $action )
{
	case 'import':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'wpxml' );

		$wp_blog_ID = param( 'wp_blog_ID', 'integer', 0 );
		param_check_not_empty( 'wp_blog_ID', T_('Please select a collection!') );

		// XML File
		$xml_file = param( 'wp_file', 'string', '' );
		if( empty( $xml_file ) )
		{ // File is not selected
			param_error( 'wp_file', T_('Please select file to import.') );
		}
		else if( ! preg_match( '/\.(xml|txt|zip)$/i', $xml_file ) )
		{ // Extension is incorrect
			param_error( 'wp_file', sprintf( T_('&laquo;%s&raquo; has an unrecognized extension.'), $xml_file ) );
		}

		if( param_errors_detected() )
		{ // Stop import if errors exist
			$action = 'file';
			break;
		}

		break;
}


// Highlight the requested tab (if valid):
$AdminUI->set_path( 'options', 'misc', 'import' );

$AdminUI->breadcrumbpath_init( false );
$AdminUI->breadcrumbpath_add( T_('System'), $admin_url.'?ctrl=system' );
$AdminUI->breadcrumbpath_add( T_('Maintenance'), $admin_url.'?ctrl=tools' );
$AdminUI->breadcrumbpath_add( T_('Import'), $admin_url.'?ctrl=tools&amp;tab3=import' );
$AdminUI->breadcrumbpath_add( T_('WordPress XML Importer'), $admin_url.'?ctrl=wpimportxml' );

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
	case 'import':	// Step 2
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