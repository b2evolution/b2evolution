<?php
/**
 * XML-RPC : Central Antispam API
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2009-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package xmlsrv
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );



### b2evo.reportabuse ###

$b2evoreportabuse_sig = array( array( $xmlrpcString, $xmlrpcInt, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString ) );

$b2evoreportabuse_doc = 'Report abuse to b2evo\'s centralized ban blacklist';

function b2evoreportabuse( $m )
{
	global $xmlrpcerruser; // import user errcode value
	global $DB, $Settings, $Messages, $enable_blacklist_server_API;

	if( empty( $enable_blacklist_server_API ) )
	{	// Exit here if the server cannot be used as central antispam:
		return new xmlrpcresp( 0, $xmlrpcerruser + 7, 'This server does not accept antispam reports.' ); // user error 7
	}

	$err = '';

	$ReportAbuseString= $m->getParam( 3 );
	$ReportAbuseString = $ReportAbuseString->scalarval();
	logIO( 'ReportAbuseString: '.$ReportAbuseString );

	$ReportBaseUrl = $m->getParam( 4 );
	$ReportBaseUrl = $ReportBaseUrl->scalarval();
	logIO( 'ReportBaseUrl: '.$ReportBaseUrl );

	$time_difference = $Settings->get( 'time_difference' );
	$post_timestamp = date( 'Y-m-d H:i:s', ( time() + $time_difference ) );
	// additional timestamp to add to reports - EdB
	$post_timestamp_logged = date( 'Y-m-d', ( time() + $time_difference ) );

	// CHECK and FORMAT content
	$new_keyword = format_to_output( $ReportAbuseString, 'xml' );

	if( $errstring = $Messages->get_string( 'Cannot post, please correct these errors:', '' ) )
	{
		return new xmlrpcresp( 0, $xmlrpcerruser + 1, $errstring ); // user error 1
	}

	$DB->show_errors = false;
	$DB->halt_on_error = false;

	$DB->begin( 'SERIALIZABLE' );

	// Check if the reported keyword already exists in DB
	$keyword_SQL = new SQL();
	$keyword_SQL->SELECT( 'cakw_ID' );
	$keyword_SQL->FROM( 'T_centralantispam__keyword' );
	$keyword_SQL->WHERE( 'cakw_keyword = '.$DB->quote( $new_keyword ) );
	$keyword_ID = $DB->get_var( $keyword_SQL->get() );

	if( $keyword_ID > 0 )
	{ // The keyword has been found, Update it

		// Update the last report date of the existing keyword
		$query_result = $DB->query( 'UPDATE T_centralantispam__keyword
			  SET cakw_lastreport_ts = '.$DB->quote( $post_timestamp ).'
			WHERE cakw_ID = '.$keyword_ID );

		if( $query_result === false )
		{ // Query error, Exit here
			$DB->rollback();
			return new xmlrpcresp( 0, $xmlrpcerruser + 2, 'Update attempt (1) failed.'); // user error 2
		}

		logIO( 'Updated.' );
		$return_string = 'Your report has been added to existing reports on this abuse. Thanks for your help.';
	}
	else
	{ // Try to insert new keyword
		logIO( 'Update (1) failed, try to insert' );

		// Insert new keyword
		$query_result = $DB->query( 'INSERT INTO T_centralantispam__keyword
				( cakw_keyword, cakw_lastreport_ts ) VALUES
				( '.$DB->quote( $new_keyword ).', '.$DB->quote( $post_timestamp ).' )' );
		$keyword_ID = $DB->insert_id;

		if( $query_result === false || empty( $keyword_ID ) )
		{ // Query error, Exit here
			$DB->rollback();
			return new xmlrpcresp( 0, $xmlrpcerruser + 3, 'Insert keyword attempt failed.' ); // user error 3
		}

		logIO( 'Posted ! Keyword ID: '.$keyword_ID );
		$return_string = 'This is the first report for this abuse. Thanks for your help.';
	}

	// Check if the Reporter/Source already exists in DB
	$source_SQL = new SQL();
	$source_SQL->SELECT( 'casrc_ID' );
	$source_SQL->FROM( 'T_centralantispam__source' );
	$source_SQL->WHERE( 'casrc_baseurl = '.$DB->quote( $ReportBaseUrl ) );
	$source_ID = $DB->get_var( $source_SQL->get() );

	if( empty( $source_ID ) )
	{ // Create new reporter if it doesn't exist in DB yet
		$query_result = $DB->query( 'INSERT INTO T_centralantispam__source
				( casrc_baseurl ) VALUES
				( '.$DB->quote( $ReportBaseUrl ).' )' );
		$source_ID = $DB->insert_id;

		if( $query_result === false || empty( $source_ID ) )
		{ // Query error, Exit here
			$DB->rollback();
			return new xmlrpcresp( 0, $xmlrpcerruser + 4, 'Insert reporter attempt failed.' ); // user error 4
		}

		logIO( 'Posted ! Source ID: '.$source_ID );
	}

	// Check if a Report already exists from such source and with such keyword in DB
	$report_SQL = new SQL();
	$report_SQL->SELECT( 'carpt_cakw_ID' );
	$report_SQL->FROM( 'T_centralantispam__report' );
	$report_SQL->WHERE( 'carpt_cakw_ID = '.$DB->quote( $keyword_ID ) );
	$report_SQL->WHERE_and( 'carpt_casrc_ID = '.$DB->quote( $source_ID ) );
	$report_ID = $DB->get_var( $report_SQL->get() );

	if( empty( $report_ID ) )
	{ // No such report, Create it
		$query_result = $DB->query( 'INSERT INTO T_centralantispam__report
				( carpt_cakw_ID, carpt_casrc_ID, carpt_ts ) VALUES
				( '.$DB->quote( $keyword_ID ).', '.$DB->quote( $source_ID ).', '.$DB->quote( $post_timestamp ).' )' );

		if( $query_result === false )
		{ // Query error, Exit here
			$DB->rollback();
			return new xmlrpcresp( 0, $xmlrpcerruser + 5, 'Insert report attempt failed.' ); // user error 5
		}
	}
	else
	{ // If report was already done we should update the report date
		$query_result = $DB->query( 'UPDATE T_centralantispam__report
			  SET carpt_ts = '.$DB->quote( $post_timestamp ).'
			WHERE carpt_cakw_ID = '.$keyword_ID.'
			  AND carpt_casrc_ID = '.$source_ID );

		if( $query_result === false )
		{ // Query error, Exit here
			$DB->rollback();
			return new xmlrpcresp( 0, $xmlrpcerruser + 6, 'Update report attempt failed.' ); // user error 6
		}
	}

	$DB->commit();

	if( isset( $sleep_after_edit ) && $sleep_after_edit > 0 )
	{
		sleep( $sleep_after_edit );
	}

	return new xmlrpcresp( new xmlrpcval( $return_string, 'string' ) );
}

$xmlrpc_procs['b2evo.reportabuse'] = array(
		'function'  => 'b2evoreportabuse',
		'signature' => $b2evoreportabuse_sig,
		'docstring' => $b2evoreportabuse_doc
	);


### b2evo.pollabuse ###

$b2evopollabuse_sig = array( array( $xmlrpcStruct, $xmlrpcInt, $xmlrpcString, $xmlrpcString, $xmlrpcDateTime, $xmlrpcInt ) );

$b2evopollabuse_doc = 'Get an update of banned strings from b2evo\'s centralized ban blacklist';

function b2evopollabuse( $m )
{
	global $xmlrpcerruser; // import user errcode value
	global $DB, $Settings, $enable_blacklist_server_API;
	global $xmlrpcStruct, $xmlrpcDateTime;
	$err = '';

	logIO( 'Called function: b2evo.pollabuse' );

	if( empty( $enable_blacklist_server_API ) )
	{	// Exit here if the server cannot be used as central antispam:
		return new xmlrpcresp( 0, $xmlrpcerruser + 7, 'This server does not serve an antispam blacklist.' ); // user error 7
	}

	// This is the datetime we want to start at:
	$startat = $m->getParam( 3 );
	$startat = $startat->scalarval();
	logIO( 'startat: '.$startat );
	xmlrpc_debugmsg( 'startat: ['.$startat.']'.iso8601_decode( $startat ).' '.time() );

	// Get the keywords to display:
	$time_difference = $Settings->get( 'time_difference' );
	xmlrpc_debugmsg( ' time difference = '.$time_difference.' ' );
	$timestamp_min = iso8601_decode( $startat ) - $time_difference;
	xmlrpc_debugmsg( ' timestamp min = '.$timestamp_min.' date min = '.date2mysql( $timestamp_min ).' ' );

	$SQL = new SQL();
	$SQL->SELECT( 'cakw_keyword, cakw_statuschange_ts' );
	$SQL->FROM( 'T_centralantispam__keyword' );
	$SQL->WHERE( 'cakw_status = "published"' );
	$SQL->WHERE_And( 'cakw_statuschange_ts >= '.$DB->quote( date2mysql( $timestamp_min ) ) );
	$SQL->ORDER_BY( 'cakw_statuschange_ts' );
	$SQL->LIMIT( 1000 );
	$keywords = $DB->get_results( $SQL->get() );

	// Array of spam strings
	$resp_array = new xmlrpcval( array(), 'array' );

	if( ! empty( $keywords ) )
	{ // There are some answers
		foreach( $keywords as $keyword )
		{
			// Add string to list:
			$resp_array->addScalar( $keyword->cakw_keyword, 'string' );
			// Datetime of last string
			$last_timestamp = mysql2date( 'Ymd\TH:i:s', $keyword->cakw_statuschange_ts );
		}
	}
	else
	{	// There are no answers:
		xmlrpc_debugmsg( 'No new strings.' );
		$last_timestamp = $startat;
	}

	xmlrpc_debugmsg( 'last timestamp: ['.$last_timestamp.']' );
	$resp_timestamp = new xmlrpcval( $last_timestamp, $xmlrpcDateTime );

	$response = new xmlrpcval( array(
			'strings'       => $resp_array,
			'lasttimestamp' => $resp_timestamp
		), $xmlrpcStruct );

	// Return response:
	return new xmlrpcresp( $response );
}

$xmlrpc_procs['b2evo.pollabuse'] = array(
		'function'  => 'b2evopollabuse',
		'signature' => $b2evopollabuse_sig,
		'docstring' => $b2evopollabuse_doc
	);