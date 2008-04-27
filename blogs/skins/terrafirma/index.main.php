<?php
/**
 * This is the main/default page template for the "custom" skin.
 *
 * This skin only uses one single template which includes most of its features.
 * It will also rely on default includes for specific dispays (like the comment form).
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://manual.b2evolution.net/Skins_2.0}
 *
 * The main page template is used to display the blog when no specific page template is available
 * to handle the request (based on $disp).
 *
 * @package evoskins
 * @subpackage terrafirma
 *
 * @version $Id$
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
skin_include( '_html_header.inc.php' );
// Note: You can customize the default HTML header by copying the generic
// /skins/_html_header.inc.php file into the current skin folder.
// -------------------------------- END OF HEADER --------------------------------
?>
<div id="wrapper">
	<div id="upbg"></div>
	<div id="inner">

		<div class="pageHeader">
			<?php
				// ------------------------- "Header" CONTAINER EMBEDDED HERE --------------------------
				// Display container and contents:
				skin_container( NT_('Header'), array(
						// The following params will be used as defaults for widgets included in this container:
						'block_start'       => '<div class="$wi_class$">',
						'block_end'         => '</div>',
						'block_title_start' => '<h1>',
						'block_title_end'   => '</h1>',
					) );
				// ----------------------------- END OF "Header" CONTAINER -----------------------------
			?>
		</div>
		<div id="splash">
			<div class="PageTop">
			<?php
				// ------------------------- "Page Top" CONTAINER EMBEDDED HERE --------------------------
				// Display container and contents:
				skin_container( NT_('Page Top'), array(
						// The following params will be used as defaults for widgets included in this container:
						'block_start'         => '<div class="$wi_class$">',
						'block_end'           => '</div>',
						'block_display_title' => false,
						'list_start'          => '<ul>',
						'list_end'            => '</ul>',
						'item_start'          => '<li>',
						'item_end'            => '</li>',
					) );
				// ----------------------------- END OF "Page Top" CONTAINER -----------------------------
			?>
			</div>
		</div>
			<div class="top_menu">
				<ul>
				<?php
					// ------------------------- "Menu" CONTAINER EMBEDDED HERE --------------------------
					// Display container and contents:
					// Note: this container is designed to be a single <ul> list
					skin_container( NT_('Menu'), array(
							// The following params will be used as defaults for widgets included in this container:
							'block_start'         => '',
							'block_end'           => '',
							'block_display_title' => false,
							'list_start'          => '',
							'list_end'            => '',
							'item_start'          => '<li>',
							'item_end'            => '</li>',
						) );
					// ----------------------------- END OF "Menu" CONTAINER -----------------------------
				?>
				</ul>
				<?php if ( true /* change to false to hide the search box */ ) { ?>
				<div id="search">
				Find<br />
				<form action="<?php $Blog->gen_blogurl() ?>" method="get" class="search">
				<input name="s" size="15" value="" class="form_text_input" type="text" />&nbsp;<input name="submit" class="searchsubmit" value="Go" type="submit" /></form></div>
				<?php } ?>
			</div>


			<!-- =================================== START OF MAIN AREA =================================== -->
			<div class="bPosts">

				<?php
					// ------------------------- MESSAGES GENERATED FROM ACTIONS -------------------------
					messages( array(
							'block_start' => '<div class="action_messages">',
							'block_end'   => '</div>',
						) );
					// --------------------------------- END OF MESSAGES ---------------------------------
				?>

				<?php
					// ------------------------ TITLE FOR THE CURRENT REQUEST ------------------------
					if ($disp != 'page')
					{
						request_title( array(
								'title_before'=> '<h2 class="pagetitle">',
								'title_after' => '</h2>',
								'title_none'  => '',
								'glue'        => ' - ',
								'title_single_disp' => false,
								'format'      => 'htmlbody',
							) );
					}
					// ----------------------------- END OF REQUEST TITLE ----------------------------
				?>

				<?php
					// --------------------------------- START OF POSTS -------------------------------------
					// Display message if no post:
					display_if_empty();

					while( $Item = & mainlist_get_item() )
					{	// For each blog post, do everything below up to the closing curly brace "}"
					?>

					<div id="<?php $Item->anchor_id() ?>" class="bPost bPost<?php $Item->status_raw() ?>" lang="<?php $Item->lang() ?>">

						<?php
							$Item->locale_temp_switch(); // Temporarily switch to post locale (useful for multilingual blogs)
						?>

						<div class="bSmallHead">
						<div class="date">
						<?php
							$Item->author( array(
							'before'    => '<em class="user">',
							'after'     => '</em><br />',
									) );

							$Item->issue_date( array(
									'before'    => '<em class="bPostdate">',
									'after'     => '</em>',
								));
						?>
						</div>
						<h2 class="bTitle"><?php $Item->title(); ?></h2>
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

						<?php
							// List all tags attached to this post:
							$Item->tags( array(
									'before' =>         '<div class="post-tags">'.T_('Tags').': ',
									'after' =>          '</div>',
									'separator' =>      ', ',
								) );
						?>

						<div class="bSmallPrint">
							<ul>
							<?php
							$Item->edit_link( array( // Link to backoffice for editing
								'before'    => '<li>',
								'after'     => '</li>',
							) ); ?>

							<li class="readmore">
							<?php
							$Item->categories( array(
								'before'          => '',
								'after'           => '',
								'include_main'    => true,
								'include_other'   => true,
								'include_external'=> true,
								'link_categories' => true,
							) );
							 ?>
							</li>
							<li class="comments">
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
											'use_popup' => false,
										) );

							// Link to comments, trackbacks, etc.:
							$Item->feedback_link( array(
											'type' => 'trackbacks',
											'link_before' => ' &bull; ',
											'link_after' => '',
											'link_text_zero' => '#',
											'link_text_one' => '#',
											'link_text_more' => '#',
											'link_title' => '#',
											'use_popup' => false,
										) );
							?>
							</li>
							</ul>
							</div>

						<?php
							// ------------------ FEEDBACK (COMMENTS/TRACKBACKS) INCLUDED HERE ------------------
							skin_include( '_item_feedback.inc.php', array(
									'before_section_title' => '<h3>',
									'after_section_title'  => '</h3>',
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
					// -------------------- PREV/NEXT PAGE LINKS (POST LIST MODE) --------------------
					mainlist_page_links( array(
							'block_start' => '<p class="center"><strong>',
							'block_end' => '</strong></p>',
						'prev_text' => '&lt;&lt;',
						'next_text' => '&gt;&gt;',
						) );
					// ------------------------- END OF PREV/NEXT PAGE LINKS -------------------------
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
			<div class="bSideBar">

				<?php
					// ------------------------- "Sidebar" CONTAINER EMBEDDED HERE --------------------------
					// Display container contents:
					skin_container( NT_('Sidebar'), array(
							// The following (optional) params will be used as defaults for widgets included in this container:
							// This will enclose each widget in a block:
							'block_start' => '<div class="bSideItem $wi_class$">',
							'block_end' => '</div>',
							// This will enclose the title of each widget:
							'block_title_start' => '<h2>',
							'block_title_end' => '</h2>',
							// If a widget displays a list, this will enclose that list:
							'list_start' => '<ul>',
							'list_end' => '</ul>',
							// This will enclose each item in a list:
							'item_start' => '<li>',
							'item_end' => '</li>',
							// This will enclose sub-lists in a list:
							'group_start' => '<ul>',
							'group_end' => '</ul>',
							// This will enclose (foot)notes:
							'notes_start' => '<div class="notes">',
							'notes_end' => '</div>',
						) );
					// ----------------------------- END OF "Sidebar" CONTAINER -----------------------------
				?>

				<?php
					// Please help us promote b2evolution and leave this logo on your blog:
					powered_by( array(
							'block_start' => '<div class="powered_by">',
							'block_end'   => '</div>',
							// Check /rsc/img/ for other possible images -- Don't forget to change or remove width & height too
							'img_url'     => '$rsc$img/powered-by-b2evolution-120t.gif',
							'img_width'   => 120,
							'img_height'  => 32,
						) );
				?>
			</div>


			<!-- =================================== START OF FOOTER =================================== -->
			<div id="pageFooter">
				<?php
					// Display container and contents:
					skin_container( NT_("Footer"), array(
							// The following params will be used as defaults for widgets included in this container:
						) );
					// Note: Double quotes have been used around "Footer" only for test purposes.
				?>
				<p class="baseline">
					<span class="author_credits">
					<?php
						// Display a link to contact the owner of this blog (if owner accepts messages):
						$Blog->contact_link( array(
								'before'      => '',
								'after'       => '',
								'text'   => T_('Contact'),
								'title'  => T_('Send a message to the owner of this blog...'),
							) );
					?>
					<?php
						// Display footer text (text can be edited in Blog Settings):
						$Blog->footer_text( array(
								'before'      => ' &bull; ',
								'after'       => '',
							) );
					?>
					</span>
					<br />
					Design: <a href="http://www.nodethirtythree.com">Node33</a>
					&bull;
					Skin: <a href="http://wpthemepark.com/themes/terrafirma/">Sadish Bala</a> |
					<?php display_param_link( $skinfaktory_links ) ?>
					<br />
					<?php
						// Display additional credits:
						// If you can add your own credits without removing the defaults, you'll be very cool :))
						// Please leave this at the bottom of the page to make sure your blog gets listed on b2evolution.net
						credits( array(
								'list_start'  => ' ',
								'list_end'    => ' ',
								'separator'   => '|',
								'item_start'  => ' ',
								'item_end'    => ' ',
							) );
					?>
				</p>
		</div>
	</div>
</div>

<?php
// ------------------------- HTML FOOTER INCLUDED HERE --------------------------
skin_include( '_html_footer.inc.php' );
// Note: You can customize the default HTML footer by copying the
// _html_footer.inc.php file into the current skin folder.
// ------------------------------- END OF FOOTER --------------------------------
?>