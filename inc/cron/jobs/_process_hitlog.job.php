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
if( $extract_keyphrase_result === true )
{
	return 1; /* ok */
}

$result_message = $extract_keyphrase_result;
return 2;
?>