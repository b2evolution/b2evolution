<?php
/**
 * This template generates a sitemap feed for the requested blog's latest posts
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://manual.b2evolution.net/Skins_2.0}
 *
 * @package evoskins
 * @subpackage rss
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

$Timer->start( 'prepare list' );

// Use a LIGHT Item List:  (Sitemap = 50000 entries max)
$MainList = & new ItemListLight( $Blog, $timestamp_min, $timestamp_max, 50000 );

// By default we only want items that have the MAIN cat in this blog,
// i-e with its canonical URL in this blog (cross posted stuff will be listed in its main blog)
// However this may be overriden in a stub (or param)
param( 'cat_focus', 'string', 'main' );

// Filter list:
$MainList->set_filters( array(
		'visibility_array' => array( 'published' ),  // We only want to advertised published items
		'types' => NULL,						// ALL types (including pages)
	  'unit' => 'all',						// We want to advertise all items (not just a page or a day)
	  'cat_focus' => $cat_focus,
	) );

// pre_dump( $cat_focus, $MainList->filters );

// Run the query:
$MainList->query();

// Old style globals for category.funcs:
$postIDlist = $MainList->get_page_ID_list();
$postIDarray = $MainList->get_page_ID_array();

$Timer->stop( 'prepare list' );

$Timer->start( 'display list' );

// Note: since URLs are likely to be clean ASCII, $io_charset can probably be faked to UTF-8 here

header( 'Content-type: application/xml; charset=UTF-8' );

echo '<?xml version="1.0" encoding="UTF-8"?'.'>';
?>

<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php
while( $Item = & $MainList->get_item() )
{ ?>
	<url>
		<loc><?php $Item->permanent_url( 'single' ) ?></loc>
		<lastmod><?php $Item->mod_date( 'isoZ', true ) ?></lastmod>
		<priority>0.9</priority>
	</url>
<?php
} ?>
</urlset>
<?php

$Timer->stop( 'display list' );

$Hit->log(); // log the hit on this page

// debug_info(); // output debug info if requested

// This is a self contained XML document, make sure there is no additional output:
exit();
?>