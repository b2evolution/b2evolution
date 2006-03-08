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
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


$AdminUI->title = $AdminUI->title_titlearea = T_('Browse blog:');

param( 'action', 'string', 'list' );

$blog = autoselect_blog( $Request->param( 'blog', 'integer', 0 ), 'blog_ismember', 1 );

if( ! $blog  )
{ // No blog could be selected
	$Messages->add( sprintf( T_('Since you\'re a newcomer, you\'ll have to wait for an admin to authorize you to post. You can also <a %s>e-mail the admin</a> to ask for a promotion. When you\'re promoted, just reload this page and you\'ll be able to blog. :)'),
									 'href="mailto:'. $admin_email. '?subject=b2-promotion"' ), 'error' );
	$tab = 'postlist';
}
else
{ // We could select a valid blog which we have permission to access:
	$Blog = Blog_get_by_ID( $blog ); /* TMP: */ $blogparams = get_blogparams_by_ID( $blog );
	$AdminUI->title .= ' '.$Blog->dget( 'shortname' );


	// This is used in the display templates
	// TODO: have a method of some object ?
	$add_item_url = '?ctrl=edit&amp;blog='.$blog;

	// Determine tab to use:
	$pref_browse_tab = $UserSettings->get( 'pref_browse_tab' ); // Get last memorized
	$Request->param( 'tab', 'string', $pref_browse_tab, true /* memorize */ );
	if( $tab != $pref_browse_tab )
	{	// We have chosen a different tab from the last one:
		// Make it the new preference:
    $UserSettings->set( 'pref_browse_tab', $tab );
		$UserSettings->dbupdate();
	}

	$Request->param( 'show_past', 'integer', '0', true );
	$Request->param( 'show_future', 'integer', '0', true );
	if( ($show_past == 0) && ( $show_future == 0 ) )
	{
		$show_past = 1;
		$show_future = 1;
	}

	switch( $tab )
	{
		case 'postlist':
		case 'posts':
			/*
			 * Do it all the OLD way:
			 */
			// Show the posts:
			$edit_item_url = $dispatcher.'?ctrl=edit&amp;action=edit&amp;post=';
			$delete_item_url = $dispatcher.'?ctrl=editactions&amp;action=delete&amp;post=';
			$objType = 'Item';
			$dbtable = 'T_posts';
			$dbprefix = 'post_';
			$dbIDname = 'post_ID';

			$Request->param( 'p', 'integer' );                    // Specific post number to display
			$Request->param( 'm', 'integer', '', true );          // YearMonth(Day) to display
			$Request->param( 'w', 'integer', '', true );          // Week number
			$Request->param( 'dstart', 'integer', '', true );     // YearMonth(Day) to start at
			$Request->param( 'unit', 'string', '', true );    		// list unit: 'posts' or 'days'

			$Request->param( 'cat', '/^[*\-]?([0-9]+(,[0-9]+)*)?$/', '', true ); // List of cats to restrict to
			$Request->param( 'catsel', 'array', array(), true );  // Array of cats to restrict to
			// Let's compile those values right away (we use them in several different places):
			$cat_array = array();
			$cat_modifier = '';
			compile_cat_array( $cat, $catsel, /* by ref */ $cat_array, /* by ref */ $cat_modifier, $Blog->ID == 1 ? 0 : $Blog->ID );

			$Request->param( 'author', '/^-?[0-9]+(,[0-9]+)*$/', '', true );     // List of authors to restrict to

			$Request->param( 'order', 'string', 'DESC', true );   // ASC or DESC
			$Request->param( 'orderby', 'string', '', true );     // list of fields to order by

			$Request->param( 'posts', 'integer', 0, true );       // # of units to display on the page
			$Request->param( 'paged', 'integer', '', true );      // List page number in paged display

			$Request->param( 'poststart', 'integer', 1, true );   // Start results at this position
			$Request->param( 'postend', 'integer', '', true );    // End results at this position

			$Request->param( 's', 'string', '', true );           // Search string
			$Request->param( 'sentence', 'string', 'AND', true ); // Search for sentence or for words
			$Request->param( 'exact', 'integer', '', true );      // Require exact match of title or contents

			$preview = 0;

			$Request->param( 'c', 'string' );
			$Request->param( 'tb', 'integer', 0 );
			$Request->param( 'pb', 'integer', 0 );

			$Request->param( 'show_status', 'array', array( 'published', 'protected', 'private', 'draft', 'deprecated' ), true );	// Array of cats to restrict to
			$show_statuses = $show_status;

			$timestamp_min = ( $show_past == 0 ) ? 'now' : '';
			$timestamp_max = ( $show_future == 0 ) ? 'now' : '';

			if( $p )
			{	// We are requesting a specific post, force mode to post display:
				$tab = 'posts';
			}

			if( $posts == 0 && $tab == 'postlist' )
			{
				$posts = 20;
			}

			// Get the posts to display:
			$MainList = & new ItemList( $blog, $show_statuses, $p, $m, $w, $cat, $catsel, $author, $order,
																	$orderby, $posts, $paged, $poststart, $postend, $s, $sentence, $exact,
																	$preview, $unit, $timestamp_min, $timestamp_max, '', $dstart );

			// DO we still use those old style globals? :
			$posts_per_page = $MainList->posts_per_page;
			$result_num_rows = $MainList->get_num_rows();

			$postIDlist = & $MainList->postIDlist;
			$postIDarray = & $MainList->postIDarray;
			break;


		case 'exp':
		case 'tracker':
			/*
			 * Let's go the clean new way...
			 */
			require_once $model_path.'items/_itemlist2.class.php';

			// Create empty List:
			$ItemList = & new ItemList2( $Blog, NULL, NULL );

			// Init filter params:
			if( ! $ItemList->load_from_Request() )
			{ // If we could not init a filterset from request
				// typically happens when we could no fall back to previously saved filterset...
				// echo ' no filterset!';
			}


			if( $ItemList->single_post )
			{	// We have requested a specific post
				// hack this over to the exp tab
				$tab = 'exp';
			}


			switch( $tab )
			{
				case 'exp':
					// Run the query:
					$ItemList->query();

					// Temporary inits:
		      $postIDlist = $ItemList->get_page_ID_list();
		      $postIDarray = $ItemList->get_page_ID_array();

					$Request->param( 'c', 'string' );
					$Request->param( 'tb', 'integer', 0 );
					$Request->param( 'pb', 'integer', 0 );
					break;

				case 'tracker':
					// DO **NOT** Run the query yet! (we want column definitions to be loaded and act as ORDER BY fields)
					break;
			}
			break;


		case 'comments':
			/*
			 * Latest comments:
			 */
			$Request->param( 'show_status', 'array', array( 'published', 'protected', 'private', 'draft', 'deprecated' ), true );	// Array of cats to restrict to
			$show_statuses = $show_status;

			$CommentList = & new CommentList( $blog, "'comment','trackback','pingback'", $show_statuses, '',	'',	'DESC',	'',	20 );
			break;


		default:
  		debug_die( 'Unhandled content; tab='.$tab );
	}
}


