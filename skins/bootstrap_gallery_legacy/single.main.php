<?php
/**
 * This is the main/default page template.
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-development-primer}
 *
 * It is used to display the blog when no specific page template is available to handle the request.
 *
 * @package evoskins
 * @subpackage bootstrap_gallery_skin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );
if( version_compare( $app_version, '6.4' ) < 0 )
{ // Older skins (versions 2.x and above) should work on newer b2evo versions, but newer skins may not work on older b2evo versions.
	die( 'This skin is designed for b2evolution 6.4 and above. Please <a href="http://b2evolution.net/downloads/index.html">upgrade your b2evolution</a>.' );
}
global $Skin;
// This is the main template; it may be used to display very different things.
// Do inits depending on current $disp:
skin_init( $disp );
// TODO: move to Skin::display_init
require_js( 'functions.js', 'blog' );	// for opening popup window (comments)
// -------------------------- HTML HEADER INCLUDED HERE --------------------------
skin_include( '_html_header.inc.php', array(
		'arcdir_text'     => T_('Index'),
		'catdir_text'     => T_('Galleries'),
		'category_text'   => T_('Gallery').': ',
		'categories_text' => T_('Galleries').': ',
	) );
// -------------------------------- END OF HEADER --------------------------------
// ---------------------------- SITE HEADER INCLUDED HERE ----------------------------
// If site headers are enabled, they will be included here:
siteskin_include( '_site_body_header.inc.php' );
// ------------------------------- END OF SITE HEADER --------------------------------
?>


<div class="container">

<header class="row">

	<div class="coll-xs-12 coll-sm-12 col-md-4 col-md-push-8">
		<div class="evo_container evo_container__page_top">
		<?php
			// ------------------------- "Page Top" CONTAINER EMBEDDED HERE --------------------------
			// Display container and contents:
			skin_container( NT_('Page Top'), array(
					// The following params will be used as defaults for widgets included in this container:
					'block_start'         => '<div class="evo_widget $wi_class$">',
					'block_end'           => '</div>',
					'block_display_title' => false,
					'list_start'          => '<ul>',
					'list_end'            => '</ul>',
					'item_start'          => '<li>',
					'item_end'            => '</li>',
				) );
			// ----------------------------- END OF "Page Top" CONTAINER -----------------------------
		?>

		<?php
			skin_widget( array(
				// CODE for the widget:
				'widget' => 'member_count',
				// Optional display params
				'before' => '(',
				'after'  => ')',
			) );
		?>
		</div>
	</div><!-- .col -->

	<div class="coll-xs-12 col-sm-12 col-md-8 col-md-pull-4">
		<div class="evo_container evo_container__header">
		<?php
			// ------------------------- "Header" CONTAINER EMBEDDED HERE --------------------------
			// Display container and contents:
			skin_container( NT_('Header'), array(
					// The following params will be used as defaults for widgets included in this container:
					'block_start'       => '<div class="evo_widget $wi_class$">',
					'block_end'         => '</div>',
					'block_title_start' => '<h1>',
					'block_title_end'   => '</h1>',
				) );
			// ----------------------------- END OF "Header" CONTAINER -----------------------------
		?>
		</div>

	</div><!-- .col -->

</header><!-- .row -->


<nav class="row">

	<div class="col-xs-12">
		<ul class="nav nav-tabs evo_container evo_container__menu">
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
					'item_start'          => '<li class="evo_widget $wi_class$">',
					'item_end'            => '</li>',
					'item_selected_start' => '<li class="active evo_widget $wi_class$">',
					'item_selected_end'   => '</li>',
					'item_title_before'   => '',
					'item_title_after'    => '',
				) );
			// ----------------------------- END OF "Menu" CONTAINER -----------------------------
		?>
		</ul>
	</div><!-- .col -->

</nav><!-- .row -->

<main><!-- This is were a link like "Jump to main content" would land -->

	<!-- =================================== START OF POST TITLE BAR =================================== -->

	<?php
		if( $single_Item = & mainlist_get_item() )
		{ // Get Item here, because it can be not defined yet, e.g. in Preview mode
		?>
		<div class="row">
			<div class="col-xs-12">
			<nav class="nav_album">

			<a href="<?php $Blog->disp( 'url', 'raw' ) ?>" title="<?php echo format_to_output( T_('All Albums'), 'htmlattr' ); ?>" class="all_albums">All Albums</a>

			<span class="nav_album_title">
				<?php
					$single_Item->title( array(
							'link_type' => 'permalink',
							'before'    => '',
							'after'     => '',
						) );
				?>
				<?php 	
					if( $Skin->enabled_status_banner( $single_Item->status ) )
					{ // Status banner
						$single_Item->format_status( array( 'template' => '<div class="evo_status evo_status__$status$ badge">$status_title$</div>' ) );						
					}
					$single_Item->edit_link( array( // Link to backoffice for editing
							'before'    => '',
							'after'     => '',
							'text'      => get_icon( 'edit' ),
							'title'     => T_('Edit title/description...'),
						) );
				?>
			</span><!-- .nav_album_title -->
		
			<?php
				// ------------------- PREV/NEXT POST LINKS (SINGLE POST MODE) -------------------
				item_prevnext_links( array(
						'template' => '$prev$$next$',
						'block_start' => '<ul class="pager hidden-xs">',
						'next_class' => 'next',
						'next_start'  => '<li class="next">',
						'next_text' => 'Next',
						'next_no_item' => '',
						'next_end'    => '</li>',
						'prev_class' => 'previous',
						'prev_start'  => '<li class="previous">',
						'prev_text' => 'Previous',
						'prev_no_item' => '',
						'prev_end'    => '',
						'block_end'   => '</ul>',
					) );
				// ------------------------- END OF PREV/NEXT POST LINKS -------------------------
			?>
		
			<div class="clear"></div>
			
			</nav><!-- .nav_album -->
			</div><!-- .col -->
		</div><!-- .row -->
		<?php
		} // ------------------- END OF NAVIGATION BAR FOR ALBUM(POST) ------------------- 
	?>

	<?php
		// ------------------------- MESSAGES GENERATED FROM ACTIONS -------------------------
		messages( array(
				'block_start' => '<div class="row"><div class="col-xs-12 action_messages">',
				'block_end'   => '</div></div>',
			) );
		// --------------------------------- END OF MESSAGES ---------------------------------
	?>
		
	<article class="row">	

	<?php
		$Item->locale_temp_switch(); // Temporarily switch to post locale (useful for multilingual blogs)
	?>

	<div class="post_images col-xl-9 col-lg-8 col-md-6 col-sm-6">
		<?php
			// Display images that are linked to this post:
			$Item->images( array(
					'before'              => '',
					'before_image'        => '<figure class="single-image">',
					'before_image_legend' => '<figcaption class="evo_image_legend">',
					'after_image_legend'  => '</figcaption>',
					'after_image'         => '</figure>',
					'after'               => '<div class="clear"></div>',
					'image_size'          => $Skin->get_setting( 'single_thumb_size' ),
					'image_align'         => 'middle',
					'before_gallery'      => '<div class="evo_post_gallery">',
					'after_gallery'       => '</div>',
					'gallery_table_start' => '',
					'gallery_table_end'   => '',
					'gallery_row_start'   => '',
					'gallery_row_end'     => '',
					'gallery_cell_start'  => '<div class="evo_post_gallery__image">',
					'gallery_cell_end'    => '</div>',
				) );
		?>
	</div>

	<div class="evo_post_content col-xl-3 col-lg-4 col-md-6 col-sm-6">

		<div class="evo_details">

			<?php
				// ---------------------- POST CONTENT INCLUDED HERE ----------------------
				// Note: at the top of this file, we set: 'image_size' =>	'', // Do not display images in content block - Image is handled separately
				skin_include( '_item_content.inc.php', array(
						'feature_block'          => false,
						'item_class'        	 => 'evo_post',
						'item_type_class'   	 => 'evo_post__ptyp_',
						'item_status_class' 	 => 'evo_post__',
						'content_mode'           => 'full', // We want regular "full" content, even in category browsing: i-e no excerpt or thumbnail
						'image_size'             => '', // Do not display images in content block - Image is handled separately
						'url_link_text_template' => '', // link will be displayed (except player if podcast)
					) );
				// Note: You can customize the default item content by copying the generic
				// /skins/_item_content.inc.php file into the current skin folder.
				// -------------------------- END OF POST CONTENT -------------------------
			?>

			<div class="item_comments">
				<?php
					// ------------------ FEEDBACK (COMMENTS/TRACKBACKS) INCLUDED HERE ------------------
					skin_include( '_item_feedback.inc.php', array(
							'before_section_title' => '<h4>',
							'after_section_title'  => '</h4>',
							'author_link_text'     => 'preferredname',
							'comment_image_size'   => 'fit-256x256',
							// Pagination:
							'pagination' => array(
								'block_start'           => '<div class="center"><ul class="pagination">',
								'block_end'             => '</ul></div>',
								'page_current_template' => '<span>$page_num$</span>',
								'page_item_before'      => '<li>',
								'page_item_after'       => '</li>',
								'page_item_current_before' => '<li class="active">',
								'page_item_current_after'  => '</li>',
								'prev_text'             => '<i class="fa fa-angle-double-left"></i>',
								'next_text'             => '<i class="fa fa-angle-double-right"></i>',
							),
						) );
					// Note: You can customize the default item feedback by copying the generic
					// /skins/_item_feedback.inc.php file into the current skin folder.
					// ---------------------- END OF FEEDBACK (COMMENTS/TRACKBACKS) ---------------------
				?>
			</div>

		</div>

	</div>

	<?php
		locale_restore_previous();	// Restore previous locale (Blog locale)
	?>

	</article><!-- .row -->

</main>


<footer class="row">

	<!-- =================================== START OF FOOTER =================================== -->
	<div class="col-md-12 center">

		<div class="evo_container evo_container__footer">
		<?php
			// Display container and contents:
			skin_container( NT_("Footer"), array(
					// The following params will be used as defaults for widgets included in this container:
					'block_start'       => '<div class="evo_widget $wi_class$">',
					'block_end'         => '</div>',
				) );
			// Note: Double quotes have been used around "Footer" only for test purposes.
		?>
		</div>

		<p>
			<?php
				// Display footer text (text can be edited in Blog Settings):
				$Blog->footer_text( array(
						'before' => '',
						'after'  => ' &bull; ',
					) );
			?>

			<?php
				// Display a link to contact the owner of this blog (if owner accepts messages):
				$Blog->contact_link( array(
						'before' => '',
						'after'  => ' &bull; ',
						'text'   => T_('Contact'),
						'title'  => T_('Send a message to the owner of this blog...'),
					) );
				// Display a link to help page:
				$Blog->help_link( array(
						'before'      => ' ',
						'after'       => ' ',
						'text'        => T_('Help'),
					) );
			?>

			<?php
				// Display additional credits:
				// If you can add your own credits without removing the defaults, you'll be very cool :))
				// Please leave this at the bottom of the page to make sure your blog gets listed on b2evolution.net
				credits( array(
						'list_start'  => '&bull;',
						'list_end'    => ' ',
						'separator'   => '&bull;',
						'item_start'  => ' ',
						'item_end'    => ' ',
					) );
			?>
		</p>

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
	</div><!-- .col -->
	
</footer><!-- .row -->


</div><!-- .container -->


<?php
// ---------------------------- SITE FOOTER INCLUDED HERE ----------------------------
// If site footers are enabled, they will be included here:
siteskin_include( '_site_body_footer.inc.php' );
// ------------------------------- END OF SITE FOOTER --------------------------------


// ------------------------- HTML FOOTER INCLUDED HERE --------------------------
skin_include( '_html_footer.inc.php' );
// ------------------------------- END OF FOOTER --------------------------------
?>