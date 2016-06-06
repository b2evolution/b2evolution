<?php
/**
 * This file implements the UI controller for Central Antispam module
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 * @author fplanque: Francois PLANQUE.
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $central_antispam_Module;

load_funcs( 'central_antispam/model/_central_antispam.funcs.php' );
load_class( 'central_antispam/model/_keyword.class.php', 'CaKeyword' );
load_class( 'central_antispam/model/_source.class.php', 'CaSource' );

param_action( '', true );
param( 'tab', 'string', 'keywords', true );

switch( $tab )
{
	case 'keywords':
		if( param( 'cakw_ID', 'integer', '', true ) )
		{	// Load keyword from cache:
			$CaKeywordCache = & get_CaKeywordCache();
			if( ( $edited_CaKeyword = & $CaKeywordCache->get_by_ID( $cakw_ID, false ) ) === false )
			{
				unset( $edited_CaKeyword );
				forget_param( 'cakw_ID' );
				$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), $central_antispam_Module->T_('Keyword') ), 'error' );
				$action = 'nil';
			}
		}
		break;

	case 'sources':
		if( param( 'casrc_ID', 'integer', '', true ) )
		{	// Load source from cache:
			$CaSourceCache = & get_CaSourceCache();
			if( ( $edited_CaSource = & $CaSourceCache->get_by_ID( $casrc_ID, false ) ) === false )
			{
				unset( $edited_CaSource );
				forget_param( 'casrc_ID' );
				$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), $central_antispam_Module->T_('Reporter') ), 'error' );
				$action = 'nil';
			}
		}
		break;
}


switch( $action )
{
	case 'keyword_save':
		// Update keyword record:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'cakeyword' );

		// load data from request
		if( $edited_CaKeyword->load_from_Request() )
		{	// We could load data from form without errors:
			$edited_CaKeyword->dbupdate();
			$Messages->add( $central_antispam_Module->T_('The keyword has been saved.'), 'success' );
			header_redirect( $admin_url.'?ctrl=central_antispam&tab=keywords', 303 );
		}
		$action = 'keyword_edit';
		break;

	case 'source_save':
		// Update source record:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'casource' );

		// Load data from request:
		if( $edited_CaSource->load_from_Request() )
		{	// We could load data from form without errors:
			$edited_CaSource->dbupdate();
			$Messages->add( $central_antispam_Module->T_('The source has been saved.'), 'success' );
			header_redirect( $admin_url.'?ctrl=central_antispam&tab=sources', 303 );
		}
		$action = 'source_edit';
		break;
}

// Highlight the requested tab (if valid):
$AdminUI->set_path( 'central_antispam', $tab );

$AdminUI->breadcrumbpath_init( false );
$AdminUI->breadcrumbpath_add( $central_antispam_Module->T_('Central Antispam'), $admin_url.'?ctrl=central_antispam' );
switch( $tab )
{
	case 'keywords':
		$AdminUI->breadcrumbpath_add( $central_antispam_Module->T_('Keywords'), $admin_url.'?ctrl=central_antispam&amp;tab='.$tab );
		break;

	case 'reporters':
		$AdminUI->breadcrumbpath_add( $central_antispam_Module->T_('Reporters'), $admin_url.'?ctrl=central_antispam&amp;tab='.$tab );
		break;
}

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();
switch( $action )
{
	case 'keyword_edit':
		$AdminUI->disp_view( 'central_antispam/views/_keywords.form.php' );
		break;

	case 'source_edit':
		$AdminUI->disp_view( 'central_antispam/views/_sources.form.php' );
		break;

	default:
		switch( $tab )
		{
			case 'keywords':
				$AdminUI->disp_view( 'central_antispam/views/_keywords.view.php' );
				break;

			case 'sources':
				$AdminUI->disp_view( 'central_antispam/views/_sources.view.php' );
				break;
		}
		break;
}

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>