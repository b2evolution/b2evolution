<?php
/**
 * This file implements the test Cron controller
 *
 * @author fplanque: Francois PLANQUE
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

cron_log_append( T_('The TEST cron controller says hello!') );

return 1; /* ok */
?>