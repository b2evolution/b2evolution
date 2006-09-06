<?php
/**
 * This is the template that displays the linkblog
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the _main.php template.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

if( !isset( $linkblog ) )
{	// No link blog explicitely specified, we use default:
	$linkblog = $Blog->get('links_blog_ID');
}

if( ! $linkblog )
{	// No linkblog blog requested for this blog
	return;
}


# maximum number of linkblog entries to display:
if(!isset($linkblog_limit)) $linkblog_limit = 20;
# global linkblog delimiters:
if(!isset($linkblog_main_start)) $linkblog_main_start = '<div class="bSideItem"><h3>'.
																													T_('Linkblog').'</h3>';
if(!isset($linkblog_main_end)) $linkblog_main_end = '</div>';
# Category delimiters:
if(!isset($linkblog_catname_before)) $linkblog_catname_before = '<h4>';
if(!isset($linkblog_catname_after)) $linkblog_catname_after = '</h4><ul>';
if(!isset($linkblog_catlist_end)) $linkblog_catlist_end = '</ul>';
# Item delimiters:
if(!isset($linkblog_item_before)) $linkblog_item_before = '<li>';
if(!isset($linkblog_item_after)) $linkblog_item_after = '</li>';


// --- //


// Load the linkblog blog:
$link_Blog = & $BlogCache->get_by_ID( $linkblog );

$LinkblogList = & new ItemList2( $link_Blog, $timestamp_min, $timestamp_max, $linkblog_limit );

// Compile cat array stuff:
$linkblog_cat_array = array();
$linkblog_cat_modifier = '';
compile_cat_array( $linkblog_cat, $linkblog_catsel, & $linkblog_cat_array, & $linkblog_cat_modifier, $linkblog );

$LinkblogList->set_filters( array(
		'cat_array' => $linkblog_cat_array,
		'cat_modifier' => $linkblog_cat_modifier,
		'order' => 'ASC',
		'orderby' => 'main_cat_ID title',
		'unit' => 'posts',
	) );

// Run the query:
$LinkblogList->query();


// Open the global list
echo $linkblog_main_start;


while( $Item = & $LinkblogList->get_category_group() )
{
	// Open new cat:
	echo $linkblog_catname_before;
	$Item->main_category();
	echo $linkblog_catname_after;

	while( $Item = & $LinkblogList->get_item() )
	{
		echo $linkblog_item_before;
		$Item->title();
		echo ' ';
		$Item->content( 1, 0, T_('more'), '[', ']' );	// Description + more link
		echo ' ';
		$Item->permanent_link( '#icon#' );
		echo $linkblog_item_after;
	}

	// Close cat
	echo $linkblog_catlist_end;
}
// Close the global list
echo $linkblog_main_end;


/*
 * $Log$
 * Revision 1.15  2006/09/06 18:34:04  fplanque
 * Finally killed the old stinkin' ItemList(1) class which is deprecated by ItemList2
 *
 * Revision 1.14  2006/07/06 19:56:29  fplanque
 * no message
 *
 * Revision 1.13  2006/04/11 21:22:26  fplanque
 * partial cleanup
 *
 */
?>