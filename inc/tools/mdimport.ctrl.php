<?php
/**
 * This file implements the UI controller for Markdown Importer.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2019 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 * @author fplanque: Francois PLANQUE.
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Check permission:
$current_User->check_perm( 'admin', 'normal', true );
$current_User->check_perm( 'options', 'edit', true );

load_class( 'tools/model/_markdownimport.class.php', 'MarkdownImport' );

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

if( param( 'md_blog_ID', 'integer', 0 ) > 0 )
{	// Save last import collection in Session:
	$Session->set( 'last_import_coll_ID', get_param( 'md_blog_ID' ) );

	// Save last used import controller in Session:
	$Session->set( 'last_import_controller_'.get_param( 'md_blog_ID' ), 'markdown' );
}

switch( $action )
{
	case 'import':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'mdimport' );

		// Initialize markdown import object:
		if( $app_pro )
		{	// Use PRO markdown import:
			load_class( 'tools/model/_markdownimportpro.class.php', 'MarkdownImportPro' );
			$MarkdownImport = new MarkdownImportPro();
		}
		else
		{	// Use default markdown import:
			$MarkdownImport = new MarkdownImport();
		}

		// Load import data from request:
		if( ! $MarkdownImport->load_from_Request() )
		{	// Don't import if errors have been detected:
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
$AdminUI->breadcrumbpath_add( T_('Markdown Importer'), $admin_url.'?ctrl=mdimport' );

// Set an url for manual page:
$AdminUI->set_page_manual_link( 'markdown-importer' );


// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

switch( $action )
{
	case 'import':
		// Step 2:
		$AdminUI->disp_view( 'tools/views/_md_import.form.php' );
		break;

	case 'file':
	default:
		// Step 1:
		// Initialize markdown import object:
		if( $app_pro )
		{	// Use PRO markdown import:
			load_class( 'tools/model/_markdownimportpro.class.php', 'MarkdownImportPro' );
			$MarkdownImport = new MarkdownImportPro();
		}
		else
		{	// Use default markdown import:
			$MarkdownImport = new MarkdownImport();
		}
		$AdminUI->disp_view( 'tools/views/_md_file.form.php' );
		break;
}


// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>