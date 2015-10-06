<?php
/**
 * This is the main/default page template.
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-development-primer}
 *
 * The main page template is used to display the blog when no specific page template is available
 * to handle the request (based on $disp).
 *
 * @package evoskins
 * @subpackage evocamp
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

if( version_compare( $app_version, '2.4.1' ) < 0 )
{ // Older 2.x skins work on newer 2.x b2evo versions, but newer 2.x skins may not work on older 2.x b2evo versions.
	die( 'This skin is designed for b2evolution 2.4.1 and above. Please <a href="http://b2evolution.net/downloads/index.html">upgrade your b2evolution</a>.' );
}

// This is the main template; it may be used to display very different things.
// Do inits depending on current $disp:
skin_init( $disp );


// -------------------------- HTML HEADER INCLUDED HERE --------------------------
skin_include( '_html_header.inc.php', array() );
// -------------------------------- END OF HEADER --------------------------------
?>


<?php
// ------------------------- BODY HEADER INCLUDED HERE --------------------------
skin_include( '_body_header.inc.php' );
// Note: You can customize the default BODY header by copying the generic
// /skins/_body_header.inc.php file into the current skin folder.
// ------------------------------- END OF FOOTER --------------------------------
?>

<div id="page">

	<div id="contentleft">

	<?php
	// ------------------------- SIDEBAR INCLUDED HERE --------------------------
	skin_include( '_sidebar_left.inc.php' );
	// Note: You can customize the left sidebar by copying the
	// _sidebar_left.inc.php file into the current skin folder.
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

		echo '<div class="evo_content_block">';

		$item_class_params = array(
				'item_class'        => 'post',
				'item_type_class'   => 'post_ptyp',
				'item_status_class' => 'post',
			);

		while( $Item = & mainlist_get_item() )
		{ // For each blog post, do everything below up to the closing curly brace "}"
		?>
		<div id="<?php $Item->anchor_id() ?>" class="<?php $Item->div_classes( $item_class_params ) ?>" lang="<?php $Item->lang() ?>">

			<?php
				if( $Item->status != 'published' )
				{
					$Item->format_status( array(
							'template' => '<div class="floatright"><span class="note status_$status$"><span>$status_title$</span></span></div>',
						) );
				}
				$Item->locale_temp_switch(); // Temporarily switch to post locale (useful for multilingual blogs)
			?>

			<h2><?php
				$Item->title( array(
					'link_type' => 'permalink'
				) );
			?></h2>

			<p class="postinfo">
			<?php
			$Item->author( array(
					'profile_tab' => 'user',
					'before'      => T_('By').' ',
					'after'       => ' ',
					'link_text'   => 'preferredname',
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
					) );
			?>
			<?php
				$Item->edit_link( array( // Link to backoffice for editing
						'before'    => ' | ',
						'after'     => '',
					) );
			?>
			</p>
			<?php
				// ---------------------- POST CONTENT INCLUDED HERE ----------------------
				skin_include( '_item_content.inc.php', array(
						'image_size' => 'fit-400x320',
					) );
				// Note: You can customize the default item content by copying the generic
				// /skins/_item_content.inc.php file into the current skin folder.
				// -------------------------- END OF POST CONTENT -------------------------
			?>

			<?php
				// List all tags attached to this post:
				$Item->tags( array(
						'before' =>         '<div class="posttags">'.T_('Tags').': ',
						'after' =>          '</div>',
						'separator' =>      ', ',
					) );
			?>

		</div>

		<?php
			// ------------------ FEEDBACK (COMMENTS/TRACKBACKS) INCLUDED HERE ------------------
			skin_include( '_item_feedback.inc.php', array(
					'before_section_title' => '<h3 class="feedback_section">',
					'after_section_title'  => '</h3>',
					'form_title_start'     => '<h3 class="comment_form_title">',
					'form_title_end'       => '</h3>',
					'author_link_text'     => 'preferredname',
				) );
			// Note: You can customize the default item feedback by copying the generic
			// /skins/_item_feedback.inc.php file into the current skin folder.
			// ---------------------- END OF FEEDBACK (COMMENTS/TRACKBACKS) ---------------------
		?>

		<?php
			locale_restore_previous();	// Restore previous locale (Blog locale)
		}
		echo '</div>';
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
// Note: You can customize the right sidebar by copying the
// _sidebar_right.inc.php file into the current skin folder.
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
