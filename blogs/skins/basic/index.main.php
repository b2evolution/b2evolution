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
 * @subpackage basic
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// This is the main template; it may be used to display very different things.
// Do inits depending on current $disp:
skin_init( $disp );

// ----------------------------- HEADER BEGINS HERE ------------------------------
skin_content_header();	// Sets charset!
?>
<html>
<head>
	<?php skin_content_meta(); /* Charset for static pages */ ?>
	<?php $Plugins->trigger_event( 'SkinBeginHtmlHead' ); ?>
	<title><?php
		// ------------------------- TITLE FOR THE CURRENT REQUEST -------------------------
		request_title( array(
			'auto_pilot'      => 'seo_title',
		) );
		// ------------------------------ END OF REQUEST TITLE -----------------------------
	?></title>
	<?php skin_base_tag(); /* Base URL for this skin. You need this to fix relative links! */ ?>
	<meta name="generator" content="b2evolution <?php echo $app_version ?>" /> <!-- Please leave this for stats -->
	<?php include_headlines() ?>
</head>
<body>
<?php
// -------------------------------- END OF HEADER --------------------------------
?>

	<?php
		// Display container and contents:
		skin_container( NT_('Page Top'), array(
				// The following params will be used as defaults for widgets included in this container:
				'block_start' => '<div class="$wi_class$">',
				'block_end' => '</div>',
				'block_display_title' => false,
				'list_start' =>  T_('Select blog:').' ',
				'list_end' => '',
				'item_start' => ' [',
				'item_end' => '] ',
				'item_selected_start' => ' [<strong>',
				'item_selected_end' => '</strong>] ',
			) );
	?>


	<hr>
	<div align="center">
		<h1><?php $Blog->name() ?></h1>
		<?php
			$Blog->tagline( array(
					'before'    => '<p>',
					'after'     => '</p>',
				) );
		?>
	</div>
	<?php
		$Blog->longdesc( array(
				'before'    => '<hr><small>',
				'after'     => '</small>',
			) );
	?>

	<hr>

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
				'block_start' => '',
				'prev_start'  => '',
				'prev_end'    => ' :',
				'next_start'  => ': ',
				'next_end'    => '',
				'block_end'   => '',
			) );
		// ------------------------- END OF PREV/NEXT POST LINKS -------------------------
	?>

	<?php
		// ------------------------- TITLE FOR THE CURRENT REQUEST -------------------------
		request_title( array(
				'title_before'=> '<h2>',
				'title_after' => '</h2>',
				'title_none'  => '',
				'glue'        => ' - ',
				'title_single_disp' => true,
				'format'      => 'htmlbody',
			) );
		// ------------------------------ END OF REQUEST TITLE -----------------------------
	?>

	<?php	// ---------------------------------- START OF POSTS --------------------------------------
		// Display message if no post:
		display_if_empty();

		while( $Item = & mainlist_get_item() )
		{	// For each blog post, do everything below up to the closing curly brace "}"
		?>

			<?php
			// ------------------------------ DATE SEPARATOR ------------------------------
			$MainList->date_if_changed( array(
					'before'      => '<h2>',
					'after'       => '</h2>',
					'date_format' => '#',
				) );
			?>

			<div id="<?php $Item->anchor_id() ?>" lang="<?php $Item->lang() ?>">

			<?php
				$Item->locale_temp_switch(); // Temporarily switch to post locale (useful for multilingual blogs)
			?>

			<h3>
				<?php $Item->issue_time(); ?>
				<a href="<?php $Item->permanent_url() ?>" title="<?php echo T_('Permanent link to full entry') ?>"><img src="img/icon_minipost.gif" alt="Permalink" width="12" height="9" border="0" align="absmiddle" /></a>
				<?php $Item->title(); ?>
			</h3>

			<blockquote>

				<?php
					$Item->categories( array(
						'before'          => '<small>'.T_('Categories').': ',
						'after'           => '</small>',
						'include_main'    => true,
						'include_other'   => true,
						'include_external'=> true,
						'link_categories' => true,
					) );
				?>

				<?php
					// ---------------------- POST CONTENT INCLUDED HERE ----------------------
					skin_include( '_item_content.inc.php', array(
							'image_size'	=>	'fit-400x320',
						) );
					// Note: You can customize the default item feedback by copying the generic
					// /skins/_item_feedback.inc.php file into the current skin folder.
					// -------------------------- END OF POST CONTENT -------------------------
				?>

				<small>
					<?php
						// Link to comments, trackbacks, etc.:
						$Item->feedback_link( array(
										'type' => 'feedbacks',
										'link_before' => '',
										'link_after' => ' &bull; ',
										'link_text_zero' => '#',
										'link_text_one' => '#',
										'link_text_more' => '#',
										'link_title' => '#',
										'use_popup' => false,
									) );
					?>

					<?php
						$Item->edit_link( array( // Link to backoffice for editing
								'before'    => '',
								'after'     => ' &bull; ',
							) );
					?>

					<?php $Item->permanent_link(); ?>
				</small>

			</blockquote>

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

	<?php } // --------------------------------- END OF POSTS ----------------------------------- ?>


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


	<hr>


	<div align="center">
		<?php
			// -------------------- PREV/NEXT PAGE LINKS (POST LIST MODE) --------------------
			mainlist_page_links( array(
					'block_start' => '<p class="center"><strong>',
					'block_end' => '</strong></p>',
					'links_format' => '$prev$ :: $next$',
   				'prev_text' => '&lt;&lt; '.T_('Previous'),
   				'next_text' => T_('Next').' &gt;&gt;',
				) );
			// ------------------------- END OF PREV/NEXT PAGE LINKS -------------------------
		?>

		<strong>
		<a href="<?php $Blog->disp( 'arcdirurl', 'raw' ) ?>"><?php echo T_('Archives') ?></a>
		</strong>

		<p>
		<?php
			// Display a link to contact the owner of this blog (if owner accepts messages):
			$Blog->contact_link( array(
					'before'      => ' [',
					'after'       => '] ',
					'text'   => T_('Contact'),
					'title'  => T_('Send a message to the owner of this blog...'),
				) );
		?>

		<?php
			user_login_link( ' [', '] ' );
			user_register_link( ' [', '] ' );
			user_admin_link( ' [', '] ' );
			user_logout_link( ' [', '] ' );
		?>
		</p>
	</div>

	<hr>

	<p align="center"><!-- Please help us promote b2evolution and leave this link on your blog. --><a href="http://b2evolution.net/" title="b2evolution: next generation blog software"><img src="../../rsc/img/powered-by-b2evolution-120t.gif" alt="powered by b2evolution free blog software" title="b2evolution: next generation blog software" width="120" height="32" border="0" /></a></p>

	<?php
		$Hit->log();  // log the hit on this page
		debug_info();	// output debug info if requested
	?>
</body>
</html>