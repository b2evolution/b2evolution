<?php
/**
 * This is the main/default page template.
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://manual.b2evolution.net/Skins_2.0}
 *
 * The main page template is used to display the blog when no specific page template is available
 * to handle the request (based on $disp).
 *
 * @package evoskins
 * @subpackage zeke
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// This is the main template; it may be used to display very different things.
// Do inits depending on current $disp:
skin_init( $disp );


// -------------------------- HTML HEADER INCLUDED HERE --------------------------
skin_include( '_html_header.inc.php' );
// Note: You can customize the default HTML header by copying the generic
// /skins/_html_header.inc.php file into the current skin folder.
// -------------------------------- END OF HEADER --------------------------------
?>


<?php
// ------------------------- BODY HEADER INCLUDED HERE --------------------------
skin_include( '_body_header.inc.php' );
// Note: You can customize the default BODY heder by copying the generic
// /skins/_body_footer.inc.php file into the current skin folder.
// ------------------------------- END OF FOOTER --------------------------------
?>

<div id="page">

	<div id="contentleft">

	<?php
	// ------------------------- SIDEBAR INCLUDED HERE --------------------------
	skin_include( '_sidebar_left.inc.php' );
	// Note: You can customize the default BODY footer by copying the
	// _body_footer.inc.php file into the current skin folder.
	// ----------------------------- END OF SIDEBAR -----------------------------
	?>

	<div id="content">

	<?php
		// ------------------------- MESSAGES GENERATED FROM ACTIONS -------------------------
		messages( array(
			'block_start' => '<div class="action_messages">',
			'block_end'   => '</div>',
		) );
		// --------------------------------- END OF MESSAGES ---------------------------------
	?>


	<?php
		// Display message if no post:
		display_if_empty();

		while( $Item = & mainlist_get_item() )
		{	// For each blog post, do everything below up to the closing curly brace "}"
		?>
		<div class="post post<?php $Item->status_raw() ?>" lang="<?php $Item->lang() ?>">

			<?php
				$Item->locale_temp_switch(); // Temporarily switch to post locale (useful for multilingual blogs)
				$Item->anchor(); // Anchor for permalinks to refer to.
			?>

			<h2><?php $Item->title(); ?></h2>

			<p class="postinfo">
			<?php
      	$Item->author( array(
					'before'       => T_('By'),
					'after'        => ' ',
				) );
			?>
			<?php
				$Item->issue_time( array(
						'before'      => /* TRANS: date */ T_('on '),
						'after'       => '',
						'time_format' => 'M j, Y',
					) );
			?>
			<?php
				$Item->categories( array(
						'before'          => ' | '.T_('In '),
						'after'           => ' ',
						'include_main'    => true,
						'include_other'   => true,
						'include_external'=> true,
						'link_categories' => true,
					) );
			?>
			<?php
				// Link to comments, trackbacks, etc.:
				$Item->feedback_link( array(
						'type' => 'feedbacks',
						'link_before' => ' | ',
						'link_after' => '',
						'link_text_zero' => '#',
						'link_text_one' => '#',
						'link_text_more' => '#',
						'link_title' => '#',
						'use_popup' => false,
					) );
			?>
			<?php $Item->edit_link( ' | ', ''); ?>

			<?php
				// ---------------------- POST CONTENT INCLUDED HERE ----------------------
				skin_include( '_item_content.inc.php', array(
						'image_size'	=>	'fit-400x320',
					) );
				// Note: You can customize the default item feedback by copying the generic
				// /skins/_item_feedback.inc.php file into the current skin folder.
				// -------------------------- END OF POST CONTENT -------------------------
			?>

		</div>

		<?php
			// ------------------ FEEDBACK (COMMENTS/TRACKBACKS) INCLUDED HERE ------------------
			skin_include( '_item_feedback.inc.php', array(
					'before_section_title' => '<h3 class="feedback_section">',
					'after_section_title'  => '</h3>',
					'form_title_start' => '<h3 class="comment_form_title">',
					'form_title_end'  => '</h3>',
				) );
			// Note: You can customize the default item feedback by copying the generic
			// /skins/_item_feedback.inc.php file into the current skin folder.
			// ---------------------- END OF FEEDBACK (COMMENTS/TRACKBACKS) ---------------------
		?>

		<?php
			locale_restore_previous();	// Restore previous locale (Blog locale)
		}
	?>

	<?php
		// ------------------- PREV/NEXT POST LINKS (SINGLE POST MODE) -------------------
		item_prevnext_links( array(
				'block_start' => '<table class="prevnext_post"><tr>',
				'prev_start'  => '<td>',
				'prev_end'    => '</td>',
				'next_start'  => '<td class="right">',
				'next_end'    => '</td>',
				'block_end'   => '</tr></table>',
			) );
		// ------------------------- END OF PREV/NEXT POST LINKS -------------------------
	?>

	</div>

</div>

<?php
// ------------------------- SIDEBAR INCLUDED HERE --------------------------
skin_include( '_sidebar_right.inc.php' );
// Note: You can customize the default BODY footer by copying the
// _body_footer.inc.php file into the current skin folder.
// ----------------------------- END OF SIDEBAR -----------------------------
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