/*
 * Add sub menu entries.
 * We do this here instead of _header because we need to include all filter params into regenerate_url()
 * Note: this will override default tabs from _header config.
 */
$AdminUI->add_menu_entries(
		'edit',
		array(
				'postlist' => array(
					'text' => T_('Post list'),
					'href' => regenerate_url( 'tab', 'tab=postlist' ),
					),
				'posts' => array(
					'text' => T_('Full posts'),
					'href' => regenerate_url( 'tab', 'tab=posts' ),
					),
				// EXPERIMENTAL:
				'exp' => array(
					'text' => T_('Experimental'),
					'href' => regenerate_url( 'tab', 'tab=exp&amp;filter=restore' ),
					),
				'tracker' => array(
					'text' => T_('Tracker'),
					'href' => regenerate_url( 'tab', 'tab=tracker&amp;filter=restore' ),
					),
			/*	'commentlist' => array(
					'text' => T_('Comment list'),
					'href' => 'tab=commentlist ), */
				'comments' => array(
					'text' => T_('Comments'),
					'href' => regenerate_url( 'tab', 'tab=comments' ),
					),
			)
	);


$AdminUI->set_path( 'edit', $tab );

// Generate available blogs list:
$blogListButtons = $AdminUI->get_html_collection_list( 'blog_ismember', 1, $dispatcher.'?ctrl=browse&amp;blog=%d&amp;tab='.$tab.'&amp;filter=restore' );

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();


if( $blog )
{ // We could select a valid blog which we have permission to access:
	// Begin payload block:
	$AdminUI->disp_payload_begin();

	switch( $tab )
	{
		case 'comments':
			// Display VIEW:
			$AdminUI->disp_view( 'comments/_browse_comments.inc' );
			break;

		default:
			// fplanque> Note: this is depressing, but I have to put a table back here
			// just because IE supports standards really badly! :'(
			echo '<table class="browse" cellspacing="0" cellpadding="0" border="0"><tr>';

			echo '<td class="browse_left_col">';
				switch( $tab )
				{
					case 'postlist':
						// Display VIEW:
						$AdminUI->disp_view( 'items/_browse_posts_list.inc' );
						break;

					case 'posts':
						// Display VIEW:
						$AdminUI->disp_view( 'items/_edit_showposts' );
						break;

					case 'exp':
						// Display VIEW:
						$AdminUI->disp_view( 'items/_browse_posts_exp.inc' );
						break;

					case 'tracker':
						// Display VIEW:
						$AdminUI->disp_view( 'items/_browse_tracker.inc' );
						break;
				}
			echo '</td>';

			echo '<td class="browse_right_col">';
				// Display VIEW:
				$AdminUI->disp_view( 'items/_browse_posts_sidebar.inc' );
			echo '</td>';

			echo '</tr></table>';
	}

	// End payload block:
	$AdminUI->disp_payload_end();
}

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();
?>