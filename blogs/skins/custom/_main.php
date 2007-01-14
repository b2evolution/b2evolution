<?php
/**
 * This is the main template. It displays the blog.
 *
 * However this file is not meant to be called directly.
 * It is meant to be called automagically by b2evolution.
 * To display a blog, the easiest way is to call index.php?blog=#
 * where # is the number of your blog.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2005 by Jason EDGECOMBE.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 *
 * Jason EDGECOMBE grants Francois PLANQUE the right to license
 * Jason EDGECOMBE's personal contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evoskins
 * @subpackage custom
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author cafelog (team)
 * @author edgester: Jason EDGECOMBE (personal contributions, not for hire)
 * @author fplanque: Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

$Timer->start( 'skin/_main.inc:header' );

skin_content_header();	// Sets charset!
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
<head>
	<?php skin_content_meta(); /* Charset for static pages */ ?>
	<?php $Plugins->trigger_event( 'SkinBeginHtmlHead' ); ?>
	<title><?php
		$Blog->disp('name', 'htmlhead');
		request_title( ' - ', '', ' - ', 'htmlhead' );
	?></title>
	<?php skin_base_tag(); /* Base URL for this skin. You need this to fix relative links! */ ?>
	<meta name="description" content="<?php $Blog->disp( 'shortdesc', 'htmlattr' ); ?>" />
	<meta name="keywords" content="<?php $Blog->disp( 'keywords', 'htmlattr' ); ?>" />
	<meta name="generator" content="b2evolution <?php echo $app_version ?>" /> <!-- Please leave this for stats -->
	<link rel="alternate" type="text/xml" title="RSS 2.0" href="<?php $Blog->disp( 'rss2_url', 'raw' ) ?>" />
	<link rel="alternate" type="application/atom+xml" title="Atom" href="<?php $Blog->disp( 'atom_url', 'raw' ) ?>" />
	<link rel="stylesheet" href="custom.css" type="text/css" />
	<?php
		$Blog->disp( 'blog_css', 'raw');
		$Blog->disp( 'user_css', 'raw');
	?>
</head>

<body>
<div id="wrapper">

<?php
	// --------------------------- BLOG LIST INCLUDED HERE -----------------------------
	require dirname(__FILE__).'/_bloglist.php';
	// ------------------------------- END OF BLOG LIST --------------------------------
?>

<div class="pageHeader">
	<?php
		// Display container and contents:
		$Skin->container( NT_('Header'), array(
				// The following params will be used as defaults for widgets included in this container:
				'block_start' => '<div class="$wi_class$">',
				'block_end' => '</div>',
				'block_title_start' => '<h1>',
				'block_title_end' => '</h1>',
			) );
	?>
</div>

<div class="bPosts">

<!-- =================================== START OF MAIN AREA =================================== -->

<?php
	$Timer->pause( 'skin/_main.inc:header' );

	$Timer->start( 'skin/_main.inc:mainarea' );
?>

<?php
	// ------------------------- MESSAGES GENERATED FROM ACTIONS -------------------------
	if( empty( $preview ) ) $Messages->disp( );
	// fp>> TODO: I think we should rather forget the messages here so they don't get displayed again.
	// --------------------------------- END OF MESSAGES ---------------------------------
?>


<?php
	// ------------------------- TITLE FOR THE CURRENT REQUEST -------------------------
	request_title( '<h2>', '</h2>' );
	// ------------------------------ END OF REQUEST TITLE -----------------------------
?>


<?php
	if( isset($MainList) )
	{ // Links to list pages:
		$MainList->page_links( '<p class="center">'.T_('Pages:').' <strong>', '</strong></p>' );
	}
?>


