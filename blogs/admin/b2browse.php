<?php
/**
 * This file implements the UI controller for the browsing posts.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */

/**
 * Includes:
 */
require_once (dirname(__FILE__). '/_header.php');

$itemTypeCache = & new DataObjectCache( 'Element', true, 'T_posttypes', 'ptyp_', 'ptyp_ID' );
$itemStatusCache = & new DataObjectCache( 'Element', true, 'T_poststatuses', 'pst_', 'pst_ID' );

$AdminUI->setPath( 'edit', param( 'tab', 'string', 'postlist', true /* memorize */ ) );
$AdminUI->title = $AdminUI->title_titlearea = T_('Browse blog:');

$blog = autoselect_blog( param( 'blog', 'integer', 0 ), 'blog_ismember', 1 );

if( $blog != 0 )
{ // We could select a blog:
	$Blog = Blog_get_by_ID( $blog ); /* TMP: */ $blogparams = get_blogparams_by_ID( $blog );
	$AdminUI->title .= ' '.$Blog->dget( 'shortname' );
}
else
{ // No blog could be selected
	$Messages->add( sprintf( T_('Since you\'re a newcomer, you\'ll have to wait for an admin to authorize you to post. You can also <a %s>e-mail the admin</a> to ask for a promotion. When you\'re promoted, just reload this page and you\'ll be able to blog. :)'), 'href="mailto:'. $admin_email. '?subject=b2-promotion"' ) );
}


// Generate available blogs list:
$blogListButtons = $AdminUI->getCollectionList( 'blog_ismember', 1, $pagenow.'?blog=%d' );

require dirname(__FILE__).'/_menutop.php';

if( $blog )
{ // We could select a valid blog which we have permission to access:
	// Show the posts:
	$add_item_url = 'b2edit.php?blog='.$blog;
	$edit_item_url = 'b2edit.php?action=edit&amp;post=';
	$delete_item_url = 'edit_actions.php?action=delete&amp;post=';
	$objType = 'Item';
	$dbtable = 'T_posts';
	$dbprefix = 'post_';
	$dbIDname = 'ID';

	// fplanque>> I'm not sure this is a good place to call the submenu. It should probaly be displayed within the "page top"
	$AdminUI->dispSubmenu();
	// Begin payload block:
	$AdminUI->dispPayloadBegin();


	param( 'safe_mode', 'integer', 0 );         // Blogger style
	param( 'p', 'integer' );                    // Specific post number to display
	param( 'm', 'integer', '', true );          // YearMonth(Day) to display
	param( 'w', 'integer', '', true );          // Week number
	param( 'cat', 'string', '', true );         // List of cats to restrict to
	param( 'catsel', 'array', array(), true );  // Array of cats to restrict to
	param( 'author', 'integer', '', true );     // List of authors to restrict to
	param( 'order', 'string', 'DESC', true );   // ASC or DESC
	param( 'orderby', 'string', '', true );     // list of fields to order by
	param( 'dstart', 'integer', '', true );     // YearMonth(Day) to start at
	param( 'unit', 'string', '', true );    		// list unit: 'posts' or 'days'
	param( 'posts', 'integer', 0, true );       // # of units to display on the page
	param( 'paged', 'integer', '', true );      // List page number in paged display
	param( 'poststart', 'integer', 1, true );   // Start results at this position
	param( 'postend', 'integer', '', true );    // End results at this position
	param( 's', 'string', '', true );           // Search string
	param( 'sentence', 'string', 'AND', true ); // Search for sentence or for words
	param( 'exact', 'integer', '', true );      // Require exact match of title or contents
	$preview = 0;
	param( 'c', 'string' );
	param( 'tb', 'integer', 0 );
	param( 'pb', 'integer', 0 );
	param( 'show_status', 'array', array( 'published', 'protected', 'private', 'draft', 'deprecated' ), true );	// Array of cats to restrict to
	$show_statuses = $show_status;
	param( 'show_past', 'integer', '0', true );
	param( 'show_future', 'integer', '0', true );
	if( ($show_past == 0) && ( $show_future == 0 ) )
	{
		$show_past = 1;
		$show_future = 1;
	}
	$timestamp_min = ( $show_past == 0 ) ? 'now' : '';
	$timestamp_max = ( $show_future == 0 ) ? 'now' : '';

	// Get the posts to display:
	$MainList = & new ItemList( $blog, $show_statuses, $p, $m, $w, $cat, $catsel, $author, $order,
															$orderby, $posts, $paged, $poststart, $postend, $s, $sentence, $exact,
															$preview, $unit, $timestamp_min, $timestamp_max, '', $dstart,
															$objType,	$dbtable, $dbprefix, $dbIDname );

	$posts_per_page = $MainList->posts_per_page;
	$result_num_rows = $MainList->get_num_rows();
	$postIDlist = & $MainList->postIDlist;
	$postIDarray = & $MainList->postIDarray;

	echo '<div class="left_col">';
		switch( $tab )
		{
			case 'posts':
				require dirname(__FILE__).'/_edit_showposts.php';
				break;

			default;
				echo 'unhandled display mode';
		}
	echo '</div>';

  echo '<div class="right_col">';
	 	require dirname(__FILE__).'/_browse_posts_sidebar.inc.php';
	echo '</div>';

	echo '<div class="clear"></div>';

	// End payload block:
	$AdminUI->dispPayloadEnd();
}

require dirname(__FILE__).'/_footer.php';
?>