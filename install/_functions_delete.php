<?php
/**
 * This file implements deletion of DB tables
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package install
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * db_delete(-)
 */
function db_delete()
{
	global $DB, $db_config, $tableprefix;

	echo "Disabling foreign key checks...<br />\n";
	$DB->query( 'SET FOREIGN_KEY_CHECKS=0' );

	foreach( $db_config['aliases'] as $alias => $tablename )
	{
		echo "Dropping $tablename table...<br />\n";
		evo_flush();
		$DB->query( 'DROP TABLE IF EXISTS '.$tablename );
	}

	// Get remaining tables with the same prefix and delete them as well. Probably these tables are some b2evolution plugins tables.
	$remaining_tables = $DB->get_col( 'SHOW TABLES FROM `'.$db_config['name'].'` LIKE "'.$tableprefix.'%"' );
	foreach( $remaining_tables as $tablename )
	{
		echo "Dropping $tablename table...<br />\n";
		evo_flush();
		$DB->query( 'DROP TABLE IF EXISTS '.$tablename );
	}
}


/**
 * Uninstall b2evolution: Delete DB & Cache files
 */
function uninstall_b2evolution()
{
	global $DB;

	/* REMOVE PAGE CACHE */
	load_class( '_core/model/_pagecache.class.php', 'PageCache' );

	// Remove general page cache
	$PageCache = new PageCache( NULL );
	$PageCache->cache_delete();

	// Skip if T_blogs table is already deleted. Note that db_delete() will not throw any errors on missing tables.
	if( $DB->query( 'SHOW TABLES LIKE "T_blogs"' ) )
	{ // Get all blogs
		$blogs_SQL = new SQL();
		$blogs_SQL->SELECT( 'blog_ID' );
		$blogs_SQL->FROM( 'T_blogs' );
		$blogs = $DB->get_col( $blogs_SQL->get() );

		$BlogCache = & get_BlogCache( 'blog_ID' );
		foreach( $blogs as $blog_ID )
		{
			$Blog = $BlogCache->get_by_ID( $blog_ID );

			// Remove page cache of current blog
			$PageCache = new PageCache( $Blog );
			$PageCache->cache_delete();
		}
	}

	/* REMOVE DATABASE */
	db_delete();

	echo '<p>'.T_('Reset done!').'</p>';
}
?>