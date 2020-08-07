<?php
/**
 * This is the template that displays the help screen for a collection
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 * To display the archive directory, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?disp=help
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 * @subpackage bootstrap_main_skin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $app_version, $disp, $Collection, $Blog;

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
skin_include( '_body_header.inc.access.php' );
// ------------------------------- END OF SITE HEADER --------------------------------
?>


<div class="evo_container__standalone_page_area_oth">

<div class="container">

<header class="row">

	<div class="coll-xs-12 coll-sm-12 col-md-4 col-md-push-8">
		<?php
		if( $Skin->show_container_when_access_denied( 'page_top' ) )
		{ // Display 'Page Top' widget container
			// ------------------------- "Page Top" CONTAINER EMBEDDED HERE --------------------------
			// Display container and contents:
			widget_container( 'page_top', array(
					// The following params will be used as defaults for widgets included in this container:
					'container_display_if_empty' => false, // If no widget, don't display container at all
					'container_start'     => '<div class="evo_container $wico_class$">',
					'container_end'       => '</div>',
					'block_start'         => '<div class="evo_widget $wi_class$">',
					'block_end'           => '</div>',
					'block_display_title' => false,
					'list_start'          => '<ul>',
					'list_end'            => '</ul>',
					'item_start'          => '<li>',
					'item_end'            => '</li>',
					// Widget 'Search form':
					'search_input_before'  => '<div class="input-group">',
					'search_input_after'   => '',
					'search_submit_before' => '<span class="input-group-btn">',
					'search_submit_after'  => '</span></div>',    
				) );
			// ----------------------------- END OF "Page Top" CONTAINER -----------------------------
		} ?>
	</div><!-- .col -->
	
	<div class="col-md-12">
		<?php
			// ------------------------ TITLE FOR THE CURRENT REQUEST ------------------------
			// request_title( array(
					// 'title_before'      => '<h1 class="page_title">',
					// 'title_after'       => '</h1>',
					// 'title_none'        => '',
					// 'glue'              => ' - ',
				// ) );
				
				echo '<h1 class="page_title">'.T_('Content issues').'</h1>'; // Hardcoded, since dynamic content is not properly installed for disp=help
			// ----------------------------- END OF REQUEST TITLE ----------------------------
		?>
	</div>

</header><!-- .row -->

</div><!-- .container -->

</div><!-- .evo_container__standalone_page_area_oth -->

<div class="container main_page_wrapper">

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


<?php
// ---------------------------- SITE FOOTER INCLUDED HERE ----------------------------
// If site footers are enabled, they will be included here:
skin_include( '_body_footer.inc.access.php' );
// ------------------------------- END OF SITE FOOTER --------------------------------


// ------------------------- HTML FOOTER INCLUDED HERE --------------------------
skin_include( '_html_footer.inc.php' );
// ------------------------------- END OF FOOTER --------------------------------
?>