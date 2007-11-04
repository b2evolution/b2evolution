<?php
/**
 * This is the main/default page template.
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://manual.b2evolution.net/Skins_2.0}
 *
 * It is used to display the blog when no specific page template is available to handle the request.
 *
 * @package evoskins
 * @subpackage photoblog
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// This is the main template; it may be used to display very different things.
// Do inits depending on current $disp:
skin_init( $disp );


// -------------------------- HTML HEADER INCLUDED HERE --------------------------
skin_include( '_html_header.inc.php' );
// Note: You can customize the default HTML header by copying the
// _html_header.inc.php file into the current skin folder.
// -------------------------------- END OF HEADER --------------------------------
?>

<div class="PageTop">
	<?php
		// Display container and contents:
		skin_container( NT_('Page Top'), array(
				// The following params will be used as defaults for widgets included in this container:
				'block_start' => '<div class="$wi_class$">',
				'block_end' => '</div>',
				'block_display_title' => false,
				'list_start' => '<ul>',
				'list_end' => '</ul>',
				'item_start' => '<li>',
				'item_end' => '</li>',
			) );
	?>
</div>

<div class="pageHeader">

	<div class="top_menu floatright">
		<?php
			// ------------------------- "Menu" CONTAINER EMBEDDED HERE --------------------------
			// Display container and contents:
			skin_container( NT_('Menu'), array(
					// The following params will be used as defaults for widgets included in this container:
					'block_start' => '',
					'block_end' => '',
					'block_display_title' => false,
					'list_start' => '',
					'list_end' => '',
					'item_start' => ' ',
					'item_end' => ' | ',
				) );
			// ----------------------------- END OF "Menu" CONTAINER -----------------------------
		?>
		<a href="<?php $Blog->disp( 'arcdirurl', 'raw' ) ?>"><?php echo T_('Index') ?></a>
	</div>
	
	<h1 id="pageTitle"><a href="<?php $Blog->disp( 'url', 'raw' ) ?>"><?php $Blog->disp( 'name', 'htmlbody' ) ?></a></h1>

</div>
<div class="bPosts">
	
<!-- =================================== START OF MAIN AREA =================================== -->

	<?php
	// ------------------------- MESSAGES GENERATED FROM ACTIONS -------------------------
	messages( array(
			'block_start' => '<div class="action_messages">',
			'block_end'   => '</div>',
		) );
	// --------------------------------- END OF MESSAGES ---------------------------------
	?>

	<?php
		// -------------------- PREV/NEXT PAGE LINKS (POST LIST MODE) --------------------
		mainlist_page_links( array(
				'block_start' => '<div class="nav_right">',
				'block_end' => '</div>',
				'links_format' => '$next$ $prev$',
				'prev_text' => '<img src="img/prev.gif" width="29" height="29" alt="'.T_('Previous').'" title="'.T_('Previous').'" />',
				'next_text' => '<img src="img/next.gif" width="29" height="29" alt="'.T_('Next').'" title="'.T_('Next').'" />',
				'no_prev_text' => '',
				'no_next_text' => '<img src="'.$rsc_url.'/img/blank.gif" width="29" height="29" alt="" class="no_nav" />',
			) );
		// ------------------------- END OF PREV/NEXT PAGE LINKS -------------------------
	?>

	<?php
		// ------------------- PREV/NEXT POST LINKS (SINGLE POST MODE) -------------------
		item_prevnext_links( array(
				'template' => '$next$$prev$',
				'block_start' => '<div class="nav_right">',
				'next_start'  => '',
				'next_text' => '<img src="img/next.gif" width="29" height="29" alt="'.T_('Next').'" title="'.T_('Next').'" />',
				'next_no_item' => '<img src="'.$rsc_url.'/img/blank.gif" width="29" height="29" alt="" class="no_nav" />',
				'next_end'    => ' ',
				'prev_start'  => '',
				'prev_text' => '<img src="img/prev.gif" width="29" height="29" alt="'.T_('Previous').'" title="'.T_('Previous').'" />',
				'prev_no_item' => '',
				'prev_end'    => '',
				'block_end'   => '</div>',
			) );
		// ------------------------- END OF PREV/NEXT POST LINKS -------------------------
	?>

	<?php
		// ------------------------- TITLE FOR THE CURRENT REQUEST -------------------------
		request_title( array(
				'title_before'=> '<h2>',
				'title_after' => '</h2>',
				'title_none'  => '<h2>&nbsp;</h2>',
				'glue'        => ' - ',
				'title_single_disp' => false,
				'format'      => 'htmlbody',
				'arcdir_text' => T_('Index'),
				'catdir_text' => T_('Albums'),
				'category_text' => T_('Album').': ',
				'categories_text' => T_('Albums').': ',
			) );
		// ------------------------------ END OF REQUEST TITLE -----------------------------
	?>

	<?php
	// ------------------------------------ START OF POSTS ----------------------------------------
	// Display message if no post:
	display_if_empty();

	while( $Item = & mainlist_get_item() )
	{	// For each blog post, do everything below up to the closing curly brace "}"
	?>
	
	<div class="bPost bPost<?php $Item->status_raw() ?>" lang="<?php $Item->lang() ?>">

		<?php
			$Item->locale_temp_switch(); // Temporarily switch to post locale (useful for multilingual blogs)
			$Item->anchor(); // Anchor for permalinks to refer to.
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

				<?php
					// Link to comments, trackbacks, etc.:
					$Item->feedback_link( array(
									'type' => 'feedbacks',
									'link_before' => '<div class="action_right">',
									'link_after' => '</div>',
									'link_text_zero' => get_icon( 'nocomment' ),
									'link_text_one' => get_icon( 'comments' ),
									'link_text_more' => get_icon( 'comments' ),
									'link_title' => '#',
									'use_popup' => true,
								) );

					$Item->permanent_link( array(
							'before'    => '<div class="action_right">',
							'after'     => '</div>',
							'text' => T_('Permalink'),
						) );
				?>

				<?php
					$Item->edit_link( array( // Link to backoffice for editing
							'before'    => '<div class="action_right">',
							'after'     => '</div>',
							'text'      => T_('Edit...'),
              'title'     => T_('Edit title/description...'),
						) );
				?>

				<h3 class="bTitle"><?php $Item->title(); ?></h3>

				<?php
					$Item->issue_date( array(
							'before'      => '<span class="timestamp">',
							'after'       => '</span>',
							'date_format' => locale_datefmt().' H:i',
						) );
				?>

			</div>

			<?php
				// ---------------------- POST CONTENT INCLUDED HERE ----------------------
				skin_include( '_item_content.inc.php', array(
						'image_size'	=>	'',
					) );
				// Note: You can customize the default item feedback by copying the generic
				// /skins/_item_feedback.inc.php file into the current skin folder.
				// -------------------------- END OF POST CONTENT -------------------------
			?>

			<div class="bSmallPrint">
			<?php
				$Item->categories( array(
					'before'          => T_('Albums').': ',
					'after'           => ' ',
					'include_main'    => true,
					'include_other'   => true,
					'include_external'=> true,
					'link_categories' => true,
				) );
			?>
			</div>
		</div>

		<?php
			// ------------------ FEEDBACK (COMMENTS/TRACKBACKS) INCLUDED HERE ------------------
			skin_include( '_item_feedback.inc.php', array(
					'before_section_title' => '<h4>',
					'after_section_title'  => '</h4>',
				) );
			// Note: You can customize the default item feedback by copying the generic
			// /skins/_item_feedback.inc.php file into the current skin folder.
			// ---------------------- END OF FEEDBACK (COMMENTS/TRACKBACKS) ---------------------
		?>

		<?php
			locale_restore_previous();	// Restore previous locale (Blog locale)
		?>
	</div>
	<?php
	} // ---------------------------------- END OF POSTS ------------------------------------
	?>


	<?php
		// -------------- MAIN CONTENT TEMPLATE INCLUDED HERE (Based on $disp) --------------
		skin_include( '$disp$', array(
				'disp_posts'  => '',		// We already handled this case above
				'disp_single' => '',		// We already handled this case above
				'disp_page'   => '',		// We already handled this case above
			) );
		// Note: you can customize any of the sub templates included here by
		// copying the matching php file into your skin directory.
		// ------------------------- END OF MAIN CONTENT TEMPLATE ---------------------------
	?>

</div>


<?php
// ------------------------- BODY FOOTER INCLUDED HERE --------------------------
skin_include( '_body_footer.inc.php' );
// Note: You can customize the default BODY footer by copying the
// _body_footer.inc.php file into the current skin folder.
// ------------------------------- END OF FOOTER --------------------------------
?>


<?php
// ------------------------- HTML FOOTER INCLUDED HERE --------------------------
skin_include( '_html_footer.inc.php' );
// Note: You can customize the default HTML footer by copying the
// _html_footer.inc.php file into the current skin folder.
// ------------------------------- END OF FOOTER --------------------------------
?>