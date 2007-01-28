<?php
/**
 * This is the main page template.
 *
 * It is used to display the blog when no specific page template is available.
 *
 * @package evoskins
 * @subpackage photoblog
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// -------------------------- HTML HEADER INCLUDED HERE --------------------------
require $skins_path.'_html_header.inc.php';
// Note: You can customize the default HTML header by copying the
// _html_header.inc.php file into the current skin folder.
// -------------------------------- END OF HEADER --------------------------------
?>

<?php
// -------------------------- BLOG LIST INCLUDED HERE ----------------------------
require dirname(__FILE__).'/_bloglist.php';
// ------------------------------ END OF BLOG LIST -------------------------------
?>

<div class="pageHeader">

	<div class="floatright">
		<a href="<?php $Blog->disp( 'dynurl', 'raw' ) ?>"><?php echo T_('Recently') ?></a>
		|
		<a href="<?php $Blog->disp( 'arcdirurl', 'raw' ) ?>"><?php echo T_('Index') ?></a>
		<?php
				user_login_link( ' | ', ' ' );
				user_register_link( ' | ', ' ' );
			?>
	</div>
	
	<h1 id="pageTitle"><a href="<?php $Blog->disp( 'url', 'raw' ) ?>"><?php $Blog->disp( 'name', 'htmlbody' ) ?></a></h1>

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
		if( isset($MainList) )
		{ // Links to list pages:
			$MainList->page_links( '<div class="nav_right">', '</div>', '$next$ $prev$', array(
				'prev_text' => '<img src="img/prev.gif" width="29" height="29" alt="'.T_('Previous').'" title="'.T_('Previous').'" />',
				'next_text' => '<img src="img/next.gif" width="29" height="29" alt="'.T_('Next').'" title="'.T_('Next').'" />',
				'no_prev_text' => '',
				'no_next_text' => '<img src="'.$rsc_url.'/img/blank.gif" width="29" height="29" alt="" class="no_nav" />',

			) );
		}
	?>


	<?php
	// ------------------------- TITLE FOR THE CURRENT REQUEST -------------------------
	request_title( '<h2>', '</h2>', ' - ', 'htmlbody', array(
				'category_text' => T_('Album').': ',
				'categories_text' => T_('Albums').': ',
		 ), false, '<h2>&nbsp;</h2>' );
	// ------------------------------ END OF REQUEST TITLE -----------------------------
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


		<?php
			// Display images that are linked to this post:
			$Item->images( array(
					'before' =>              '<div class="bImages">',
					'before_image' =>        '<div class="image_block">',
					'before_image_legend' => '<div class="image_legend">',
					'after_image_legend' =>  '</div>',
					'after_image' =>         '</div>',
					'after' =>               '</div>',
					'image_size' =>          'fit-720x500'
				) );
		?>


		<div class="bDetails">

			<div class="bSmallHead">

				<?php $Item->feedback_link( 'feedbacks', '<div class="action_right">', '</div>',
								get_icon( 'nocomment' ), get_icon( 'comments' ), get_icon( 'comments' ),
								'#', 'published', true ) // Link to comments ?>

				<div class="action_right"><?php $Item->permanent_link( T_('Permalink'), '#' ); ?></div>

				<?php $Item->edit_link( '<div class="action_right">', '</div>', T_('Edit...'), T_('Edit title/description...') ) // Link to backoffice for editing ?>

				<h3 class="bTitle"><?php $Item->title(); ?></h3>
				<span class="timestamp"><?php $Item->issue_date( locale_datefmt().' H:i' ); ?></span>

			</div>

			<div class="bText">
				<?php $Item->content(); ?>
				<?php
					// Links to post pages (for multipage posts):
					$Item->page_links( '<p class="right">'.T_('Pages:').' ', '</p>', ' &middot; ' );
				?>
			</div>

			<div class="bSmallPrint">
			<?php
					echo T_('Albums'), ': ';
					$Item->categories();
				?>
			</div>
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
		?>

		<?php
			locale_restore_previous();	// Restore previous locale (Blog locale)
		?>
	</div>
	<?php
	} // ---------------------------------- END OF POSTS ------------------------------------

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
