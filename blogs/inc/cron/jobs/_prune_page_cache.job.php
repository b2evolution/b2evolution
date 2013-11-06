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
 * Revision 1.4  2013/11/06 08:04:07  efy-asimo
 * Update to version 5.0.1-alpha-5
 *
 */
?>