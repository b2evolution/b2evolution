<?php
/**
 * This file implements the recycled comments pruning Cron controller
 *
 * @author fplanque: Francois PLANQUE
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'comments/model/_commentlist.class.php', 'CommentList2' );

$result_message = CommentList2::dbprune(); // will prune once per day, according to Settings

if( empty($result_message) )
{
	return 1; /* ok */
}

return 100;

?>