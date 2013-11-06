<?php
/**
 * This file implements the UI controller for Wordpress XML importer.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

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
		param_check_not_empty( 'wp_blog_ID', T_('Please select a blog!') );
		
		// XML File
		$xml_file = $_FILES['wp_file'];
		if( $xml_file['size'] == 0 )
		{	// File is empty
			param_error( 'wp_file', T_('Please select XML file to import.') );
		}
		else if( !preg_match( '/\.xml$/i', $xml_file['name'] ) )
		{	// Extension is incorrect
			param_error( 'wp_file', sprintf( T_('&laquo;%s&raquo; has an unrecognized extension.'), $xml['name'] ) );
		}
		else
		{	// Check WordPress XML file for correct format
			wpxml_check_file( $xml_file['tmp_name'] );
		}

		if( param_errors_detected() )
		{	// Stop import if errors exist
			$action = 'file';
			break;
		}

		break;
}


// Highlight the requested tab (if valid):
$AdminUI->set_path( 'options', 'misc', 'import' );

$AdminUI->breadcrumbpath_init( false );
$AdminUI->breadcrumbpath_add( T_('System'), '?ctrl=system' );
$AdminUI->breadcrumbpath_add( T_('Maintenance'), '?ctrl=tools' );
$AdminUI->breadcrumbpath_add( T_('Import'), '?ctrl=tools&amp;tab3=import' );
$AdminUI->breadcrumbpath_add( T_('phpBB Importer'), '?ctrl=phpbbimport' );


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

/*
 * $Log$
 * Revision 1.2  2013/11/06 08:04:54  efy-asimo
 * Update to version 5.0.1-alpha-5
 *
 */
?>