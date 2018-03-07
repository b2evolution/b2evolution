<?php
/**
 * This file implements the recycled comments pruning Cron controller
 *
 * @author fplanque: Francois PLANQUE
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'comments/model/_commentlist.class.php', 'CommentList2' );

$error_message = CommentList2::dbprune(); // will prune once per day, according to Settings

cron_log_append( $error_message );

if( empty( $error_message ) )
{
	return 1; /* ok */
}

return 100;

?>