<?php
	// ------------------------------------ START OF POSTS ----------------------------------------
	if( isset($MainList) ) $MainList->display_if_empty(); // Display message if no post

	if( isset($MainList) ) while( $Item = & $MainList->get_item() )
	{
	?>

	<?php
		//previous_post();	// link to previous post in single page mode
		//next_post(); 			// link to next post in single page mode
	?>

	<?php
		$Timer->resume( 'skin/_main.inc:mainarea:postheaders' );

		$MainList->date_if_changed();
	?>

	<div class="bPost bPost<?php $Item->status( 'raw' ) ?>" lang="<?php $Item->lang() ?>">
		<?php
			locale_temp_switch( $Item->locale ); // Temporarily switch to post locale
			$Item->anchor(); // Anchor for permalinks to refer to
		?>
		<div class="bSmallHead">
		<?php
			$Item->permanent_link( '#icon#' );
			echo ' ';
			$Item->issue_time();
			echo ', '.T_('by').' ';
			$Item->author( '<strong>', '</strong>' );
			$Item->msgform_link( $Blog->get('msgformurl') );
			echo ', ';
			$Item->wordcount();
			echo ' '.T_('words');
			echo ', ';
			$Item->views();
			echo ' &nbsp; ';
			locale_flag( $Item->locale, 'h10px' );
			echo '<br /> ', T_('Categories'), ': ';
			$Item->categories();

			$Timer->pause( 'skin/_main.inc:mainarea:postheaders' );

			$Timer->resume( 'skin/_main.inc:mainarea:postcontents' );
		?>
		</div>
		<h3 class="bTitle"><?php $Item->title(); ?></h3>
		<div class="bText">
			<?php $Item->content(); ?>
			<?php
				// Links to post pages (for multipage posts):
				$Item->page_links( '<p class="right">'.T_('Pages:').' ', '</p>', ' &middot; ' );
			?>
		</div>
		<div class="bSmallPrint">
			<?php
			$Timer->pause( 'skin/_main.inc:mainarea:postcontents' );

			$Timer->resume( 'skin/_main.inc:mainarea:postfooters' );

			$Item->permanent_link( '#', '#', 'permalink_right' ); ?>

			<?php $Item->feedback_link( 'comments', '' ) // Link to comments ?>
			<?php $Item->feedback_link( 'trackbacks', ' &bull; ' ) // Link to trackbacks ?>
			<?php $Item->edit_link( ' &bull; ' ) // Link to backoffice for editing ?>

			<?php $Item->trackback_rdf() // trackback autodiscovery information ?>
		</div>
		<?php
			// ------------- START OF INCLUDE FOR COMMENTS, TRACKBACK, PINGBACK, ETC. -------------
			$disp_comments = 1;					// Display the comments if requested
			$disp_comment_form = 1;			// Display the comments form if comments requested
			$disp_trackbacks = 1;				// Display the trackbacks if requested

			$disp_trackback_url = 1;		// Display the trackbal URL if trackbacks requested
			$disp_pingbacks = 0;        // Don't display the pingbacks (deprecated)
			require( dirname(__FILE__).'/_feedback.php' );
			// -------------- END OF INCLUDE FOR COMMENTS, TRACKBACK, PINGBACK, ETC. --------------

			locale_restore_previous();	// Restore previous locale (Blog locale)
		?>
	</div>
	<?php
	$Timer->pause( 'skin/_main.inc:mainarea:postfooters' );
	} // ---------------------------------- END OF POSTS ------------------------------------

	$Timer->start( 'skin/_main.inc:mainarea:extradisp' );

?>

	<?php
		// Links to list pages:
		if( isset($MainList) ) $MainList->page_links( '<p class="center"><strong>', '</strong></p>' );
	?>
	<?php
		// previous_post( '<p class="center">%</p>' );
		// next_post( '<p class="center">%</p>' );
	?>

