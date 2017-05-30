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

// Check permission:
$current_User->check_perm( 'centralantispam', 'view', true );

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
				$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Keyword') ), 'error' );
				$action = 'nil';
			}
		}
		break;

	case 'reporters':
		if( param( 'casrc_ID', 'integer', '', true ) )
		{	// Load source from cache:
			$CaSourceCache = & get_CaSourceCache();
			if( ( $edited_CaSource = & $CaSourceCache->get_by_ID( $casrc_ID, false ) ) === false )
			{
				unset( $edited_CaSource );
				forget_param( 'casrc_ID' );
				$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Reporter') ), 'error' );
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

		// Check permission:
		$current_User->check_perm( 'centralantispam', 'edit', true );

		// load data from request
		if( $edited_CaKeyword->load_from_Request() )
		{	// We could load data from form without errors:
			$edited_CaKeyword->dbupdate();
			$Messages->add( T_('The keyword has been saved.'), 'success' );
			header_redirect( $admin_url.'?ctrl=central_antispam&tab=keywords', 303 );
		}
		$action = 'keyword_edit';
		break;

	case 'source_save':
		// Update source record:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'casource' );

		// Check permission:
		$current_User->check_perm( 'centralantispam', 'edit', true );

		// Load data from request:
		if( $edited_CaSource->load_from_Request() )
		{	// We could load data from form without errors:
			$edited_CaSource->dbupdate();
			$Messages->add( T_('The source has been saved.'), 'success' );
			header_redirect( $admin_url.'?ctrl=central_antispam&tab=reporters', 303 );
		}
		$action = 'source_edit';
		break;

	case 'import':
		if( ! param( 'confirm', 'integer', 0 ) )
		{	// The import action must be confirmed:
			break;
		}

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'cakeywordsimport' );

		// Check permission:
		$current_User->check_perm( 'centralantispam', 'edit', true );

		$import_keywords = param( 'import_keywords', 'array:string' );

		if( empty( $import_keywords ) )
		{	// No selected keywords to import:
			$Messages->add( T_('Please select keywords to import.'), 'error' );
			$action = 'import';
			break;
		}

		// Get start dates for keywords:
		$keyword_dates = array();
		foreach( $import_keywords as $keyword_source )
		{
			param_date( 'date_start_'.$keyword_source, T_('Invalid date'), true );
			param_time( 'time_start_'.$keyword_source );
			$keyword_dates[ $keyword_source ] = strtotime( form_date( get_param( 'date_start_'.$keyword_source ), get_param( 'time_start_'.$keyword_source ) ) );
		}

		if( param_errors_detected() )
		{	// Don't allow to import with wrong entered data:
			break;
		}

		$DB->begin();

		$keywords_SQL = new SQL( 'Get keywords that will be imported as local reports' );
		$keywords_SQL->SELECT( 'askw_string, askw_source' );
		$keywords_SQL->FROM( 'T_antispam__keyword' );
		$keywords_SQL->WHERE( 'askw_string NOT IN ( SELECT cakw_keyword FROM T_centralantispam__keyword )' );
		$keywords_SQL->WHERE_and( 'askw_source IN( '.$DB->quote( $import_keywords ).' )' );
		$new_keywords = $DB->get_results( $keywords_SQL->get(), ARRAY_A, $keywords_SQL->title );

		$keywords_imported_count = array();
		if( count( $new_keywords ) )
		{	// If there are new keywords to import:

			// Check if the Reporter/Source already exists in DB
			$source_SQL = new SQL( 'Get source of current baseurl for central antispam' );
			$source_SQL->SELECT( 'casrc_ID, casrc_status' );
			$source_SQL->FROM( 'T_centralantispam__source' );
			$source_SQL->WHERE( 'casrc_baseurl = '.$DB->quote( $baseurl ) );
			$source_row = $DB->get_row( $source_SQL->get(), ARRAY_A, NULL, $source_SQL->title );
			$source_ID = empty( $source_row ) ? 0 : intval( $source_row['casrc_ID'] );

			if( empty( $source_ID ) )
			{	// Create new reporter if it doesn't exist in DB yet:
				$DB->query( 'INSERT INTO T_centralantispam__source
						( casrc_baseurl, casrc_status ) VALUES
						( '.$DB->quote( $baseurl ).', "trusted" )' );
				$source_ID = $DB->insert_id;
			}
			elseif( $source_row['casrc_status'] != 'trusted' )
			{	// Make current baseurl as trusted source:
				$DB->query( 'UPDATE T_centralantispam__source
					SET   casrc_status = "trusted"
					WHERE casrc_ID = '.$source_ID );
			}

			$keywords_reports = array();
			foreach( $new_keywords as $keyword )
			{
				$keyword_timestamp = date( 'Y-m-d H:i:s', $keyword_dates[ $keyword['askw_source'] ] );
				if( $keyword['askw_source'] == 'central' && $keyword_timestamp > '2014-02-24 21:10:18' )
				{	// Limit central keywords by this max date:
					$keyword_timestamp = '2014-02-24 21:10:18';
				}
				// Insert new keyword:
				$query_result = $DB->query( 'INSERT INTO T_centralantispam__keyword
						( cakw_keyword, cakw_status, cakw_statuschange_ts, cakw_lastreport_ts ) VALUES
						( '.$DB->quote( $keyword['askw_string'] ).', "published", '.$DB->quote( $keyword_timestamp ).', '.$DB->quote( $keyword_timestamp ).' )' );
				if( $query_result )
				{	// If new keyword has been inserted:
					if( ! isset( $keywords_imported_count[ $keyword['askw_source'] ] ) )
					{
						$keywords_imported_count[ $keyword['askw_source'] ] = 0;
					}
					$keywords_imported_count[ $keyword['askw_source'] ]++;
					$keyword_ID = $DB->insert_id;
					$keywords_reports[] = '( '.$DB->insert_id.', '.$source_ID.', '.$DB->quote( $keyword_timestamp ).' )';
				}
				// Increase 1 second for next keyword:
				$keyword_dates[ $keyword['askw_source'] ]++;
			}

			if( count( $keywords_imported_count ) )
			{	// Insert reports to know from what host new keyword was added:
				$DB->query( 'INSERT INTO T_centralantispam__report
					( carpt_cakw_ID, carpt_casrc_ID, carpt_ts ) VALUES '
					.implode( ', ', $keywords_reports ) );
			}
		}

		foreach( $keywords_imported_count as $keywords_source => $keywords_count )
		{
			$Messages->add( sprintf( T_('%d new keywords have been imported as "%s" reports.'), $keywords_count, $keywords_source ), 'success' );
		}

		$DB->commit();
		$action = '';
		break;
}

// Highlight the requested tab (if valid):
$AdminUI->set_path( 'central_antispam', $tab );

$AdminUI->breadcrumbpath_init( false );
$AdminUI->breadcrumbpath_add( T_('Central Antispam'), $admin_url.'?ctrl=central_antispam' );
switch( $tab )
{
	case 'keywords':
		$AdminUI->breadcrumbpath_add( T_('Keywords'), $admin_url.'?ctrl=central_antispam&amp;tab='.$tab );
		if( $action == 'import' )
		{
			$AdminUI->breadcrumbpath_add( T_('Import'), $admin_url.'?ctrl=central_antispam&amp;action='.$action );
			// Initialize date picker:
			init_datepicker_js();
		}
		if( empty( $action ) && $current_User->check_perm( 'centralantispam', 'edit' ) )
		{	// Load JS to edit keyword status from list:
			require_js( '#jquery#', 'rsc_url' );
			require_js( 'jquery/jquery.jeditable.js', 'rsc_url' );
		}
		break;

	case 'reporters':
		$AdminUI->breadcrumbpath_add( T_('Reporters'), $admin_url.'?ctrl=central_antispam&amp;tab='.$tab );
		if( empty( $action ) && $current_User->check_perm( 'centralantispam', 'edit' ) )
		{	// Load JS to edit source status from list:
			require_js( '#jquery#', 'rsc_url' );
			require_js( 'jquery/jquery.jeditable.js', 'rsc_url' );
		}
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

	case 'import':
		$AdminUI->disp_view( 'central_antispam/views/_keywords_import.view.php' );
		break;

	default:
		switch( $tab )
		{
			case 'keywords':
				$AdminUI->disp_view( 'central_antispam/views/_keywords.view.php' );
				break;

			case 'reporters':
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