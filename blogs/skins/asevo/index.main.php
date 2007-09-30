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
 * @subpackage asevo
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

<div class="head_zone">
<div class="inner_wrapper">
<div class="PageTop">
	<?php
		// Display container and contents:
		$Skin->container( NT_('Page Top'), array(
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

	&nbsp;
</div>
</div>
</div>


<div class="nav_zone">
<div class="inner_wrapper">
<div class="top_menu">
	<ul>
	<?php
		// ------------------------- "Menu" CONTAINER EMBEDDED HERE --------------------------
		// Display container and contents:
		// Note: this container is designed to be a single <ul> list
		$Skin->container( NT_('Menu'), array(
				// The following params will be used as defaults for widgets included in this container:
				'block_start' => '',
				'block_end' => '',
				'block_display_title' => false,
				'list_start' => '',
				'list_end' => '',
				'item_start' => '<li>',
				'item_end' => '</li>',
			) );
		// ----------------------------- END OF "Menu" CONTAINER -----------------------------
	?>
	</ul>
	&nbsp;
</div>
</div>
</div>


<div class="main_zone">
<div class="inner_wrapper">

<div class="evo_title_area">
	<?php
		// Display container and contents:
		$Skin->container( NT_('Header'), array(
				// The following params will be used as defaults for widgets included in this container:
				'block_start' => '<div class="$wi_class$">',
				'block_end' => '</div>',
				'block_title_start' => '<h2>',
				'block_title_end' => '</h2>',
			) );
	?>
</div>


<div class="evo_main_area">

	<?php
		// ------------------------- MESSAGES GENERATED FROM ACTIONS -------------------------
		$Messages->disp( '<div class="action_messages">', '</div>' );
		// --------------------------------- END OF MESSAGES ---------------------------------
	?>

	<?php
		if( isset($MainList) )
		{ // Links to previous and next post in single post mode:
			$MainList->prevnext_item_links( array(
					'block_start' => '<table class="prevnext_post"><tr>',
					'prev_start'  => '<td>',
					'prev_end'    => '</td>',
					'next_start'  => '<td class="right">',
					'next_end'    => '</td>',
					'block_end'   => '</tr></table>' ) );
		}
	?>

	<?php
		// ------------------------- TITLE FOR THE CURRENT REQUEST -------------------------
		request_title( array(
				'title_before'=> '<h2 class="evo_req_title">',
				'title_after' => '</h2>',
				'title_none'  => '',
				'glue'        => ' - ',
				'title_single_disp' => false,
				'format'      => 'htmlbody',
			) );
		// ------------------------------ END OF REQUEST TITLE -----------------------------
	?>

	<!-- =================================== START OF MAIN AREA =================================== -->

	<?php // ------------------------------------ START OF POSTS ----------------------------------------
		if( isset($MainList) ) $MainList->display_if_empty(); // Display message if no post

		if( isset($MainList) ) while( $Item = & $MainList->get_item() )
		{
		// $MainList->date_if_changed( '<h2>', '</h2>', '' );
		?>
		<div class="evo_post" lang="<?php $Item->lang() ?>">
			<?php
				locale_temp_switch( $Item->locale ); // Temporarily switch to post locale
				$Item->anchor(); // Anchor for permalinks to refer to
			?>

			<?php
    		$Item->edit_link( '', '', '#', '#', 'floatright small' ); // Link to backoffice for editing
			?>
			<h1 class="evo_post_title"><?php
			$Item->permanent_link( '#title#' );
			?></h1>

			<div class="evo_post_head">
			<?php
				$Item->permanent_link( '#icon#' );
				echo ' ';
				$Item->issue_date();
				echo ' ';
				$Item->issue_time( 'H:i' );
				echo ', ', T_('Categories'), ': ';
				$Item->categories();
			?>
			</div>

			<?php
				// ------------------ FEEDBACK (COMMENTS/TRACKBACKS) INCLUDED HERE ------------------
				skin_include( '_item_content.inc.php', array(
						'image_size'	=>	'fit-400x320',
					) );
				// Note: You can customize the default item feedback by copying the generic
				// /skins/_item_feedback.inc.php file into the current skin folder.
				// ---------------------- END OF FEEDBACK (COMMENTS/TRACKBACKS) ---------------------
			?>

			<div class="evo_post_foot">
				<?php
					// Link to comments, trackbacks, etc.:
					$Item->feedback_link( array(
									'type' => 'comments',
									'link_before' => '',
									'link_after' => '',
									'link_text_zero' => '#',
									'link_text_one' => '#',
									'link_text_more' => '#',
									'link_title' => '#',
									'use_popup' => true,
								) );
				 ?>
				<?php
					// Link to comments, trackbacks, etc.:
					$Item->feedback_link( array(
									'type' => 'trackbacks',
									'link_before' => ' &bull; ',
									'link_after' => '',
									'link_text_zero' => '#',
									'link_text_one' => '#',
									'link_text_more' => '#',
									'link_title' => '#',
									'use_popup' => true,
								) );
				 ?>

				<?php $Item->trackback_rdf() // trackback autodiscovery information ?>
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

	<?php } // ---------------------------------- END OF POSTS ------------------------------------ ?>

	
	<?php
		// Links to list pages:
		if( isset($MainList) ) $MainList->page_links( '<p class="center"><strong>', '</strong></p>', '$prev$ :: $next$', array(
   			'prev_text' => '&lt;&lt; '.T_('Previous'),
   			'next_text' => T_('Next').' &gt;&gt;',
			) );
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
<!-- =================================== START OF SIDEBAR =================================== -->

<div class="evo_sidebar">

	<?php
		// Display container contents:
		$Skin->container( NT_('Sidebar'), array(
				// The following (optional) params will be used as defaults for widgets included in this container:
				// This will enclose each widget in a block:
				'block_start' => '<div class="evo_side_item $wi_class$">',
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

	<div class="evo_side_credits">
		<p class="center"><!-- Please help us promote b2evolution and leave this link on your blog. --><a href="http://b2evolution.net/" title="b2evolution: next generation blog software"><img src="../../rsc/img/powered-by-b2evolution-120t.gif" alt="powered by b2evolution free blog software" title="b2evolution: next generation blog software" width="120" height="32" border="0" /></a></p>
	</div>

</div>

<div class="clear"><img src="<?php echo $rsc_url; ?>img/blank.gif" width="1" height="1" alt="" /></div>

</div>
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