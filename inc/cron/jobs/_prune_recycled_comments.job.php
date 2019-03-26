<?php
/**
 * This file implements the recycled comments pruning Cron controller
 *
 * @author fplanque: Francois PLANQUE
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'comments/model/_commentlist.class.php', 'CommentList2' );

$comments_prune_result = CommentList2::dbprune(); // will prune once per day, according to Settings

if( is_string( $comments_prune_result ) )
{	// Display error message:
	cron_log_append( $comments_prune_result, 'error' );
	return 100;
}

cron_log_append( sprintf( T_('%d recycled comments were pruned.'), $comments_prune_result ), ( $comments_prune_result > 0 ? 'success' : NULL ) );

// Save a number of new inserted key phrases:
cron_log_report_action_count( $comments_prune_result );

return 1; /* ok */
?>