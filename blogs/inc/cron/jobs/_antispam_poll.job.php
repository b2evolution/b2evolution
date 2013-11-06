<?php
/**
 * This file implements the Antispam poll Cron controller
 *
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id$
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

/*
 * $Log$
 * Revision 1.3  2013/11/06 08:04:07  efy-asimo
 * Update to version 5.0.1-alpha-5
 *
 */
?>