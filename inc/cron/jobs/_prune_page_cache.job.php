<?php
/**
 * This file implements the Page Cache pruning Cron controller (delete old files from the cache)
 *
 * @author asimo: Attila Simo
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/_pagecache.class.php', 'PageCache' );

if( PageCache::prune_page_cache( 'cron_job' ) )
{
	return 1; /* OK */
}

return 100;
?>