<?php
/**
 * This is the main/default page template.
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-structure}
 *
 * It is used to display the blog when no specific page template is available to handle the request.
 *
 * @package evoskins
 * @subpackage basic
 *
 * @version $Id: index.main.php 4276 2013-07-17 11:05:10Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

if( version_compare( $app_version, '2.4.1' ) < 0 )
{ // Older 2.x skins work on newer 2.x b2evo versions, but newer 2.x skins may not work on older 2.x b2evo versions.
	die( 'This skin is designed for b2evolution 2.4.1 and above. Please <a href="http://b2evolution.net/downloads/index.html">upgrade your b2evolution</a>.' );
}

// This is the main template; it may be used to display very different things.
// Do inits depending on current $disp:
skin_init( $disp );

require_js( 'ajax.js', 'blog' );	// Functions to work with AJAX response data

// The following is temporary and should be moved to some SiteSkin class
siteskin_init();

// ----------------------------- HEADER BEGINS HERE ------------------------------
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


// ---------------------------- SITE HEADER INCLUDED HERE ----------------------------
// If site headers are enabled, they will be included here:
siteskin_include( '_site_body_header.inc.php' );
// ------------------------------- END OF SITE HEADER --------------------------------
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
				<?php
					$Item->title( array(
							'link_type' => 'permalink'
						) );
				?>
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

					if( $Item->status != 'published' )
					{
						$Item->status( array( 'before' => ' &bull; <small>'.T_('Status').': ', 'after' => '</small>' ) );
					}
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
				'author_link_text' => 'preferredname',
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
			user_login_link( ' [', '] ', '', '#', 'sidebar login link' );
			user_register_link( ' [', '] ', '', '#', false, 'sidebar register link' );
			user_admin_link( ' [', '] ' );
			user_logout_link( ' [', '] ' );
		?>
		</p>
	</div>

	<hr>

	<?php
		// Please help us promote b2evolution and leave this logo on your blog:
		powered_by( array(
				'block_start' => '<div align="center">',
				'block_end'   => '</div>',
				// Check /rsc/img/ for other possible images -- Don't forget to change or remove width & height too
				'img_url'     => '$rsc$img/powered-by-b2evolution-120t.gif',
				'img_width'   => 120,
				'img_height'  => 32,
			) );
	?>

	<?php
		// ---------------------------- SITE FOOTER INCLUDED HERE ----------------------------
		// If site footers are enabled, they will be included here:
		siteskin_include( '_site_body_footer.inc.php' );
		// ------------------------------- END OF SITE FOOTER --------------------------------

		$Hit->log();  // log the hit on this page
		debug_info();	// output debug info if requested
	?>
</body>
</html>