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
 * @subpackage touch
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

if( version_compare( $app_version, '4.0.0-dev' ) < 0 )
{ // Older 2.x skins work on newer 2.x b2evo versions, but newer 2.x skins may not work on older 2.x b2evo versions.
	die( 'This skin is designed for b2evolution 4.0.0 and above. Please <a href="http://b2evolution.net/downloads/index.html">upgrade your b2evolution</a>.' );
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
// ------------------------------- END OF HEADER --------------------------------
?>



<div id="content" class="widecolumn">


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

echo '<div class="evo_content_block">'; // Beginning of posts display
while( $Item = & mainlist_get_item() )
{	// For each blog post, do everything below up to the closing curly brace "}"
	?>

	<?php
		$Item->locale_temp_switch(); // Temporarily switch to post locale (useful for multilingual blogs)
	?>

	<div class="post">
		<?php
			$Item->title( array(
					'link_type'  => 'permalink',
					'link_class' => 'sh2'
				) );
		?>

		<div class="single-post-meta-top">
			<?php
				// We want to display the post date:
				$Item->issue_time( array(
						'before'      => /* TRANS: date */ '',
						'time_format' => 'F jS, Y',
					) );
				$Item->issue_time( array(
						'before'      => /* TRANS: at (time) */ T_('at').' ',
						'time_format' => '#short_time',
					) );
				$Item->author( array(
						'before'    => ' > ',
						'link_text' => 'preferredname',
					) );
			?>
		<br>
			<?php /*<a href="#com-head">&darr; Skip to comments</a>*/ ?>
	<?php
		// Link to comments, trackbacks, etc.:
		$Item->feedback_link( array(
				'type' => 'feedbacks',
				'link_before' => '',
				'link_after' => '',
				'link_text_zero' => '&darr; '.T_('Skip to comments'),
				'link_text_one' => '&darr; '.T_('Skip to comments'),
				'link_text_more' => '&darr; '.T_('Skip to comments'),
				'link_title' => '',
				'show_in_single_mode' => true
			) );
	?>
		</div>
	</div>

	<div id="<?php $Item->anchor_id() ?>" class="post post<?php $Item->status_raw() ?>" lang="<?php $Item->lang() ?>">
		<?php
			if( $Item->status != 'published' )
			{
				$Item->format_status( array(
						'template' => '<div class="floatright"><span class="note status_$status$"><span>$status_title$</span></span></div>',
					) );
			}
			// ---------------------- POST CONTENT INCLUDED HERE ----------------------
			skin_include( '_item_content.inc.php', array(
					'image_size' => 'fit-256x256',
				) );
			// Note: You can customize the default item content by copying the generic
			// /skins/_item_content.inc.php file into the current skin folder.
			// -------------------------- END OF POST CONTENT -------------------------
		?>

		<?php
			$Item->edit_link( array( // Link to backoffice for editing
					'before'    => '',
					'after'     => '',
				) );
		?>

		<div class="single-post-meta-bottom">
				<?php
					$Item->categories( array(
						'before'          => ' '.T_('Categories').': ',
						'after'           => '.',
						'include_main'    => true,
						'include_other'   => true,
						'include_external'=> true,
						'link_categories' => true,
					) );
				?>

				<?php
					// List all tags attached to this post:
					$Item->tags( array(
							'before' =>         '<br />'.T_('Tags').': ',
							'after' =>          ' ',
							'separator' =>      ', ',
						) );
				?>
		</div>

		<?php
			// ------------------- PREV/NEXT POST LINKS (SINGLE POST MODE) -------------------
			item_prevnext_links( array(
					'block_start' => '<ul id="post-options">',
					'prev_start'  => '<li>',
					'prev_text'   => '',
					'prev_end'    => '</li>',
					'prev_class'  => 'oprev',
					'next_start'  => '<li>',
					'next_text'   => '',
					'next_end'    => '</li>',
					'next_class'  => 'onext',
					'block_end'   => '</ul>',
				) );
			// ------------------------- END OF PREV/NEXT POST LINKS -------------------------
		?>

	</div>


	<?php
		// ------------------ FEEDBACK (COMMENTS/TRACKBACKS) INCLUDED HERE ------------------
		skin_include( '_item_feedback.inc.php', array(
				'author_link_text' => 'preferredname',
			) );
		// Note: You can customize the default item feedback by copying the generic
		// /skins/_item_feedback.inc.php file into the current skin folder.
		// ---------------------- END OF FEEDBACK (COMMENTS/TRACKBACKS) ---------------------
	?>

	<?php
	locale_restore_previous();	// Restore previous locale (Blog locale)
}
echo '</div>'; // End of posts display
?>

</div>


<?php
// ------------------------- MOBILE FOOTER INCLUDED HERE --------------------------
skin_include( '_mobile_footer.inc.php' );
// Note: You can customize the default MOBILE FOOTER footer by copying the
// _mobile_footer.inc.php file into the current skin folder.
// ----------------------------- END OF MOBILE FOOTER -----------------------------

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