<?php
	// -------------- START OF INCLUDES FOR LAST COMMENTS, MY PROFILE, ETC. --------------
	// Note: you can customize any of the sub templates included here by
	// copying the matching php file into your skin directory.
	$current_skin_includes_path = dirname(__FILE__).'/';
	// Call the dispatcher:
	require $skins_path.'_dispatch.inc.php';
	// --------------- END OF INCLUDES FOR LAST COMMENTS, MY PROFILE, ETC. ---------------

	$Timer->pause( 'skin/_main.inc:mainarea:extradisp' );

?>

</div>
<!-- =================================== START OF SIDEBAR =================================== -->

<?php
	$Timer->pause( 'skin/_main.inc:mainarea' );

	$Timer->start( 'skin/_main.inc:sidebar' );
?>

<div class="bSideBar">


	<?php
		// Display container contents:
		$Skin->container( NT_('Sidebar'), array(
				// The following (optional) params will be used as defaults for widgets included in this container:
				// This will enclose each widget in a block:
				'block_start' => '<div class="bSideItem $wi_class$">',
				'block_end' => '</div>',
				// This will enclose the title of each widget:
				'block_title_start' => '<h3>',
				'block_title_end' => '</h3>',
				// If a widget displays a list, this will enclose that list:
				'list_start' => '<ul>',
				'list_end' => '</ul>',
				// This will enclose each item in a list:
				'item_start' => '<li>',
				'item_end' => '</li>',
				// This will enclose sub-lists in a list:
				'group_start' => '<ul>',
				'group_end' => '</ul>',
			) );
	?>


	<?php
		// -------------------------- LINKBLOG INCLUDED HERE -----------------------------
		require( dirname(__FILE__).'/_linkblog.php' );
		// -------------------------------- END OF LINKBLOG ----------------------------------
	?>


	<?php
	if( empty($generating_static) && ! $Plugins->trigger_event_first_true('CacheIsCollectingContent') )
	{ // We're not generating static pages nor is a caching plugin collecting the content, so we can display this block
		// TODO: when this gets a SkinTag plugin this check should get done by the Plugin
		// fp> will not be a plugin because it's too closely tied to internals (Sessions)
		?>
		<div class="bSideItem">
			<h3 class="sideItemTitle"><?php echo T_('Who\'s Online?') ?></h3>
			<?php
				$Sessions->display_onliners();
			?>
		</div>
		<?php
	}
	?>


	<p class="center">powered by<br />
	<a href="http://b2evolution.net/" title="b2evolution home"><img src="<?php echo $rsc_url; ?>img/b2evolution_logo_80.gif" alt="b2evolution" width="80" height="17" border="0" class="middle" /></a></p>

</div>

<?php
	$Timer->pause( 'skin/_main.inc:sidebar' );

	$Timer->start( 'skin/_main.inc:footer' );
?>

<div id="pageFooter">
	<?php
		// Display container and contents:
		$Skin->container( NT_("Footer"), array(
				// The following params will be used as defaults for widgets included in this container:
			) );
		// Note: Double quotes have been used around "Footer" only for test purposes.
	?>
	<p class="baseline">
		<a href="<?php echo $Blog->get('msgformurl').'&amp;recipient_id=1&amp;redirect_to='.rawurlencode(url_rel_to_same_host(regenerate_url('','','','&'), $Blog->get('msgformurl'))); ?>">Contact the admin</a>.
		Original template design by <a href="http://fplanque.net/">Fran&ccedil;ois PLANQUE</a> / <a href="http://skinfaktory.com/">The Skin Faktory</a>.
		<?php
			// Display additional credits (see /conf/_advanced.php):
 			// If you can add your own credits without removing the defaults, you'll be very cool :))
		 	// Please leave this at the bottom of the page to make sure your blog gets listed on b2evolution.net
			display_list( $credit_links, T_('Credits').': ', ' ', '|', ' ', ' ' );
		?>
	</p>
</div>
</div>
<?php
	$Hit->log();	// log the hit on this page
	debug_info(); // output debug info if requested

	$Timer->pause( 'skin/_main.inc:footer' );
?>
</body>
</html>