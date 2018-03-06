<?php
/**
 * This file implements the Antispam poll Cron controller
 *
 * @author fplanque: Francois PLANQUE
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

if( antispam_poll_abuse( true ) )
{ // Success
	$job_ret = 1;
}
else
{	// Error
	$job_ret = 100;
}

return $job_ret;
?>