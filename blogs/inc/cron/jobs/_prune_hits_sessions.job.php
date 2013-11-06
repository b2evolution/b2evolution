<?php
/**
 * This file implements the Hit and Session pruning Cron controller
 *
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Settings;

if( $Settings->get( 'auto_prune_stats_mode' ) != 'cron' )
{ // Autopruning is NOT requested
	$result_message = T_('Auto pruning is not set to run as a scheduled task');
	return 2;
}

load_class( 'sessions/model/_hitlist.class.php', 'Hitlist' );

$result_message = Hitlist::dbprune(); // will prune once per day, according to Settings

if( empty($result_message) )
{
	return 1; /* ok */
}

return 100;

/*
 * $Log$
 * Revision 1.8  2013/11/06 08:04:07  efy-asimo
 * Update to version 5.0.1-alpha-5
 *
 */
?>