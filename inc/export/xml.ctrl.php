<?php
/**
 * This file implements the UI controller for Export module
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 * @author fplanque: Francois PLANQUE.
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $export_Module;

// Check permission:
$current_User->check_perm( 'options', 'edit', true );

load_funcs( 'export/model/_export.funcs.php' );
load_funcs( 'locales/_charset.funcs.php' );

param_action();

switch( $action )
{
	case 'export':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'exportxml' );


		$blog_ID = param( 'blog_ID', 'integer', 0 );
		param_check_not_empty( 'blog_ID', T_('Please select a blog!') );

		param( 'options', 'array', array() );

		if( empty( $options ) )
		{
			param_error( 'options[all]', $export_Module->T_('Please select at least one option what you want to export') );
		}

		if( param_errors_detected() )
		{	// Stop export if errors exist
			break;
		}

		// Do not append Debuglog to XML file!
		$debug = false;

		// Do not append Debug JSlog to XML file!
		$debug_jslog = false;

		// Export collection data:
		export_xml( array(
				'blog_ID' => $blog_ID,
				'options' => $options,
			) );
}

// Highlight the requested tab (if valid):
$AdminUI->set_path( 'options', 'misc', 'export' );

$AdminUI->breadcrumbpath_init( false );
$AdminUI->breadcrumbpath_add( $export_Module->T_('System'), '?ctrl=system' );
$AdminUI->breadcrumbpath_add( $export_Module->T_('Maintenance'), '?ctrl=tools' );
$AdminUI->breadcrumbpath_add( $export_Module->T_('Export'), '?ctrl=exportxml' );

$AdminUI->set_page_manual_link( 'export-xml-zip' );

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

if( empty( $options ) )
{	// Set default options to export
	$options = array(
		//'all' => 0,
		'user' => 1,
		//'pass' => 0,
		'cat' => 1,
		'tag' => 1,
		'post' => 1,
		'comment' => 1,
		'file' => 1
	);
}
$AdminUI->disp_view( 'export/views/_xml.form.php' );

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();
?>