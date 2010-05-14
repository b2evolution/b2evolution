<?php
/**
 * This file implements the Page Cache pruning Cron controller (delete old files from the cache)
 *
 * @author asimo: Attila Simo
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_funcs('files/model/_file.funcs.php');

$result_message = prune_page_cache();
if( empty( $result_message ) )
{
	return 1; /* OK */
}

return 100;