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

$Timer->resume( 'prepare list' );

// Use a LIGHT Item List:  (Sitemap = 50000 entries max)
$MainList = & new ItemListLight( $Blog, $timestamp_min, $timestamp_max, 50000 );

// By default we only want items that have the MAIN cat in this blog,
// i-e with its canonical URL in this blog (cross posted stuff will be listed in its main blog)
// However this may be overriden in a stub (or param)
param( 'cat_focus', 'string', 'main' );

// Filter list:
$MainList->set_filters( array(
		'visibility_array' => array( 'published' ),  // We only want to advertised published items
		'types' => '-1500,1520,1530,1570,1600,3000',	// INCLUDE pages BUT STILL EXCLUDE intros and sidebar links
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

$Timer->resume( 'display list' );

// Note: since URLs are likely to be clean ASCII, $io_charset can probably be faked to UTF-8 here

header_content_type( 'application/xml', 'UTF-8' );

echo '<?xml version="1.0" encoding="UTF-8"?'.'>';
?>

<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php
while( $Item = & mainlist_get_item() )
{	// For each blog post, do everything below up to the closing curly brace "}"
	// age in days of teh post
	$age = ($localtimenow - mysql2timestamp($Item->datemodified)) / 86400;
	// Prio: Recent posts will get higher priority compared to older posts, in case the SE doesn't want to index all posts!
	// Change frequency: recent posts are more likely to change often than older posts, especially regarding comments.
	// We hint SEs to check back more often (and not to waste indexing credits on old stuff).
	if( $age < 8 )
	{
		$prio = 0.9;
		$changefreq = 'hourly';
	}
	elseif( $age < 30 )
	{
		$prio = 0.8;
		$changefreq = 'daily';
	}
	elseif( $age < 90 )
	{
		$prio = 0.7;
		$changefreq = 'daily';
	}
	elseif( $age < 365 )
	{
		$prio = 0.6;
		$changefreq = 'weekly';
	}
	else
	{
		$prio = 0.5;
		$changefreq = 'monthly';
	}
	?>
	<url>
		<loc><?php $Item->permanent_url( 'single' ) ?></loc>
		<lastmod><?php $Item->mod_date( 'isoZ', true ) /* fp> date_touched including comments would be even better */ ?></lastmod>
		<priority><?php echo $prio; ?></priority>
		<changefreq><?php echo $changefreq; ?></changefreq>
	</url>
	<?php
} ?>
</urlset>
<?php

$Timer->stop( 'display list' );
?>