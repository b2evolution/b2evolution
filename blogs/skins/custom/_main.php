<?php
/**
 * This is the main page template.
 *
 * It is used to display the blog when no specific page template is available.
 *
 * @package evoskins
 * @subpackage teal
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// -------------------------- HTML HEADER INCLUDED HERE --------------------------
require $skins_path.'_html_header.inc.php';
// Note: You can customize the default HTML header by copying the 
// _html_header.inc.php file into the current skin folder.
// -------------------------------- END OF HEADER --------------------------------
?>

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
		?>
		</div>

		<h3 class="bTitle"><?php $Item->title(); ?></h3>

		<?php
			// Display images that are linked to this post:
			$Item->images( array(
					'before' =>              '<div class="bImages">',
					'before_image' =>        '<div class="image_block">',
					'before_image_legend' => '<div class="image_legend">',
					'after_image_legend' =>  '</div>',
					'after_image' =>         '</div>',
					'after' =>               '</div>',
					'image_size' =>          'fit-320x320'
				) );
		?>

		<div class="bText">
			<?php $Item->content(); ?>
			<?php
				// Links to post pages (for multipage posts):
				$Item->page_links( '<p class="right">'.T_('Pages:').' ', '</p>', ' &middot; ' );
			?>
		</div>

		<div class="bSmallPrint">
			<?php
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
	} // ---------------------------------- END OF POSTS ------------------------------------

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
	// -------------- START OF INCLUDES FOR LATEST COMMENTS, MY PROFILE, ETC. --------------
	// Note: you can customize any of the sub templates included here by
	// copying the matching php file into your skin directory.
	// Call the dispatcher:
	require $skins_path.'_dispatch.inc.php';
	// --------------- END OF INCLUDES FOR LATEST COMMENTS, MY PROFILE, ETC. ---------------

?>

</div>
<!-- =================================== START OF SIDEBAR =================================== -->

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
// ------------------------- BODY FOOTER INCLUDED HERE --------------------------
require $skins_path.'_body_footer.inc.php';
// Note: You can customize the default BODY footer by copying the
// _body_footer.inc.php file into the current skin folder.
// ------------------------------- END OF FOOTER --------------------------------
?>


<?php
// ------------------------- HTML FOOTER INCLUDED HERE --------------------------
require $skins_path.'_html_footer.inc.php';
// Note: You can customize the default HTML footer by copying the
// _html_footer.inc.php file into the current skin folder.
// ------------------------------- END OF FOOTER --------------------------------
?>
