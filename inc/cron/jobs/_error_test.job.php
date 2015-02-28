<?php
/**
 * This file implements the Error Test Cron controller
 *
 * @author fplanque: Francois PLANQUE
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

$result_message = T_('The Error TEST cron controller simulates an error, thus this "error" is normal!');

return 100; /* Simulated error */
?>