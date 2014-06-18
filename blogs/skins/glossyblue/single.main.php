<?php
/**
 * This is the main/default page template.
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-structure}
 *
 * The main page template is used to display the blog when no specific page template is available
 * to handle the request (based on $disp).
 *
 * @package evoskins
 * @subpackage glossyblue
 *
 * @version $Id: single.main.php 4276 2013-07-17 11:05:10Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

if( version_compare( $app_version, '2.4.1' ) < 0 )
{ // Older 2.x skins work on newer 2.x b2evo versions, but newer 2.x skins may not work on older 2.x b2evo versions.
	die( 'This skin is designed for b2evolution 2.4.1 and above. Please <a href="http://b2evolution.net/downloads/index.html">upgrade your b2evolution</a>.' );
}

// This is the main template; it may be used to display very different things.
// Do inits depending on current $disp:
skin_init( $disp );


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


<div id="content" >


<?php
	// ------------------------- MESSAGES GENERATED FROM ACTIONS -------------------------
	messages( array(
			'block_start' => '<div class="action_messages">',
			'block_end'   => '</div>',
		) );
	// --------------------------------- END OF MESSAGES ---------------------------------
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


<?php
// Display message if no post:
display_if_empty();

echo '<div id="styled_content_block">'; // Beginning of posts display
while( $Item = & mainlist_get_item() )
{	// For each blog post, do everything below up to the closing curly brace "}"
	?>

	<?php
		$Item->locale_temp_switch(); // Temporarily switch to post locale (useful for multilingual blogs)
		$Item->anchor(); // Anchor for permalinks to refer to.
	?>

	<div id="post-<?php $Item->ID() ?>" class="post post<?php $Item->status_raw() ?>" lang="<?php $Item->lang() ?>">
		<div class="post-date">
			<span class="post-month"><?php $Item->issue_time( array(
						'before'    => '',
						'after'     => '',
						'date_format' => 'M',
					)); ?></span>
			<span class="post-day"><?php $Item->issue_time( array(
						'before'    => '',
						'after'     => '',
						'date_format' => 'd',
					)); ?></span>
		</div>
		<div class="post-title">
			<?php
			if( $Item->status != 'published' )
			{
				$Item->status( array( 'format' => 'styled' ) );
			}
			?>
			<h2><?php
				$Item->title( array(
					'link_type' => 'permalink'
					) );
			?></h2>
		<?php
				$Item->categories( array(
					'before'          => '<span class="post-cat">',
					'after'           => '</span>',
					'include_main'    => true,
					'include_other'   => true,
					'include_external'=> true,
					'link_categories' => true,
				) );
			// Link to comments, trackbacks, etc.:
				$Item->feedback_link( array(
					'link_before' => '<span class="mini-add-comment">',
					'link_after' => '</span>',
					'link_text_zero' => T_('Add comments'),
					'link_text_one' => T_('Add comments'),
					'link_text_more' => T_('Add comments'),
					'link_title' => '#',
					'use_popup' => false,
					'show_in_single_mode' => true
				) ); ?>
		</div>

		<?php
			// ---------------------- POST CONTENT INCLUDED HERE ----------------------
			skin_include( '_item_content.inc.php', array(
					'image_size'	=>	'fit-400x320',
				) );
			// Note: You can customize the default item feedback by copying the generic
			// /skins/_item_feedback.inc.php file into the current skin folder.
			// -------------------------- END OF POST CONTENT -------------------------
		?>

		<p class="postmetadata alt small">
				<?php
					// List all tags attached to this post:
					$Item->tags( array(
							'before' =>         ' '.T_('Tags').': ',
							'after' =>          ' ',
							'separator' =>      ', ',
						) );
				?>
				<!-- You can follow any responses to this entry through the RSS feed. -->
				<?php
					$Item->edit_link( array( // Link to backoffice for editing
							'before'    => '',
							'after'     => '',

						) );
				?>
		</p>

	</div>


	<?php
		// ------------------ FEEDBACK (COMMENTS/TRACKBACKS) INCLUDED HERE ------------------
		skin_include( '_item_feedback.inc.php', array(
				'before_section_title' => '<h3>',
				'after_section_title'  => '</h3>',
				'author_link_text'     => 'preferredname',
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
// ------------------------- SIDEBAR INCLUDED HERE --------------------------
skin_include( '_sidebar.inc.php' );
// Note: You can customize the default BODY footer by copying the
// _body_footer.inc.php file into the current skin folder.
// ----------------------------- END OF SIDEBAR -----------------------------
?>

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