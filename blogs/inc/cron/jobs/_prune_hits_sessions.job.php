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

load_class('sessions/model/_hitlist.class.php');

$result_message = Hitlist::dbprune(); // will prune once per day, according to Settings

if( empty($result_message) )
{
	return 1; /* ok */
}

return 100;

/*
 * $Log$
 * Revision 1.1  2007/06/25 10:59:46  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.1  2006/07/06 19:59:08  fplanque
 * better logs, better stats, better pruning
 *
 */
?>