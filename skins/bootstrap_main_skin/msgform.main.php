<?php
/**
 * This is the template that displays the message user form
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 * To display a feedback, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?disp=msgform&recipient_id=n
 * Note: don't code this URL by hand, use the template functions to generate it!
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 * @subpackage bootstrap_main_skin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $app_version, $disp, $Blog;

if( evo_version_compare( $app_version, '6.4' ) < 0 )
{ // Older skins (versions 2.x and above) should work on newer b2evo versions, but newer skins may not work on older b2evo versions.
	die( 'This skin is designed for b2evolution 6.4 and above. Please <a href="http://b2evolution.net/downloads/index.html">upgrade your b2evolution</a>.' );
}

// This is the main template; it may be used to display very different things.
// Do inits depending on current $disp:
skin_init( $disp );


// -------------------------- HTML HEADER INCLUDED HERE --------------------------
skin_include( '_html_header.inc.php' );
// -------------------------------- END OF HEADER --------------------------------


// ---------------------------- SITE HEADER INCLUDED HERE ----------------------------
// If site headers are enabled, they will be included here:
siteskin_include( '_site_body_header.inc.php' );
// ------------------------------- END OF SITE HEADER --------------------------------

// Display a picture from skin setting as background image
global $media_path, $media_url;
$bg_image = $Skin->get_setting( 'front_bg_image' );
echo '<div id="bg_picture">';
if( ! empty( $bg_image ) && file_exists( $media_path.$bg_image ) )
{ // If it exists in media folder
	echo '<img src="'.$media_url.$bg_image.'" />';
}
echo '</div>';
?>


<div class="container main_page_wrapper">

<header class="row">

	<div class="coll-xs-12 coll-sm-12 col-md-4 col-md-push-8">
		<?php
		if( $Skin->is_visible_container( 'page_top' ) )
		{ // Display 'Page Top' widget container
		?>
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
		</div>
		<?php } ?>
	</div><!-- .col -->

	<div class="coll-xs-12 col-sm-12 col-md-8 col-md-pull-4">
		<?php
		if( $Skin->is_visible_container( 'header' ) )
		{ // Display 'Header' widget container
		?>
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
		<?php } ?>
	</div><!-- .col -->

</header><!-- .row -->


<?php
if( $Skin->is_visible_container( 'menu' ) )
{ // Display 'Menu' widget container
?>
<nav class="row">

	<div class="col-md-12">
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
<?php } ?>


<div class="row">
	<div class="col-md-12">
		<main><!-- This is were a link like "Jump to main content" would land -->

		<!-- ================================= START OF MAIN AREA ================================== -->

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
			request_title( array(
					'title_before'      => '<h2 class="page_title">',
					'title_after'       => '</h2>',
					'title_none'        => '',
					'glue'              => ' - ',
				) );
			// ----------------------------- END OF REQUEST TITLE ----------------------------
		?>

		<?php
			// -------------- MAIN CONTENT TEMPLATE INCLUDED HERE (Based on $disp) --------------
			skin_include( '$disp$' );
			// Note: you can customize any of the sub templates included here by
			// copying the matching php file into your skin directory.
			// ------------------------- END OF MAIN CONTENT TEMPLATE ---------------------------
		?>

		</main>

	</div><!-- .col -->

</div><!-- .row -->

</div><!-- .container -->


<!-- =================================== START OF SECONDARY AREA =================================== -->
<section class="secondary_area"><!-- white background -->
<div class="container">

	<div class="row">

		<footer class="col-md-12 center">
	
			<?php
			if( $Skin->is_visible_container( 'footer' ) )
			{ // Display 'Footer' widget container
			?>
			<div class="evo_container evo_container__footer">
			<?php
				// ------------------------- "Footer" CONTAINER EMBEDDED HERE --------------------------
				// Display container and contents:
				skin_container( NT_('Footer'), array(
						// The following params will be used as defaults for widgets included in this container:
					) );
				// ----------------------------- END OF "Footer" CONTAINER -----------------------------
			?>
			</div>
			<?php } ?>
	
			<p>
			<?php
				// Display footer text (text can be edited in Blog Settings):
				$Blog->footer_text( array(
						'before' => '',
						'after'  => ' &bull; ',
					) );

			// TODO: dh> provide a default class for pTyp, too. Should be a name and not the ityp_ID though..?!
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
						'before' => ' ',
						'after'  => ' ',
						'text'   => T_('Help'),
					) );
			?>

			<?php
				// Display additional credits:
				// If you can add your own credits without removing the defaults, you'll be very cool :))
				// Please leave this at the bottom of the page to make sure your blog gets listed on b2evolution.net
				credits( array(
						'list_start' => '&bull;',
						'list_end'   => ' ',
						'separator'  => '&bull;',
						'item_start' => ' ',
						'item_end'   => ' ',
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

		</footer><!-- .col -->

	</div><!-- .row -->


</div><!-- .container -->

</section><!-- .secondary_area -->


<?php
// ---------------------------- SITE FOOTER INCLUDED HERE ----------------------------
// If site footers are enabled, they will be included here:
siteskin_include( '_site_body_footer.inc.php' );
// ------------------------------- END OF SITE FOOTER --------------------------------


// ------------------------- HTML FOOTER INCLUDED HERE --------------------------
skin_include( '_html_footer.inc.php' );
// ------------------------------- END OF FOOTER --------------------------------
?>