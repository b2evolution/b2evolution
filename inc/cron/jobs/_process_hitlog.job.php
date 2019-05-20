<?php
/**
 * This file implements the cron job to extract keyphrase from the hit logs
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_funcs( '../inc/sessions/model/_hitlog.funcs.php' );

$extract_keyphrase_result = extract_keyphrase_from_hitlogs();

if( is_string( $extract_keyphrase_result ) )
{	// Display error message:
	cron_log_append( $extract_keyphrase_result, 'error' );
	return 2;
}

cron_log_append( sprintf( T_('%d keyphrases were extracted.'), $extract_keyphrase_result ), ( $extract_keyphrase_result > 0 ? 'success' : NULL ) );

// Save a number of new inserted key phrases:
cron_log_report_action_count( $extract_keyphrase_result );

return 1; /* ok */
?>