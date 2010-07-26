<?php
/**
 * This file implements the Page Cache pruning Cron controller (delete old files from the cache)
 *
 * @author asimo: Attila Simo
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/_pagecache.class.php', 'PageCache' );

$result_message = PageCache::prune_page_cache();
if( empty( $result_message ) )
{
	return 1; /* OK */
}

return 100;

/*
 * $Log$
 * Revision 1.3  2010/07/26 06:52:16  efy-asimo
 * MFB v-4-0
 *
 */
?>