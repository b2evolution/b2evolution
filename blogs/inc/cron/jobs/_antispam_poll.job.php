<?php
/**
 * This file implements the Antispam poll Cron controller
 *
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id: _antispam_poll.job.php 5557 2014-01-03 04:13:43Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

if( antispam_poll_abuse() )
{ // Success
	$job_ret = 1;
}
else
{	// Error
	$job_ret = 100;
}

global $Messages;
$result_message = $Messages->get_string( '', '', "\n" );

return $job_ret;
?>